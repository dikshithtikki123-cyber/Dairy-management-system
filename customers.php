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
    <title>Dairy Customers</title>
</head>
<body>

<h1>Customer Management</h1>

<form method="POST">
    <label>Customer Name:</label>
    <input type="text" name="name" required>
    <br><br>

    <label>Phone Number:</label>
    <input type="tel" name="phone" required>
    <br><br>

    <label>Address:</label>
    <textarea name="address" required></textarea>
    <br><br>

    <button type="submit">Add Customer</button>
</form>

<br>
<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>
