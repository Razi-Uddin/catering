<?php
include '../includes/db.php';
include 'header.php';

// Function to generate full SQL dump
function exportDatabase($conn, $dbname) {
    $sqlDump  = "-- Database Export: $dbname\n";
    $sqlDump .= "-- Generation Time: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sqlDump .= "START TRANSACTION;\n";
    $sqlDump .= "SET time_zone = \"+00:00\";\n\n";

    $tablesResult = $conn->query("SHOW TABLES");
    while ($tableRow = $tablesResult->fetch_array()) {
        $table = $tableRow[0];

        // Table structure
        $sqlDump .= "-- --------------------------------------------------------\n\n";
        $sqlDump .= "-- Table structure for table `$table`\n\n";
        $createResult = $conn->query("SHOW CREATE TABLE `$table`");
        $createRow = $createResult->fetch_assoc();
        $sqlDump .= $createRow['Create Table'] . ";\n\n";

        // Table data
        $sqlDump .= "-- Dumping data for table `$table`\n\n";
        $dataResult = $conn->query("SELECT * FROM `$table`");
        while ($row = $dataResult->fetch_assoc()) {
            $columns = array_keys($row);
            $values = array_map(function($value) use ($conn) {
                return isset($value) ? "'" . $conn->real_escape_string($value) . "'" : "NULL";
            }, array_values($row));
            $sqlDump .= "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $values) . ");\n";
        }
        $sqlDump .= "\n";
    }

    $sqlDump .= "COMMIT;\n";
    return $sqlDump;
}

// Function to generate Excel (CSV) export
function exportDatabaseToExcel($conn, $dbname) {
    $output = fopen("php://output", "w");
    $tablesResult = $conn->query("SHOW TABLES");

    while ($tableRow = $tablesResult->fetch_array()) {
        $table = $tableRow[0];

        // Add sheet title
        fputcsv($output, []); 
        fputcsv($output, ["--- Table: $table ---"]);

        // Fetch data
        $dataResult = $conn->query("SELECT * FROM `$table`");
        if ($dataResult->num_rows > 0) {
            // Headers
            $headers = array_keys($dataResult->fetch_assoc());
            fputcsv($output, $headers);

            // Reset pointer and fetch rows
            $dataResult->data_seek(0);
            while ($row = $dataResult->fetch_assoc()) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ["(No Data)"]);
        }
    }

    fclose($output);
}

// Handle SQL export
if (isset($_POST['export_sql'])) {
    $sqlFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $dump = exportDatabase($conn, $conn->query("SELECT DATABASE()")->fetch_row()[0]);
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $sqlFile . '"');
    echo $dump;
    exit;
}

// Handle Excel export
if (isset($_POST['export_excel'])) {
    $excelFile = 'backup_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $excelFile . '"');
    exportDatabaseToExcel($conn, $conn->query("SELECT DATABASE()")->fetch_row()[0]);
    exit;
}

// --- Fetch totals ---
$total_customers = $conn->query("SELECT COUNT(*) as total FROM customers")->fetch_assoc()['total'];
$total_meals     = $conn->query("SELECT COUNT(*) as total FROM meals")->fetch_assoc()['total'];
$total_orders    = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$total_revenue   = $conn->query("SELECT SUM(total_price) as total FROM orders")->fetch_assoc()['total'];
?>

<div class="container mt-4">
    <h2>Admin Dashboard</h2>

    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <h5>Total Customers</h5>
                    <h3><?= $total_customers ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow">
                <div class="card-body">
                    <h5>Total Meals</h5>
                    <h3><?= $total_meals ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow">
                <div class="card-body">
                    <h5>Total Orders</h5>
                    <h3><?= $total_orders ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white shadow">
                <div class="card-body">
                    <h5>Total Revenue</h5>
                    <h3>Rs. <?= number_format($total_revenue,2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Buttons -->
    <div class="mt-4">
        <form method="post" style="display:inline;">
            <button type="submit" name="export_sql" class="btn btn-success">
                💾 Download Full SQL Backup
            </button>
        </form>
        <form method="post" style="display:inline; margin-left:10px;">
            <button type="submit" name="export_excel" class="btn btn-primary">
                📊 Download Excel (CSV) Export
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
