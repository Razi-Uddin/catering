<?php
include 'header.php';

$success = '';
$error = '';

// ===== ADD CUSTOMER =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addCustomer'])) {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $rawPass = trim($_POST['password'] ?? '');

    if ($name === '' || $username === '' || $rawPass === '') {
        $error = "All fields are required.";
    } else {
        // Check if username exists
        $check = $conn->prepare("SELECT id FROM customers WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            $password = password_hash($rawPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customers (name, username, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $username, $password);
            if ($stmt->execute()) {
                $success = "Customer added successfully.";
            } else {
                $error = "Error adding customer. " . $conn->error;
            }
        }
        $check->close();
    }
}

// ===== UPDATE CUSTOMER =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateCustomer'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $rawPass = trim($_POST['password']);

    if ($name === '' || $username === '') {
        $error = "Name and username cannot be empty.";
    } else {
        if ($rawPass !== '') {
            // Update with new password
            $password = password_hash($rawPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE customers SET name=?, username=?, password=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $username, $password, $id);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE customers SET name=?, username=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $username, $id);
        }

        if ($stmt->execute()) {
            $success = "Customer updated successfully.";
        } else {
            $error = "Error updating customer. " . $conn->error;
        }
        $stmt->close();
    }
}

// ===== DELETE CUSTOMER =====
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM customers WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Customer deleted successfully.";
    } else {
        $error = "Error deleting customer. " . $conn->error;
    }
    $stmt->close();
}

// ===== FETCH CUSTOMERS =====
$result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
?>

<div class="card p-4">
  <h4 class="mb-3">Add New Customer</h4>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Customer Name</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Username (for login)</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Password (for login)</label>
        <input type="text" name="password" class="form-control" required>
      </div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary" name="addCustomer">Add Customer</button>
    </div>
  </form>
</div>

<!-- VIEW / EDIT CUSTOMERS -->
<div class="card p-4 mt-4">
  <h4 class="mb-3">All Customers</h4>
  <div style="max-height: 500px; overflow-y: auto;">
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Username</th>
          <th>Password (leave blank if not changing)</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <form method="post">
              <td><?= $row['id'] ?>
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
              </td>
              <td><input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control"></td>
              <td><input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" class="form-control"></td>
              <td><input type="text" name="password" class="form-control" placeholder="Leave blank to keep old password"></td>
              <td>
                <button class="btn btn-success btn-sm" name="updateCustomer">Update</button>
                <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this customer?');">Delete</a>
              </td>
            </form>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'footer.php'; ?>
