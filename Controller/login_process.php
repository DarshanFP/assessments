<?php
// Start session before any output
session_start();

// Include the session manager first
require_once '../includes/SessionManager.php';

// Include the database manager and logging files
require_once '../includes/DatabaseManager.php';
require_once '../includes/log_activity.php';
require_once '../includes/logger.inc.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $username_email = trim($_POST['username_email']);
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($username_email) || empty($password)) {
        SessionManager::setMessage('error', "Both fields are required.");
        header("Location: ../index.php");
        exit();
    }

    try {
        // Get database connection from pool
        $pdo = getDatabaseConnection();
        
        // Prepare and execute SQL query to fetch user data
        $sql = "SELECT * FROM ssmntUsers WHERE username = :username OR email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username_email, 'email' => $username_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the user exists
        if ($user) {
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Set session variables using SessionManager
                SessionManager::setUserSession($user);
                
                // Set success message
                SessionManager::setMessage('success', "Welcome, " . htmlspecialchars($user['full_name']) . "!");

                // Log the successful login
                $logMessage = "User ID {$user['id']} ({$user['full_name']}) logged in successfully.";
                logActivityToDatabase($user['id'], 'Login', 'Success', $logMessage);
                logActivityToFile($logMessage, "info");

                // Role-based redirection
                if ($user['role'] === 'Councillor') {
                    header("Location: ../View/CouncillorDashboard.php");
                } elseif ($user['role'] === 'Project In-Charge') {
                    header("Location: ../View/ProjectInChargeDashboard.php");
                } else {
                    // Default redirection for other roles (if any)
                    header("Location: ../View/dashboard.php");
                }
                exit();
            } else {
                // Invalid password
                $errorLogMessage = "Invalid login attempt with username/email: {$username_email} (incorrect password)";
                SessionManager::setMessage('error', "Invalid username/email or password.");
                logActivityToDatabase(null, 'Login', 'Error', $errorLogMessage);
                logActivityToFile($errorLogMessage, "warning");
                header("Location: ../index.php");
                exit();
            }
        } else {
            // User not found
            $errorLogMessage = "Invalid login attempt: User not found for username/email: {$username_email}";
            SessionManager::setMessage('error', "Invalid username/email or password.");
            logActivityToDatabase(null, 'Login', 'Error', $errorLogMessage);
            logActivityToFile($errorLogMessage, "warning");
            header("Location: ../index.php");
            exit();
        }
    } catch (PDOException $e) {
        // Log the database error
        $dbErrorLogMessage = "Database error during login: " . $e->getMessage();
        error_log($dbErrorLogMessage);
        logActivityToFile($dbErrorLogMessage, "error");
        SessionManager::setMessage('error', "An error occurred. Please try again later.");
        header("Location: ../index.php");
        exit();
    } finally {
        // Release database connection back to pool
        if (isset($pdo)) {
            $dbManager = DatabaseManager::getInstance();
            $dbManager->releaseConnection($pdo);
        }
    }
} else {
    // Redirect to login page if accessed directly
    header("Location: ../index.php");
    exit();
}
?>
