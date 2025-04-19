<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Monthly income & expense
$income_query = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'income'");
$income_query->execute([$user_id]);
$total_income = $income_query->fetchColumn() ?? 0;

$expense_query = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE user_id = ? AND type = 'expense'");
$expense_query->execute([$user_id]);
$total_expenses = $expense_query->fetchColumn() ?? 0;

$savings = $total_income - $total_expenses;
$saving_rate = ($total_income > 0) ? ($savings / $total_income) * 100 : 0;

// Expense by category for pie chart
$cat_stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'expense' GROUP BY category");
$cat_stmt->execute([$user_id]);
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$labels = json_encode(array_column($categories, 'category'));
$data = json_encode(array_column($categories, 'total'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Score Analysis</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .summary-box {
            background: linear-gradient(to right, #1e3c72, #2a5298);
            color: white;
            border-radius: 15px;
            padding: 20px;
        }

        .summary-box h4 {
            font-weight: bold;
        }

        canvas {
            background: white;
            border-radius: 12px;
            padding: 20px;
        }
    </style>
</head>
<body>

<div class="container my-5">
    <h2 class="text-center mb-4">📊 Financial Score Analysis</h2>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <canvas id="barChart"></canvas>
        </div>
        <div class="col-md-6">
            <canvas id="pieChart"></canvas>
        </div>
    </div>

    <div class="summary-box text-center mt-4">
        <h4>Conclusion</h4>
        <p class="lead mt-2">
            <?php
            if ($savings < 0) {
                echo "You are spending more than you earn. Time to revisit your expenses and plan a strict budget.";
            } elseif ($saving_rate < 20) {
                echo "You're saving, but there's room for improvement. Aim for at least a 20% savings rate.";
            } else {
                echo "Great job! Your financial health looks good with a savings rate of " . round($saving_rate) . "%.";
            }
            ?>
        </p>
    </div>
</div>

<script>
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['Income', 'Expenses'],
            datasets: [{
                label: 'Monthly Overview',
                data: [<?= $total_income ?>, <?= $total_expenses ?>],
                backgroundColor: ['#28a745', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            }
        }
    });

    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: <?= $labels ?>,
            datasets: [{
                data: <?= $data ?>,
                backgroundColor: [
                    '#007bff', '#fd7e14', '#ffc107', '#dc3545', '#20c997', '#6f42c1'
                ]
            }]
        },
        options: {
            responsive: true
        }
    });
</script>

</body>
</html>
