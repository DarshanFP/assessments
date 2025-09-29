<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();

// Include required files
require_once '../includes/dbh.inc.php'; // Database connection
require_once '../includes/log_activity.php'; // Log activity
require_once '../includes/logger.inc.php'; // File logging

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    $action = "Recommendation Action";

    // Check if user is logged in
    if (!$userId) {
        $_SESSION['error'] = "User not logged in.";
        header("Location: ../index.php");
        exit();
    }

    // Check if user is Project In-Charge
    if ($_SESSION['role'] !== 'Project In-Charge') {
        $_SESSION['error'] = "Access denied. This action is for Project In-Charge users only.";
        header("Location: ../View/dashboard.php");
        exit();
    }

    try {
        // Begin a transaction
        $pdo->beginTransaction();

        // Retrieve form data
        $recommendationId = $_POST['recommendation_id'] ?? null;
        $actionTaken = $_POST['action_taken'] ?? null;
        $actionDate = $_POST['action_date'] ?? null;
        $comments = $_POST['comments'] ?? null;
        $newStatus = $_POST['new_status'] ?? null;

        // Validate required fields
        if (!$recommendationId || !$actionTaken || !$actionDate || !$newStatus) {
            throw new Exception("All required fields must be filled.");
        }

        // Verify recommendation exists and user has access
        $sqlCheck = "SELECT 
                        cr.*,
                        a.Community
                     FROM CentreRecommendations cr
                     INNER JOIN Assessment a ON cr.keyID = a.keyID
                     WHERE cr.id = :id";
        
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([':id' => $recommendationId]);
        $recommendation = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$recommendation) {
            throw new Exception("Recommendation not found.");
        }

        // Check if user has access to this recommendation (same community)
        if ($_SESSION['community'] !== $recommendation['Community']) {
            throw new Exception("Access denied. This recommendation is not for your community.");
        }

        // Insert action into RecommendationActions table
        $sqlAction = "INSERT INTO RecommendationActions (
                        recommendation_id, action_taken, action_by, 
                        action_date, comments
                    ) VALUES (
                        :recommendation_id, :action_taken, :action_by, 
                        :action_date, :comments
                    )";

        $stmtAction = $pdo->prepare($sqlAction);
        $stmtAction->execute([
            ':recommendation_id' => $recommendationId,
            ':action_taken' => $actionTaken,
            ':action_by' => $userId,
            ':action_date' => $actionDate,
            ':comments' => $comments
        ]);

        // Update recommendation status
        $sqlUpdate = "UPDATE CentreRecommendations 
                     SET status = :status, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = :id";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':status' => $newStatus,
            ':id' => $recommendationId
        ]);

        // Commit the transaction
        $pdo->commit();

        // Log the activity
        logActivityToDatabase($userId, $action, "success", "Action added to recommendation ID: $recommendationId");
        $_SESSION['success'] = "Action added successfully.";
        header("Location: ../View/RecommendationDetail.php?id=" . $recommendationId);
        exit();

    } catch (Exception $e) {
        // Roll back the transaction in case of an error
        $pdo->rollBack();
        logActivityToDatabase($userId, $action, "error", $e->getMessage());
        $_SESSION['error'] = "An error occurred while saving the action. Error: " . $e->getMessage();
        header("Location: ../View/AddRecommendationAction.php?id=" . $recommendationId);
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../View/RecommendationsList.php");
    exit();
}
?>
