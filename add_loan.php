<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_amount = $_POST['loan_amount'];
    $lender = $_POST['lender'];
    $purpose = $_POST['purpose'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO loans (user_id, loan_amount, lender, purpose, date_taken) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $loan_amount, $lender, $purpose]);
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Loan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">Add Loan</h4>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Loan Amount</label>
                    <input type="number" step="0.01" name="loan_amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lender</label>
                    <input type="text" name="lender" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Purpose</label>
                    <textarea name="purpose" class="form-control" rows="2"></textarea>
                </div>
                <button class="btn btn-success">Add</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
