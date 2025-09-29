<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';

// Ensure the user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit();
}

// Fetch project ID from the URL
$projectId = intval($_GET['project_id']);
$userId = $_SESSION['user_id'];

try {
    // Fetch project details
    $projectStmt = $pdo->prepare("SELECT project_name, total_budget FROM Projects WHERE project_id = :project_id");
    $projectStmt->execute([':project_id' => $projectId]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch all expense entries for the project with budget information
    $expenseStmt = $pdo->prepare("
        SELECT ee.expense_id, ee.particular, ee.amount_expensed, ee.expensed_at, su.full_name AS created_by,
               be.amount_this_phase AS amount_allocated,
               (SELECT SUM(ee2.amount_expensed) FROM ExpenseEntries ee2 WHERE ee2.entry_id = ee.entry_id) AS total_expensed
        FROM ExpenseEntries ee
        LEFT JOIN ssmntUsers su ON ee.created_by = su.id
        LEFT JOIN BudgetEntries be ON ee.entry_id = be.entry_id
        WHERE ee.project_id = :project_id
        ORDER BY ee.expensed_at DESC
    ");
    $expenseStmt->execute([':project_id' => $projectId]);
    $expenseEntries = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total expense
    $totalExpense = array_sum(array_column($expenseEntries, 'amount_expensed'));

    // Group transactions by month
    $monthlyTransactions = [];
    $monthlyTotals = [];
    
    foreach ($expenseEntries as $entry) {
        $monthYear = date('F Y', strtotime($entry['expensed_at']));
        $monthKey = date('Y-m', strtotime($entry['expensed_at']));
        
        if (!isset($monthlyTransactions[$monthKey])) {
            $monthlyTransactions[$monthKey] = [
                'month_name' => $monthYear,
                'transactions' => []
            ];
            $monthlyTotals[$monthKey] = 0;
        }
        
        $monthlyTransactions[$monthKey]['transactions'][] = $entry;
        $monthlyTotals[$monthKey] += $entry['amount_expensed'];
    }

    // Sort by month (oldest first)
    ksort($monthlyTransactions);
    ksort($monthlyTotals);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Set headers for HTML download (which can be converted to PDF by browser)
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $projectId . '.html"');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions Report - <?php echo htmlspecialchars($project['project_name']); ?></title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: white;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .report-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            padding: 0;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4a6fd1;
        }
        
        .report-title {
            font-size: 24px;
            font-weight: bold;
            color: #4a6fd1;
            margin: 0 0 8px 0;
        }
        
        .project-info {
            font-size: 16px;
            color: #666;
            margin: 0 0 15px 0;
        }
        
        .report-date {
            font-size: 12px;
            color: #888;
        }
        
        .summary-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        
        .summary-row {
            display: table-row;
        }
        
        .summary-card {
            display: table-cell;
            background: #f8fafc;
            padding: 15px;
            text-align: center;
            border: 1px solid #e2e8f0;
            width: 25%;
        }
        
        .summary-number {
            font-size: 20px;
            font-weight: bold;
            color: #4a6fd1;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
        }
        
        .month-section {
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        
        .month-header {
            background: #4a6fd1;
            color: white;
            padding: 12px 15px;
            font-size: 16px;
            font-weight: bold;
            display: table;
            width: 100%;
        }
        
        .month-header span:first-child {
            display: table-cell;
            width: 70%;
        }
        
        .month-header span:last-child {
            display: table-cell;
            width: 30%;
            text-align: right;
        }
        
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        
        .transactions-table th {
            background: #f8fafc;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            color: #374151;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .transactions-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            color: #374151;
        }
        
        .transactions-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .budget-cell {
            font-weight: bold;
            color: #059669;
        }
        
        .expense-cell {
            font-weight: bold;
            color: #dc2626;
        }
        
        .available-cell {
            font-weight: bold;
            color: #2563eb;
        }
        
        .date-cell {
            color: #6b7280;
            font-size: 10px;
        }
        
        .month-total {
            background: #f8fafc;
            padding: 10px 15px;
            font-weight: bold;
            color: #374151;
            display: table;
            width: 100%;
            border-top: 2px solid #e2e8f0;
        }
        
        .month-total span:first-child {
            display: table-cell;
            width: 70%;
        }
        
        .month-total .expense-cell {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-weight: bold;
            color: #dc2626;
        }
        
        .grand-total {
            background: #4a6fd1;
            color: white;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
            margin-top: 25px;
        }
        
        .grand-total h2 {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 8px 0;
        }
        
        .grand-total .amount {
            font-size: 28px;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        
        @media print {
            body {
                background-color: white;
            }
            
            .report-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- Report Header -->
        <div class="report-header">
            <h1 class="report-title">Project Transactions Report</h1>
            <p class="project-info"><?php echo htmlspecialchars($project['project_name']); ?></p>
            <p class="report-date">Generated on: <?php echo date('d M Y, h:i A'); ?></p>
        </div>

        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-row">
                <div class="summary-card">
                    <div class="summary-number"><?php echo count($expenseEntries); ?></div>
                    <div class="summary-label">Total Transactions</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number"><?php echo count($monthlyTransactions); ?></div>
                    <div class="summary-label">Months with Transactions</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number">₹<?php echo number_format($totalExpense, 2); ?></div>
                    <div class="summary-label">Total Expensed</div>
                </div>
                <div class="summary-card">
                    <div class="summary-number">₹<?php echo number_format($project['total_budget'] - $totalExpense, 2); ?></div>
                    <div class="summary-label">Available Funds</div>
                </div>
            </div>
        </div>

        <!-- Month-wise Transactions -->
        <?php if (!empty($monthlyTransactions)): ?>
            <?php foreach ($monthlyTransactions as $monthKey => $monthData): ?>
                <div class="month-section">
                    <div class="month-header">
                        <span><?php echo $monthData['month_name']; ?></span>
                        <span>Total: ₹<?php echo number_format($monthlyTotals[$monthKey], 2); ?></span>
                    </div>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Particular</th>
                                <th>Current Expenses (₹)</th>
                                <th>Amount Allocated (₹)</th>
                                <th>Total Expensed (₹)</th>
                                <th>Available Funds (₹)</th>
                                <th>Expense Date</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $serialNumber = 1;
                            foreach ($monthData['transactions'] as $entry):
                            ?>
                                <tr>
                                    <td><?php echo $serialNumber++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($entry['particular']); ?></strong></td>
                                    <td class="expense-cell">₹<?php echo number_format($entry['amount_expensed'], 2); ?></td>
                                    <td class="budget-cell">₹<?php echo number_format($entry['amount_allocated'] ?? 0, 2); ?></td>
                                    <td class="expense-cell">₹<?php echo number_format($entry['total_expensed'] ?? 0, 2); ?></td>
                                    <td class="available-cell">₹<?php echo number_format(($entry['amount_allocated'] ?? 0) - ($entry['total_expensed'] ?? 0), 2); ?></td>
                                    <td class="date-cell"><?php echo date('d M Y, h:i A', strtotime($entry['expensed_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($entry['created_by']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="month-total">
                        <span>Monthly Total:</span>
                        <span class="expense-cell">₹<?php echo number_format($monthlyTotals[$monthKey], 2); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Grand Total -->
            <div class="grand-total">
                <h2>Grand Total</h2>
                <div class="amount">₹<?php echo number_format($totalExpense, 2); ?></div>
            </div>
            
        <?php else: ?>
            <div class="month-section">
                <div class="month-header">
                    <span>No Transactions</span>
                </div>
                <div style="padding: 30px; text-align: center; color: #666;">
                    <p>No transactions found for this project.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>This report was generated automatically by the Assessment System</p>
            <p>Report ID: <?php echo $projectId; ?>_<?php echo date('YmdHis'); ?></p>
        </div>
    </div>
</body>
</html>
