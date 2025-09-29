<?php
// Start session before any output
session_start();

require_once '../includes/SessionManager.php';
require_once '../includes/RoleMiddleware.php';
require_once '../includes/SidebarManager.php';
require_once '../includes/DashboardManager.php';
require_once '../includes/path_resolver.php';

// Ensure only 'Project In-Charge' users can access this page
RoleMiddleware::requireProjectInCharge();

// Get user data
$userId = SessionManager::getUserId();
$community = SessionManager::getUserCommunity();

// Get dashboard statistics
$stats = DashboardManager::getProjectInChargeStats($userId, $community);
$recentActivities = DashboardManager::getRecentActivities(5);
$recentAssessments = DashboardManager::getRecentAssessments(5);

// Fetch user's projects with budget information
try {
    require_once '../includes/dbh.inc.php';
    $projectStmt = $pdo->prepare("
        SELECT p.project_id, p.project_name, p.total_budget, p.start_date, p.end_date,
               IFNULL(SUM(ee.amount_expensed), 0) AS total_expensed,
               (p.total_budget - IFNULL(SUM(ee.amount_expensed), 0)) AS available_funds
        FROM Projects p
        LEFT JOIN ExpenseEntries ee ON p.project_id = ee.project_id
        WHERE p.project_incharge = :user_id
        GROUP BY p.project_id, p.project_name, p.total_budget, p.start_date, p.end_date
        ORDER BY p.project_name
    ");
    $projectStmt->execute([':user_id' => $userId]);
    $userProjects = $projectStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $userProjects = [];
    error_log("Error fetching user projects: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project In-Charge Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
</head>
<body>
    <!-- Layout Container -->
    <div class="layout-container">
        
        <!-- Topbar -->
        <?php include '../topbar.php'; ?>
        
        <!-- Sidebar -->
        <?php include '../includes/role_based_sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            
            <!-- Content Container -->
            <div class="content-container">
                
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Project In-Charge Dashboard</h1>
                    <p class="text-gray-600">Welcome, <?php echo htmlspecialchars(SessionManager::getUserFullName()); ?>!</p>
                </div>

            <!-- User Info Card -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <div class="flex items-center space-x-4">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars(SessionManager::getUserFullName()); ?></h3>
                        <p class="text-gray-600">Project In-Charge • <?php echo htmlspecialchars($community ?? 'Community Not Set'); ?></p>
                        <p class="text-sm text-gray-500">User ID: <?php echo $userId; ?></p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- My Projects -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">My Projects</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['my_projects'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Active Projects -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Active Projects</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['active_projects'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Community Assessments -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Community Assessments</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['community_assessments'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <!-- My Budget -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">My Budget</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo DashboardManager::formatCurrency($stats['my_budget'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assessment Centre Card (First Card) -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h3 class="text-lg font-semibold mb-4">Assessment Centre</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="<?php echo PathResolver::resolve('View/AssessmentCentre.php'); ?>" 
                       class="bg-blue-500 text-white py-4 px-6 rounded-lg text-center transition duration-200 hover:shadow-lg hover:bg-blue-600">
                        <div class="text-3xl mb-2">🏢</div>
                        <div class="text-sm font-medium">Assessment Centre</div>
                        <div class="text-xs opacity-75 mt-1">Manage assessments</div>
                    </a>
                </div>
            </div>

            <!-- My Projects -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h3 class="text-lg font-semibold mb-4">My Projects</h3>
                <?php if (!empty($userProjects)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($userProjects as $project): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-200">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($project['project_name']); ?></h4>
                                    <span class="text-xs text-gray-500">
                                        <?php if ($project['start_date'] && $project['end_date']): ?>
                                            <?php echo date('d M Y', strtotime($project['start_date'])); ?> - <?php echo date('d M Y', strtotime($project['end_date'])); ?>
                                        <?php else: ?>
                                            No dates set
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Total Budget:</span>
                                        <span class="font-medium text-green-600">₹<?php echo number_format($project['total_budget'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Total Expensed:</span>
                                        <span class="font-medium text-red-600">₹<?php echo number_format($project['total_expensed'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Available:</span>
                                        <span class="font-medium text-blue-600">₹<?php echo number_format($project['available_funds'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <div class="flex gap-2">
                                    <a href="<?php echo PathResolver::resolve('ProjectBudgets/Blade/transactions.php'); ?>?project_id=<?php echo $project['project_id']; ?>" 
                                       class="flex-1 bg-blue-500 text-white py-2 px-3 rounded text-center text-xs font-medium hover:bg-blue-600 transition duration-200">
                                        Add Expenses
                                    </a>
                                    <a href="<?php echo PathResolver::resolve('ProjectBudgets/Blade/all_transactions.php'); ?>?project_id=<?php echo $project['project_id']; ?>" 
                                       class="flex-1 bg-green-500 text-white py-2 px-3 rounded text-center text-xs font-medium hover:bg-green-600 transition duration-200">
                                        View All
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="text-gray-500 text-lg mb-2">No projects assigned to you.</div>
                        <div class="text-gray-400 text-sm">Projects will appear here once they are assigned by a Councillor.</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activities and Data -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Activities -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold mb-4">Recent Activities</h3>
                    <div class="space-y-3">
                        <?php if (empty($recentActivities)): ?>
                            <p class="text-gray-500 text-sm">No recent activities</p>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium"><?php echo htmlspecialchars($activity['action']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Community Assessments -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Recent Community Assessments</h3>
                        <a href="ProjectInChargeAssessmentView.php?keyID=<?php echo !empty($communityAssessments) ? urlencode($communityAssessments[0]['keyID']) : ''; ?>" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All
                        </a>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($recentAssessments)): ?>
                            <p class="text-gray-500 text-sm">No recent assessments</p>
                        <?php else: ?>
                            <?php 
                            // Filter assessments for this community
                            $communityAssessments = array_filter($recentAssessments, function($assessment) use ($community) {
                                return $assessment['Community'] === $community;
                            });
                            ?>
                            <?php if (empty($communityAssessments)): ?>
                                <p class="text-gray-500 text-sm">No assessments for <?php echo htmlspecialchars($community); ?></p>
                            <?php else: ?>
                                <?php foreach (array_slice($communityAssessments, 0, 5) as $assessment): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium"><?php echo htmlspecialchars($assessment['Community']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($assessment['AssessorsName']); ?> - <?php echo date('M j, Y', strtotime($assessment['DateOfAssessment'])); ?></p>
                                            </div>
                                        </div>
                                        <a href="ProjectInChargeAssessmentView.php?keyID=<?php echo urlencode($assessment['keyID']); ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                            View Details
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Community Information -->
            <div class="bg-white p-6 rounded-lg shadow-md mt-8">
                <h3 class="text-lg font-semibold mb-4">Community Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 bg-blue-50 rounded">
                        <h4 class="font-medium text-blue-800">Community</h4>
                        <p class="text-2xl font-bold text-blue-600"><?php echo htmlspecialchars($community ?? 'Not Set'); ?></p>
                    </div>
                    <div class="p-4 bg-green-50 rounded">
                        <h4 class="font-medium text-green-800">Recent Assessments</h4>
                        <p class="text-2xl font-bold text-green-600"><?php echo $stats['recent_assessments'] ?? 0; ?></p>
                        <p class="text-sm text-green-600">Last 30 days</p>
                    </div>
                </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
