<?php
include 'header.php';

$success = '';
$error = '';

// ===== ADD MEAL =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_meal'])) {
    $meal = trim($_POST['meal'] ?? '');
    if ($meal === '') {
        $error = "Meal name cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO meals (name) VALUES (?)");
        $stmt->bind_param("s", $meal);
        if ($stmt->execute()) {
            $success = "Meal added successfully.";
        } else {
            $error = "Error adding meal. " . $conn->error;
        }
        $stmt->close();
    }
}

// ===== EDIT MEAL =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_meal'])) {
    $id = intval($_POST['meal_id']);
    $meal = trim($_POST['meal_name'] ?? '');
    if ($meal === '') {
        $error = "Meal name cannot be empty.";
    } else {
        $stmt = $conn->prepare("UPDATE meals SET name=? WHERE id=?");
        $stmt->bind_param("si", $meal, $id);
        if ($stmt->execute()) {
            $success = "Meal updated successfully.";
        } else {
            $error = "Error updating meal. " . $conn->error;
        }
        $stmt->close();
    }
}

// ===== DELETE MEAL =====
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM meals WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Meal deleted successfully.";
    } else {
        $error = "Error deleting meal. " . $conn->error;
    }
    $stmt->close();
}

// ===== FETCH ALL MEALS =====
$meals = [];
$result = $conn->query("SELECT * FROM meals ORDER BY id DESC");
if ($result && $result->num_rows > 0) {
    $meals = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="card p-4">
  <h4 class="mb-3">Add New Meal</h4>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Add Meal Form -->
  <form method="post" autocomplete="off">
    <div class="mb-3">
      <label class="form-label">Meal Name</label>
      <input type="text" name="meal" class="form-control" placeholder="e.g. Chicken Biryani" required>
    </div>
    <button type="submit" name="add_meal" class="btn btn-primary">Add Meal</button>
  </form>
</div>

<hr>

<!-- Meal List -->
<div class="card p-4 mt-3">
  <h4 class="mb-3">Meals List</h4>
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th width="10%">ID</th>
        <th>Meal Name</th>
        <th width="25%">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($meals): ?>
        <?php foreach ($meals as $m): ?>
          <tr>
            <td><?= $m['id'] ?></td>
            <td><?= htmlspecialchars($m['name']) ?></td>
            <td>
              <!-- Edit Button -->
              <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $m['id'] ?>">Edit</button>
              <!-- Delete Button -->
              <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Are you sure you want to delete this meal?');">
                 Delete
              </a>
            </td>
          </tr>

          <!-- Edit Modal -->
          <div class="modal fade" id="editModal<?= $m['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="post">
                  <div class="modal-header">
                    <h5 class="modal-title">Edit Meal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="meal_id" value="<?= $m['id'] ?>">
                    <div class="mb-3">
                      <label class="form-label">Meal Name</label>
                      <input type="text" name="meal_name" class="form-control" value="<?= htmlspecialchars($m['name']) ?>" required>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" name="edit_meal" class="btn btn-success">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="3" class="text-center">No meals found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include 'footer.php'; ?>
