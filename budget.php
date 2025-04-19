<?php
session_start();
require 'db.php';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id = ?");
$stmt->execute([$user_id]);
$budgets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Budget</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container my-5">
    <h3 class="mb-4">📋 Monthly Budget</h3>
    <table class="table table-bordered bg-white shadow">
      <thead class="table-secondary"><tr><th>Category</th><th>Limit</th><th>Spent</th></tr></thead>
      <tbody>
        <?php foreach ($budgets as $b): ?>
        <?php
          $spent = $pdo->query("SELECT SUM(amount) FROM transactions WHERE user_id = $user_id AND category = '{$b['category']}' AND type = 'expense'")->fetchColumn() ?? 0;
        ?>
        <tr>
          <td><?= $b['category'] ?></td>
          <td>₹<?= $b['limit_amount'] ?></td>
          <td class="<?= $spent > $b['limit_amount'] ? 'text-danger' : 'text-success' ?>">₹<?= $spent ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
