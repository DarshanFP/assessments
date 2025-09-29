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
$action = "Expense Edit Request";
$expenseId = intval($_POST['expense_id']);
$requestAction = $_POST['action'] ?? 'edit'; // 'edit' or 'deactivate'

// Validate expense ID
if ($expenseId <= 0) {
    $_SESSION['error'] = "Invalid expense ID.";
    header("Location: ../Blade/all_transactions.php");
    exit();
}

// Only Project In-Charges can submit edit requests
if ($userRole === 'Councillor') {
    $_SESSION['error'] = "Councillors can edit expenses directly. Use the regular edit form.";
    header("Location: ../Blade/expense_edit_form.php?expense_id=" . $expenseId);
    exit();
}

// Retrieve form data (for edit requests)
$particular = trim($_POST['particular'] ?? '');
$amountExpensed = floatval($_POST['amount_expensed'] ?? 0);
$expensedAt = $_POST['expensed_at'] ?? '';
$remarks = trim($_POST['remarks'] ?? '');

// Validate form input for edit requests
if ($requestAction === 'edit') {
    if (empty($particular) || $amountExpensed <= 0 || empty($expensedAt)) {
        $_SESSION['error'] = "Please provide all required fields and ensure the amount is greater than zero.";
        header("Location: ../Blade/expense_edit_form.php?expense_id=" . $expenseId);
        exit();
    }
}

try {
    // Get the expense
    $expenseStmt = $pdo->prepare("
        SELECT ee.*, p.project_name, p.project_id
        FROM ExpenseEntries ee
        LEFT JOIN Projects p ON ee.project_id = p.project_id
        WHERE ee.expense_id = :expense_id AND ee.is_active = 1
    ");
    $expenseStmt->execute([':expense_id' => $expenseId]);
    $existingExpense = $expenseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingExpense) {
        $_SESSION['error'] = "Expense not found or has been deactivated.";
        header("Location: ../Blade/all_transactions.php");
        exit();
    }

    // Check if user is assigned to this project
    $assignmentStmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM ProjectAssignments 
        WHERE project_id = :project_id 
        AND project_incharge_id = :user_id 
        AND is_active = 1
    ");
    $assignmentStmt->execute([':project_id' => $existingExpense['project_id'], ':user_id' => $userId]);
    $isAssigned = $assignmentStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

    if (!$isAssigned) {
        $_SESSION['error'] = "You don't have permission to request edits for this expense.";
        header("Location: ../Blade/all_transactions.php");
        exit();
    }

    // Prepare requested changes based on action type
    if ($requestAction === 'edit') {
        $requestedChanges = [
            'particular' => $particular,
            'amount_expensed' => $amountExpensed,
            'expensed_at' => $expensedAt,
            'remarks' => $remarks
        ];
    } else {
        // Deactivate request
        $requestedChanges = [
            'is_active' => 0
        ];
    }

    // Begin a transaction
    $pdo->beginTransaction();

    // Insert the edit request
    $requestSql = "
        INSERT INTO ExpenseEditRequests (
            expense_id, requested_by, request_type, status, 
            original_data, requested_changes, requested_at
        ) VALUES (
            :expense_id, :requested_by, :request_type, 'pending',
            :original_data, :requested_changes, CURRENT_TIMESTAMP
        )
    ";
    $requestStmt = $pdo->prepare($requestSql);
    $requestStmt->execute([
        ':expense_id' => $expenseId,
        ':requested_by' => $userId,
        ':request_type' => $requestAction,
        ':original_data' => json_encode($existingExpense),
        ':requested_changes' => json_encode($requestedChanges)
    ]);

    // Get the request ID
    $requestId = $pdo->lastInsertId();

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    $actionMessage = $requestAction === 'edit' ? 'edit request' : 'deactivation request';
    logActivityToDatabase($userId, $action, "success", "Expense $actionMessage submitted successfully. Request ID: $requestId, Expense ID: $expenseId");
    logActivityToFile("User ID $userId: Expense $actionMessage submitted successfully. Request ID: $requestId, Expense ID: $expenseId", "info");

    // Create notification for Councillors
    require_once '../../includes/NotificationManager.php';
    $expenseDescription = $existingExpense['description'] ?? 'Expense #' . $expenseId;
    NotificationManager::createExpenseEditNotification($expenseId, $userId, $expenseDescription);

    // Set success message and redirect
    $successMessage = $requestAction === 'edit' 
        ? "Your expense edit request has been submitted successfully and is pending approval from a Councillor."
        : "Your expense deactivation request has been submitted successfully and is pending approval from a Councillor.";
    
    $_SESSION['success'] = $successMessage;
    header("Location: ../Blade/all_transactions.php?project_id=" . $existingExpense['project_id']);
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while submitting the request. Error: " . $e->getMessage();
    header("Location: ../Blade/expense_edit_form.php?expense_id=" . $expenseId);
    exit();
}
?>
