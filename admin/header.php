<?php
// admin/header.php
session_start();
include '../includes/db.php';
include '../includes/functions.php';

if (!function_exists('isLoggedInAdmin') || !isLoggedInAdmin()) {
    // If you’re using hardcoded login (no DB), replace the check with:
    // if (!isset($_SESSION['admin'])) { header('Location: login.php'); exit(); }
    header('Location: login.php');
    exit();
}

$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PWC Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    body { min-height: 100vh; margin: 0; display: flex; background:#f8f9fa; }
    .sidebar {
      width: 260px; background:#343a40; color:#fff; flex-shrink:0;
      position: sticky; top:0; height:100vh; overflow-y:auto;
    }
    .sidebar .brand { padding:18px 20px; font-weight:700; background:#23272b; margin:0; }
    .sidebar a { display:block; color:#fff; text-decoration:none; padding:12px 20px; }
    .sidebar a:hover { background:#495057; }
    .sidebar a.active { background:#0d6efd; }
    .content { flex:1; padding:24px; }
    .card { border:0; box-shadow:0 6px 20px rgba(0,0,0,0.06); }
    .table thead th { white-space: nowrap; }
  </style>
</head>
<body>
  <aside class="sidebar">
    <h4 class="brand m-0">PWC Admin</h4>
    <nav class="mt-2">
      <a href="dashboard.php" class="<?= $current==='dashboard.php'?'active':''; ?>">Dashboard</a>
      <a href="add_customer.php" class="<?= $current==='add_customer.php'?'active':''; ?>">Customer</a>
      <a href="add_meal.php" class="<?= $current==='add_meal.php'?'active':''; ?>">Meal</a>
      <a href="add_order.php" class="<?= $current==='add_order.php'?'active':''; ?>">Add Order</a>
      <a href="view_orders.php" class="<?= $current==='view_orders.php'?'active':''; ?>">View Orders</a>
      <a href="../logout.php">Logout</a>
    </nav>
  </aside>
  <main class="content">
