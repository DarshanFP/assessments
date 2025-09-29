<?php
session_start();
require_once '../includes/SessionManager.php';
require_once '../includes/PasswordResetManager.php';

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    SessionManager::setMessage('error', "Invalid reset link.");
    header("Location: ../index.php");
    exit();
}

// Validate token
$passwordResetManager = new PasswordResetManager();
$validation = $passwordResetManager->validateToken($token);

if (!$validation['valid']) {
    SessionManager::setMessage('error', $validation['message']);
    header("Location: ../index.php");
    exit();
}

$user = $validation['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Reset Password - Assessment System</title>
</head>
<body>
    <!-- Layout Container -->
    <div class="layout-container">
        
        <!-- Topbar -->
        <?php include '../topbar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            
            <!-- Content Container -->
            <div class="content-container">
                
                <!-- Reset Password Form -->
                <div class="form-container">
                    <h1 class="text-2xl font-bold text-center mb-5">Reset Your Password</h1>
                    
                    <div class="text-center mb-6">
                        <p class="text-gray-600">Hello, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
                        <p class="text-gray-600">Please enter your new password below.</p>
                    </div>

                    <!-- Reset password form -->
                    <form action="../Controller/reset_password_self_process.php" method="POST" id="resetForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                            <small class="text-gray-500">Password must be at least 8 characters long</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Reset Password</button>
                    </form>

                    <!-- Display messages -->
                    <?php
                    $errorMessage = SessionManager::getMessage('error');
                    $successMessage = SessionManager::getMessage('success');
                    
                    if ($errorMessage) {
                        echo "<div class='alert alert-error mt-4'>" . htmlspecialchars($errorMessage) . "</div>";
                    }
                    if ($successMessage) {
                        echo "<div class='alert alert-success mt-4'>" . htmlspecialchars($successMessage) . "</div>";
                    }
                    ?>
                    
                    <!-- Back to login link -->
                    <div class="text-center mt-6">
                        <a href="../index.php" class="text-blue-500 hover:underline">Back to Login</a>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
    
    <script>
        // Client-side password validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
        });
    </script>
</body>
</html>
