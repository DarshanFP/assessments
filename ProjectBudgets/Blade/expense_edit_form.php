<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';
// require_once '../../includes/role_based_sidebar.php';

// Ensure the user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit();
}

// Get expense ID from URL
$expenseId = isset($_GET['expense_id']) ? intval($_GET['expense_id']) : 0;

if ($expenseId <= 0) {
    $_SESSION['error'] = "Invalid expense ID.";
    header("Location: all_transactions.php");
    exit();
}

// Fetch the logged-in user's details
$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];

try {
    // Fetch expense details with project information
    $expenseStmt = $pdo->prepare("
        SELECT ee.*, p.project_name, p.project_id, o.organization_name
        FROM ExpenseEntries ee
        LEFT JOIN Projects p ON ee.project_id = p.project_id
        LEFT JOIN Organizations o ON p.organization_id = o.organization_id
        WHERE ee.expense_id = :expense_id AND ee.is_active = 1
    ");
    $expenseStmt->execute([':expense_id' => $expenseId]);
    $expense = $expenseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$expense) {
        $_SESSION['error'] = "Expense not found or has been deactivated.";
        header("Location: all_transactions.php");
        exit();
    }

    // Check permissions - only project in-charge or councillor can edit
    $canEditDirectly = false;
    $canRequestEdit = false;
    
    if ($currentUserRole === 'Councillor') {
        $canEditDirectly = true;
    } else {
        // Check if user is assigned to this project
        $assignmentStmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM ProjectAssignments 
            WHERE project_id = :project_id 
            AND project_incharge_id = :user_id 
            AND is_active = 1
        ");
        $assignmentStmt->execute([':project_id' => $expense['project_id'], ':user_id' => $currentUserId]);
        $isAssigned = $assignmentStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($isAssigned) {
            $canRequestEdit = true;
        } else {
            $_SESSION['error'] = "You don't have permission to edit this expense.";
            header("Location: all_transactions.php");
            exit();
        }
    }

    // Fetch expense types for dropdown
    $typesStmt = $pdo->prepare("SELECT * FROM ExpenseIncomeTypes WHERE category = 'Expense' ORDER BY type_name");
    $typesStmt->execute();
    $expenseTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching expense data: " . $e->getMessage();
    header("Location: all_transactions.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Expense - Assessment System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
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
                
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Edit Expense</h1>
                    <p class="text-gray-600">Project: <?php echo htmlspecialchars($expense['project_name']); ?> - Organization: <?php echo htmlspecialchars($expense['organization_name']); ?></p>
                    <?php if ($canRequestEdit && !$canEditDirectly): ?>
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mt-4">
                            <strong>Note:</strong> As a Project In-Charge, your changes will require approval from a Councillor before being applied.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Display Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <form action="../Controller/<?php echo $canEditDirectly ? 'expense_edit_process.php' : 'expense_edit_request_process.php'; ?>" method="POST">
                    <input type="hidden" name="expense_id" value="<?php echo $expenseId; ?>">
                    <input type="hidden" name="project_id" value="<?php echo $expense['project_id']; ?>">
                    
                    <!-- Expense Details Section -->
                    <div class="form-section">
                        <h2 class="text-xl font-semibold mb-4">Expense Details</h2>

                        <div class="form-group">
                            <label for="particular">Particular:</label>
                            <input type="text" id="particular" name="particular" value="<?php echo htmlspecialchars($expense['particular']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="amount_expensed">Amount Expensed (Rs):</label>
                            <input type="number" id="amount_expensed" name="amount_expensed" step="0.01" value="<?php echo $expense['amount_expensed']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="expensed_at">Date of Expense:</label>
                            <input type="date" id="expensed_at" name="expensed_at" value="<?php echo date('Y-m-d', strtotime($expense['expensed_at'])); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="remarks">Remarks:</label>
                            <textarea id="remarks" name="remarks" placeholder="Enter any additional remarks about this expense"><?php echo htmlspecialchars($expense['remarks'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Submit Button Section -->
                    <div class="form-section">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                            <?php echo $canEditDirectly ? 'Update Expense' : 'Submit Edit Request for Approval'; ?>
                        </button>
                    </div>
                </form>

                <!-- Action Buttons -->
                <div class="form-section">
                    <div class="flex gap-4">
                        <a href="all_transactions.php?project_id=<?php echo $expense['project_id']; ?>" class="btn btn-secondary">
                            Back to Transactions
                        </a>
                        <?php if ($canEditDirectly): ?>
                            <button onclick="confirmDeactivate(<?php echo $expenseId; ?>, '<?php echo htmlspecialchars($expense['particular']); ?>')" class="btn btn-danger">
                                Deactivate Expense
                            </button>
                        <?php else: ?>
                            <button onclick="confirmRequestDeactivate(<?php echo $expenseId; ?>, '<?php echo htmlspecialchars($expense['particular']); ?>')" class="btn btn-danger">
                                Request Deactivation
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>

    <!-- JavaScript -->
    <script>
        function confirmDeactivate(expenseId, particular) {
            if (confirm('Are you sure you want to deactivate the expense "' + particular + '"?\n\nThis action will hide the expense from the system but preserve all data for future reference.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../Controller/expense_deactivate_process.php';
                
                const expenseIdInput = document.createElement('input');
                expenseIdInput.type = 'hidden';
                expenseIdInput.name = 'expense_id';
                expenseIdInput.value = expenseId;
                
                form.appendChild(expenseIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function confirmRequestDeactivate(expenseId, particular) {
            if (confirm('Are you sure you want to request deactivation of the expense "' + particular + '"?\n\nThis request will require approval from a Councillor.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../Controller/expense_edit_request_process.php';
                
                const expenseIdInput = document.createElement('input');
                expenseIdInput.type = 'hidden';
                expenseIdInput.name = 'expense_id';
                expenseIdInput.value = expenseId;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'deactivate';
                
                form.appendChild(expenseIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
