<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/log_activity.php';
require_once '../../includes/logger.inc.php';

// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: ../../index.php");
    exit();
}

// Ensure only 'Councillor' users can access this page
if ($_SESSION['role'] !== 'Councillor') {
    $_SESSION['error'] = "Access denied. Only Councillors can manage organizations.";
    header("Location: ../../index.php");
    exit();
}

// Retrieve user information from session
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            handleAddOrganization($pdo, $userId);
            break;
        case 'edit':
            handleEditOrganization($pdo, $userId);
            break;
        case 'delete':
            handleDeleteOrganization($pdo, $userId);
            break;
        case 'toggle':
            handleToggleOrganizationStatus($pdo, $userId);
            break;
        default:
            $_SESSION['error'] = "Invalid action specified.";
            header("Location: ../Blade/organization_management.php");
            exit();
    }
} catch (Exception $e) {
    // Log the error
    logActivityToDatabase($userId, "Organization Management", "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error in organization management - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    header("Location: ../Blade/organization_management.php");
    exit();
}

function handleAddOrganization($pdo, $userId) {
    // Retrieve form data
    $organizationCode = trim($_POST['organization_code']);
    $organizationName = trim($_POST['organization_name']);
    $fullName = trim($_POST['full_name']);
    $description = trim($_POST['description'] ?? '');
    $colorTheme = trim($_POST['color_theme']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Validate form input
    if (empty($organizationCode) || empty($organizationName) || empty($fullName) || empty($colorTheme)) {
        $_SESSION['error'] = "Please provide all required fields.";
        header("Location: ../Blade/organization_management.php");
        exit();
    }

    // Validate organization code format
    if (!preg_match('/^[A-Z0-9]{2,10}$/', $organizationCode)) {
        $_SESSION['error'] = "Organization code must be 2-10 characters long and contain only uppercase letters and numbers.";
        header("Location: ../Blade/organization_management.php");
        exit();
    }

    // Check if organization code already exists
    $checkStmt = $pdo->prepare("SELECT organization_id FROM Organizations WHERE organization_code = :code");
    $checkStmt->execute([':code' => $organizationCode]);
    if ($checkStmt->fetch()) {
        $_SESSION['error'] = "Organization code already exists. Please choose a different code.";
        header("Location: ../Blade/organization_management.php");
        exit();
    }

    // Insert the organization
    $insertSql = "
        INSERT INTO Organizations (organization_code, organization_name, full_name, description, color_theme, is_active)
        VALUES (:code, :name, :full_name, :description, :color_theme, :is_active)
    ";
    $insertStmt = $pdo->prepare($insertSql);
    $insertStmt->execute([
        ':code' => $organizationCode,
        ':name' => $organizationName,
        ':full_name' => $fullName,
        ':description' => $description,
        ':color_theme' => $colorTheme,
        ':is_active' => $isActive
    ]);

    // Log the successful action
    logActivityToDatabase($userId, "Organization Management", "success", "Organization added successfully: $organizationCode");
    logActivityToFile("User ID $userId: Organization added successfully: $organizationCode", "info");

    // Set success message and redirect
    $_SESSION['success'] = "Organization added successfully.";
    header("Location: ../Blade/organization_management.php");
    exit();
}

function handleEditOrganization($pdo, $userId) {
    // This would be implemented for editing organizations
    // For now, redirect back to management page
    $_SESSION['error'] = "Edit functionality will be implemented in the next update.";
    header("Location: ../Blade/organization_management.php");
    exit();
}

function handleDeleteOrganization($pdo, $userId) {
    $organizationId = intval($_GET['id'] ?? 0);

    if ($organizationId <= 0) {
        $_SESSION['error'] = "Invalid organization ID.";
        header("Location: ../Blade/organization_management.php");
        exit();
    }

    // Check if organization has any projects
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as project_count FROM Projects WHERE organization_id = :id");
    $checkStmt->execute([':id' => $organizationId]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($result['project_count'] > 0) {
        $_SESSION['error'] = "Cannot delete organization that has associated projects. Please deactivate it instead.";
        header("Location: ../Blade/organization_management.php");
        exit();
    }

    // Get organization details for logging
    $orgStmt = $pdo->prepare("SELECT organization_code FROM Organizations WHERE organization_id = :id");
    $orgStmt->execute([':id' => $organizationId]);
    $organization = $orgStmt->fetch(PDO::FETCH_ASSOC);

    if (!$organization) {
        $_SESSION['error'] = "Organization not found.";
        header("Location: ../Blade/organization_management.php");
        exit();
    }

    // Delete the organization
    $deleteStmt = $pdo->prepare("DELETE FROM Organizations WHERE organization_id = :id");
    $deleteStmt->execute([':id' => $organizationId]);

    // Log the successful action
    logActivityToDatabase($userId, "Organization Management", "success", "Organization deleted: " . $organization['organization_code']);
    logActivityToFile("User ID $userId: Organization deleted: " . $organization['organization_code'], "info");

    // Set success message and redirect
    $_SESSION['success'] = "Organization deleted successfully.";
    header("Location: ../Blade/organization_management.php");
    exit();
}

function handleToggleOrganizationStatus($pdo, $userId) {
    $organizationId = intval($_GET['id'] ?? 0);
    $newStatus = $_GET['status'] === 'true' ? 1 : 0;

    if ($organizationId <= 0) {
        $_SESSION['error'] = "Invalid organization ID.";
        header("Location: ../Blade/organization_management.php");
        exit();
    }

    // Get organization details for logging
    $orgStmt = $pdo->prepare("SELECT organization_code FROM Organizations WHERE organization_id = :id");
    $orgStmt->execute([':id' => $organizationId]);
    $organization = $orgStmt->fetch(PDO::FETCH_ASSOC);

    if (!$organization) {
        $_SESSION['error'] = "Organization not found.";
        header("Location: ../Blade/organization_management.php");
        exit();
    }

    // Update organization status
    $updateStmt = $pdo->prepare("UPDATE Organizations SET is_active = :status WHERE organization_id = :id");
    $updateStmt->execute([':status' => $newStatus, ':id' => $organizationId]);

    $action = $newStatus ? 'activated' : 'deactivated';
    
    // Log the successful action
    logActivityToDatabase($userId, "Organization Management", "success", "Organization $action: " . $organization['organization_code']);
    logActivityToFile("User ID $userId: Organization $action: " . $organization['organization_code'], "info");

    // Set success message and redirect
    $_SESSION['success'] = "Organization $action successfully.";
    header("Location: ../Blade/organization_management.php");
    exit();
}
?>
