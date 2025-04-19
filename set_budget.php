<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $month = $_POST['month'];

    $stmt = $pdo->prepare("INSERT INTO budgets (user_id, category, amount, month) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $category, $amount, $month]);

    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Set Budget</title>
</head>
<body>
    <h2>Set Budget</h2>
    <form method="post">
        <label>Category:</label><br>
        <input type="text" name="category" required><br>
        <label>Amount:</label><br>
        <input type="number" name="amount" step="0.01" required><br>
        <label>Month:</label><br>
        <input type="month" name="month" required><br><br>
        <button type="submit">Set Budget</button>
    </form>
</body>
</html>
