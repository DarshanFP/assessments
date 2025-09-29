<?php
// Enable error reporting
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/log_activity.php';
require_once '../includes/logger.inc.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../View/AssessmentSisters.php");
    exit();
}

// Retrieve user ID from session
$userId = $_SESSION['user_id'] ?? null;
$action = "Assessment - Sisters";

if (!$userId) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: ../index.php");
    exit();
}

try {
    // Begin a transaction
    $pdo->beginTransaction();

    // Retrieve form data
    $keyID = $_POST['keyID'];
    $dateOfAssessment = $_POST['DateOfAssessment'];
    $noOfSisters = $_POST['NoOfSisters'];
    $chronicle = $_POST['Chronicle'];
    $chronicleComment = $_POST['chronicleComment'];

    // Insert main assessment form data into AssessmentSisters
    $sql = "INSERT INTO AssessmentSisters (
                keyID, DateOfAssessment, NoOfSisters,
                Chronicle, chronicleComment
            ) VALUES (
                :keyID, :DateOfAssessment, :NoOfSisters,
                :Chronicle, :chronicleComment
            )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':keyID' => $keyID,
        ':DateOfAssessment' => $dateOfAssessment,
        ':NoOfSisters' => $noOfSisters,
        ':Chronicle' => $chronicle,
        ':chronicleComment' => $chronicleComment
    ]);

    // Insert dynamic sister card data into AssessmentSisterCards
    $sqlCard = "INSERT INTO AssessmentSisterCards (
                    keyID, SerialNumber, DateOfStarting,
                    Programme, SisterInCharge, NoOfStaff, NoOfBeneficiaries
                ) VALUES (
                    :keyID, :SerialNumber, :DateOfStarting,
                    :Programme, :SisterInCharge, :NoOfStaff, :NoOfBeneficiaries
                )";
    $stmtCard = $pdo->prepare($sqlCard);

    // Loop through the dynamic card data
    foreach ($_POST['SerialNumber'] as $index => $serialNumber) {
        $dateOfStarting = $_POST['DateOfStarting'][$index];
        $programme = $_POST['Programme'][$index];
        $sisterInCharge = $_POST['SisterInCharge'][$index];
        $noOfStaff = $_POST['NoOfStaff'][$index];
        $noOfBeneficiaries = $_POST['NoOfBeneficiaries'][$index];

        // Execute the prepared statement for each card
        $stmtCard->execute([
            ':keyID' => $keyID,
            ':SerialNumber' => $serialNumber,
            ':DateOfStarting' => $dateOfStarting,
            ':Programme' => $programme,
            ':SisterInCharge' => $sisterInCharge,
            ':NoOfStaff' => $noOfStaff,
            ':NoOfBeneficiaries' => $noOfBeneficiaries
        ]);
    }

    // Commit the transaction
    $pdo->commit();

    // Log activity
    logActivityToDatabase($userId, $action, "success", "Assessment - Sisters and sister cards saved successfully.");
    logActivityToFile("User ID $userId: Assessment - Sisters and sister cards saved successfully.", "info");

    // Redirect to Assessment Programme with success message and KeyID
    $_SESSION['success'] = "Assessment Sisters data saved successfully. Please continue with Programme Assessment.";
    header("Location: ../View/AssessmentProgramme.php?keyID=" . urlencode($keyID));
    exit();
} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect back to the form
    $_SESSION['error'] = "An error occurred while saving the data. Error: " . $e->getMessage();
    header("Location: ../View/AssessmentSisters.php");
    exit();
}
?>
