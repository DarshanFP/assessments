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
$action = "Expense Deactivate";

// Get expense ID from POST
$expenseId = isset($_POST['expense_id']) ? intval($_POST['expense_id']) : 0;

// Validate expense ID
if ($expenseId <= 0) {
    $_SESSION['error'] = "Invalid expense ID.";
    header("Location: ../Blade/all_transactions.php");
    exit();
}

// Only Councillors can deactivate expenses directly
if ($userRole !== 'Councillor') {
    $_SESSION['error'] = "Only Councillors can deactivate expenses directly. Project In-Charges must submit deactivation requests.";
    header("Location: ../Blade/all_transactions.php");
    exit();
}

try {
    // Check if expense exists and get details
    $expenseStmt = $pdo->prepare("
        SELECT ee.*, p.project_name, p.project_id
        FROM ExpenseEntries ee
        LEFT JOIN Projects p ON ee.project_id = p.project_id
        WHERE ee.expense_id = :expense_id AND ee.is_active = 1
    ");
    $expenseStmt->execute([':expense_id' => $expenseId]);
    $expense = $expenseStmt->fetch(PDO::FETCH_ASSOC);

    if (!$expense) {
        $_SESSION['error'] = "Expense not found or has been deactivated.";
        header("Location: ../Blade/all_transactions.php");
        exit();
    }

    // Begin a transaction
    $pdo->beginTransaction();

    // Deactivate the expense (soft delete)
    $deactivateStmt = $pdo->prepare("
        UPDATE ExpenseEntries 
        SET is_active = 0, 
            updated_at = CURRENT_TIMESTAMP
        WHERE expense_id = :expense_id
    ");
    $deactivateStmt->execute([':expense_id' => $expenseId]);

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    logActivityToDatabase($userId, $action, "success", "Expense deactivated successfully. Expense ID: $expenseId, Particular: " . $expense['particular']);
    logActivityToFile("User ID $userId: Expense deactivated successfully. Expense ID: $expenseId, Particular: " . $expense['particular'], "info");

    // Set success message and redirect
    $_SESSION['success'] = "Expense '" . $expense['particular'] . "' has been deactivated successfully.";
    header("Location: ../Blade/all_transactions.php?project_id=" . $expense['project_id']);
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while deactivating the expense. Error: " . $e->getMessage();
    header("Location: ../Blade/all_transactions.php");
    exit();
}
?>
