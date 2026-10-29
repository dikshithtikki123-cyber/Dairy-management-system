<?php
session_start();

if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dairy Management Dashboard</title>
</head>
<body>

<h1>Dairy Management System</h1>
<h2>Admin Dashboard</h2>

<p>Welcome, Admin!</p>

<h3>Manage Your Dairy</h3>

<ul>
    <li><a href="milk_records.php">Milk Records</a></li>
    <li><a href="customers.php">Customers</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>

</body>
</html>
