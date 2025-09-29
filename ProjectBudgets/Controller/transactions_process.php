<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/log_activity.php'; // Database logging
require_once '../../includes/logger.inc.php'; // File logging

// Enable error reporting for debugging (set to 0 in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$userId = $_SESSION['user_id'];
$projectId = intval($_POST['project_id']);
$entries = $_POST['entries'];
$globalExpenseDate = isset($_POST['global_expense_date']) ? $_POST['global_expense_date'] : date('Y-m-d');

try {
    // Begin a transaction
    $pdo->beginTransaction();

    foreach ($entries as $entry) {
        $entryId = $entry['entry_id'];
        $particular = trim($entry['particular']);
        $amount = floatval($entry['amount']);

        // Skip processing if the amount is not valid
        if ($amount <= 0) {
            continue;
        }

        if ($entryId === 'new') {
            // Insert a new budget entry if it's a new row with a new particular
            $insertBudgetStmt = $pdo->prepare("
                INSERT INTO BudgetEntries (project_id, particular, rate_quantity, rate_multiplier, rate_duration, amount_this_phase)
                VALUES (:project_id, :particular, 0, 1, 1, :amount)
            ");
            $insertBudgetStmt->execute([
                ':project_id' => $projectId,
                ':particular' => $particular,
                ':amount' => $amount
            ]);

            // Get the newly inserted entry ID
            $newEntryId = $pdo->lastInsertId();

            // Insert the corresponding expense entry
            $insertExpenseStmt = $pdo->prepare("
                INSERT INTO ExpenseEntries (project_id, entry_id, particular, amount_expensed, expensed_at, created_by)
                VALUES (:project_id, :entry_id, :particular, :amount, :expensed_at, :created_by)
            ");
            $insertExpenseStmt->execute([
                ':project_id' => $projectId,
                ':entry_id' => $newEntryId,
                ':particular' => $particular,
                ':amount' => $amount,
                ':expensed_at' => $globalExpenseDate,
                ':created_by' => $userId
            ]);

        } else {
            // Validate the allocated amount for the existing entry
            $validationStmt = $pdo->prepare("
                SELECT amount_this_phase, IFNULL(SUM(ee.amount_expensed), 0) AS total_expensed
                FROM BudgetEntries be
                LEFT JOIN ExpenseEntries ee ON be.entry_id = ee.entry_id
                WHERE be.entry_id = :entry_id
                GROUP BY be.entry_id
            ");
            $validationStmt->execute([':entry_id' => $entryId]);
            $entryData = $validationStmt->fetch(PDO::FETCH_ASSOC);

            if (!$entryData) {
                throw new Exception("Invalid entry ID: $entryId.");
            }

            $allocatedAmount = $entryData['amount_this_phase'];
            $totalExpensed = $entryData['total_expensed'];
            $availableFunds = $allocatedAmount - $totalExpensed;

            // Check if the expense exceeds available funds
            if ($amount > $availableFunds) {
                throw new Exception("Expense of Rs $amount exceeds available funds of Rs $availableFunds for particular: $particular.");
            }

            // Insert the expense entry for the existing budget entry
            $insertExpenseStmt = $pdo->prepare("
                INSERT INTO ExpenseEntries (project_id, entry_id, particular, amount_expensed, expensed_at, created_by)
                VALUES (:project_id, :entry_id, :particular, :amount, :expensed_at, :created_by)
            ");
            $insertExpenseStmt->execute([
                ':project_id' => $projectId,
                ':entry_id' => $entryId,
                ':particular' => $particular,
                ':amount' => $amount,
                ':expensed_at' => $globalExpenseDate,
                ':created_by' => $userId
            ]);
        }
    }

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    logActivityToDatabase($userId, "Transactions Entry", "success", "Expenses recorded successfully for project ID: $projectId.");
    logActivityToFile("User ID $userId: Expenses recorded successfully for project ID: $projectId.", "info");

    // Return success response
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, "Transactions Entry", "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Return error response
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
