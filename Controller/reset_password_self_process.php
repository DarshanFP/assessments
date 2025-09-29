<?php
session_start();

// Include the session manager and password reset manager
require_once '../includes/SessionManager.php';
require_once '../includes/PasswordResetManager.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $token = trim($_POST['token']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    // Basic validation
    if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
        SessionManager::setMessage('error', "All fields are required.");
        header("Location: ../View/reset_password_self.php?token=" . urlencode($token));
        exit();
    }

    // Validate password length
    if (strlen($newPassword) < 8) {
        SessionManager::setMessage('error', "Password must be at least 8 characters long.");
        header("Location: ../View/reset_password_self.php?token=" . urlencode($token));
        exit();
    }

    // Check if passwords match
    if ($newPassword !== $confirmPassword) {
        SessionManager::setMessage('error', "Passwords do not match.");
        header("Location: ../View/reset_password_self.php?token=" . urlencode($token));
        exit();
    }

    try {
        // Initialize password reset manager
        $passwordResetManager = new PasswordResetManager();
        
        // Reset password using token
        $result = $passwordResetManager->resetPassword($token, $newPassword);
        
        if ($result['success']) {
            SessionManager::setMessage('success', $result['message']);
            // Redirect to login page after successful reset
            header("Location: ../index.php");
            exit();
        } else {
            SessionManager::setMessage('error', $result['message']);
            // Redirect back to reset page if there's an error
            header("Location: ../View/reset_password_self.php?token=" . urlencode($token));
            exit();
        }
        
    } catch (Exception $e) {
        // Log the error
        error_log("Error in reset_password_self_process: " . $e->getMessage());
        
        // Set generic error message for security
        SessionManager::setMessage('error', "An error occurred. Please try again later.");
        header("Location: ../View/reset_password_self.php?token=" . urlencode($token));
        exit();
    }
} else {
    // If not POST request, redirect to login page
    header("Location: ../index.php");
    exit();
}
?>
