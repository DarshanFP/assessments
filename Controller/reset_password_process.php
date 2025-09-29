<?php
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/log_activity.php';
require_once '../includes/logger.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: user_list.php");
    exit();
}

$userId = $_POST['user_id'];
$newPassword = trim($_POST['new_password']);
$confirmPassword = trim($_POST['confirm_password']);
$action = "Password Reset";

if (empty($newPassword) || empty($confirmPassword)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: reset_password.php?id=$userId");
    exit();
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: reset_password.php?id=$userId");
    exit();
}

try {
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password in the database
    $stmt = $pdo->prepare("UPDATE ssmntUsers SET password = :password WHERE id = :id");
    $stmt->execute([':password' => $hashedPassword, ':id' => $userId]);

    // Log the activity
    logActivityToDatabase($_SESSION['user_id'], $action, 'success', "Password reset for user ID $userId");
    logActivityToFile("User ID {$_SESSION['user_id']} reset the password for user ID $userId", "info");

    $_SESSION['success'] = "Password reset successfully.";
    header("Location: ../View/user_list.php");
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    logActivityToDatabase($_SESSION['user_id'], $action, 'error', $e->getMessage());
    logActivityToFile("Database error during password reset: " . $e->getMessage(), "error");

    header("Location: reset_password.php?id=$userId");
    exit();
}
?>
