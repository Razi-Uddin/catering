<?php
include 'header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $meals = $_POST['meal_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['price'] ?? [];
    $order_date = date('Y-m-d');

    if ($customer_id <= 0 || empty($meals)) {
        $error = "Please select a customer and at least one meal.";
    } else {
        $conn->begin_transaction();
        try {
            // Calculate total order price
            $order_total = 0;
            foreach ($meals as $i => $meal_id) {
                $qty = intval($quantities[$i]);
                $price = floatval($prices[$i]);
                $order_total += ($qty * $price);
            }

            // Insert into orders
            $stmt = $conn->prepare("INSERT INTO orders (customer_id, order_date, total_price) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $customer_id, $order_date, $order_total);
            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();

            // Insert items
            $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, meal_id, quantity, price_per_item, total_price) VALUES (?, ?, ?, ?, ?)");
            foreach ($meals as $i => $meal_id) {
                $qty = intval($quantities[$i]);
                $price = floatval($prices[$i]);
                $line_total = $qty * $price;
                $stmtItem->bind_param("iiidd", $order_id, $meal_id, $qty, $price, $line_total);
                $stmtItem->execute();
            }
            $stmtItem->close();

            $conn->commit();
            $success = "Order added successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error adding order: " . $e->getMessage();
        }
    }
}
?>

<div class="card p-4">
  <h4 class="mb-3">Add Meal Order</h4>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">Select Customer</label>
        <select name="customer_id" class="form-select" required>
          <option value="">-- Select --</option>
          <?php
            $res = $conn->query("SELECT id, name FROM customers ORDER BY name ASC");
            while ($row = $res->fetch_assoc()) {
              echo '<option value="'.(int)$row['id'].'">'.htmlspecialchars($row['name']).'</option>';
            }
          ?>
        </select>
      </div>
    </div>

    <h5>Meals</h5>
    <table class="table table-bordered" id="mealTable">
        <thead>
            <tr>
                <th>Meal</th>
                <th>Quantity</th>
                <th>Price per item</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                  <select name="meal_id[]" class="form-select" required>
                    <option value="">-- Select --</option>
                    <?php
                      $resM = $conn->query("SELECT id, name FROM meals ORDER BY name ASC");
                      while ($row = $resM->fetch_assoc()) {
                        echo '<option value="'.(int)$row['id'].'">'.htmlspecialchars($row['name']).'</option>';
                      }
                    ?>
                  </select>
                </td>
                <td><input type="number" name="quantity[]" class="form-control" min="1" required></td>
                <td><input type="number" step="0.01" name="price[]" class="form-control" min="0.01" required></td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">X</button></td>
            </tr>
        </tbody>
    </table>
    <button type="button" class="btn btn-secondary mb-3" onclick="addRow()">+ Add Meal</button>

    <div class="mt-3">
      <button class="btn btn-success">Add Order</button>
    </div>
  </form>
</div>

<script>
function addRow() {
  let table = document.getElementById("mealTable").getElementsByTagName('tbody')[0];
  let newRow = table.rows[0].cloneNode(true);
  newRow.querySelectorAll("input").forEach(input => input.value = "");
  table.appendChild(newRow);
}
function removeRow(btn) {
  let row = btn.closest("tr");
  let table = document.getElementById("mealTable").getElementsByTagName('tbody')[0];
  if (table.rows.length > 1) {
    row.remove();
  } else {
    alert("At least one meal is required.");
  }
}
</script>

<?php include 'footer.php'; ?>
