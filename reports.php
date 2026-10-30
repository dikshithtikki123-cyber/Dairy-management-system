<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'db.php';

// Summary metrics
$total_milk = $conn->query("SELECT COALESCE(SUM(quantity_liters), 0) AS total FROM milk_collections")->fetch_assoc()['total'];
$total_sales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM milk_sales")->fetch_assoc()['total'];
$total_expenses = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - DairyFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background-color: #0d233a; color: #fff; }
        .sidebar a { color: #a3b1c6; text-decoration: none; padding: 12px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #1e3a5f; color: #fff; border-left: 4px solid #0d6efd; }
    </style>
</head>
<body>
<div class="container-fluid"><div class="row">
    <nav class="col-md-3 col-lg-2 sidebar p-0">
        <div class="p-3 fs-4 fw-bold text-center border-bottom border-secondary"><i class="fa-solid fa-cow me-2"></i>DairyFlow</div>
        <div class="mt-3">
            <a href="dashboard.php"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
            <a href="farmers.php"><i class="fa-solid fa-users me-2"></i> Farmers</a>
            <a href="cattle.php"><i class="fa-solid fa-cow me-2"></i> Cattle</a>
            <a href="milk_records.php"><i class="fa-solid fa-bucket me-2"></i> Milk Collection</a>
            <a href="customers.php"><i class="fa-solid fa-user-group me-2"></i> Customers</a>
            <a href="sales.php"><i class="fa-solid fa-cart-shopping me-2"></i> Milk Sales</a>
            <a href="payments.php"><i class="fa-solid fa-hand-holding-dollar me-2"></i> Payments</a>
            <a href="expenses.php"><i class="fa-solid fa-receipt me-2"></i> Expenses</a>
            <a href="reports.php" class="active"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Reports</a>
            <a href="logout.php" class="text-danger mt-4"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a>
        </div>
    </nav>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
            <h1 class="h3">Reports & Analytics</h1>
            <button onclick="window.print();" class="btn btn-secondary"><i class="fa-solid fa-print me-1"></i> Print Report</button>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card p-3 border-0 shadow-sm"><p class="text-muted mb-1">Total Milk Collected</p><h3><?= number_format($total_milk, 1) ?> L</h3></div></div>
            <div class="col-md-4"><div class="card p-3 border-0 shadow-sm"><p class="text-muted mb-1">Total Sales Revenue</p><h3 class="text-success">₹<?= number_format($total_sales, 2) ?></h3></div></div>
            <div class="col-md-4"><div class="card p-3 border-0 shadow-sm"><p class="text-muted mb-1">Total Expenses</p><h3 class="text-danger">₹<?= number_format($total_expenses, 2) ?></h3></div></div>
        </div>
    </main>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
