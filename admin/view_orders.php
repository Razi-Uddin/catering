<?php
include 'header.php';

// --- Pagination setup ---
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- Search filter ---
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$search_sql = "";
if ($search !== "") {
    $search_safe = $conn->real_escape_string($search);
    // Only search by customer name, order id, or order date
    $search_sql = " AND (c.name LIKE '%$search_safe%' 
                     OR o.id LIKE '%$search_safe%' 
                     OR o.order_date LIKE '%$search_safe%')";
}

// --- Handle delete ---
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $conn->query("DELETE FROM order_items WHERE order_id = $del_id");
    $conn->query("DELETE FROM orders WHERE id = $del_id");
    echo "<script>alert('Order deleted'); window.location='view_orders.php';</script>";
    exit;
}

// --- Count total rows (distinct orders) ---
$count_sql = "SELECT COUNT(*) AS total FROM orders o
              JOIN customers c ON o.customer_id = c.id
              WHERE 1 $search_sql";
$count_result = $conn->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// --- Fetch just order IDs for pagination ---
$order_ids_sql = "SELECT o.id FROM orders o
                  JOIN customers c ON o.customer_id = c.id
                  WHERE 1 $search_sql
                  ORDER BY o.order_date DESC, o.id DESC
                  LIMIT $limit OFFSET $offset";
$order_ids_res = $conn->query($order_ids_sql);

$order_ids = [];
while ($row = $order_ids_res->fetch_assoc()) {
    $order_ids[] = $row['id'];
}

$orders = [];
if (count($order_ids) > 0) {
    $id_list = implode(",", $order_ids);

    // --- Fetch both single-meal and multi-meal items ---
    $sql = "SELECT o.id AS order_id, o.order_date, o.total_price,
                   c.name AS customer_name,
                   m1.name AS meal_name, o.quantity, o.price_per_item, o.total_price AS item_total
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            LEFT JOIN meals m1 ON o.meal_id = m1.id
            WHERE o.id IN ($id_list)

            UNION ALL

            SELECT o.id AS order_id, o.order_date, o.total_price,
                   c.name AS customer_name,
                   m2.name AS meal_name, oi.quantity, oi.price_per_item, oi.total_price AS item_total
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN meals m2 ON oi.meal_id = m2.id
            WHERE o.id IN ($id_list)

            ORDER BY order_date DESC, order_id DESC";

    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        // Initialize order
        if (!isset($orders[$row['order_id']])) {
            $orders[$row['order_id']] = [
                'info' => [
                    'customer_name' => $row['customer_name'],
                    'order_date'    => $row['order_date'],
                    'total_price'   => $row['total_price']
                ],
                'items' => []
            ];
        }

        // Add item if meal_name exists
        if ($row['meal_name'] !== null) {
            $orders[$row['order_id']]['items'][] = [
                'meal_name'     => $row['meal_name'],
                'quantity'      => $row['quantity'],
                'price_per_item'=> $row['price_per_item'],
                'item_total'    => $row['item_total']
            ];
        }
    }
}
?>

<div class="container mt-4">
    <h2 class="mb-4">All Orders</h2>

    <!-- Search form -->
    <form method="get" class="mb-3 d-flex">
<input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
       class="form-control me-2" placeholder="Search by customer, date, or order ID">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Orders table -->
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Meals</th>
                    <th>Date</th>
                    <th>Grand Total (PKR)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($orders) > 0): ?>
                <?php foreach ($orders as $id => $order): ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td><?= htmlspecialchars($order['info']['customer_name']) ?></td>
                        <td>
                            <?php if (!empty($order['items'])): ?>
                                <ul class="mb-0">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li>
                                            <?= htmlspecialchars($item['meal_name']) ?> - 
                                            <?= $item['quantity'] ?> × 
                                            <?= number_format($item['price_per_item'], 2) ?> = 
                                            <strong><?= number_format($item['item_total'], 2) ?></strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <em>No items</em>
                            <?php endif; ?>
                        </td>
                        <td><?= date("d-M-Y", strtotime($order['info']['order_date'])) ?></td>
                        <td><strong><?= number_format($order['info']['total_price'], 2) ?></strong></td>
                        <td>
                            <a href="edit_order.php?id=<?= $id ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="print_order.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-success">🖨 Print</a>
                            <a href="?delete=<?= $id ?>" onclick="return confirm('Delete this order?')" class="btn btn-sm btn-danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">No orders found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page == $total_pages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
