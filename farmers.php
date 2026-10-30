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
// 1. HANDLE FORM SUBMISSION: ADD NEW FARMER
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_farmer'])) {
    $name    = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);

    if (!empty($name) && !empty($contact)) {
        $stmt =$conn->prepare("INSERT INTO farmers (name, contact, address) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $contact,$address);
        
        if ($stmt->execute()) {$message = "Farmer added successfully!";
        } else {
            $error = "Failed to add farmer: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Name and Contact fields are required.";
    }
}

// ----------------------------------------------------
// 2. HANDLE DELETE FARMER
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $farmer_id = intval($_GET['id']);
    
    $stmt =$conn->prepare("DELETE FROM farmers WHERE farmer_id = ?");
    $stmt->bind_param("i", $farmer_id);
    
    if ($stmt->execute()) {$message = "Farmer record deleted successfully.";
    } else {
        $error = "Could not delete farmer record (Ensure no active cattle/collection dependencies exist).";
    }
    $stmt->close();
}

// ----------------------------------------------------
// 3. FETCH FARMERS WITH SEARCH FILTER
// ----------------------------------------------------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    $stmt =$conn->prepare("SELECT f.*, 
                            (SELECT COUNT(*) FROM cattle WHERE farmer_id = f.farmer_id) AS total_cattle,
                            (SELECT COALESCE(SUM(quantity_liters), 0) FROM milk_collections WHERE farmer_id = f.farmer_id) AS total_milk
                            FROM farmers f 
                            WHERE f.name LIKE ? OR f.contact LIKE ?
                            ORDER BY f.farmer_id DESC");
    $searchTerm = "\%" . $search . "%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);$stmt->execute();
    $result =$stmt->get_result();
} else {
    $query = "SELECT f.*, 
              (SELECT COUNT(*) FROM cattle WHERE farmer_id = f.farmer_id) AS total_cattle,
              (SELECT COALESCE(SUM(quantity_liters), 0) FROM milk_collections WHERE farmer_id = f.farmer_id) AS total_milk
              FROM farmers f 
              ORDER BY f.farmer_id DESC";
    $result = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Management - DairyFlow
