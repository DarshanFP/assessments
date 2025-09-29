<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/log_activity.php';

// Ensure the user is logged in and is a Councillor
if (!isLoggedIn() || $_SESSION['role'] !== 'Councillor') {
    header("Location: ../../index.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$requestId = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$action = $_POST['action']; // 'approve' or 'reject'
$rejectionReason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';

if (!$requestId) {
    $_SESSION['error'] = "Invalid request ID.";
    header("Location: ../Blade/activity_approvals.php");
    exit();
}

if ($action === 'reject' && empty($rejectionReason)) {
    $_SESSION['error'] = "Rejection reason is required.";
    header("Location: ../Blade/activity_approvals.php");
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Fetch the edit request
    $stmt = $pdo->prepare("
        SELECT aer.*, a.activity_title
        FROM ActivityEditRequests aer
        LEFT JOIN Activities a ON aer.activity_id = a.activity_id
        WHERE aer.request_id = :request_id AND aer.status = 'pending'
    ");
    $stmt->execute([':request_id' => $requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception("Edit request not found or already processed.");
    }
    
    if ($action === 'approve') {
        // Apply the requested changes to the activity
        $requestedChanges = json_decode($request['requested_changes'], true);
        
        $updateStmt = $pdo->prepare("
            UPDATE Activities SET
                activity_title = :activity_title,
                funding_source = :funding_source,
                funding_source_other = :funding_source_other,
                organization_id = :organization_id,
                project_id = :project_id,
                activity_date = :activity_date,
                place = :place,
                conducted_for = :conducted_for,
                number_of_participants = :number_of_participants,
                is_collaboration = :is_collaboration,
                collaborator_organization = :collaborator_organization,
                collaborator_name = :collaborator_name,
                collaborator_position = :collaborator_position,
                immediate_outcome = :immediate_outcome,
                long_term_impact = :long_term_impact,
                updated_at = CURRENT_TIMESTAMP
            WHERE activity_id = :activity_id
        ");
        
        $updateStmt->execute([
            ':activity_title' => $requestedChanges['activity_title'],
            ':funding_source' => $requestedChanges['funding_source'],
            ':funding_source_other' => $requestedChanges['funding_source_other'],
            ':organization_id' => $requestedChanges['organization_id'],
            ':project_id' => $requestedChanges['project_id'],
            ':activity_date' => $requestedChanges['activity_date'],
            ':place' => $requestedChanges['place'],
            ':conducted_for' => $requestedChanges['conducted_for'],
            ':number_of_participants' => $requestedChanges['number_of_participants'],
            ':is_collaboration' => $requestedChanges['is_collaboration'],
            ':collaborator_organization' => $requestedChanges['collaborator_organization'],
            ':collaborator_name' => $requestedChanges['collaborator_name'],
            ':collaborator_position' => $requestedChanges['collaborator_position'],
            ':immediate_outcome' => $requestedChanges['immediate_outcome'],
            ':long_term_impact' => $requestedChanges['long_term_impact'],
            ':activity_id' => $request['activity_id']
        ]);
        
        // Update the request status to approved
        $statusStmt = $pdo->prepare("
            UPDATE ActivityEditRequests SET
                status = 'approved',
                approved_by = :approved_by,
                approved_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE request_id = :request_id
        ");
        
        $statusStmt->execute([
            ':approved_by' => $currentUserId,
            ':request_id' => $requestId
        ]);
        
        // Log activity
        $logMessage = "Activity edit request approved for '{$request['activity_title']}' by Councillor ID {$currentUserId}";
        logActivity($currentUserId, 'activity_edit_approval', $logMessage);
        
        $_SESSION['success'] = "Activity edit request approved successfully!";
        
    } else {
        // Reject the request
        $statusStmt = $pdo->prepare("
            UPDATE ActivityEditRequests SET
                status = 'rejected',
                approved_by = :approved_by,
                approved_at = CURRENT_TIMESTAMP,
                rejection_reason = :rejection_reason,
                updated_at = CURRENT_TIMESTAMP
            WHERE request_id = :request_id
        ");
        
        $statusStmt->execute([
            ':approved_by' => $currentUserId,
            ':rejection_reason' => $rejectionReason,
            ':request_id' => $requestId
        ]);
        
        // Log activity
        $logMessage = "Activity edit request rejected for '{$request['activity_title']}' by Councillor ID {$currentUserId}. Reason: {$rejectionReason}";
        logActivity($currentUserId, 'activity_edit_rejection', $logMessage);
        
        $_SESSION['success'] = "Activity edit request rejected successfully!";
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect back to approvals page
    header("Location: ../Blade/activity_approvals.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log("Activity edit approval error: " . $e->getMessage());
    
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: ../Blade/activity_approvals.php");
    exit();
}
?>
