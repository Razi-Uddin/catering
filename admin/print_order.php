<?php
include '../includes/db.php';

if (!isset($_GET['id'])) {
    die("Order ID is missing.");
}
$order_id = intval($_GET['id']);

// Fetch customer + order info
$order_info_sql = "SELECT o.id, o.order_date, c.name AS customer_name, c.username
                   FROM orders o
                   JOIN customers c ON o.customer_id = c.id
                   WHERE o.id = ?";
$stmt = $conn->prepare($order_info_sql);
if (!$stmt) die("SQL Error: " . $conn->error);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_info = $stmt->get_result()->fetch_assoc();

if (!$order_info) die("Order not found.");

// Check if order has multiple items
$items_result = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id");

$rows = [];
$grand_total = 0;

if ($items_result && $items_result->num_rows > 0) {
    // Multiple meals
    $meal_stmt = $conn->prepare("SELECT name FROM meals WHERE id=?");
    while ($item = $items_result->fetch_assoc()) {
        $meal_stmt->bind_param("i", $item['meal_id']);
        $meal_stmt->execute();
        $meal_name = $meal_stmt->get_result()->fetch_assoc()['name'] ?? "Unknown Meal";

        $rows[] = [
            'meal_name' => $meal_name,
            'quantity' => $item['quantity'],
            'price_per_item' => $item['price_per_item'],
            'total_price' => $item['total_price']
        ];
        $grand_total += $item['total_price'];
    }
} else {
    // Single meal
    $single_sql = "SELECT m.name AS meal_name, o.quantity, o.price_per_item, o.total_price
                   FROM orders o
                   JOIN meals m ON o.meal_id = m.id
                   WHERE o.id = ?";
    $stmt_single = $conn->prepare($single_sql);
    $stmt_single->bind_param("i", $order_id);
    $stmt_single->execute();
    $single_result = $stmt_single->get_result();
    while ($row = $single_result->fetch_assoc()) {
        $rows[] = $row;
        $grand_total += $row['total_price'];
    }
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
    <!-- <p><strong>Username:</strong> <?= htmlspecialchars($order_info['username']) ?></p> -->

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
