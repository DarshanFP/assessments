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
$attachmentId = isset($_POST['attachment_id']) ? (int)$_POST['attachment_id'] : 0;

if (!$attachmentId) {
    $_SESSION['error'] = "Invalid attachment ID.";
    header("Location: ../Blade/activities_list.php");
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify attachment exists and user has permission
    $stmt = $pdo->prepare("
        SELECT aa.*, a.activity_title, a.created_by
        FROM ActivityAttachments aa
        LEFT JOIN Activities a ON aa.activity_id = a.activity_id
        WHERE aa.attachment_id = :attachment_id
    ");
    $stmt->execute([':attachment_id' => $attachmentId]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$attachment) {
        throw new Exception("Attachment not found.");
    }
    
    // Check permissions
    $canRemove = ($_SESSION['role'] === 'Councillor') || ($attachment['created_by'] == $currentUserId);
    
    if (!$canRemove) {
        throw new Exception("You don't have permission to remove this photo.");
    }
    
    // Delete the file from filesystem
    if (file_exists($attachment['file_path'])) {
        unlink($attachment['file_path']);
    }
    
    // Delete the attachment record
    $deleteStmt = $pdo->prepare("DELETE FROM ActivityAttachments WHERE attachment_id = :attachment_id");
    $deleteStmt->execute([':attachment_id' => $attachmentId]);
    
    // Commit transaction
    $pdo->commit();
    
    // Log activity
    $logMessage = "Photo removed from activity '{$attachment['activity_title']}' by user ID {$currentUserId}";
    logActivity($currentUserId, 'activity_photo_remove', $logMessage);
    
    // Set success message
    $_SESSION['success'] = "Photo removed successfully!";
    
    // Redirect back to edit form
    header("Location: ../Blade/activity_edit_form.php?id=" . $attachment['activity_id']);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log("Activity photo remove error: " . $e->getMessage());
    
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: ../Blade/activities_list.php");
    exit();
}
?>
