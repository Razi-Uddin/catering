<?php
session_start();
include '../includes/functions.php';

if (!isLoggedInCustomer()) {
    header('Location: login.php');
    exit();
}

$customerName = $_SESSION['customer_name'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard - PWC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Welcome, <?= htmlspecialchars($customerName) ?>!</h4>
        <a href="../logout.php" class="btn btn-danger">Logout</a>
    </div>

    <div class="list-group">
        <a href="view_bills.php" class="list-group-item list-group-item-action">
            View My Meal Orders / Bills
        </a>
    </div>
</div>
</body>
</html>
