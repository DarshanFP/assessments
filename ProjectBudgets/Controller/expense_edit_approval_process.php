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

// Only Councillors can approve/reject expense edit requests
if ($_SESSION['role'] !== 'Councillor') {
    $_SESSION['error'] = "Only Councillors can approve or reject expense edit requests.";
    header("Location: ../../index.php");
    exit();
}

// Retrieve user information from session
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$action = "Expense Edit Approval";
$requestId = intval($_POST['request_id']);
$approvalAction = $_POST['action']; // 'approve' or 'reject'
$rejectionReason = trim($_POST['rejection_reason'] ?? '');

// Validate request ID
if ($requestId <= 0) {
    $_SESSION['error'] = "Invalid request ID.";
    header("Location: ../Blade/expense_edit_approvals.php");
    exit();
}

// Validate action
if (!in_array($approvalAction, ['approve', 'reject'])) {
    $_SESSION['error'] = "Invalid action.";
    header("Location: ../Blade/expense_edit_approvals.php");
    exit();
}

// Validate rejection reason if rejecting
if ($approvalAction === 'reject' && empty($rejectionReason)) {
    $_SESSION['error'] = "Please provide a reason for rejection.";
    header("Location: ../Blade/expense_edit_approvals.php");
    exit();
}

try {
    // Get the edit request
    $requestStmt = $pdo->prepare("
        SELECT eer.*, ee.project_id
        FROM ExpenseEditRequests eer
        LEFT JOIN ExpenseEntries ee ON eer.expense_id = ee.expense_id
        WHERE eer.request_id = :request_id AND eer.status = 'pending'
    ");
    $requestStmt->execute([':request_id' => $requestId]);
    $editRequest = $requestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$editRequest) {
        $_SESSION['error'] = "Edit request not found or already processed.";
        header("Location: ../Blade/expense_edit_approvals.php");
        exit();
    }

    // Begin a transaction
    $pdo->beginTransaction();

    if ($approvalAction === 'approve') {
        // Approve the request and apply changes
        $originalData = json_decode($editRequest['original_data'], true);
        $requestedChanges = json_decode($editRequest['requested_changes'], true);
        
        if ($editRequest['request_type'] === 'deactivate') {
            // Deactivate the expense
            $updateSql = "
                UPDATE ExpenseEntries 
                SET is_active = 0,
                    updated_at = CURRENT_TIMESTAMP
                WHERE expense_id = :expense_id
            ";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([':expense_id' => $editRequest['expense_id']]);
        } else {
            // Update the expense
            $updateSql = "
                UPDATE ExpenseEntries 
                SET particular = :particular,
                    amount_expensed = :amount_expensed,
                    expensed_at = :expensed_at,
                    remarks = :remarks,
                    updated_at = CURRENT_TIMESTAMP
                WHERE expense_id = :expense_id
            ";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':particular' => $requestedChanges['particular'],
                ':amount_expensed' => $requestedChanges['amount_expensed'],
                ':expensed_at' => $requestedChanges['expensed_at'],
                ':remarks' => $requestedChanges['remarks'] ?? '',
                ':expense_id' => $editRequest['expense_id']
            ]);
        }

        // Update the request status
        $statusSql = "
            UPDATE ExpenseEditRequests 
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

        $actionMessage = "Expense edit request approved and changes applied successfully.";
        $logMessage = "Expense edit request approved. Request ID: $requestId, Expense ID: " . $editRequest['expense_id'];

    } else {
        // Reject the request
        $statusSql = "
            UPDATE ExpenseEditRequests 
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

        $actionMessage = "Expense edit request rejected successfully.";
        $logMessage = "Expense edit request rejected. Request ID: $requestId, Expense ID: " . $editRequest['expense_id'] . ", Reason: " . $rejectionReason;
    }

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    logActivityToDatabase($userId, $action, "success", $logMessage);
    logActivityToFile("User ID $userId: " . $logMessage, "info");

    // Set success message and redirect
    $_SESSION['success'] = $actionMessage;
    header("Location: ../Blade/expense_edit_approvals.php");
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while processing the request. Error: " . $e->getMessage();
    header("Location: ../Blade/expense_edit_approvals.php");
    exit();
}
?>
