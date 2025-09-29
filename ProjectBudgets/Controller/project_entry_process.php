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
$action = "Project Entry";
$createdBy = $userId;

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

// Determine the Primary Project In-Charge
$primaryProjectInCharge = $userId;
if ($userRole === 'Councillor') {
    $primaryProjectInCharge = intval($_POST['project_incharge']);
}

// Get additional Project In-Charges
$additionalIncharges = $_POST['additional_incharges'] ?? [];
$allIncharges = [$primaryProjectInCharge]; // Start with primary
foreach ($additionalIncharges as $inchargeId) {
    if (!empty($inchargeId) && !in_array($inchargeId, $allIncharges)) {
        $allIncharges[] = intval($inchargeId);
    }
}

// Validate form input
if (empty($projectName) || empty($projectCenter) || $organizationId <= 0 || $totalBudget <= 0) {
    $_SESSION['error'] = "Please provide all required fields and ensure the budget is greater than zero.";
    header("Location: ../Blade/project_entry_form.php");
    exit();
}

// Validate organization exists and is active
$orgStmt = $pdo->prepare("SELECT organization_id, organization_name FROM Organizations WHERE organization_id = :id AND is_active = 1");
$orgStmt->execute([':id' => $organizationId]);
$organization = $orgStmt->fetch(PDO::FETCH_ASSOC);

if (!$organization) {
    $_SESSION['error'] = "Please select a valid active organization.";
    header("Location: ../Blade/project_entry_form.php");
    exit();
}

try {
    // Begin a transaction
    $pdo->beginTransaction();

    // Insert the project into the Projects table
    $projectSql = "
        INSERT INTO Projects (project_name, project_center, organization_id, project_incharge, total_budget, start_date, end_date, funding_source, fund_type, remarks, created_by)
        VALUES (:project_name, :project_center, :organization_id, :project_incharge, :total_budget, :start_date, :end_date, :funding_source, :fund_type, :remarks, :created_by)
    ";
    $projectStmt = $pdo->prepare($projectSql);
    $projectStmt->execute([
        ':project_name' => $projectName,
        ':project_center' => $projectCenter,
        ':organization_id' => $organizationId,
        ':project_incharge' => $primaryProjectInCharge,
        ':total_budget' => $totalBudget,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':funding_source' => $fundingSource,
        ':fund_type' => $fundType,
        ':remarks' => $remarks,
        ':created_by' => $createdBy,
    ]);

    // Get the last inserted project ID
    $projectId = $pdo->lastInsertId();

    // Insert Project Assignments for all Project In-Charges
    $assignmentSql = "
        INSERT INTO ProjectAssignments (project_id, project_incharge_id, is_primary, assigned_by, is_active)
        VALUES (:project_id, :project_incharge_id, :is_primary, :assigned_by, 1)
    ";
    $assignmentStmt = $pdo->prepare($assignmentSql);
    
    foreach ($allIncharges as $index => $inchargeId) {
        $isPrimary = ($index === 0) ? 1 : 0; // First one is primary
        $assignmentStmt->execute([
            ':project_id' => $projectId,
            ':project_incharge_id' => $inchargeId,
            ':is_primary' => $isPrimary,
            ':assigned_by' => $createdBy,
        ]);
    }

    // Insert budget entries
    $budgetEntries = $_POST['budget_entries'];
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
            $_SESSION['error'] = "Invalid budget entry at row " . ($index + 1) . ". Please check your inputs.";
            header("Location: ../Blade/project_entry_form.php");
            exit();
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

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    logActivityToDatabase($userId, $action, "success", "Project and budget entries added successfully. Project ID: $projectId");
    logActivityToFile("User ID $userId: Project and budget entries added successfully. Project ID: $projectId", "info");

    // Set success message and redirect based on user role
    $_SESSION['success'] = "Project and budget entries added successfully.";
    
    // Redirect based on user role
    if ($userRole === 'Councillor') {
        header("Location: ../../View/CouncillorDashboard.php");
    } else {
        header("Location: ../../View/ProjectInChargeDashboard.php");
    }
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while saving the project. Error: " . $e->getMessage();
    header("Location: ../Blade/project_entry_form.php");
    exit();
}
?>
