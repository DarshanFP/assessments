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
$action = "Project Edit";
$projectId = intval($_POST['project_id']);

// Validate project ID
if ($projectId <= 0) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: ../Blade/all_projects.php");
    exit();
}

// Retrieve form data
$projectName = trim($_POST['project_name']);
$projectCenter = trim($_POST['project_center']);
$organizationId = intval($_POST['organization_id']);
$totalBudget = floatval($_POST['total_budget']);
$startDate = $_POST['start_date'] ?? null;
$endDate = $_POST['end_date'] ?? null;
$fundingSource = trim($_POST['funding_source'] ?? '');
$fundType = trim($_POST['fund_type'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');

// Determine the Project In-Charge
$projectInCharge = $userId;
if ($userRole === 'Councillor') {
    $projectInCharge = intval($_POST['project_incharge']);
} elseif (!empty($_POST['project_incharge_other'])) {
    $projectInCharge = intval($_POST['project_incharge_other']);
}

// Validate form input
if (empty($projectName) || empty($projectCenter) || $organizationId <= 0 || $totalBudget <= 0) {
    $_SESSION['error'] = "Please provide all required fields and ensure the budget is greater than zero.";
    header("Location: ../Blade/project_edit_form.php?project_id=" . $projectId);
    exit();
}

// Validate organization exists and is active
$orgStmt = $pdo->prepare("SELECT organization_id, organization_name FROM Organizations WHERE organization_id = :id AND is_active = 1");
$orgStmt->execute([':id' => $organizationId]);
$organization = $orgStmt->fetch(PDO::FETCH_ASSOC);

if (!$organization) {
    $_SESSION['error'] = "Please select a valid active organization.";
    header("Location: ../Blade/project_edit_form.php?project_id=" . $projectId);
    exit();
}

// Check if project exists and user has permission to edit
$projectCheckStmt = $pdo->prepare("
    SELECT project_id, project_incharge, is_active 
    FROM Projects 
    WHERE project_id = :project_id AND is_active = 1
");
$projectCheckStmt->execute([':project_id' => $projectId]);
$existingProject = $projectCheckStmt->fetch(PDO::FETCH_ASSOC);

if (!$existingProject) {
    $_SESSION['error'] = "Project not found or has been deactivated.";
    header("Location: ../Blade/all_projects.php");
    exit();
}

// Check permissions - only project in-charge or councillor can edit
if ($userRole !== 'Councillor' && $existingProject['project_incharge'] != $userId) {
    $_SESSION['error'] = "You don't have permission to edit this project.";
    header("Location: ../Blade/my_projects.php");
    exit();
}

try {
    // Begin a transaction
    $pdo->beginTransaction();

    // Update the project in the Projects table
    $projectSql = "
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
    $projectStmt = $pdo->prepare($projectSql);
    $projectStmt->execute([
        ':project_name' => $projectName,
        ':project_center' => $projectCenter,
        ':organization_id' => $organizationId,
        ':project_incharge' => $projectInCharge,
        ':total_budget' => $totalBudget,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':funding_source' => $fundingSource,
        ':fund_type' => $fundType,
        ':remarks' => $remarks,
        ':project_id' => $projectId,
    ]);

    // Handle budget entries
    $budgetEntries = $_POST['budget_entries'] ?? [];
    
    if (!empty($budgetEntries)) {
        // First, delete existing budget entries for this project
        $deleteStmt = $pdo->prepare("DELETE FROM BudgetEntries WHERE project_id = :project_id");
        $deleteStmt->execute([':project_id' => $projectId]);

        // Insert updated budget entries
        $entrySql = "
            INSERT INTO BudgetEntries (project_id, particular, rate_quantity, rate_multiplier, rate_duration, amount_this_phase)
            VALUES (:project_id, :particular, :rate_quantity, :rate_multiplier, :rate_duration, :amount_this_phase)
        ";
        $entryStmt = $pdo->prepare($entrySql);

        foreach ($budgetEntries as $index => $entry) {
            $particular = trim($entry['particular']);
            $rate = floatval($entry['rate']);
            $quantity = floatval($entry['rate_quantity']);
            $duration = intval($entry['rate_duration']);
            $amountThisPhase = floatval($entry['amount_this_phase']);

            // Validate each budget entry
            if (empty($particular) || $rate <= 0 || $quantity <= 0 || $duration <= 0 || $amountThisPhase <= 0) {
                throw new Exception("Invalid budget entry at row " . ($index + 1) . ". Please check your inputs.");
            }

            // Execute the prepared statement for each budget entry
            $entryStmt->execute([
                ':project_id' => $projectId,
                ':particular' => $particular,
                ':rate_quantity' => $quantity,
                ':rate_multiplier' => $rate,
                ':rate_duration' => $duration,
                ':amount_this_phase' => $amountThisPhase,
            ]);
        }
    }

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    logActivityToDatabase($userId, $action, "success", "Project updated successfully. Project ID: $projectId");
    logActivityToFile("User ID $userId: Project updated successfully. Project ID: $projectId", "info");

    // Set success message and redirect based on user role
    $_SESSION['success'] = "Project updated successfully.";
    
    // Redirect based on user role
    if ($userRole === 'Councillor') {
        header("Location: ../Blade/all_projects.php");
    } else {
        header("Location: ../Blade/my_projects.php");
    }
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while updating the project. Error: " . $e->getMessage();
    header("Location: ../Blade/project_edit_form.php?project_id=" . $projectId);
    exit();
}
?>
