<?php
include '../includes/db.php';
include 'header.php';

if (!isset($_GET['id'])) {
    header("Location: view_orders.php");
    exit();
}

$order_id = intval($_GET['id']);

// --- Fetch order ---
$order_sql = "SELECT o.*, c.name AS customer_name 
              FROM orders o
              JOIN customers c ON o.customer_id = c.id
              WHERE o.id = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if (!$order_result || $order_result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Order not found!</div>";
    include 'footer.php';
    exit();
}

$order = $order_result->fetch_assoc();

// --- Fetch order items ---
$items_result = $conn->query("SELECT oi.*, m.name AS meal_name 
                              FROM order_items oi
                              JOIN meals m ON oi.meal_id = m.id
                              WHERE oi.order_id = $order_id");

// --- Fetch customers ---
$customers_result = $conn->query("SELECT * FROM customers ORDER BY name ASC");

// --- Fetch meals ---
$meals_result = $conn->query("SELECT * FROM meals ORDER BY name ASC");

// Convert meals_result to array for reuse
$meals = [];
if ($meals_result && $meals_result->num_rows > 0) {
    while ($m = $meals_result->fetch_assoc()) {
        $meals[] = $m;
    }
}

// --- Handle form submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = intval($_POST['customer_id']);
    $new_total = 0;

    if (!empty($_POST['items'])) {
        // Multiple meals
        foreach ($_POST['items'] as $item_id => $item) {
            $meal_id = intval($item['meal_id']);
            $qty     = intval($item['quantity']);
            $price   = floatval($item['price_per_item']);
            $item_total = $price * $qty;
            $new_total += $item_total;

            $stmt = $conn->prepare("UPDATE order_items SET meal_id=?, quantity=?, price_per_item=?, total_price=? WHERE id=? AND order_id=?");
            $stmt->bind_param("iidiii", $meal_id, $qty, $price, $item_total, $item_id, $order_id);
            $stmt->execute();
        }
    } else {
        // Single meal
        $meal_id = intval($_POST['single_meal_id']);
        $qty = intval($_POST['single_quantity']);
        $price = floatval($_POST['single_price']);
        $item_total = $price * $qty;
        $new_total = $item_total;

        $stmt = $conn->prepare("UPDATE orders SET meal_id=?, quantity=?, price_per_item=?, total_price=? WHERE id=?");
        $stmt->bind_param("iiddi", $meal_id, $qty, $price, $item_total, $order_id);
        $stmt->execute();
    }

    // Update customer and total in orders table
    $stmt2 = $conn->prepare("UPDATE orders SET customer_id=?, total_price=? WHERE id=?");
    $stmt2->bind_param("idi", $customer_id, $new_total, $order_id);
    $stmt2->execute();

    header("Location: view_orders.php?msg=updated");
    exit();
}
?>

<div class="container mt-4">
    <h2>Edit Order #<?= $order_id ?></h2>
    <form method="POST">
        <div class="form-group mb-3">
            <label>Customer</label>
            <select name="customer_id" class="form-control" required>
                <?php 
                if ($customers_result && $customers_result->num_rows > 0) {
                    while ($c = $customers_result->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>" <?= ($order['customer_id'] == $c['id']) ? "selected" : "" ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endwhile; 
                } ?>
            </select>
        </div>

        <?php if ($items_result && $items_result->num_rows > 0): ?>
            <h4>Order Items</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Meal</th>
                        <th>Quantity</th>
                        <th>Price/Item</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($item = $items_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <select name="items[<?= $item['id'] ?>][meal_id]" class="form-control mealSelect" data-item="<?= $item['id'] ?>">
                                <?php foreach ($meals as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= ($item['meal_id'] == $m['id']) ? "selected" : "" ?>>
                                        <?= htmlspecialchars($m['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="items[<?= $item['id'] ?>][quantity]" class="form-control qtyInput" data-item="<?= $item['id'] ?>" value="<?= $item['quantity'] ?>" min="1" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="items[<?= $item['id'] ?>][price_per_item]" class="form-control priceInput" data-item="<?= $item['id'] ?>" value="<?= number_format($item['price_per_item'],2) ?>" required>
                        </td>
                        <td>
                            <input type="text" class="form-control totalInput" data-item="<?= $item['id'] ?>" value="<?= number_format($item['total_price'],2) ?>" disabled>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <h4>Single Meal</h4>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Meal</label>
                    <select name="single_meal_id" id="single_meal_id" class="form-control">
                        <?php foreach($meals as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($order['meal_id']==$m['id'])?'selected':'' ?>>
                                <?= htmlspecialchars($m['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Quantity</label>
                    <input type="number" name="single_quantity" id="single_quantity" class="form-control" min="1" value="<?= $order['quantity'] ?>">
                </div>
                <div class="col-md-3">
                    <label>Price/Item</label>
                    <input type="number" step="0.01" id="single_price" name="single_price" class="form-control" value="<?= number_format($order['price_per_item'],2) ?>">
                </div>
                <div class="col-md-3">
                    <label>Total</label>
                    <input type="text" id="single_total" class="form-control" value="<?= number_format($order['total_price'],2) ?>" disabled>
                </div>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-success">Update Order</button>
        <a href="view_orders.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Multiple meals
    function updateRow(itemId) {
        const qtyInput = document.querySelector(`.qtyInput[data-item="${itemId}"]`);
        const priceInput = document.querySelector(`.priceInput[data-item="${itemId}"]`);
        const totalInput = document.querySelector(`.totalInput[data-item="${itemId}"]`);
        if (!qtyInput || !priceInput) return;

        const qty = parseInt(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        totalInput.value = (price * qty).toFixed(2);
    }
    document.querySelectorAll(".qtyInput, .priceInput").forEach(el => {
        el.addEventListener("input", function(){ updateRow(this.dataset.item); });
    });

    // Single meal
    const singleQty = document.getElementById('single_quantity');
    const singlePrice = document.getElementById('single_price');
    const singleTotal = document.getElementById('single_total');

    function updateSingleTotal() {
        const total = (parseFloat(singlePrice.value) || 0) * (parseInt(singleQty.value) || 0);
        singleTotal.value = total.toFixed(2);
    }
    singleQty && singleQty.addEventListener('input', updateSingleTotal);
    singlePrice && singlePrice.addEventListener('input', updateSingleTotal);
});
</script>

<?php include 'footer.php'; ?>
