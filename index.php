<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome to FinTrack</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #121212;
      color: #ffffff;
      font-family: 'Segoe UI', sans-serif;
    }
    .welcome-box {
      text-align: center;
      margin-top: 20vh;
    }
    .welcome-box h1 {
      font-size: 2.8rem;
      margin-bottom: 20px;
    }
    .btn-custom {
      padding: 12px 25px;
      font-size: 1.2rem;
      margin: 10px;
      border-radius: 8px;
      transition: background-color 0.3s ease;
    }
    .btn-login {
      background-color: #007bff;
      color: white;
    }
    .btn-login:hover {
      background-color: #0056b3;
    }
    .btn-register {
      background-color: #28a745;
      color: white;
    }
    .btn-register:hover {
      background-color: #1e7e34;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="welcome-box">
      <h1>Welcome to FinTrack</h1>
      <p class="lead">Your personal finance companion to track income, expenses, loans, and more.</p>
      <div>
        <a href="login.php" class="btn btn-custom btn-login">Login</a>
        <a href="register.php" class="btn btn-custom btn-register">Register</a>
      </div>
    </div>
  </div>
</body>
</html>
