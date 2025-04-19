<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_name = $_POST['property_name'];
    $property_value = $_POST['property_value'];

    $stmt = $pdo->prepare("INSERT INTO properties (user_id, property_name, property_value) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $property_name, $property_value]);

    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2 class="mb-4">🏠 Add Property</h2>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Property Name:</label>
                    <input type="text" name="property_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Property Value (₹):</label>
                    <input type="number" name="property_value" class="form-control" step="0.01" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Property</button>
            </form>
        </div>
    </div>
</body>
</html>
