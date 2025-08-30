<?php
include '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_POST['customer_id'];
    $start_date  = $_POST['start_date'];
    $end_date    = $_POST['end_date'];

    $sql = "SELECT o.id, c.name as customer_name, m.name as meal_name, 
                   o.quantity, o.total_price, o.order_date
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN meals m ON o.meal_id = m.id
            WHERE o.customer_id=? AND DATE(o.order_date) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $customer_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Bill</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>@media print { .no-print { display: none; } }</style>
</head>
<body class="container mt-4">
    <h2>Customer Bill</h2>
    <?php if(isset($result)) { ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Meal</th>
                <th>Quantity</th>
                <th>Total (PKR)</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $grand_total = 0;
            while($row = $result->fetch_assoc()) {
                $grand_total += $row['total_price']; ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['meal_name']) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td><?= number_format($row['total_price'], 2) ?></td>
                    <td><?= date("d-M-Y", strtotime($row['order_date'])) ?></td>
                </tr>
            <?php } ?>
            <tr>
                <td colspan="3"><strong>Grand Total</strong></td>
                <td colspan="2"><strong>PKR <?= number_format($grand_total, 2) ?></strong></td>
            </tr>
        </tbody>
    </table>
    <button class="btn btn-primary no-print" onclick="window.print()">Print</button>
    <?php } ?>
</body>
</html>
