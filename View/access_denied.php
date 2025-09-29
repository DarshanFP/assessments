<?php
// Start session before any output
session_start();

require_once '../includes/SessionManager.php';
require_once '../includes/path_resolver.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
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
                
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md text-center">
        <h2 class="text-2xl font-bold mb-4">Access Denied</h2>
        <p class="mb-4">You do not have permission to view this page.</p>
        <p class="mb-4">If you are not logged in, please log in to access the application.</p>
        <div class="flex justify-center space-x-4 mt-4">
            <?php if (SessionManager::isLoggedIn()): ?>
                <?php 
                $userRole = SessionManager::getUserRole();
                if ($userRole === 'Councillor') {
                    $dashboardPath = 'CouncillorDashboard.php';
                } elseif ($userRole === 'Project In-Charge') {
                    $dashboardPath = 'ProjectInChargeDashboard.php';
                } else {
                    $dashboardPath = 'dashboard.php';
                }
                ?>
                <a href="<?php echo $dashboardPath; ?>" class="py-2 px-4 bg-blue-500 text-white rounded hover:bg-blue-600">Go Back to Dashboard</a>
            <?php else: ?>
                <a href="../index.php" class="py-2 px-4 bg-blue-500 text-white rounded hover:bg-blue-600">Go to Login</a>
            <?php endif; ?>
            <a href="../index.php" class="py-2 px-4 bg-gray-500 text-white rounded hover:bg-gray-600">Go to Login</a>
        </div>
    </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
