<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/dbh.inc.php';
require_once '../../includes/log_activity.php';
require_once '../../includes/logger.inc.php'; // File logger

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    $action = "Edit Assessment Sisters Data";

    if (!$userId) {
        $_SESSION['error'] = "User not logged in.";
        header("Location: ../../index.php");
        exit();
    }

    // Retrieve form data
    $keyID = $_POST['keyID'];
    $noOfSisters = $_POST['NoOfSisters'];
    $chronicle = $_POST['Chronicle'];
    $chronicleComment = $_POST['chronicleComment'];

    try {
        $pdo->beginTransaction();

        // Update main AssessmentSisters table
        $sql = "UPDATE AssessmentSisters
                SET NoOfSisters = :NoOfSisters,
                    Chronicle = :Chronicle,
                    chronicleComment = :chronicleComment
                WHERE keyID = :keyID";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':NoOfSisters' => $noOfSisters,
            ':Chronicle' => $chronicle,
            ':chronicleComment' => $chronicleComment,
            ':keyID' => $keyID
        ]);

        // Update sister cards data in AssessmentSisterCards table
        $sqlCardUpdate = "UPDATE AssessmentSisterCards
                          SET DateOfStarting = :DateOfStarting,
                              Programme = :Programme,
                              SisterInCharge = :SisterInCharge,
                              NoOfStaff = :NoOfStaff,
                              NoOfBeneficiaries = :NoOfBeneficiaries
                          WHERE SerialNumber = :SerialNumber AND keyID = :keyID";

        $stmtCardUpdate = $pdo->prepare($sqlCardUpdate);

        // Loop through the dynamic sister card data and update
        foreach ($_POST['SerialNumber'] as $index => $serialNumber) {
            $dateOfStarting = $_POST['DateOfStarting'][$index];
            $programme = $_POST['Programme'][$index];
            $sisterInCharge = $_POST['SisterInCharge'][$index];
            $noOfStaff = $_POST['NoOfStaff'][$index];
            $noOfBeneficiaries = $_POST['NoOfBeneficiaries'][$index];

            // Execute the update for each card
            $stmtCardUpdate->execute([
                ':DateOfStarting' => $dateOfStarting,
                ':Programme' => $programme,
                ':SisterInCharge' => $sisterInCharge,
                ':NoOfStaff' => $noOfStaff,
                ':NoOfBeneficiaries' => $noOfBeneficiaries,
                ':SerialNumber' => $serialNumber,
                ':keyID' => $keyID
            ]);
        }

        $pdo->commit();

        // Log success activity
        logActivityToDatabase($userId, $action, "success", "Sisters data updated successfully for keyID: $keyID.");
        logActivityToFile("User ID $userId: Sisters data updated successfully for keyID: $keyID.", "info");

        // Redirect with success message
        $_SESSION['success'] = "Data updated successfully.";
        header("Location: ../../View/Show/AssessmentCentreDetail.php?keyID=$keyID");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();

        // Log the error details
        logActivityToDatabase($userId, $action, "error", "Error updating sisters data for keyID: $keyID. Error: " . $e->getMessage());
        logActivityToFile("User ID $userId: Error updating sisters data for keyID: $keyID. Error: " . $e->getMessage(), "error");

        // Set a user-friendly error message
        $_SESSION['error'] = "An unexpected error occurred while updating the data. Please try again later.";

        // Redirect to the edit form
        header("Location: ../../Edit/AssessmentSistersEdit.php?keyID=$keyID");
        exit();
    }
}
?>
