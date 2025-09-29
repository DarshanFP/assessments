<?php
// Start session
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: ../index.php");
    exit();
}

// Check if user is a councillor
if ($_SESSION['role'] !== 'Councillor') {
    $_SESSION['error'] = "Access denied. Only councillors can manage users.";
    header("Location: ../View/access_denied.php");
    exit();
}

try {
    // Fetch all users from the database
    $stmt = $pdo->prepare("SELECT id, full_name, username, email, role, community FROM ssmntUsers ORDER BY full_name ASC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching user list: " . $e->getMessage();
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Manage Users</title>
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
                <div class="page-header mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Manage Users</h1>
                    <p class="text-gray-600">View and manage all system users</p>
                </div>

                <!-- Display Session Messages -->
                <?php 
                $successMessage = $_SESSION['success'] ?? null;
                $errorMessage = $_SESSION['error'] ?? null;
                
                if ($successMessage): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <?php
                // Clear session messages after displaying them
                unset($_SESSION['success'], $_SESSION['error']);
                ?>

                <!-- Action Buttons -->
                <div class="mb-6">
                    <a href="register.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Add New User
                    </a>
                </div>

                <!-- Users Table -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Full Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Username
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Community
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No users found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                    switch($user['role']) {
                                                        case 'Councillor': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'Project In-Charge': echo 'bg-green-100 text-green-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800'; break;
                                                    }
                                                    ?>">
                                                    <?php echo htmlspecialchars($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($user['community'] ?? 'N/A'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900">Edit</a>
                                                    <a href="reset_password.php?id=<?php echo $user['id']; ?>" 
                                                       class="text-red-600 hover:text-red-900">Reset Password</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- User Statistics -->
                <?php if (!empty($users)): ?>
                    <div class="mt-8 bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">User Statistics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <div class="text-2xl font-bold text-blue-600"><?php echo count($users); ?></div>
                                <div class="text-sm text-gray-600">Total Users</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <div class="text-2xl font-bold text-green-600">
                                    <?php 
                                    $councillors = array_filter($users, function($user) {
                                        return $user['role'] === 'Councillor';
                                    });
                                    echo count($councillors);
                                    ?>
                                </div>
                                <div class="text-sm text-gray-600">Councillors</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <div class="text-2xl font-bold text-purple-600">
                                    <?php 
                                    $projectInCharges = array_filter($users, function($user) {
                                        return $user['role'] === 'Project In-Charge';
                                    });
                                    echo count($projectInCharges);
                                    ?>
                                </div>
                                <div class="text-sm text-gray-600">Project In-Charges</div>
                            </div>
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
