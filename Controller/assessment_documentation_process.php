<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();

// Include required files
require_once '../includes/dbh.inc.php';
require_once '../includes/log_activity.php'; // Database logging
require_once '../includes/logger.inc.php'; // File logging

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../View/AssessmentDocumentation.php");
    exit();
}

// Retrieve user ID from session
$userId = $_SESSION['user_id'] ?? null;
$action = "Assessment - Documentation";

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
    $feedbackReport = $_POST['feedbackReport'] ?? null;
    $annualReports = $_POST['annualReports'] ?? null;
    $beneficiariesData = $_POST['beneficiariesData'] ?? null;
    $beneficiariesType = $_POST['beneficiariesType'] ?? null;
    $beneficiariesCount = $_POST['beneficiariesCount'] ?? null;
    $requestThankingLetters = $_POST['requestThankingLetters'] ?? null;
    $bylaws = $_POST['bylaws'] ?? null;
    $amendment = $_POST['amendment'] ?? null;
    $pan = $_POST['pan'] ?? null;
    $womenSocietyRegistrationCopies = $_POST['womenSocietyRegistrationCopies'] ?? null;
    $leaseAgreement = $_POST['leaseAgreement'] ?? null;
    $monthlyReports = $_POST['monthlyReports'] ?? null;
    $lsacMinutes = $_POST['lsacMinutes'] ?? null;
    $lsacMinutesText = $_POST['lsacMinutesText'] ?? null;

    // Prepare the SQL query for inserting form data into `AssessmentDocumentation`
    $sql = "INSERT INTO AssessmentDocumentation (
                keyID, DateOfAssessment, feedbackReport, annualReports,
                beneficiariesData, beneficiariesType, beneficiariesCount,
                requestThankingLetters, bylaws, amendment, pan,
                womenSocietyRegistrationCopies, leaseAgreement, monthlyReports,
                lsacMinutes, lsacMinutesText
            ) VALUES (
                :keyID, :DateOfAssessment, :feedbackReport, :annualReports,
                :beneficiariesData, :beneficiariesType, :beneficiariesCount,
                :requestThankingLetters, :bylaws, :amendment, :pan,
                :womenSocietyRegistrationCopies, :leaseAgreement, :monthlyReports,
                :lsacMinutes, :lsacMinutesText
            )";

    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->execute([
        ':keyID' => $keyID,
        ':DateOfAssessment' => $dateOfAssessment,
        ':feedbackReport' => $feedbackReport,
        ':annualReports' => $annualReports,
        ':beneficiariesData' => $beneficiariesData,
        ':beneficiariesType' => $beneficiariesType,
        ':beneficiariesCount' => $beneficiariesCount,
        ':requestThankingLetters' => $requestThankingLetters,
        ':bylaws' => $bylaws,
        ':amendment' => $amendment,
        ':pan' => $pan,
        ':womenSocietyRegistrationCopies' => $womenSocietyRegistrationCopies,
        ':leaseAgreement' => $leaseAgreement,
        ':monthlyReports' => $monthlyReports,
        ':lsacMinutes' => $lsacMinutes,
        ':lsacMinutesText' => $lsacMinutesText
    ]);

    // Commit the transaction
    $pdo->commit();

    // Log activity to the database
    logActivityToDatabase($userId, $action, "success", "Assessment - Documentation data saved successfully.");

    // Log activity to a file
    logActivityToFile("User ID $userId: Assessment - Documentation data saved successfully.", "info");

    // Set success message and redirect to Assessment Finance with KeyID
    $_SESSION['success'] = "Assessment Documentation data saved successfully. Please continue with Finance Assessment.";
    header("Location: ../View/AssessmentFinance.php?keyID=" . urlencode($keyID));
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect back to the form
    $_SESSION['error'] = "An error occurred while saving the data. Error: " . $e->getMessage();
    header("Location: ../View/AssessmentDocumentation.php");
    exit();
}
?>
