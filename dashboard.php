<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: login.php');
    exit();
}

require 'db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

try {
    // Financial stats
    
    $balance = $pdo->prepare("SELECT SUM(amount) AS balance FROM transactions WHERE user_id = ?");
    $property = $pdo->prepare("SELECT SUM(property_value) AS total_property FROM properties WHERE user_id = ?");
    $property->execute([$user_id]);
    $total_property = $property->fetch()['total_property'] ?? 0;
    $loan = $pdo->prepare("SELECT SUM(loan_amount) AS total_loan FROM loans WHERE user_id = ?");
    $loan->execute([$user_id]);
    $total_loan = $loan->fetch()['total_loan'] ?? 0;
    $loans = $pdo->prepare("SELECT SUM(loan_amount) AS total_loans FROM loans WHERE user_id = ?");
    $loans->execute([$user_id]);
    $total_loans = $loans->fetch()['total_loans'] ?? 0;
    $stmt = $pdo->prepare("SELECT SUM(liability_amount) AS total_liabilities FROM liabilities WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $totalLiabilities = $stmt->fetch(PDO::FETCH_ASSOC)['total_liabilities'] ?? 0;
    // Check for due payments in dashboard.php
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? AND due_date <= ?");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $duePayments = $stmt->fetchAll();
    $showDueAlert = count($duePayments) > 0;

    $balance->execute([$user_id]);
    $balance = $balance->fetch()['balance'] ?? 0;

    $income = $pdo->prepare("SELECT SUM(amount) AS total_income FROM transactions WHERE user_id = ? AND type = 'income'");
    $income->execute([$user_id]);
    $total_income = $income->fetch()['total_income'] ?? 0;

    $expense = $pdo->prepare("SELECT SUM(amount) AS total_expense FROM transactions WHERE user_id = ? AND type = 'expense'");
    $expense->execute([$user_id]);
    $total_expense = $expense->fetch()['total_expense'] ?? 0;

    // Recent transactions
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($recent_transactions as &$txn) {
        $txn['date'] = date('Y-m-d', strtotime($txn['date']));
    }

    // Payments for calendar
    $stmt = $pdo->prepare("SELECT amount, due_date, description FROM payments WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare calendar events
    $calendar_events = array_map(function ($p) {
        return [
            'title' => '₹' . $p['amount'] . ' - ' . $p['description'],
            'start' => $p['due_date'],
            'color' => '#ff4d4d'
        ];
    }, $payments);

    // Get detailed data for modals
    // Get all income transactions
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? AND type = 'income' ORDER BY date DESC");
    $stmt->execute([$user_id]);
    $all_income = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all expense transactions
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? AND type = 'expense' ORDER BY date DESC");
    $stmt->execute([$user_id]);
    $all_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all loans - removing the ORDER BY loan_date which was causing the error
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $all_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all properties
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $all_properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all liabilities
    $stmt = $pdo->prepare("SELECT * FROM liabilities WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $all_liabilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all transactions for balance details
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY date DESC");
    $stmt->execute([$user_id]);
    $all_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - FinTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6fc;
            margin: 0;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 0.75rem 2rem;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            width: 200px;
            height: calc(100% - 56px);
            background: white;
            border-right: 1px solid #ddd;
            padding-top: 1rem;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 999;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar li {
            padding: 10px 20px;
            cursor: pointer;
            transition: background 0.3s, padding-left 0.3s;
        }

        .sidebar li:hover {
            background: #e0e7ff;
            padding-left: 25px;
        }

        .main-content {
            padding: 2rem;
            margin-top: 56px;
            transition: margin-left 0.3s ease;
            margin-left: 0;
        }

        .main-content.shifted {
            margin-left: 200px;
        }

        .stat-card {
            border-radius: 12px;
            color: white;
            padding: 1rem;
            margin-bottom: 1rem;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .balance { background: linear-gradient(135deg, rgb(200, 175, 221), rgb(102, 121, 173)); }
        .income { background: linear-gradient(135deg, #059669, #06b6d4); }
        .expense { background: linear-gradient(135deg, rgb(110, 167, 145), rgb(79, 112, 121)); }
        .loan { background: linear-gradient(135deg, rgb(116, 131, 156), #6366f1); }
        .property { background: linear-gradient(135deg, #6b7280, #374151); }
        .liability { background: linear-gradient(135deg, rgb(98, 104, 160), rgb(180, 174, 207)); }

        table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 8px rgba(0,0,0,0.05);
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            margin-right: 1rem;
            cursor: pointer;
        }
        
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .modal-dialog {
            max-width: 800px;
        }
        
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="d-flex align-items-center">
        <button class="toggle-btn" onclick="toggleSidebar()">&#9776;</button>
        <span class="fw-bold fs-4 text-primary">FinTrack</span>
    </div>
    <div>
        <a href="dashboard.php" class="me-3 text-decoration-none">Dashboard</a>
        <a href="summary.php" class="me-3 text-decoration-none">Summary</a>
        <a href="goals.php" class="me-3 text-decoration-none">Goals</a>
        <a href="budget.php" class="me-3 text-decoration-none">Budget</a>
        <a href="logout.php" class="text-danger fw-bold text-decoration-none">Logout</a>
    </div>
</nav>

<aside id="sidebar" class="sidebar">
    <ul>
        <li onclick="location.href='add_transaction.php'"><i class="fas fa-plus"></i> Add Transaction</li>
        <li onclick="location.href='add_property.php'"><i class="fas fa-building"></i> Add Property</li>
        <li onclick="location.href='add_loan.php'"><i class="fas fa-money-check-alt"></i> Add Loan</li>
        <li onclick="location.href='add_liability.php'"><i class="fas fa-exclamation-triangle"></i> Add Liability</li>
        <li onclick="location.href='budget.php'"><i class="fas fa-chart-line"></i> Set Budget</li>
        <li onclick="location.href='add_goal.php'"><i class="fas fa-bullseye"></i> Add Goal</li>
        <li onclick="location.href='score_analysis.php'"><i class="fas fa-plus"></i> Score Analysis</li>
        <li onclick="location.href='add_payment.php'"><i class="fas fa-credit-card"></i> Add Payment</li>
    </ul>
</aside>

<div id="mainContent" class="main-content">
    <h2 class="mb-4">Welcome, <?= htmlspecialchars($user_name) ?> 👋</h2>

    <div class="row">
        <div class="col-md-4">
            <div class="stat-card balance" data-bs-toggle="modal" data-bs-target="#balanceModal">
                <strong>Total Balance</strong>
                <h4>₹<?= number_format($balance, 2) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card income" data-bs-toggle="modal" data-bs-target="#incomeModal">
                <strong>Monthly Income</strong>
                <h4>₹<?= number_format($total_income, 2) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card expense" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <strong>Monthly Expenses</strong>
                <h4>₹<?= number_format($total_expense, 2) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card loan" data-bs-toggle="modal" data-bs-target="#loanModal">
                <strong>Total Loans</strong>
                <h4>₹<?= number_format($total_loan, 2) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card property" data-bs-toggle="modal" data-bs-target="#propertyModal">
                <strong>Total Property Value</strong>
                <h4>₹<?= number_format($total_property, 2) ?></h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card liability" data-bs-toggle="modal" data-bs-target="#liabilityModal">
                <strong>Total Liabilities</strong>
                <h4>₹<?= number_format($totalLiabilities, 2) ?></h4>
            </div>
        </div>
    </div>

    <div class="mt-5">
        <h4 class="mb-3">📆 Payment Calendar</h4>
        <div id="calendar" class="bg-white p-3 rounded shadow"></div>
    </div>
    <?php if ($showDueAlert): ?>
    <div class="alert alert-danger mt-3">
        ⚠️ You have <?= count($duePayments) ?> payment(s) due or overdue!
    </div>
    <?php endif; ?>

        
    <h5 class="mt-5 mb-2"><i class="fas fa-list"></i> Recent Transactions</h5>
    <table class="table">
        <thead><tr><th>#</th><th>Amount</th><th>Category</th><th>Description</th><th>Date</th></tr></thead>
        <tbody>
            <?php foreach ($recent_transactions as $i => $txn): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td class="text-primary fw-bold">₹<?= number_format($txn['amount'], 2) ?></td>
                <td><?= htmlspecialchars($txn['category']) ?></td>
                <td><?= htmlspecialchars($txn['description']) ?></td>
                <td><?= htmlspecialchars($txn['date']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Balance Modal -->
<div class="modal fade" id="balanceModal" tabindex="-1" aria-labelledby="balanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="balanceModalLabel">Balance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_transactions as $transaction): ?>
                            <tr>
                                <td class="<?= $transaction['type'] == 'income' ? 'text-success' : 'text-danger' ?>">
                                    ₹<?= number_format($transaction['amount'], 2) ?>
                                </td>
                                <td><?= ucfirst(htmlspecialchars($transaction['type'])) ?></td>
                                <td><?= htmlspecialchars($transaction['category']) ?></td>
                                <td><?= htmlspecialchars($transaction['description']) ?></td>
                                <td><?= date('Y-m-d', strtotime($transaction['date'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="add_transaction.php" class="btn btn-primary">Add Transaction</a>
            </div>
        </div>
    </div>
</div>

<!-- Income Modal -->
<div class="modal fade" id="incomeModal" tabindex="-1" aria-labelledby="incomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="incomeModalLabel">Income Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_income as $income): ?>
                            <tr>
                                <td class="text-success">₹<?= number_format($income['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($income['category']) ?></td>
                                <td><?= htmlspecialchars($income['description']) ?></td>
                                <td><?= date('Y-m-d', strtotime($income['date'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="add_transaction.php" class="btn btn-primary">Add Income</a>
            </div>
        </div>
    </div>
</div>

<!-- Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expenseModalLabel">Expense Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_expenses as $expense): ?>
                            <tr>
                                <td class="text-danger">₹<?= number_format($expense['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($expense['category']) ?></td>
                                <td><?= htmlspecialchars($expense['description']) ?></td>
                                <td><?= date('Y-m-d', strtotime($expense['date'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="add_transaction.php" class="btn btn-primary">Add Expense</a>
            </div>
        </div>
    </div>
</div>

<!-- Loans Modal -->
<div class="modal fade" id="loanModal" tabindex="-1" aria-labelledby="loanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loanModalLabel">Loan Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Loan Amount</th>
                                <th>Type</th>
                                <th>Interest Rate</th>
                                <th>Term (Months)</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_loans as $loan): ?>
                            <tr>
                                <td>₹<?= number_format($loan['loan_amount'], 2) ?></td>
                                <td><?= htmlspecialchars($loan['loan_type'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($loan['interest_rate'] ?? 'N/A') ?>%</td>
                                <td><?= htmlspecialchars($loan['term_months'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($loan['description'] ?? 'N/A') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="add_loan.php" class="btn btn-primary">Add Loan</a>
            </div>
        </div>
    </div>
</div>

<!-- Property Modal -->
<div class="modal fade" id="propertyModal" tabindex="-1" aria-labelledby="propertyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="propertyModalLabel">Property Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Property Value</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_properties as $property): ?>
                            <tr>
                                <td>₹<?= number_format($property['property_value'], 2) ?></td>
                                <td><?= htmlspecialchars($property['property_type'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($property['location'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($property['description'] ?? 'N/A') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="add_property.php" class="btn btn-primary">Add Property</a>
            </div>
        </div>
    </div>
</div>

<!-- Liability Modal -->
<div class="modal fade" id="liabilityModal" tabindex="-1" aria-labelledby="liabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="liabilityModalLabel">Liability Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Liability Amount</th>
                                <th>Type</th>
                                <th>Due Date</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_liabilities as $liability): ?>
                            <tr>
                                <td class="text-danger">₹<?= number_format($liability['liability_amount'], 2) ?></td>
                                <td><?= htmlspecialchars($liability['liability_type'] ?? 'N/A') ?></td>
                                <td><?= isset($liability['due_date']) ? date('Y-m-d', strtotime($liability['due_date'])) : 'N/A' ?></td>
                                <td><?= htmlspecialchars($liability['description'] ?? 'N/A') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="add_liability.php" class="btn btn-primary">Add Liability</a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
    document.getElementById("mainContent").classList.toggle("shifted");
}

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 500,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        events: <?= json_encode($calendar_events) ?>
    });
    calendar.render();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>