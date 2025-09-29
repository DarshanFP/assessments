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

// Only Councillors can approve/reject edit requests
if ($_SESSION['role'] !== 'Councillor') {
    $_SESSION['error'] = "Only Councillors can approve or reject edit requests.";
    header("Location: ../../index.php");
    exit();
}

// Retrieve user information from session
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$action = "Project Edit Approval";
$requestId = intval($_POST['request_id']);
$approvalAction = $_POST['action']; // 'approve' or 'reject'
$rejectionReason = trim($_POST['rejection_reason'] ?? '');

// Validate request ID
if ($requestId <= 0) {
    $_SESSION['error'] = "Invalid request ID.";
    header("Location: ../Blade/project_edit_approvals.php");
    exit();
}

// Validate action
if (!in_array($approvalAction, ['approve', 'reject'])) {
    $_SESSION['error'] = "Invalid action.";
    header("Location: ../Blade/project_edit_approvals.php");
    exit();
}

// Validate rejection reason if rejecting
if ($approvalAction === 'reject' && empty($rejectionReason)) {
    $_SESSION['error'] = "Please provide a reason for rejection.";
    header("Location: ../Blade/project_edit_approvals.php");
    exit();
}

try {
    // Get the edit request
    $requestStmt = $pdo->prepare("
        SELECT * FROM ProjectEditRequests 
        WHERE request_id = :request_id AND status = 'pending'
    ");
    $requestStmt->execute([':request_id' => $requestId]);
    $editRequest = $requestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$editRequest) {
        $_SESSION['error'] = "Edit request not found or already processed.";
        header("Location: ../Blade/project_edit_approvals.php");
        exit();
    }

    // Begin a transaction
    $pdo->beginTransaction();

    if ($approvalAction === 'approve') {
        // Approve the request and apply changes
        $originalData = json_decode($editRequest['original_data'], true);
        $requestedChanges = json_decode($editRequest['requested_changes'], true);
        
        // Update the project
        $updateSql = "
            UPDATE Projects 
            SET project_name = :project_name,
                project_center = :project_center,
                organization_id = :organization_id,
                project_incharge = :project_incharge,
                total_budget = :total_budget,
                start_date = :start_date,
                end_date = :end_date,
                funding_source = :funding_source,
                fund_type = :fund_type,
                remarks = :remarks,
                updated_at = CURRENT_TIMESTAMP
            WHERE project_id = :project_id
        ";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':project_name' => $requestedChanges['project_name'],
            ':project_center' => $requestedChanges['project_center'],
            ':organization_id' => $requestedChanges['organization_id'],
            ':project_incharge' => $requestedChanges['project_incharge'],
            ':total_budget' => $requestedChanges['total_budget'],
            ':start_date' => $requestedChanges['start_date'],
            ':end_date' => $requestedChanges['end_date'],
            ':funding_source' => $requestedChanges['funding_source'],
            ':fund_type' => $requestedChanges['fund_type'],
            ':remarks' => $requestedChanges['remarks'],
            ':project_id' => $editRequest['project_id']
        ]);

        // Update budget entries if provided
        if (!empty($requestedChanges['budget_entries'])) {
            // Delete existing budget entries
            $deleteStmt = $pdo->prepare("DELETE FROM BudgetEntries WHERE project_id = :project_id");
            $deleteStmt->execute([':project_id' => $editRequest['project_id']]);

            // Insert new budget entries
            $entrySql = "
                INSERT INTO BudgetEntries (project_id, particular, rate_quantity, rate_multiplier, rate_duration, amount_this_phase)
                VALUES (:project_id, :particular, :rate_quantity, :rate_multiplier, :rate_duration, :amount_this_phase)
            ";
            $entryStmt = $pdo->prepare($entrySql);

            foreach ($requestedChanges['budget_entries'] as $entry) {
                $entryStmt->execute([
                    ':project_id' => $editRequest['project_id'],
                    ':particular' => $entry['particular'],
                    ':rate_quantity' => $entry['rate_quantity'],
                    ':rate_multiplier' => $entry['rate'],
                    ':rate_duration' => $entry['rate_duration'],
                    ':amount_this_phase' => $entry['amount_this_phase']
                ]);
            }
        }

        // Update the request status
        $statusSql = "
            UPDATE ProjectEditRequests 
            SET status = 'approved',
                approved_by = :approved_by,
                approved_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE request_id = :request_id
        ";
        $statusStmt = $pdo->prepare($statusSql);
        $statusStmt->execute([
            ':approved_by' => $userId,
            ':request_id' => $requestId
        ]);

        $actionMessage = "Edit request approved and changes applied successfully.";
        $logMessage = "Edit request approved. Request ID: $requestId, Project ID: " . $editRequest['project_id'];

    } else {
        // Reject the request
        $statusSql = "
            UPDATE ProjectEditRequests 
            SET status = 'rejected',
                approved_by = :approved_by,
                approved_at = CURRENT_TIMESTAMP,
                rejection_reason = :rejection_reason,
                updated_at = CURRENT_TIMESTAMP
            WHERE request_id = :request_id
        ";
        $statusStmt = $pdo->prepare($statusSql);
        $statusStmt->execute([
            ':approved_by' => $userId,
            ':rejection_reason' => $rejectionReason,
            ':request_id' => $requestId
        ]);

        $actionMessage = "Edit request rejected successfully.";
        $logMessage = "Edit request rejected. Request ID: $requestId, Project ID: " . $editRequest['project_id'] . ", Reason: " . $rejectionReason;
    }

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    logActivityToDatabase($userId, $action, "success", $logMessage);
    logActivityToFile("User ID $userId: " . $logMessage, "info");

    // Clear related notifications
    require_once '../../includes/NotificationManager.php';
    NotificationManager::clearApprovalNotifications($editRequest['project_id'], 'project');

    // Set success message and redirect
    $_SESSION['success'] = $actionMessage;
    header("Location: ../Blade/project_edit_approvals.php");
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while processing the request. Error: " . $e->getMessage();
    header("Location: ../Blade/project_edit_approvals.php");
    exit();
}
?>
