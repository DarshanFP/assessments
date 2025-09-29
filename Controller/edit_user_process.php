<?php
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/log_activity.php';
require_once '../includes/logger.inc.php';

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../View/user_list.php");
    exit();
}

$userId = $_POST['id'];
$fullName = $_POST['full_name'];
$email = $_POST['email'];
$role = $_POST['role'];
$community = $_POST['community'];

try {
    $stmt = $pdo->prepare("UPDATE ssmntUsers SET full_name = :full_name, email = :email, role = :role, community = :community WHERE id = :id");
    $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':role' => $role,
        ':community' => $community,
        ':id' => $userId
    ]);

    logActivityToDatabase($_SESSION['user_id'], "Edit User", "success", "User ID $userId updated successfully.");
    logActivityToFile("User ID {$_SESSION['user_id']} updated user ID $userId.", "info");

    $_SESSION['success'] = "User updated successfully.";
    header("Location: ../View/user_list.php");
    exit();
} catch (Exception $e) {
    logActivityToDatabase($_SESSION['user_id'], "Edit User", "error", $e->getMessage());
    logActivityToFile("Error updating user ID $userId: " . $e->getMessage(), "error");

    $_SESSION['error'] = "Failed to update user. Error: " . $e->getMessage();
    header("Location: ../View/edit_user.php?id=$userId");
    exit();
}
?>
