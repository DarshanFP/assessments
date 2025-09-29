<?php
// Start session before any output
session_start();

require_once '../includes/SessionManager.php';
require_once '../includes/RoleMiddleware.php';
require_once '../includes/SidebarManager.php';
require_once '../includes/DashboardManager.php';
require_once '../includes/path_resolver.php';

// Ensure only 'Councillor' users can access this page
RoleMiddleware::requireCouncillor();

// Get dashboard statistics
$stats = DashboardManager::getCouncillorStats();
$recentActivities = DashboardManager::getRecentActivities(5);
$recentAssessments = DashboardManager::getRecentAssessments(5);
$recentProjects = DashboardManager::getRecentProjects(5);
$communitySummary = DashboardManager::getCommunityAssessmentSummary();
$budgetSummary = DashboardManager::getProjectBudgetSummary();
$quickActions = DashboardManager::getQuickActions('Councillor');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Councillor Dashboard</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">Councillor Dashboard</h1>
                    <p class="text-gray-600">Welcome, <?php echo htmlspecialchars(SessionManager::getUserFullName()); ?>!</p>
                </div>
        
                <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Total Assessments -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Assessments</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_assessments'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Projects -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Projects</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_projects'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Budget -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Budget</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo DashboardManager::formatCurrency($stats['total_budget'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Expensed -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Expensed</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo DashboardManager::formatCurrency($stats['total_expenses'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Available Funds -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-teal-100 text-teal-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Available Funds</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo DashboardManager::formatCurrency($stats['available_funds'] ?? 0); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Project In-Charges -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Project In-Charges</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['project_incharges'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications Section -->
            <?php include '../includes/notification_component.php'; ?>

            <!-- Quick Actions -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <?php foreach ($quickActions as $action): ?>
                        <a href="<?php echo $action['url']; ?>" 
                           class="<?php echo $action['color']; ?> text-white py-3 px-4 rounded-lg text-center transition duration-200 hover:shadow-lg transform hover:scale-105"
                           style="text-decoration: none; display: block;">
                            <div class="text-2xl mb-2"><?php echo $action['icon']; ?></div>
                            <div class="text-sm font-medium text-white"><?php echo $action['title']; ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
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

                <!-- Recent Assessments -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold mb-4">Recent Assessments</h3>
                    <div class="space-y-3">
                        <?php if (empty($recentAssessments)): ?>
                            <p class="text-gray-500 text-sm">No recent assessments</p>
                        <?php else: ?>
                            <?php foreach ($recentAssessments as $assessment): ?>
                                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium"><?php echo htmlspecialchars($assessment['Community']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($assessment['AssessorsName']); ?> - <?php echo date('M j, Y', strtotime($assessment['DateOfAssessment'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Community Summary -->
            <?php if (!empty($communitySummary)): ?>
            <div class="bg-white p-6 rounded-lg shadow-md mt-8">
                <h3 class="text-lg font-semibold mb-4">Assessment Summary by Community</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach (array_slice($communitySummary, 0, 6) as $community): ?>
                        <div class="p-4 bg-gray-50 rounded">
                            <p class="font-medium"><?php echo htmlspecialchars($community['Community']); ?></p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $community['assessment_count']; ?></p>
                            <p class="text-sm text-gray-500">assessments</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
