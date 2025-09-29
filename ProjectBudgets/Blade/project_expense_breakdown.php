<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';
require_once '../../includes/role_based_sidebar.php';

// Ensure the user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit();
}

// Get project ID from URL
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($projectId <= 0) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: all_projects.php");
    exit();
}

// Fetch the logged-in user's details
$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];

try {
    // Fetch project details
    $projectStmt = $pdo->prepare("
        SELECT p.*, o.organization_name, o.organization_code, o.color_theme
        FROM Projects p
        LEFT JOIN Organizations o ON p.organization_id = o.organization_id
        WHERE p.project_id = :project_id AND p.is_active = 1
    ");
    $projectStmt->execute([':project_id' => $projectId]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        $_SESSION['error'] = "Project not found or has been deactivated.";
        header("Location: all_projects.php");
        exit();
    }

    // Check if user has access to this project
    $accessStmt = $pdo->prepare("
        SELECT COUNT(*) as has_access
        FROM ProjectAssignments pa
        WHERE pa.project_id = :project_id 
        AND pa.project_incharge_id = :user_id 
        AND pa.is_active = 1
    ");
    $accessStmt->execute([':project_id' => $projectId, ':user_id' => $currentUserId]);
    $hasAccess = $accessStmt->fetch(PDO::FETCH_ASSOC)['has_access'] > 0;

    if (!$hasAccess && $currentUserRole !== 'Councillor') {
        $_SESSION['error'] = "You don't have access to this project.";
        header("Location: my_projects.php");
        exit();
    }

    // Fetch all Project In-Charges for this project
    $inchargesStmt = $pdo->prepare("
        SELECT pa.*, u.full_name, u.community
        FROM ProjectAssignments pa
        LEFT JOIN ssmntUsers u ON pa.project_incharge_id = u.id
        WHERE pa.project_id = :project_id AND pa.is_active = 1
        ORDER BY pa.is_primary DESC, u.full_name
    ");
    $inchargesStmt->execute([':project_id' => $projectId]);
    $projectIncharges = $inchargesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch expense breakdown by Project In-Charge
    $expenseStmt = $pdo->prepare("
        SELECT 
            ee.created_by,
            u.full_name as incharge_name,
            pa.is_primary,
            COUNT(*) as expense_count,
            SUM(ee.amount_expensed) as total_expensed
        FROM ExpenseEntries ee
        LEFT JOIN ssmntUsers u ON ee.created_by = u.id
        LEFT JOIN ProjectAssignments pa ON ee.project_id = pa.project_id AND ee.created_by = pa.project_incharge_id
        WHERE ee.project_id = :project_id
        GROUP BY ee.created_by, u.full_name, pa.is_primary
        ORDER BY pa.is_primary DESC, u.full_name
    ");
    $expenseStmt->execute([':project_id' => $projectId]);
    $expenseBreakdown = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total project expenses
    $totalExpenses = array_sum(array_column($expenseBreakdown, 'total_expensed'));

} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching project data: " . $e->getMessage();
    header("Location: all_projects.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Expense Breakdown - Assessment System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
    <style>
        .expense-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .expense-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .incharge-card {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
        }
        
        .incharge-card:hover {
            border-color: #4a6fd1;
            box-shadow: 0 4px 8px rgba(74, 111, 209, 0.1);
        }
        
        .primary-badge {
            background: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .expense-amount {
            font-size: 24px;
            font-weight: 700;
            color: #059669;
        }
        
        .expense-count {
            color: #6b7280;
            font-size: 14px;
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
                
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Project Expense Breakdown</h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($project['project_name']); ?> - Individual and Total Expenses</p>
                </div>

                <!-- Display Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Project Summary -->
                <div class="expense-summary">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($project['project_name']); ?></h2>
                            <p class="text-sm opacity-90">Organization: <?php echo htmlspecialchars($project['organization_name']); ?></p>
                            <p class="text-sm opacity-90">Total Budget: ₹<?php echo number_format($project['total_budget'], 2); ?></p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-bold">₹<?php echo number_format($totalExpenses, 2); ?></div>
                            <div class="text-sm opacity-90">Total Expenses</div>
                            <div class="text-sm opacity-90">Available: ₹<?php echo number_format($project['total_budget'] - $totalExpenses, 2); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Project In-Charges and Their Expenses -->
                <div class="expense-card">
                    <h3 class="text-lg font-semibold mb-4">Project In-Charges & Individual Expenses</h3>
                    
                    <?php if (!empty($projectIncharges)): ?>
                        <div class="grid gap-4">
                            <?php foreach ($projectIncharges as $incharge): ?>
                                <?php
                                // Find expenses for this incharge
                                $inchargeExpenses = array_filter($expenseBreakdown, function($expense) use ($incharge) {
                                    return $expense['created_by'] == $incharge['project_incharge_id'];
                                });
                                $inchargeExpenses = !empty($inchargeExpenses) ? array_values($inchargeExpenses)[0] : null;
                                $inchargeTotal = $inchargeExpenses ? $inchargeExpenses['total_expensed'] : 0;
                                $inchargeCount = $inchargeExpenses ? $inchargeExpenses['expense_count'] : 0;
                                ?>
                                
                                <div class="incharge-card">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <h4 class="font-semibold text-gray-800">
                                                    <?php echo htmlspecialchars($incharge['full_name']); ?>
                                                    <?php if ($incharge['is_primary']): ?>
                                                        <span class="primary-badge">Primary</span>
                                                    <?php endif; ?>
                                                </h4>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($incharge['community']); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="expense-amount">₹<?php echo number_format($inchargeTotal, 2); ?></div>
                                            <div class="expense-count"><?php echo $inchargeCount; ?> expense<?php echo $inchargeCount != 1 ? 's' : ''; ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>No Project In-Charges assigned to this project.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4 mt-6">
                    <a href="transactions.php?project_id=<?php echo $projectId; ?>" class="btn btn-primary">
                        Add New Expense
                    </a>
                    <a href="all_transactions.php?project_id=<?php echo $projectId; ?>" class="btn btn-secondary">
                        View All Transactions
                    </a>
                    <?php if ($currentUserRole === 'Councillor'): ?>
                        <a href="all_projects.php" class="btn btn-outline">Back to All Projects</a>
                    <?php else: ?>
                        <a href="my_projects.php" class="btn btn-outline">Back to My Projects</a>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>
</body>
</html>
