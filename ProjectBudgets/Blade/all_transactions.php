<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';

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
               be.entry_id
        FROM ExpenseEntries ee
        LEFT JOIN ssmntUsers su ON ee.created_by = su.id
        LEFT JOIN BudgetEntries be ON ee.entry_id = be.entry_id
        WHERE ee.project_id = :project_id
        ORDER BY ee.expensed_at ASC
    ");
    $expenseStmt->execute([':project_id' => $projectId]);
    $expenseEntries = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total expense
    $totalExpense = array_sum(array_column($expenseEntries, 'amount_expensed'));

    // Group transactions by month and calculate monthly totals per particular
    $monthlyTransactions = [];
    $monthlyTotals = [];
    $cumulativeExpense = 0; // Track cumulative expense chronologically
    $monthlyParticularTotals = []; // Track monthly totals per particular
    
    // First, sort all entries by date (oldest first) for proper cumulative calculation
    usort($expenseEntries, function($a, $b) {
        return strtotime($a['expensed_at']) - strtotime($b['expensed_at']);
    });
    
    foreach ($expenseEntries as $entry) {
        $monthYear = date('F Y', strtotime($entry['expensed_at']));
        $monthKey = date('Y-m', strtotime($entry['expensed_at']));
        $entryId = $entry['entry_id'];
        
        if (!isset($monthlyTransactions[$monthKey])) {
            $monthlyTransactions[$monthKey] = [
                'month_name' => $monthYear,
                'transactions' => [],
                'cumulative_expense' => 0,
                'cumulative_available' => 0
            ];
            $monthlyTotals[$monthKey] = 0;
            $monthlyParticularTotals[$monthKey] = [];
        }
        
        // Initialize monthly totals for this particular if not exists
        if (!isset($monthlyParticularTotals[$monthKey][$entryId])) {
            $monthlyParticularTotals[$monthKey][$entryId] = 0;
        }
        
        // Update monthly total for this particular
        $monthlyParticularTotals[$monthKey][$entryId] += $entry['amount_expensed'];
        
        // Update cumulative expense chronologically
        $cumulativeExpense += $entry['amount_expensed'];
        
        // Calculate available funds for this particular (allocated - monthly total spent for this particular)
        $amountAllocated = $entry['amount_allocated'] ?? 0;
        $monthlyExpensedForParticular = $monthlyParticularTotals[$monthKey][$entryId];
        $availableFundsForParticular = $amountAllocated - $monthlyExpensedForParticular;
        
        // Add the entry to the month's transactions with its totals
        $entry['monthly_expensed_for_particular'] = $monthlyExpensedForParticular; // Monthly total expensed for this particular
        $entry['cumulative_expense'] = $cumulativeExpense; // Cumulative project total
        $entry['available_funds_for_particular'] = $availableFundsForParticular; // Available funds for this particular
        $monthlyTransactions[$monthKey]['transactions'][] = $entry;
        $monthlyTotals[$monthKey] += $entry['amount_expensed'];
        
        // Update the cumulative totals for this month (will be the final cumulative for this month)
        $monthlyTransactions[$monthKey]['cumulative_expense'] = $cumulativeExpense;
        $monthlyTransactions[$monthKey]['cumulative_available'] = $project['total_budget'] - $cumulativeExpense;
    }

    // Sort by month (oldest first) for display
    ksort($monthlyTransactions);
    ksort($monthlyTotals);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Transactions for Project : <?php echo htmlspecialchars($project['project_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
    <style>
        .enhanced-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin: 20px 0;
        }
        
        .enhanced-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .enhanced-table thead {
            background: #4a6fd1;
            color: white;
        }
        
        .enhanced-table th {
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        
        .enhanced-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .enhanced-table tbody tr:hover {
            background-color: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .enhanced-table td {
            padding: 16px 12px;
            border: none;
            font-size: 14px;
            color: #374151;
        }
        
        .enhanced-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .budget-cell {
            font-weight: 600;
            color: #059669;
        }
        
        .expense-cell {
            font-weight: 600;
            color: #dc2626;
        }
        
        .available-cell {
            font-weight: 600;
            color: #2563eb;
        }
        
        .date-cell {
            color: #6b7280;
            font-size: 13px;
        }
        
        .month-header {
            background: #4a6fd1;
            color: white;
            padding: 16px 20px;
            border-radius: 8px 8px 0 0;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .month-total {
            background: #f8fafc;
            padding: 12px 20px;
            border-top: 2px solid #e2e8f0;
            font-weight: 600;
            color: #374151;
            display: grid;
            grid-template-columns: 50px 1fr 1fr 1fr 1fr 1fr 1fr 1fr;
            align-items: center;
            gap: 12px;
        }
        
        .action-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #4a6fd1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .action-btn:hover {
            background: #3b5998;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(74, 111, 209, 0.3);
        }
        
        .page-header {
            background: #4a6fd1;
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #4a6fd1;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .final-total {
            background: #4a6fd1;
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .final-total h2 {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .final-total .amount {
            font-size: 32px;
            font-weight: 800;
        }
    </style>
</head>
<body>
    <!-- Layout Container -->
    <div class="layout-container">
        
        <!-- Topbar -->
        <?php include '../../topbar.php'; ?>
        
        <!-- Sidebar -->
        <?php include '../../includes/role_based_sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            
            <!-- Content Container -->
            <div class="content-container">
                
                <!-- Page Content Goes Here -->
                <div class="flex-1 p-6" style="margin-top: 80px;">
            
            <!-- Enhanced Page Header -->
            <div class="page-header">
                <h1>All Transactions for <?php echo htmlspecialchars($project['project_name']); ?></h1>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($expenseEntries); ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($monthlyTransactions); ?></div>
                    <div class="stat-label">Months with Transactions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format($totalExpense, 2); ?></div>
                    <div class="stat-label">Total Expensed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format($project['total_budget'] - $totalExpense, 2); ?></div>
                    <div class="stat-label">Available Funds</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="button-group">
                <a href="transactions.php?project_id=<?php echo $projectId; ?>" class="btn-secondary">Back to Transactions</a>
                <a href="download_transactions_pdf_tcpdf.php?project_id=<?php echo $projectId; ?>" class="btn-success">Download PDF Report</a>
            </div>

            <!-- Month-wise Transactions -->
            <?php if (!empty($monthlyTransactions)): ?>
                <?php foreach ($monthlyTransactions as $monthKey => $monthData): ?>
                    <div class="enhanced-table">
                        <div class="month-header">
                            <span><?php echo $monthData['month_name']; ?></span>
                            <span>Total: ₹<?php echo number_format($monthlyTotals[$monthKey], 2); ?></span>
                        </div>
                        <table>
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
                                // Sort transactions within this month by date (oldest first)
                                usort($monthData['transactions'], function($a, $b) {
                                    return strtotime($a['expensed_at']) - strtotime($b['expensed_at']);
                                });
                                
                                $serialNumber = 1;
                                foreach ($monthData['transactions'] as $entry):
                                ?>
                                    <tr>
                                        <td><?php echo $serialNumber++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($entry['particular']); ?></strong></td>
                                        <td class="expense-cell">₹<?php echo number_format($entry['amount_expensed'], 2); ?></td>
                                        <td class="budget-cell">₹<?php echo number_format($entry['amount_allocated'] ?? 0, 2); ?></td>
                                        <td class="expense-cell">₹<?php echo number_format($entry['monthly_expensed_for_particular'], 2); ?></td>
                                        <td class="available-cell">₹<?php echo number_format($entry['available_funds_for_particular'], 2); ?></td>
                                        <td class="date-cell"><?php echo date('d M Y, h:i A', strtotime($entry['expensed_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($entry['created_by']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="month-total">
                            <span></span>
                            <span>Monthly Total:</span>
                            <span class="expense-cell">₹<?php echo number_format($monthlyTotals[$monthKey], 2); ?></span>
                            <span class="budget-cell">₹<?php echo number_format(array_sum(array_column($monthData['transactions'], 'amount_allocated')), 2); ?></span>
                            <span class="expense-cell">₹<?php echo number_format(array_sum(array_column($monthData['transactions'], 'monthly_expensed_for_particular')), 2); ?></span>
                            <span class="available-cell">₹<?php echo number_format(array_sum(array_column($monthData['transactions'], 'available_funds_for_particular')), 2); ?></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Final Total -->
                <div class="final-total">
                    <h2>Grand Total</h2>
                    <div class="amount">₹<?php echo number_format($totalExpense, 2); ?></div>
                </div>
                
            <?php else: ?>
                <div class="enhanced-table">
                    <div class="text-center py-8">
                        <div class="text-gray-500 text-lg mb-2">No transactions found.</div>
                        <div class="text-gray-400 text-sm">Transactions will appear here once expenses are recorded.</div>
                    </div>
                </div>
            <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>
</body>
</html>
