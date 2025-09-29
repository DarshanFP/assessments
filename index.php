<?php
// Start session before any output
session_start();

// Include session manager for proper session handling
require_once 'includes/SessionManager.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check if user is already logged in
if (SessionManager::isLoggedIn()) {
    $userRole = SessionManager::getUserRole();
    
    // Role-based redirection
    if ($userRole === 'Councillor') {
        header("Location: View/CouncillorDashboard.php");
    } elseif ($userRole === 'Project In-Charge') {
        header("Location: View/ProjectInChargeDashboard.php");
    } else {
        header("Location: View/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="unified.css">
    <title>Login - Assessment System</title>
</head>
<body>
    <!-- Layout Container -->
    <div class="layout-container">
        
        <!-- Topbar -->
        <?php include 'topbar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            
            <!-- Content Container -->
            <div class="content-container">
                
                <!-- Login Form -->
                <div class="form-container">
                    <h1 class="text-2xl font-bold text-center mb-5">Login</h1>

                    <!-- Login form -->
                    <form action="Controller/login_process.php" method="POST">
                        <div class="form-group">
                            <label for="username_email">Username or Email:</label>
                            <input type="text" id="username_email" name="username_email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Login</button>
                    </form>

                    <!-- Display error messages -->
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
                    
                    <!-- Forgot Password Link -->
                    <div class="text-center mt-4">
                        <a href="View/forgot_password.php" class="text-blue-500 hover:underline">Forgot Password?</a>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include 'footer.php'; ?>
        
    </div>
</body>
</html>
