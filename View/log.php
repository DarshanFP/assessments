<?php
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch the logs from the database
$sql = "SELECT l.*, u.username FROM ActivityLog l LEFT JOIN ssmntUsers u ON l.user_id = u.id ORDER BY l.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head class="text-center py-4">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log</title>
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
                
                <!-- Page Content Goes Here -->
                
    <!-- Include topbar -->
    <?php include '../topbar.php'; ?>

    <div class="flex flex-1 min-h-0">

        <!-- Main content area -->
        <div class="form-container p-6 flex-1 bg-white">
            <h1 class="text-2xl font-bold text-center mb-6">Activity Log</h1>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border rounded-lg shadow-lg">
                    <thead>
                        <tr class="bg-gray-200 text-gray-600 uppercase text-sm">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center px-4 py-6 text-gray-600">No activity logs found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="border-b hover:bg-gray-100">
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($log['created_at']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="<?php echo ($log['status'] === 'success') ? 'text-green-500' : 'text-red-500'; ?>">
                                            <?php echo htmlspecialchars($log['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($log['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
