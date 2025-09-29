<?php
require_once '../includes/SessionManager.php';
require_once '../includes/RoleMiddleware.php';
require_once '../includes/SidebarManager.php';
require_once '../includes/ReportManager.php';
require_once '../includes/path_resolver.php';

// Ensure user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Get filters from request
$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'module' => $_GET['module'] ?? null,
    'status' => $_GET['status'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null,
    'limit' => $_GET['limit'] ?? 50
];

// Generate activity report
$report = ReportManager::generateActivityReport($filters);
$activities = $report['activities'];
$summary = $report['summary'];

// Handle export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    ReportManager::exportToCSV($activities, 'activity_log_' . date('Y-m-d') . '.csv');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <?php echo SidebarManager::getSidebarCSS(); ?>
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
                
                <!-- Page Content Goes Here -->
                
    <?php include '../topbar.php'; ?>
    <div class="flex flex-1 min-h-0">
        <?php SidebarManager::renderSidebar(); ?>
        
        <div class="container mx-auto p-6 flex-1">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Activity Log</h1>
                <div class="flex space-x-2">
                    <a href="?<?php echo http_build_query(array_merge($filters, ['export' => 'csv'])); ?>" 
                       class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        Export CSV
                    </a>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Activities</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $summary['total_activities']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Active Users</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo count($summary['users']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Modules</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo count($summary['modules']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Date Range</p>
                            <p class="text-lg font-semibold text-gray-900"><?php echo count($summary['date_breakdown']); ?> days</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h3 class="text-lg font-semibold mb-4">Filters</h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                        <select name="user_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">All Users</option>
                            <?php foreach ($summary['users'] as $user => $count): ?>
                                <option value="<?php echo htmlspecialchars($user); ?>" 
                                        <?php echo ($filters['user_id'] === $user) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user); ?> (<?php echo $count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Module</label>
                        <select name="module" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">All Modules</option>
                            <?php foreach ($summary['modules'] as $module => $count): ?>
                                <option value="<?php echo htmlspecialchars($module); ?>" 
                                        <?php echo ($filters['module'] === $module) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($module); ?> (<?php echo $count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">All Status</option>
                            <?php foreach ($summary['status_breakdown'] as $status => $count): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>" 
                                        <?php echo ($filters['status'] === $status) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status); ?> (<?php echo $count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="<?php echo $filters['date_from']; ?>" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="<?php echo $filters['date_to']; ?>" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Limit</label>
                        <select name="limit" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="25" <?php echo ($filters['limit'] == 25) ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo ($filters['limit'] == 50) ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo ($filters['limit'] == 100) ? 'selected' : ''; ?>>100</option>
                            <option value="200" <?php echo ($filters['limit'] == 200) ? 'selected' : ''; ?>>200</option>
                        </select>
                    </div>

                    <div class="lg:col-span-6 flex space-x-2">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Apply Filters
                        </button>
                        <a href="ActivityLog.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Activity List -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">Recent Activities</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($activities)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No activities found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activities as $activity): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-gray-700">
                                                            <?php echo strtoupper(substr($activity['full_name'] ?? $activity['username'] ?? 'U', 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($activity['full_name'] ?? $activity['username'] ?? 'Unknown'); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($activity['role'] ?? 'Unknown Role'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($activity['action']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($activity['module']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                            $statusColor = ($activity['status'] === 'Success') ? 'bg-green-100 text-green-800' : 
                                                          (($activity['status'] === 'Error') ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                                            ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusColor; ?>">
                                                <?php echo htmlspecialchars($activity['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs truncate">
                                                <?php echo htmlspecialchars($activity['details'] ?? ''); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php echo SidebarManager::getSidebarJS(); ?>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
