<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection script
require_once 'db.php';

$message = "";
$error = "";

// ----------------------------------------------------
// 1. HANDLE FORM SUBMISSION: ADD NEW EXPENSE
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $category     = trim($_POST['category']);
    $amount       = floatval($_POST['amount']);
    $expense_date = trim($_POST['expense_date']);
    $description  = trim($_POST['description']);

    if (!empty($category) && $amount > 0 && !empty($expense_date)) {
        $stmt =$conn->prepare("INSERT INTO expenses (category, amount, expense_date, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $category,$amount, $expense_date,$description);
        
        if ($stmt->execute()) {$message = "Expense recorded successfully!";
        } else {
            $error = "Failed to record expense: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields with valid values.";
    }
}

// ----------------------------------------------------
// 2. HANDLE DELETE EXPENSE
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $expense_id = intval($_GET['id']);
    
    $stmt =$conn->prepare("DELETE FROM expenses WHERE expense_id = ?");
    $stmt->bind_param("i", $expense_id);
    
    if ($stmt->execute()) {$message = "Expense record deleted successfully.";
    } else {
        $error = "Could not delete expense record: " . $conn->error;
    }
    $stmt->close();
}

// ----------------------------------------------------
// 3. FETCH EXPENSES WITH FILTER/SEARCH
// ----------------------------------------------------
$filter_category = isset($_GET['category']) ? trim($_GET['category']) : '';$search_query    = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];
$params = [];$types = "";

if (!empty($filter_category)) {$where_clauses[] = "category = ?";
    $params[] =$filter_category;
    $types .= "s";
}

if (!empty($search_query)) {$where_clauses[] = "(description LIKE ? OR category LIKE ?)";
    $searchTerm = "\%" . $search_query . "%";
    $params[] =$searchTerm;
    $params[] =$searchTerm;
    $types .= "ss";
}

$sql = "SELECT * FROM expenses";
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY expense_date DESC, expense_id DESC";

if (!empty($types)) {$stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);$stmt->execute();
    $result =$stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// ----------------------------------------------------
// 4. CALCULATE TOTAL EXPENSES (MONTHLY & OVERALL)
// ----------------------------------------------------
$total_overall = 0.00;
$total_this_month = 0.00;

$overall_res =$conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses");
if ($overall_res) {
    $total_overall =$overall_res->fetch_assoc()['total'];
}

$month_res =$conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())");
if ($month_res) {
    $total_this_month =$month_res->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Management - DairyFlow</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { min-height: 100vh; background-color: #0d233a; color: #fff; }
        .sidebar a { color: #a3b1c6; text-decoration: none; padding: 12px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #1e3a5f; color: #fff; border-left: 4px solid #0d6efd; }
        .card-header-custom { background-color: #ffffff; border-bottom: 2px solid #e9ecef; }
        .table-hover tbody tr:hover { background-color: #f1f5f9; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <nav class="col-md-3 col-lg-2 sidebar p-0">
            <div class="p-3 fs-4 fw-bold text-center border-bottom border-secondary">
                <i class="fa-solid fa-cow me-2"></i>DairyFlow
            </div>
            <div class="mt-3">
                <a href="dashboard.php"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
                <a href="farmers.php"><i class="fa-solid fa-users me-2"></i> Farmers</a>
                <a href="cattle.php"><i class="fa-solid fa-cow me-2"></i> Cattle</a>
                <a href="milk_records.php"><i class="fa-solid fa-bucket me-2"></i> Milk Collection</a>
                <a href="customers.php"><i class="fa-solid fa-user-group me-2"></i> Customers</a>
                <a href="sales.php"><i class="fa-solid fa-cart-shopping me-2"></i> Milk Sales</a>
                <a href="payments.php"><i class="fa-solid fa-hand-holding-dollar me-2"></i> Payments</a>
                <a href="expenses.php" class="active"><i class="fa-solid fa-receipt me-2"></i> Expenses</a>
                <a href="reports.php"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Reports</a>
                <a href="logout.php" class="text-danger mt-4"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
                <h1 class="h3">Expense Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="fa-solid fa-plus me-1"></i> Add New Expense
                </button>
            </div>

            <!-- Alerts for Success / Errors -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm border-start border-primary border-4 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 fw-bold">This Month's Expenses</p>
                                <h3 class="mb-0 text-primary">₹<?= number_format($total_this_month, 2) ?></h3>
                            </div>
                            <div class="bg-primary text-white rounded-circle p-3">
                                <i class="fa-solid fa-calendar-check fa-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm border-start border-danger border-4 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 fw-bold">Total Overall Expenses</p>
                                <h3 class="mb-0 text-danger">₹<?= number_format($total_overall, 2) ?></h3>
                            </div>
                            <div class="bg-danger text-white rounded-circle p-3">
                                <i class="fa-solid fa-wallet fa-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter Controls -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="expenses.php" class="row g-3">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Search by description or category..." value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <option value="Feed" <?= $filter_category === 'Feed' ? 'selected' : '' ?>>Feed & Cattle Food</option>
                                <option value="Veterinary" <?= $filter_category === 'Veterinary' ? 'selected' : '' ?>>Veterinary & Medical</option>
                                <option value="Transport" <?= $filter_category === 'Transport' ? 'selected' : '' ?>>Transport & Fuel</option>
                                <option value="Electricity" <?= $filter_category === 'Electricity' ? 'selected' : '' ?>>Electricity & Utility</option>
                                <option value="Equipment" <?= $filter_category === 'Equipment' ? 'selected' : '' ?>>Equipment & Maintenance</option>
                                <option value="Other" <?= $filter_category === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-secondary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Expense List Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header card-header-custom py-3">
                    <h5 class="mb-0 text-secondary">Expense Log</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Amount (₹)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result &&$result->num_rows > 0): ?>
                                    <?php while ($row =$result->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong>EXP<?= sprintf('%03d', $row['expense_id']) ?></strong></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($row['category']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($row['description']) ?></td>
                                            <td><?= date('d M Y', strtotime($row['expense_date'])) ?></td>
                                            <td class="text-danger fw-bold">₹<?= number_format($row['amount'], 2) ?></td>
                                            <td>
                                                <a href="expenses.php?action=delete&id=<?= $row['expense_id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this expense entry?');">
                                                   <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No expense records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Modal: Add New Expense -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="expenses.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addExpenseModalLabel">Record New Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label
