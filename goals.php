<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
$user_id = $_SESSION['user_id'];

// Fetch user goals
$stmt = $pdo->prepare("SELECT * FROM goals WHERE user_id = ?");
$stmt->execute([$user_id]);
$goals = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Financial Goals</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container my-5">
    <div class="card shadow-sm">
      <div class="card-header bg-primary text-white">
        <h4 class="mb-0">🎯 Financial Goals</h4>
      </div>
      <div class="card-body">
        <?php if (count($goals) > 0): ?>
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Goal</th>
                <th>Target Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($goals as $goal): ?>
              <tr>
                <td><?= htmlspecialchars($goal['goal_name']) ?></td>
                <td>₹<?= number_format($goal['target_amount'], 2) ?></td>
                <td><?= htmlspecialchars($goal['status'] ?? 'Pending') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="alert alert-info">No goals found. Start by setting one!</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
