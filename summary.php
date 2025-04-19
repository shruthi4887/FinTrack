<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
$user_id = $_SESSION['user_id'];

$stmt_income = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'income'");
$stmt_income->execute([$user_id]);
$income = $stmt_income->fetchColumn() ?? 0;

$stmt_expense = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'expense'");
$stmt_expense->execute([$user_id]);
$expense = $stmt_expense->fetchColumn() ?? 0;

$saving = $income - $expense;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Summary</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container my-5">
    <h3 class="mb-4">💼 Financial Summary</h3>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card text-white bg-success h-100">
          <div class="card-body">
            <h5>Total Income</h5>
            <h3>₹<?= number_format($income) ?></h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-white bg-danger h-100">
          <div class="card-body">
            <h5>Total Expenses</h5>
            <h3>₹<?= number_format($expense) ?></h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-white bg-info h-100">
          <div class="card-body">
            <h5>Net Savings</h5>
            <h3>₹<?= number_format($saving) ?></h3>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
