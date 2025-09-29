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
$action = "Project Edit Request";
$projectId = intval($_POST['project_id']);

// Validate project ID
if ($projectId <= 0) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: ../Blade/my_projects.php");
    exit();
}

// Only Project In-Charges can submit edit requests
if ($userRole === 'Councillor') {
    $_SESSION['error'] = "Councillors can edit projects directly. Use the regular edit form.";
    header("Location: ../Blade/project_edit_form.php?project_id=" . $projectId);
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
if (!empty($_POST['project_incharge_other'])) {
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

// Check if project exists and user has permission to request edits
$projectCheckStmt = $pdo->prepare("
    SELECT project_id, project_name, project_incharge, is_active 
    FROM Projects 
    WHERE project_id = :project_id AND is_active = 1
");
$projectCheckStmt->execute([':project_id' => $projectId]);
$existingProject = $projectCheckStmt->fetch(PDO::FETCH_ASSOC);

if (!$existingProject) {
    $_SESSION['error'] = "Project not found or has been deactivated.";
    header("Location: ../Blade/my_projects.php");
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
$assignmentStmt->execute([':project_id' => $projectId, ':user_id' => $userId]);
$isAssigned = $assignmentStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

if (!$isAssigned) {
    $_SESSION['error'] = "You don't have permission to request edits for this project.";
    header("Location: ../Blade/my_projects.php");
    exit();
}

try {
    // Get current project data for comparison
    $currentStmt = $pdo->prepare("
        SELECT * FROM Projects WHERE project_id = :project_id
    ");
    $currentStmt->execute([':project_id' => $projectId]);
    $currentProject = $currentStmt->fetch(PDO::FETCH_ASSOC);

    // Get current budget entries
    $currentBudgetStmt = $pdo->prepare("
        SELECT * FROM BudgetEntries WHERE project_id = :project_id ORDER BY entry_id
    ");
    $currentBudgetStmt->execute([':project_id' => $projectId]);
    $currentBudgetEntries = $currentBudgetStmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare requested changes
    $requestedChanges = [
        'project_name' => $projectName,
        'project_center' => $projectCenter,
        'organization_id' => $organizationId,
        'project_incharge' => $projectInCharge,
        'total_budget' => $totalBudget,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'funding_source' => $fundingSource,
        'fund_type' => $fundType,
        'remarks' => $remarks,
        'budget_entries' => $_POST['budget_entries'] ?? []
    ];

    // Begin a transaction
    $pdo->beginTransaction();

    // Insert the edit request
    $requestSql = "
        INSERT INTO ProjectEditRequests (
            project_id, requested_by, request_type, status, 
            original_data, requested_changes, requested_at
        ) VALUES (
            :project_id, :requested_by, 'edit', 'pending',
            :original_data, :requested_changes, CURRENT_TIMESTAMP
        )
    ";
    $requestStmt = $pdo->prepare($requestSql);
    $requestStmt->execute([
        ':project_id' => $projectId,
        ':requested_by' => $userId,
        ':original_data' => json_encode([
            'project' => $currentProject,
            'budget_entries' => $currentBudgetEntries
        ]),
        ':requested_changes' => json_encode($requestedChanges)
    ]);

    // Get the request ID
    $requestId = $pdo->lastInsertId();

    // Commit the transaction
    $pdo->commit();

    // Log the successful action
    logActivityToDatabase($userId, $action, "success", "Edit request submitted successfully. Request ID: $requestId, Project ID: $projectId");
    logActivityToFile("User ID $userId: Edit request submitted successfully. Request ID: $requestId, Project ID: $projectId", "info");

    // Create notification for Councillors
    require_once '../../includes/NotificationManager.php';
    NotificationManager::createProjectEditNotification($projectId, $userId, $currentProject['project_name']);

    // Set success message and redirect
    $_SESSION['success'] = "Your edit request has been submitted successfully and is pending approval from a Councillor.";
    header("Location: ../Blade/my_projects.php");
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while submitting the edit request. Error: " . $e->getMessage();
    header("Location: ../Blade/project_edit_form.php?project_id=" . $projectId);
    exit();
}
?>
