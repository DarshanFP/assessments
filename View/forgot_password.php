<?php
session_start();
require_once '../includes/SessionManager.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Forgot Password - Assessment System</title>
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
                
                <!-- Forgot Password Form -->
                <div class="form-container">
                    <h1 class="text-2xl font-bold text-center mb-5">Forgot Password</h1>
                    
                    <div class="text-center mb-6">
                        <p class="text-gray-600">Enter your email address and we'll send you a link to reset your password.</p>
                    </div>
                    
                    <!-- Email Instructions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h3 class="text-lg font-semibold text-blue-800 mb-2">📧 Email Delivery Instructions</h3>
                        <p class="text-blue-700 mb-3">If you don't see the reset email in your inbox, please check:</p>
                        <ul class="text-blue-700 text-sm space-y-1">
                            <li>• <strong>Spam/Junk folder</strong> - Check your spam or junk email folder</li>
                            <li>• <strong>Search your inbox</strong> - Search for "Assessment System" or "Password Reset"</li>
                            <li>• <strong>Promotions tab</strong> (Gmail) - Check the Promotions or Updates tab</li>
                            <li>• <strong>All Mail</strong> (Gmail) - Check the All Mail folder</li>
                            <li>• <strong>Trash/Deleted items</strong> - Check if it was accidentally deleted</li>
                        </ul>
                        <p class="text-blue-700 text-sm mt-3">
                            <strong>From:</strong> Assessment System &lt;assessments@salcompassion.org&gt;
                        </p>
                    </div>

                    <!-- Forgot password form -->
                    <form action="../Controller/forgot_password_process.php" method="POST">
                        <div class="form-group">
                            <label for="email">Email Address:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">Send Reset Link</button>
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
                        
                        // Show additional instructions when email is sent
                        echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4 mt-4'>";
                        echo "<h3 class='text-lg font-semibold text-green-800 mb-2'>📧 Email Sent Successfully!</h3>";
                        echo "<p class='text-green-700 mb-3'>Please check the following locations for your password reset email:</p>";
                        echo "<ul class='text-green-700 text-sm space-y-1'>";
                        echo "<li>• <strong>Primary inbox</strong> - Check your main email folder</li>";
                        echo "<li>• <strong>Spam/Junk folder</strong> - Check your spam or junk email folder</li>";
                        echo "<li>• <strong>Search your inbox</strong> - Search for \"Assessment System\" or \"Password Reset\"</li>";
                        echo "<li>• <strong>Promotions tab</strong> (Gmail) - Check the Promotions or Updates tab</li>";
                        echo "<li>• <strong>All Mail</strong> (Gmail) - Check the All Mail folder</li>";
                        echo "<li>• <strong>Trash/Deleted items</strong> - Check if it was accidentally deleted</li>";
                        echo "</ul>";
                        echo "<p class='text-green-700 text-sm mt-3'>";
                        echo "<strong>From:</strong> Assessment System &lt;assessments@salcompassion.org&gt;<br>";
                        echo "<strong>Subject:</strong> Password Reset Request - Assessment System";
                        echo "</p>";
                        echo "<p class='text-green-700 text-sm mt-3'>";
                        echo "<strong>Note:</strong> The reset link will expire in 24 hours for security.";
                        echo "</p>";
                        echo "</div>";
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
</body>
</html>
