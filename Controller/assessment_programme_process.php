<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();

// Include required files
require_once '../includes/dbh.inc.php'; // Database connection
require_once '../includes/log_activity.php'; // Database logging
require_once '../includes/logger.inc.php'; // File logging

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../View/AssessmentProgramme.php");
    exit();
}

// Retrieve user ID from session
$userId = $_SESSION['user_id'] ?? null;
$action = "Assessment - Programme";

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
    $successStories = $_POST['SuccessStories'] ?? null;
    $mediaCoverage = $_POST['MediaCoverage'] ?? null;
    $collaborationGoNgos = $_POST['CollaborationGoNgos'] ?? null;
    $photographs = $_POST['Photographs'] ?? null;
    $fieldInspection = $_POST['FieldInspection'] ?? null;
    $activitiesList = $_POST['ActivitiesList'] ?? null;
    $recommendations = $_POST['Recommendations'] ?? null;

    // Prepare the SQL query for inserting form data
    $sql = "INSERT INTO AssessmentProgramme (
                keyID, DateOfAssessment, SuccessStories, MediaCoverage,
                CollaborationGoNgos, Photographs, FieldInspection,
                ActivitiesList, Recommendations
            ) VALUES (
                :keyID, :DateOfAssessment, :SuccessStories, :MediaCoverage,
                :CollaborationGoNgos, :Photographs, :FieldInspection,
                :ActivitiesList, :Recommendations
            )";

    $stmt = $pdo->prepare($sql);

    // Bind parameters from the POST request
    $stmt->execute([
        ':keyID' => $keyID,
        ':DateOfAssessment' => $dateOfAssessment,
        ':SuccessStories' => $successStories,
        ':MediaCoverage' => $mediaCoverage,
        ':CollaborationGoNgos' => $collaborationGoNgos,
        ':Photographs' => $photographs,
        ':FieldInspection' => $fieldInspection,
        ':ActivitiesList' => $activitiesList,
        ':Recommendations' => $recommendations
    ]);

    // Commit the transaction
    $pdo->commit();

    // Log activity to the database
    logActivityToDatabase($userId, $action, "success", "Assessment - Programme data saved successfully.");

    // Log activity to a file
    logActivityToFile("User ID $userId: Assessment - Programme data saved successfully.", "info");

    // Set success message and redirect to Assessment Documentation with KeyID
    $_SESSION['success'] = "Assessment Programme data saved successfully. Please continue with Documentation Assessment.";
    header("Location: ../View/AssessmentDocumentation.php?keyID=" . urlencode($keyID));
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error to the database
    logActivityToDatabase($userId, $action, "error", $e->getMessage());

    // Log the error to a file
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect back to the form
    $_SESSION['error'] = "An error occurred while saving the data. Error: " . $e->getMessage();
    header("Location: ../View/AssessmentProgramme.php");
    exit();
}
?>
