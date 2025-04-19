<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $liability_name = $_POST['liability_name'];
    $liability_amount = $_POST['liability_amount'];

    $stmt = $pdo->prepare("INSERT INTO liabilities (user_id, liability_name, liability_amount) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $liability_name, $liability_amount]);

    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Liability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2 class="mb-4">💳 Add Liability</h2>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Liability Name:</label>
                    <input type="text" name="liability_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Liability Amount (₹):</label>
                    <input type="number" name="liability_amount" class="form-control" step="0.01" required>
                </div>
                <button type="submit" class="btn btn-danger">Add Liability</button>
            </form>
        </div>
    </div>
</body>
</html>
