<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Fetch project ID from the URL
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$userId = $_SESSION['user_id'];

// Validate project ID
if ($projectId <= 0) {
    $_SESSION['error'] = "Invalid project ID. Please select a valid project.";
    header("Location: my_projects.php");
    exit();
}

try {
    // Fetch project details
    $projectStmt = $pdo->prepare("SELECT project_name, total_budget FROM Projects WHERE project_id = :project_id");
    $projectStmt->execute([':project_id' => $projectId]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);

    // Check if project exists
    if (!$project) {
        $_SESSION['error'] = "Project not found. Please select a valid project.";
        header("Location: my_projects.php");
        exit();
    }

    // Fetch budget entries
    $budgetStmt = $pdo->prepare("
        SELECT be.entry_id, be.particular, be.rate_quantity, be.rate_multiplier, be.rate_duration, be.amount_this_phase,
               IFNULL(SUM(ee.amount_expensed), 0) AS total_expensed
        FROM BudgetEntries be
        LEFT JOIN ExpenseEntries ee ON be.entry_id = ee.entry_id
        WHERE be.project_id = :project_id
        GROUP BY be.entry_id
    ");
    $budgetStmt->execute([':project_id' => $projectId]);
    $budgetEntries = $budgetStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: my_projects.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Transactions</title>
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
        
        .enhanced-table input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
            background-color: #ffffff;
            color: #2d3748;
            transition: border-color 0.2s ease;
        }
        
        .enhanced-table input:focus {
            outline: none;
            border-color: #4a6fd1;
            box-shadow: 0 0 0 3px rgba(74, 111, 209, 0.1);
        }
        
        .enhanced-table input[readonly] {
            background-color: #f9fafb;
            color: #6b7280;
            cursor: not-allowed;
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
        
        .btn-primary {
            background: #4a6fd1;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: #3b5998;
            transform: translateY(-1px);
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
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
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
                
    <div class="flex">
        <!-- Main Content -->
        <div class="flex-1 p-6" style="margin-top: 80px;">
            
            <!-- Enhanced Page Header -->
            <div class="page-header">
                <h1>Transactions for <?php echo htmlspecialchars($project['project_name'] ?? 'Unknown Project'); ?></h1>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($budgetEntries); ?></div>
                    <div class="stat-label">Total Budget Entries</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format($project['total_budget'] ?? 0, 2); ?></div>
                    <div class="stat-label">Project Budget</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format(array_sum(array_column($budgetEntries, 'total_expensed')), 2); ?></div>
                    <div class="stat-label">Total Expensed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format(($project['total_budget'] ?? 0) - array_sum(array_column($budgetEntries, 'total_expensed')), 2); ?></div>
                    <div class="stat-label">Available Funds</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="button-group">
                <a href="my_projects.php" class="btn-secondary">Back to My Projects</a>
                <a href="all_transactions.php?project_id=<?php echo $projectId; ?>" class="btn-primary">View All Transactions</a>
            </div>

            <form id="expenseForm">
                <!-- Expense Date Selection -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="flex items-center gap-4">
                        <label for="globalExpenseDate" class="text-lg font-semibold text-gray-700">Expense Date:</label>
                        <input type="date" id="globalExpenseDate" name="global_expense_date" value="<?php echo date('Y-m-d'); ?>" required class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <span class="text-sm text-gray-500">(This date will apply to all expense entries)</span>
                    </div>
                </div>
                <!-- Enhanced Budget Entries Table -->
                <div class="enhanced-table">
                    <table id="expensesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Particular</th>
                                <th>Enter Expense (₹)</th>
                                <th>Amount Allocated (₹)</th>
                                <th>Total Expensed (₹)</th>
                                <th>Available Funds (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($budgetEntries)): ?>
                                <?php
                                $totalAllocated = 0;
                                $totalExpensed = 0;
                                $totalAvailable = 0;
                                $serialNumber = 1;
                                foreach ($budgetEntries as $entry):
                                    $allocatedAmount = $entry['amount_this_phase'] ?? 0;
                                    $totalExpensedAmount = $entry['total_expensed'] ?? 0;
                                    $availableFunds = $allocatedAmount - $totalExpensedAmount;

                                    $totalAllocated += $allocatedAmount;
                                    $totalExpensed += $totalExpensedAmount;
                                    $totalAvailable += $availableFunds;
                                ?>
                                    <tr>
                                        <td><?php echo $serialNumber++; ?></td>
                                        <td>
                                            <input type="hidden" name="entries[<?php echo $entry['entry_id']; ?>][entry_id]" value="<?php echo $entry['entry_id']; ?>">
                                            <input type="text" name="entries[<?php echo $entry['entry_id']; ?>][particular]" value="<?php echo htmlspecialchars($entry['particular'] ?? ''); ?>" readonly>
                                        </td>
                                        <td>
                                            <input type="number" name="entries[<?php echo $entry['entry_id']; ?>][amount]" class="enter-expense" step="0.01" placeholder="Enter Expense" oninput="updateTotalExpense()">
                                        </td>
                                        <td class="budget-cell"><?php echo number_format($allocatedAmount, 2); ?></td>
                                        <td class="expense-cell"><?php echo number_format($totalExpensedAmount, 2); ?></td>
                                        <td class="available-cell"><?php echo number_format($availableFunds, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8">
                                        <div class="text-gray-500 text-lg mb-2">No budget entries found for this project.</div>
                                        <div class="text-gray-400 text-sm">Budget entries need to be created before adding expenses.</div>
                                    </div>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="font-bold">
                                <td class="text-right" colspan="2">Total:</td>
                                <td id="totalEnterExpense">0.00</td>
                                <td class="budget-cell"><?php echo number_format($totalAllocated ?? 0, 2); ?></td>
                                <td class="expense-cell"><?php echo number_format($totalExpensed ?? 0, 2); ?></td>
                                <td class="available-cell"><?php echo number_format($totalAvailable ?? 0, 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Button Section -->
                <div class="button-group">
                    <button type="button" id="addNewRowButton" class="btn-success">Add New Row</button>
                    <button type="submit" class="btn-primary">Save Expenses</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Update Total Enter Expense
        function updateTotalExpense() {
            const expenseInputs = document.querySelectorAll('.enter-expense');
            let totalExpense = 0;

            expenseInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                totalExpense += value;
            });

            document.getElementById('totalEnterExpense').textContent = totalExpense.toFixed(2);
        }

        // Add New Row Functionality
        document.getElementById('addNewRowButton').addEventListener('click', () => {
            const tableBody = document.querySelector('#expensesTable tbody');
            const rowCount = tableBody.rows.length;
            const newRow = tableBody.insertRow();

            newRow.innerHTML = `
                <td>${rowCount + 1}</td>
                <td>
                    <input type="hidden" name="entries[new_${rowCount}][entry_id]" value="new">
                    <input type="text" name="entries[new_${rowCount}][particular]" placeholder="Enter New Particular">
                </td>
                <td>
                    <input type="number" name="entries[new_${rowCount}][amount]" class="enter-expense" step="0.01" placeholder="Enter Expense" oninput="updateTotalExpense()">
                </td>
                <td class="budget-cell">0.00</td>
                <td class="expense-cell">0.00</td>
                <td class="available-cell">0.00</td>
            `;
        });

        // Submit Form Functionality
        document.getElementById('expenseForm').addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(document.getElementById('expenseForm'));
            const projectId = <?php echo intval($projectId); ?>;
            formData.append('project_id', projectId);

            try {
                const response = await fetch("../Controller/transactions_process.php", {
                    method: "POST",
                    body: formData
                });

                const textResponse = await response.text();
                console.log(textResponse);

                const result = JSON.parse(textResponse);
                if (result.success) {
                    alert("Expenses saved successfully!");
                    location.reload();
                } else {
                    alert("Error: " + result.message);
                }
            } catch (error) {
                console.error("Failed to parse JSON:", error);
                alert("An error occurred while processing the request. Check the console for details.");
            }
        });
    </script>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div></body>
</html>
