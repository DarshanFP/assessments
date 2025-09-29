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
$action = "Expense Edit";
$expenseId = intval($_POST['expense_id']);
$projectId = intval($_POST['project_id']);

// Validate expense ID
if ($expenseId <= 0) {
    $_SESSION['error'] = "Invalid expense ID.";
    header("Location: ../Blade/all_transactions.php");
    exit();
}

// Only Councillors can edit expenses directly
if ($userRole !== 'Councillor') {
    $_SESSION['error'] = "Only Councillors can edit expenses directly. Project In-Charges must submit edit requests.";
    header("Location: ../Blade/expense_edit_form.php?expense_id=" . $expenseId);
    exit();
}

// Retrieve form data
$particular = trim($_POST['particular']);
$amountExpensed = floatval($_POST['amount_expensed']);
$expensedAt = $_POST['expensed_at'];
$remarks = trim($_POST['remarks'] ?? '');

// Validate form input
if (empty($particular) || $amountExpensed <= 0 || empty($expensedAt)) {
    $_SESSION['error'] = "Please provide all required fields and ensure the amount is greater than zero.";
    header("Location: ../Blade/expense_edit_form.php?expense_id=" . $expenseId);
    exit();
}

try {
    // Check if expense exists
    $expenseStmt = $pdo->prepare("
        SELECT * FROM ExpenseEntries 
        WHERE expense_id = :expense_id AND is_active = 1
    ");
    $expenseStmt->execute([':expense_id' => $expenseId]);
    $existingExpense = $expenseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingExpense) {
        $_SESSION['error'] = "Expense not found or has been deactivated.";
        header("Location: ../Blade/all_transactions.php");
        exit();
    }

    // Begin a transaction
    $pdo->beginTransaction();

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
        ':particular' => $particular,
        ':amount_expensed' => $amountExpensed,
        ':expensed_at' => $expensedAt,
        ':remarks' => $remarks,
        ':expense_id' => $expenseId
    ]);

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    logActivityToDatabase($userId, $action, "success", "Expense updated successfully. Expense ID: $expenseId");
    logActivityToFile("User ID $userId: Expense updated successfully. Expense ID: $expenseId", "info");

    // Set success message and redirect
    $_SESSION['success'] = "Expense updated successfully.";
    header("Location: ../Blade/all_transactions.php?project_id=" . $projectId);
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while updating the expense. Error: " . $e->getMessage();
    header("Location: ../Blade/expense_edit_form.php?expense_id=" . $expenseId);
    exit();
}
?>
