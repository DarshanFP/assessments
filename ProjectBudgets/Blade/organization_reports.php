<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';
require_once '../../includes/role_based_sidebar.php';

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
    // Fetch organization statistics
    $stmt = $pdo->prepare("
        SELECT 
            o.organization_id,
            o.organization_code,
            o.organization_name,
            o.color_theme,
            COUNT(p.project_id) as total_projects,
            IFNULL(SUM(p.total_budget), 0) as total_budget,
            IFNULL(SUM(ee.amount_expensed), 0) as total_expensed,
            (IFNULL(SUM(p.total_budget), 0) - IFNULL(SUM(ee.amount_expensed), 0)) as available_funds
        FROM Organizations o
        LEFT JOIN Projects p ON o.organization_id = p.organization_id
        LEFT JOIN ExpenseEntries ee ON p.project_id = ee.project_id
        WHERE o.is_active = 1
        GROUP BY o.organization_id, o.organization_code, o.organization_name, o.color_theme
        ORDER BY o.organization_name
    ");
    $stmt->execute();
    $organizationStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch overall statistics
    $overallStmt = $pdo->prepare("
        SELECT 
            COUNT(p.project_id) as total_projects,
            SUM(p.total_budget) as total_budget,
            IFNULL(SUM(ee.amount_expensed), 0) as total_expensed,
            (SUM(p.total_budget) - IFNULL(SUM(ee.amount_expensed), 0)) as available_funds
        FROM Projects p
        LEFT JOIN ExpenseEntries ee ON p.project_id = ee.project_id
    ");
    $overallStmt->execute();
    $overallStats = $overallStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch detailed project list by organization
    $detailStmt = $pdo->prepare("
        SELECT 
            p.project_id, p.project_name, p.total_budget, p.start_date, p.end_date,
            o.organization_id, o.organization_code, o.organization_name, o.color_theme,
            u.full_name AS project_incharge,
            IFNULL(SUM(ee.amount_expensed), 0) AS total_expensed,
            (p.total_budget - IFNULL(SUM(ee.amount_expensed), 0)) AS available_funds
        FROM Projects p
        LEFT JOIN Organizations o ON p.organization_id = o.organization_id
        LEFT JOIN ExpenseEntries ee ON p.project_id = ee.project_id
        LEFT JOIN ssmntUsers u ON p.project_incharge = u.id
        GROUP BY p.project_id, p.project_name, p.total_budget, p.start_date, p.end_date, o.organization_id, o.organization_code, o.organization_name, o.color_theme, u.full_name
        ORDER BY o.organization_name, p.project_name
    ");
    $detailStmt->execute();
    $projectDetails = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error fetching organization data: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Reports - Assessment System</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #4a6fd1;
        }
        
        .stat-card.ssct {
            border-left-color: #3b82f6;
        }
        
        .stat-card.saes {
            border-left-color: #ec4899;
        }
        
        .stat-card.overall {
            border-left-color: #10b981;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #4a6fd1;
        }
        
        .stat-card.ssct .stat-number {
            color: #3b82f6;
        }
        
        .stat-card.saes .stat-number {
            color: #ec4899;
        }
        
        .stat-card.overall .stat-number {
            color: #10b981;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .organization-header {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0 10px 0;
            border-left: 4px solid #4a6fd1;
        }
        
        .organization-header.ssct {
            border-left-color: #3b82f6;
        }
        
        .organization-header.saes {
            border-left-color: #ec4899;
        }
        
        .organization-title {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }
        
        .organization-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin: 4px 0 0 0;
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
                
                <!-- Enhanced Page Header -->
                <div class="page-header">
                    <h1>Organization Reports</h1>
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

                <!-- Organization Statistics Cards -->
                <div class="stats-grid">
                    <?php foreach ($organizationStats as $stat): ?>
                        <div class="stat-card <?php echo $stat['color_theme']; ?>">
                            <div class="stat-number"><?php echo $stat['organization_name']; ?></div>
                            <div class="stat-label">Organization</div>
                            <div class="mt-4">
                                <div class="text-sm text-gray-600">Projects: <span class="font-semibold"><?php echo $stat['total_projects']; ?></span></div>
                                <div class="text-sm text-gray-600">Budget: <span class="font-semibold">₹<?php echo number_format($stat['total_budget'], 2); ?></span></div>
                                <div class="text-sm text-gray-600">Expensed: <span class="font-semibold">₹<?php echo number_format($stat['total_expensed'], 2); ?></span></div>
                                <div class="text-sm text-gray-600">Available: <span class="font-semibold">₹<?php echo number_format($stat['available_funds'], 2); ?></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Overall Statistics -->
                    <div class="stat-card overall">
                        <div class="stat-number">Overall</div>
                        <div class="stat-label">Total Statistics</div>
                        <div class="mt-4">
                            <div class="text-sm text-gray-600">Projects: <span class="font-semibold"><?php echo $overallStats['total_projects']; ?></span></div>
                            <div class="text-sm text-gray-600">Budget: <span class="font-semibold">₹<?php echo number_format($overallStats['total_budget'], 2); ?></span></div>
                            <div class="text-sm text-gray-600">Expensed: <span class="font-semibold">₹<?php echo number_format($overallStats['total_expensed'], 2); ?></span></div>
                            <div class="text-sm text-gray-600">Available: <span class="font-semibold">₹<?php echo number_format($overallStats['available_funds'], 2); ?></span></div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Project List -->
                <div class="enhanced-table">
                    <h2 class="text-xl font-semibold mb-4 px-4 pt-4">Detailed Project List by Organization</h2>
                    
                    <?php 
                    $currentOrg = '';
                    foreach ($projectDetails as $project): 
                        if ($currentOrg !== $project['organization_id']):
                            $currentOrg = $project['organization_id'];
                    ?>
                        <div class="organization-header <?php echo $project['color_theme']; ?>">
                            <h3 class="organization-title">
                                <?php echo $project['organization_name']; ?> (<?php echo $project['organization_code']; ?>)
                            </h3>
                            <p class="organization-subtitle">Projects operated by <?php echo $project['organization_name']; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($currentOrg === $project['organization_id']): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Project In-Charge</th>
                                    <th>Project Period</th>
                                    <th>Total Budget (₹)</th>
                                    <th>Total Expensed (₹)</th>
                                    <th>Available Funds (₹)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    <?php endif; ?>
                    
                    <tr>
                        <td><strong><?php echo htmlspecialchars($project['project_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($project['project_incharge']); ?></td>
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
                            <div class="flex gap-2">
                                <a href="transactions.php?project_id=<?php echo $project['project_id']; ?>" class="text-blue-600 hover:underline text-sm">Add Expenses</a>
                                <a href="all_transactions.php?project_id=<?php echo $project['project_id']; ?>" class="text-green-600 hover:underline text-sm">View Transactions</a>
                            </div>
                        </td>
                    </tr>
                    
                    <?php 
                    // Check if this is the last project of this organization
                    $nextProject = next($projectDetails);
                    if ($nextProject === false || $nextProject['organization_id'] !== $currentOrg):
                        prev($projectDetails); // Reset pointer
                    ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>
</body>
</html>
