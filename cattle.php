<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'db.php';

$message = ""; $error = "";

// Add Cattle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cattle'])) {
    $tag_number    = trim($_POST['tag_number']);
    $breed         = trim($_POST['breed']);
    $gender        = trim($_POST['gender']);
    $health_status = trim($_POST['health_status']);
    $farmer_id     = intval($_POST['farmer_id']);

    if (!empty($tag_number) && !empty($breed) &&$farmer_id > 0) {
        $stmt =$conn->prepare("INSERT INTO cattle (tag_number, breed, gender, health_status, farmer_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $tag_number, $breed,$gender, $health_status,$farmer_id);
        if ($stmt->execute()) {$message = "Cattle registered successfully!"; }
        else { $error = "Error: " . $conn->error; }$stmt->close();
    } else { $error = "Please fill in all required fields."; }
}

// Delete Cattle
if (isset($_GET['action']) &&$_GET['action'] === 'delete' && isset($_GET['id'])) {$id = intval($_GET['id']);$conn->query("DELETE FROM cattle WHERE cattle_id = $id");
    $message = "Cattle record deleted.";
}

// Fetch Cattle with Farmer info
$result =$conn->query("SELECT c.*, f.name AS farmer_name FROM cattle c LEFT JOIN farmers f ON c.farmer_id = f.farmer_id ORDER BY c.cattle_id DESC");
$farmers =$conn->query("SELECT farmer_id, name FROM farmers ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cattle Management - DairyFlow</title>
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
            <a href="cattle.php" class="active"><i class="fa-solid fa-cow me-2"></i> Cattle</a>
            <a href="milk_records.php"><i class="fa-solid fa-bucket me-2"></i> Milk Collection</a>
            <a href="customers.php"><i class="fa-solid fa-user-group me-2"></i> Customers</a>
            <a href="sales.php"><i class="fa-solid fa-cart-shopping me-2"></i> Milk Sales</a>
            <a href="payments.php"><i class="fa-solid fa-hand-holding-dollar me-2"></i> Payments</a>
            <a href="expenses.php"><i class="fa-solid fa-receipt me-2"></i> Expenses</a>
            <a href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Reports</a>
            <a href="logout.php" class="text-danger mt-4"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a>
        </div>
    </nav>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
        <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
            <h1 class="h3">Cattle Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCattleModal"><i class="fa-solid fa-plus me-1"></i> Add Cattle</button>
        </div>
        <?php if(!empty($message)): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
        <?php if(!empty($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <div class="card border-0 shadow-sm"><div class="card-body p-0"><div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Tag Number</th><th>Breed</th><th>Gender</th><th>Health Status</th><th>Farmer Owner</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if($result &&$result->num_rows > 0): while($row =$result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['tag_number']) ?></strong></td>
                        <td><?= htmlspecialchars($row['breed']) ?></td>
                        <td><?= htmlspecialchars($row['gender']) ?></td>
                        <td><span class="badge bg-<?= $row['health_status']=='Healthy'?'success':'warning text-dark' ?>"><?= htmlspecialchars($row['health_status']) ?></span></td>
                        <td><?= htmlspecialchars($row['farmer_name'] ?? 'N/A') ?></td>
                        <td><a href="cattle.php?action=delete&id=<?= $row['cattle_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this record?');"><i class="fa-solid fa-trash"></i></a></td>
                    </tr>
                    <?php endwhile; else: ?><tr><td colspan="6" class="text-center py-4 text-muted">No cattle records found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div></div>
    </main>
</div></div>
<!-- Modal -->
<div class="modal fade" id="addCattleModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST">
        <div class="modal-header"><h5 class="modal-title">Register Cattle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Tag Number *</label><input type="text" name="tag_number" class="form-control" required placeholder="e.g. C001"></div>
            <div class="mb-3"><label class="form-label">Breed *</label><input type="text" name="breed" class="form-control" required placeholder="e.g. Holstein / Jersey"></div>
            <div class="mb-3"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="Female">Female</option><option value="Male">Male</option></select></div>
            <div class="mb-3"><label class="form-label">Health Status</label><select name="health_status" class="form-select"><option value="Healthy">Healthy</option><option value="Under Treatment">Under Treatment</option></select></div>
            <div class="mb-3"><label class="form-label">Farmer Owner *</label><select name="farmer_id" class="form-select" required><option value="">Select Farmer</option><?php while($f =$farmers->fetch_assoc()): ?><option value="<?= $f['farmer_id'] ?>"><?= $f['name'] ?></option><?php endwhile; ?></select></div>
        </div>
        <div class="modal-footer"><button type="submit" name="add_cattle" class="btn btn-primary">Save Cattle</button></div>
    </form>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
