<?php
session_start();

// Include the session manager and password reset manager
require_once '../includes/SessionManager.php';
require_once '../includes/PasswordResetManager.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize email input
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // Basic validation
    if (empty($email)) {
        SessionManager::setMessage('error', "Email address is required.");
        header("Location: ../View/forgot_password.php");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        SessionManager::setMessage('error', "Please enter a valid email address.");
        header("Location: ../View/forgot_password.php");
        exit();
    }

    try {
        // Initialize password reset manager
        $passwordResetManager = new PasswordResetManager();
        
        // Generate reset token and send email
        $result = $passwordResetManager->generateResetToken($email);
        
        if ($result['success']) {
            SessionManager::setMessage('success', $result['message']);
        } else {
            SessionManager::setMessage('error', $result['message']);
        }
        
        // Always redirect back to forgot password page (for security)
        // Even if email doesn't exist, we don't reveal that information
        header("Location: ../View/forgot_password.php");
        exit();
        
    } catch (Exception $e) {
        // Log the error with more details
        error_log("Error in forgot_password_process: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Set more specific error message for debugging (remove this in production)
        SessionManager::setMessage('error', "Error: " . $e->getMessage());
        header("Location: ../View/forgot_password.php");
        exit();
    } catch (Error $e) {
        // Log PHP errors
        error_log("PHP Error in forgot_password_process: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        SessionManager::setMessage('error', "PHP Error: " . $e->getMessage());
        header("Location: ../View/forgot_password.php");
        exit();
    }
} else {
    // If not POST request, redirect to forgot password page
    header("Location: ../View/forgot_password.php");
    exit();
}
?>
