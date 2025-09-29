<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';
require_once '../../includes/role_based_sidebar.php'; // Include role-based sidebar which also includes the top bar


// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: ../../index.php");
    exit();
}

// Ensure only 'Councillor' users can access this page
checkRole('Councillor');

try {
    // Fetch all active projects with Total Expensed and Available Funds, including start_date, end_date, organization, funding details, and multiple Project In-Charges
    $stmt = $pdo->prepare("
        SELECT p.project_id, p.project_name, p.total_budget, p.start_date, p.end_date, p.funding_source, p.fund_type,
               o.organization_id, o.organization_code, o.organization_name, o.color_theme,
               GROUP_CONCAT(
                   CONCAT(u.full_name, 
                          CASE WHEN pa.is_primary = 1 THEN ' (Primary)' ELSE '' END
                   ) 
                   ORDER BY pa.is_primary DESC, u.full_name
                   SEPARATOR ', '
               ) AS project_incharges,
               IFNULL(SUM(ee.amount_expensed), 0) AS total_expensed,
               (p.total_budget - IFNULL(SUM(ee.amount_expensed), 0)) AS available_funds
        FROM Projects p
        LEFT JOIN Organizations o ON p.organization_id = o.organization_id
        LEFT JOIN ExpenseEntries ee ON p.project_id = ee.project_id
        LEFT JOIN ProjectAssignments pa ON p.project_id = pa.project_id AND pa.is_active = 1
        LEFT JOIN ssmntUsers u ON pa.project_incharge_id = u.id
        WHERE p.is_active = 1
        GROUP BY p.project_id, p.project_name, p.total_budget, p.start_date, p.end_date, p.funding_source, p.fund_type, o.organization_id, o.organization_code, o.organization_name, o.color_theme
        ORDER BY o.organization_name, p.project_name
    ");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching projects: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Projects Overview</title>
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
        
        .period-cell {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        .period-label {
            font-weight: 600;
            color: #374151;
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
        <!-- Include the role-based sidebar -->
        <?php include '../../includes/role_based_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 p-6" style="margin-top: 80px;">
            
            <!-- Enhanced Page Header -->
            <div class="page-header">
                <h1>All Projects Budget Overview</h1>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($projects); ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format(array_sum(array_column($projects, 'total_budget')), 2); ?></div>
                    <div class="stat-label">Total Budget</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format(array_sum(array_column($projects, 'total_expensed')), 2); ?></div>
                    <div class="stat-label">Total Expensed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₹<?php echo number_format(array_sum(array_column($projects, 'available_funds')), 2); ?></div>
                    <div class="stat-label">Available Funds</div>
                </div>
            </div>

            <!-- Display Error or Success Messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Enhanced Projects Table -->
            <div class="enhanced-table">
                <?php if (!empty($projects)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Organization</th>
                                <th>Project In-Charges</th>
                                <th>Fund Type</th>
                                <th>Project Period</th>
                                <th>Total Budget (₹)</th>
                                <th>Total Expensed (₹)</th>
                                <th>Available Funds (₹)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($project['project_name']); ?></strong></td>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                            echo $project['color_theme'] === 'blue' ? 'bg-blue-100 text-blue-800' : 
                                                ($project['color_theme'] === 'pink' ? 'bg-pink-100 text-pink-800' : 
                                                ($project['color_theme'] === 'green' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')); 
                                        ?>">
                                            <?php echo htmlspecialchars($project['organization_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-sm">
                                            <?php if (!empty($project['project_incharges'])): ?>
                                                <?php 
                                                $incharges = explode(', ', $project['project_incharges']);
                                                foreach ($incharges as $incharge): 
                                                ?>
                                                    <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded mr-1 mb-1">
                                                        <?php echo htmlspecialchars($incharge); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-gray-400">Not assigned</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($project['fund_type'])): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($project['fund_type']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="period-cell">
                                        <?php if ($project['start_date'] && $project['end_date']): ?>
                                            <span class="period-label">Start:</span> <?php echo date('d M Y', strtotime($project['start_date'])); ?><br>
                                            <span class="period-label">End:</span> <?php echo date('d M Y', strtotime($project['end_date'])); ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="budget-cell">₹<?php echo number_format($project['total_budget'], 2); ?></td>
                                    <td class="expense-cell">₹<?php echo number_format($project['total_expensed'], 2); ?></td>
                                    <td class="available-cell">₹<?php echo number_format($project['available_funds'], 2); ?></td>
                                    <td>
                                        <div class="flex gap-2 flex-wrap">
                                            <a href="transactions.php?project_id=<?php echo $project['project_id']; ?>" class="action-btn">Add Expenses</a>
                                            <a href="all_transactions.php?project_id=<?php echo $project['project_id']; ?>" class="action-btn" style="background: #059669;">View All Transactions</a>
                                            <a href="project_expense_breakdown.php?project_id=<?php echo $project['project_id']; ?>" class="action-btn" style="background: #7c3aed;">Expense Breakdown</a>
                                            <a href="project_edit_form.php?project_id=<?php echo $project['project_id']; ?>" class="action-btn" style="background: #f59e0b;">Edit Project</a>
                                            <button onclick="confirmDeactivate(<?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($project['project_name']); ?>')" class="action-btn" style="background: #dc2626;">Deactivate</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="text-gray-500 text-lg mb-2">No projects found.</div>
                        <div class="text-gray-400 text-sm">Projects will appear here once they are created.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>

    <!-- JavaScript for confirmation dialogs -->
    <script>
        function confirmDeactivate(projectId, projectName) {
            if (confirm('Are you sure you want to deactivate the project "' + projectName + '"?\n\nThis action will hide the project from the system but preserve all data for future reference.')) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../Controller/project_deactivate_process.php';
                
                const projectIdInput = document.createElement('input');
                projectIdInput.type = 'hidden';
                projectIdInput.name = 'project_id';
                projectIdInput.value = projectId;
                
                form.appendChild(projectIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
