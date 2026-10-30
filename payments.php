<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'db.php';

$message = ""; $error = "";

// Add Sale & Invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sale'])) {
    $customer_id = intval($_POST['customer_id']);
    $quantity    = floatval($_POST['quantity_liters']);
    $rate        = floatval($_POST['rate']);
    $sale_date   = trim($_POST['sale_date']);
    $status      = trim($_POST['payment_status']);
    $total       = $quantity * $rate;
    $invoice_no  = 'INV' . sprintf('%03d', rand(1, 999));

    if ($customer_id > 0 && $quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO milk_sales (customer_id, invoice_number, date, quantity_liters, rate, total_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddds", $customer_id, $invoice_no, $sale_date, $quantity, $rate, $total, $status);
        if ($stmt->execute()) { $message = "Sale invoice created successfully!"; }
        else { $error = "Error: " . $conn->error; }
        $stmt->close();
    } else { $error = "Please fill out all required fields."; }
}

$sales = $conn->query("SELECT s.*, c.name AS customer_name FROM milk_sales s LEFT JOIN customers c ON s.customer_id = c.customer_id ORDER BY s.sale_id DESC");
$customers = $conn->query("SELECT customer_id, name FROM customers ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Milk Sales - DairyFlow</title>
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
            <a href="sales.php" class="active"><i class="fa-solid fa-cart-shopping me-2"></i> Milk Sales</a>
            <a href="payments.php"><i class="fa-solid fa-hand-holding-dollar me-2"></i> Payments</a>
            <a href="expenses.php"><i class="fa-solid fa-receipt me-2"></i> Expenses</a>
            <a href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Reports</a>
            <a href="logout.php" class="text-danger mt-4"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a>
        </div>
    </nav>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
            <h1 class="h3">Milk Sales & Billing</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSaleModal"><i class="fa-solid fa-plus me-1"></i> New Invoice</button>
        </div>
        <?php if(!empty($message)): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
        <div class="card border-0 shadow-sm"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Quantity</th><th>Rate (₹)</th><th>Total (₹)</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if($sales && $sales->num_rows > 0): while($row = $sales->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['invoice_number']) ?></strong></td>
                        <td><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td><?= $row['date'] ?></td>
                        <td><?= $row['quantity_liters'] ?> L</td>
                        <td>₹<?= $row['rate'] ?></td>
                        <td class="fw-bold text-success">₹<?= number_format($row['total_amount'], 2) ?></td>
                        <td><span class="badge bg-<?= $row['payment_status']=='Paid'?'success':'warning text-dark' ?>"><?= $row['payment_status'] ?></span></td>
                    </tr>
                    <?php endwhile; else: ?><tr><td colspan="7" class="text-center py-4 text-muted">No sales records found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div></div>
    </main>
</div></div>
<!-- Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST">
        <div class="modal-header"><h5 class="modal-title">Create Milk Invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Customer *</label><select name="customer_id" class="form-select" required><option value="">Select Customer</option><?php while($c = $customers->fetch_assoc()): ?><option value="<?= $c['customer_id'] ?>"><?= $c['name'] ?></option><?php endwhile; ?></select></div>
            <div class="mb-3"><label class="form-label">Quantity (Liters) *</label><input type="number" step="0.1" name="quantity_liters" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Rate per Liter (₹) *</label><input type="number" step="0.01" name="rate" class="form-control" required value="45.00"></div>
            <div class="mb-3"><label class="form-label">Sale Date *</label><input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="mb-3"><label class="form-label">Payment Status</label><select name="payment_status" class="form-select"><option value="Paid">Paid</option><option value="Pending">Pending</option></select></div>
        </div>
        <div class="modal-footer"><button type="submit" name="add_sale" class="btn btn-primary">Generate Invoice</button></div>
    </form>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
