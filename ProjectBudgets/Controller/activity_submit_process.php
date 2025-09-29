<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/log_activity.php';

// Ensure the user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$activityId = isset($_POST['activity_id']) ? (int)$_POST['activity_id'] : 0;

if (!$activityId) {
    $_SESSION['error'] = "Invalid activity ID.";
    header("Location: ../Blade/activities_list.php");
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify activity exists and user has permission
    $stmt = $pdo->prepare("
        SELECT * FROM Activities 
        WHERE activity_id = :activity_id 
        AND created_by = :user_id 
        AND status = 'draft'
    ");
    $stmt->execute([
        ':activity_id' => $activityId,
        ':user_id' => $currentUserId
    ]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        throw new Exception("Activity not found or cannot be submitted.");
    }
    
    // Update activity status to submitted
    $updateStmt = $pdo->prepare("
        UPDATE Activities SET
            status = 'submitted',
            submitted_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE activity_id = :activity_id
    ");
    
    $updateStmt->execute([':activity_id' => $activityId]);
    
    // Commit transaction
    $pdo->commit();
    
    // Log activity
    $logMessage = "Activity '{$activity['activity_title']}' submitted by user ID {$currentUserId}";
    logActivity($currentUserId, 'activity_submit', $logMessage);
    
    // Set success message
    $_SESSION['success'] = "Activity submitted successfully!";
    
    // Redirect to activities list
    header("Location: ../Blade/activities_list.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log("Activity submit error: " . $e->getMessage());
    
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: ../Blade/activities_list.php");
    exit();
}
?>
