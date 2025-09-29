<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/log_activity.php'; // Database logging
require_once '../../includes/logger.inc.php'; // File logging

// Enable error reporting for debugging (set to 0 in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: ../../index.php");
    exit();
}

// Retrieve user information from session
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$action = "Project Deactivate";

// Get project ID from POST or GET
$projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : (isset($_GET['project_id']) ? intval($_GET['project_id']) : 0);

// Validate project ID
if ($projectId <= 0) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: ../Blade/all_projects.php");
    exit();
}

try {
    // Check if project exists and get details
    $projectStmt = $pdo->prepare("
        SELECT project_id, project_name, project_incharge, is_active 
        FROM Projects 
        WHERE project_id = :project_id
    ");
    $projectStmt->execute([':project_id' => $projectId]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        $_SESSION['error'] = "Project not found.";
        header("Location: ../Blade/all_projects.php");
        exit();
    }

    // Check permissions - only project in-charge or councillor can deactivate
    if ($userRole !== 'Councillor' && $project['project_incharge'] != $userId) {
        $_SESSION['error'] = "You don't have permission to deactivate this project.";
        if ($userRole === 'Councillor') {
            header("Location: ../Blade/all_projects.php");
        } else {
            header("Location: ../Blade/my_projects.php");
        }
        exit();
    }

    // Check if project is already deactivated
    if (!$project['is_active']) {
        $_SESSION['error'] = "Project is already deactivated.";
        if ($userRole === 'Councillor') {
            header("Location: ../Blade/all_projects.php");
        } else {
            header("Location: ../Blade/my_projects.php");
        }
        exit();
    }

    // Begin a transaction
    $pdo->beginTransaction();

    // Deactivate the project (soft delete)
    $deactivateStmt = $pdo->prepare("
        UPDATE Projects 
        SET is_active = 0, 
            updated_at = CURRENT_TIMESTAMP
        WHERE project_id = :project_id
    ");
    $deactivateStmt->execute([':project_id' => $projectId]);

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    logActivityToDatabase($userId, $action, "success", "Project deactivated successfully. Project ID: $projectId, Project Name: " . $project['project_name']);
    logActivityToFile("User ID $userId: Project deactivated successfully. Project ID: $projectId, Project Name: " . $project['project_name'], "info");

    // Set success message and redirect based on user role
    $_SESSION['success'] = "Project '" . $project['project_name'] . "' has been deactivated successfully.";
    
    // Redirect based on user role
    if ($userRole === 'Councillor') {
        header("Location: ../Blade/all_projects.php");
    } else {
        header("Location: ../Blade/my_projects.php");
    }
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while deactivating the project. Error: " . $e->getMessage();
    if ($userRole === 'Councillor') {
        header("Location: ../Blade/all_projects.php");
    } else {
        header("Location: ../Blade/my_projects.php");
    }
    exit();
}
?>
