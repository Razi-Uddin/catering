<?php
include '../includes/db.php';

if (!isset($_GET['id'])) {
    die("Order ID is missing.");
}

$order_id = intval($_GET['id']);

// --- Fetch customer + order info ---
$order_info_sql = "
    SELECT o.id, o.order_date, o.total_price, c.name AS customer_name, c.username
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
";
$stmt = $conn->prepare($order_info_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_info = $stmt->get_result()->fetch_assoc();

if (!$order_info) {
    die("Order not found.");
}

// --- Fetch all meals in this order ---
$meals_sql = "
    SELECT m.name AS meal_name, o.quantity, o.price_per_item, (o.quantity * o.price_per_item) AS total_price
    FROM orders o
    LEFT JOIN meals m ON o.meal_id = m.id
    WHERE o.id = ?
    UNION ALL
    SELECT m.name AS meal_name, oi.quantity, oi.price_per_item, (oi.quantity * oi.price_per_item) AS total_price
    FROM order_items oi
    LEFT JOIN meals m ON oi.meal_id = m.id
    WHERE oi.order_id = ?
";
$stmt_meals = $conn->prepare($meals_sql);
$stmt_meals->bind_param("ii", $order_id, $order_id);
$stmt_meals->execute();
$meals_result = $stmt_meals->get_result();

// --- Prepare rows and calculate grand total ---
$grand_total = 0;
$rows = [];
while ($row = $meals_result->fetch_assoc()) {
    $rows[] = $row;
    $grand_total += $row['total_price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $order_info['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none; } }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #ddd; background: #fff; }
    </style>
</head>
<body>
<div class="invoice-box">
    <h2 class="text-center">PWC Catering & Event Planner</h2>
    <p class="text-center">Customer Invoice</p>
    <hr>

    <p><strong>Order ID:</strong> <?= $order_info['id'] ?></p>
    <p><strong>Date:</strong> <?= date("d-M-Y", strtotime($order_info['order_date'])) ?></p>
    <p><strong>Customer:</strong> <?= htmlspecialchars($order_info['customer_name']) ?></p>
    <p><strong>Username:</strong> <?= htmlspecialchars($order_info['username']) ?></p>

    <table class="table table-bordered mt-3">
        <thead class="table-light">
            <tr>
                <th>Meal</th>
                <th>Quantity</th>
                <th>Price/Item (PKR)</th>
                <th>Total (PKR)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['meal_name']) ?></td>
                    <td><?= $r['quantity'] ?></td>
                    <td><?= number_format($r['price_per_item'], 2) ?></td>
                    <td><?= number_format($r['total_price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 class="text-end">Grand Total: <?= number_format($grand_total, 2) ?> PKR</h4>

    <div class="text-center mt-3 no-print">
        <button class="btn btn-primary" onclick="window.print()">🖨 Print</button>
    </div>
</div>
</body>
</html>
