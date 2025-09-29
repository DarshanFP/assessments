<?php
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';
require_once '../includes/auth_check.php';

// Ensure only 'Councillor' users can access this page
checkRole('Councillor');

$userId = $_GET['id'] ?? null;

if (!$userId) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: user_list.php");
    exit();
}

try {
    // Fetch user details
    $stmt = $pdo->prepare("SELECT full_name, username FROM ssmntUsers WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: user_list.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: user_list.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
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
                
    <div class="form-container bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6">Reset Password for <?php echo htmlspecialchars($user['full_name']); ?></h1>

        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-red-500 mb-4"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="text-green-500 mb-4"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></p>
        <?php endif; ?>

        <form action="../Controller/reset_password_process.php" method="POST">
            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">

            <div class="form-group mb-4">
                <label for="new_password" class="block text-gray-700">New Password:</label>
                <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border rounded" required>
            </div>

            <div class="form-group mb-4">
                <label for="confirm_password" class="block text-gray-700">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border rounded" required>
            </div>

            <button type="submit" class="w-full py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Reset Password</button>
        </form>

        <div class="mt-4 text-center">
            <a href="user_list.php" class="text-blue-500 hover:underline">Back to User List</a>
        </div>
    </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
