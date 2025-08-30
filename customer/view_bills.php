<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';

if (!isLoggedInCustomer()) {
    header('Location: login.php');
    exit();
}

$customerId = $_SESSION['customer_id'];
$customerName = $_SESSION['customer_name'];

// --- Pagination setup ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- Search setup ---
$search = trim($_GET['search'] ?? '');
$searchSql = '';
if ($search !== '') {
    $safeSearch = $conn->real_escape_string($search);
    $searchSql = " AND (m1.name LIKE '%$safeSearch%' OR m2.name LIKE '%$safeSearch%' OR o.id LIKE '%$safeSearch%' OR o.order_date LIKE '%$safeSearch%')";
}

// --- Count total orders ---
$count_sql = "
    SELECT COUNT(DISTINCT o.id) AS total
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN meals m1 ON o.meal_id = m1.id
    LEFT JOIN meals m2 ON oi.meal_id = m2.id
    WHERE o.customer_id = $customerId $searchSql
";
$count_result = $conn->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// --- Fetch order IDs ---
$order_ids_sql = "
    SELECT DISTINCT o.id
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN meals m1 ON o.meal_id = m1.id
    LEFT JOIN meals m2 ON oi.meal_id = m2.id
    WHERE o.customer_id = $customerId $searchSql
    ORDER BY o.order_date DESC
    LIMIT $limit OFFSET $offset
";
$order_ids_res = $conn->query($order_ids_sql);

$order_ids = [];
while ($row = $order_ids_res->fetch_assoc()) {
    $order_ids[] = $row['id'];
}

// --- Fetch all meals per order ---
$orders = [];
if (count($order_ids) > 0) {
    $id_list = implode(",", $order_ids);

    $sql = "
        SELECT o.id AS order_id, o.order_date, o.total_price, m1.name AS meal_name, o.quantity, o.price_per_item, o.total_price AS item_total
        FROM orders o
        LEFT JOIN meals m1 ON o.meal_id = m1.id
        WHERE o.id IN ($id_list)

        UNION ALL

        SELECT o.id AS order_id, o.order_date, o.total_price, m2.name AS meal_name, oi.quantity, oi.price_per_item, oi.total_price AS item_total
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN meals m2 ON oi.meal_id = m2.id
        WHERE o.id IN ($id_list)

        ORDER BY order_date DESC, order_id DESC
    ";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        if (!isset($orders[$row['order_id']])) {
            $orders[$row['order_id']] = [
                'info' => [
                    'order_date' => $row['order_date'],
                    'total_price' => $row['total_price']
                ],
                'items' => []
            ];
        }

        if ($row['meal_name'] !== null) {
            $orders[$row['order_id']]['items'][] = [
                'meal_name' => $row['meal_name'],
                'quantity' => $row['quantity'],
                'price_per_item' => $row['price_per_item'],
                'item_total' => $row['item_total']
            ];
        }
    }
}

// Encode customer name safely for JS
$jsCustomerName = json_encode($customerName);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders - PWC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center no-print">
        <h4>Hello, <?= htmlspecialchars($customerName) ?> | Your Orders</h4>
        <a href="dashboard.php" class="btn btn-secondary">← Back</a>
    </div>

    <form method="get" class="mb-3 d-flex no-print">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control me-2" placeholder="Search by meal, order ID or date">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Meals</th>
                    <th>Grand Total (PKR)</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $id => $order): ?>
                        <tr id="row-<?= $id ?>">
                            <td><?= $id ?></td>
                            <td><?= date("d-M-Y", strtotime($order['info']['order_date'])) ?></td>
                            <td>
                                <ul class="mb-0">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li><?= htmlspecialchars($item['meal_name']) ?> - <?= $item['quantity'] ?> × <?= number_format($item['price_per_item'], 2) ?> = <strong><?= number_format($item['item_total'], 2) ?></strong></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td><strong><?= number_format($order['info']['total_price'], 2) ?></strong></td>
                            <td class="no-print">
                                <button class="btn btn-sm btn-success" onclick="printOrder(<?= $id ?>)">🖨 Print</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">No orders found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-3 no-print">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page == $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    <button onclick="window.print()" class="btn btn-primary no-print mt-2">🖨️ Print All Orders</button>
</div>

<script>
const customerName = <?= $jsCustomerName ?>;

function printOrder(orderId) {
    const row = document.getElementById('row-' + orderId).cloneNode(true);
    const btn = row.querySelector('button');
    if(btn) btn.remove();

    // Extract order info
    const date = row.querySelector('td:nth-child(2)').innerText;
    const grandTotal = row.querySelector('td:nth-child(4)').innerText;

    // Build table rows for meals
    let mealRows = '';
    row.querySelectorAll('td:nth-child(3) li').forEach(li => {
        // li content is like "Chicken Karahi - 10 × 1,500.00 = 15,000.00"
        const parts = li.innerText.split(' - ');
        const name = parts[0];
        const qtyPrice = parts[1].split(' = ')[0]; // "10 × 1,500.00"
        const total = parts[1].split(' = ')[1];    // "15,000.00"
        const qty = qtyPrice.split(' × ')[0];
        const price = qtyPrice.split(' × ')[1];
        mealRows += '<tr>' +
                        '<td>' + name + '</td>' +
                        '<td class="text-center">' + qty + '</td>' +
                        '<td class="text-end">' + price + '</td>' +
                        '<td class="text-end">' + total + '</td>' +
                    '</tr>';
    });

    const newWin = window.open('', '_blank');
    newWin.document.write(`
        <html>
        <head>
            <title>Order #${orderId} - Print</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; font-family: Arial, sans-serif; }
                h3, h4 { text-align: center; margin: 0; }
                table { width: 100%; margin-top: 20px; }
                th, td { padding: 8px; }
                th { background: #f8f9fa; }
                td { vertical-align: middle; }
                .text-end { text-align: right; }
                .text-center { text-align: center; }
            </style>
        </head>
        <body>
            <h3>PWC Catering & Event Planner</h3>
            <h4>Order Receipt #${orderId}</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Meal</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${mealRows}
                </tbody>
            </table>
            <p><strong>Date:</strong> ${date}</p>
            <p><strong>Grand Total:</strong> ${grandTotal}</p>
            <p><strong>Customer:</strong> ${customerName}</p>
            <script>
                window.onload = function() { window.print(); window.close(); }
            <\/script>
        </body>
        </html>
    `);
    newWin.document.close();
}
</script>

</body>
</html>
