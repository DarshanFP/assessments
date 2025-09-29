<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();

// Include required files
require_once '../includes/dbh.inc.php';
require_once '../includes/log_activity.php';
require_once '../includes/logger.inc.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../View/AssessmentFinance.php");
    exit();
}

// Retrieve user ID from session
$userId = $_SESSION['user_id'] ?? null;
$action = "Assessment - Finance";

if (!$userId) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: ../index.php");
    exit();
}

try {
    // Begin a database transaction
    $pdo->beginTransaction();

    // Retrieve form data
    $keyID = $_POST['keyID'];
    $dateOfAssessment = $_POST['DateOfAssessment'];
    $approvedAnnualBudget = $_POST['approvedAnnualBudget'] ?? null;
    $auditStatements = $_POST['AuditStatements'] ?? null;
    $bankStatements = $_POST['BankStatements'] ?? null;
    $scopeToExpand = $_POST['scopeToExpand'] ?? null;
    $newInitiatives = $_POST['newInitiatives'] ?? null;
    $helpNeeded = $_POST['helpNeeded'] ?? null;
    $coursesAndTrainings = $_POST['CoursesAndTrainings'] ?? null;
    $anyUpdates = $_POST['AnyUpdates'] ?? null;
    $remarksOfTheTeam = $_POST['RemarksOfTheTeam'] ?? null;
    $nameOfTheAssessors = $_POST['NameOfTheAssessors'] ?? null;
    $nameOfTheSisterIncharge = $_POST['NameOfTheSisterIncharge'] ?? null;

    // Prepare the SQL query for inserting form data into `AssessmentFinance`
    $sql = "INSERT INTO AssessmentFinance (
                keyID, DateOfAssessment, approvedAnnualBudget, AuditStatements,
                BankStatements, scopeToExpand, newInitiatives, helpNeeded,
                CoursesAndTrainings, AnyUpdates, RemarksOfTheTeam,
                NameOfTheAssessors, NameOfTheSisterIncharge
            ) VALUES (
                :keyID, :DateOfAssessment, :approvedAnnualBudget, :AuditStatements,
                :BankStatements, :scopeToExpand, :newInitiatives, :helpNeeded,
                :CoursesAndTrainings, :AnyUpdates, :RemarksOfTheTeam,
                :NameOfTheAssessors, :NameOfTheSisterIncharge
            )";

    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->execute([
        ':keyID' => $keyID,
        ':DateOfAssessment' => $dateOfAssessment,
        ':approvedAnnualBudget' => $approvedAnnualBudget,
        ':AuditStatements' => $auditStatements,
        ':BankStatements' => $bankStatements,
        ':scopeToExpand' => $scopeToExpand,
        ':newInitiatives' => $newInitiatives,
        ':helpNeeded' => $helpNeeded,
        ':CoursesAndTrainings' => $coursesAndTrainings,
        ':AnyUpdates' => $anyUpdates,
        ':RemarksOfTheTeam' => $remarksOfTheTeam,
        ':NameOfTheAssessors' => $nameOfTheAssessors,
        ':NameOfTheSisterIncharge' => $nameOfTheSisterIncharge
    ]);

    // Insert project impact data into `AssessmentFinanceProjects`
    $sqlProject = "INSERT INTO AssessmentFinanceProjects (
                    keyID, projectName, impactOfProject
                ) VALUES (
                    :keyID, :projectName, :impactOfProject
                )";

    $stmtProject = $pdo->prepare($sqlProject);

    // Loop through each project card data
    foreach ($_POST['projectName'] as $index => $projectName) {
        $impactOfProject = $_POST['impactOfProject'][$index] ?? null;

        // Execute insert for each project card
        $stmtProject->execute([
            ':keyID' => $keyID,
            ':projectName' => $projectName,
            ':impactOfProject' => $impactOfProject
        ]);
    }

    // Commit the transaction
    $pdo->commit();

    // Log activity to the database
    logActivityToDatabase($userId, $action, "success", "Assessment - Finance data saved successfully.");

    // Log activity to a file
    logActivityToFile("User ID $userId: Assessment - Finance data saved successfully.", "info");

    // Set success message and redirect to Centre view with KeyID
    $_SESSION['success'] = "Assessment Finance data saved successfully. Please complete the final Centre Assessment.";
    header("Location: ../View/centre.php?keyID=" . urlencode($keyID));
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect back to the form
    $_SESSION['error'] = "An error occurred while saving the data. Error: " . $e->getMessage();
    header("Location: ../View/AssessmentFinance.php");
    exit();
}
?>
