<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/dbh.inc.php';
require_once '../../includes/log_activity.php'; // Contains logActivityToDatabase()
require_once '../../includes/logger.inc.php';   // Contains logActivityToFile()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    $action = "Edit Documentation Details";

    // Check if user is logged in
    if (!$userId) {
        $_SESSION['error'] = "User not logged in.";
        header("Location: ../../index.php");
        exit();
    }

    // Retrieve form data
    $keyID = $_POST['keyID'];
    $feedbackReport = $_POST['feedbackReport'];
    $annualReports = $_POST['annualReports'];
    $beneficiariesData = $_POST['beneficiariesData'];
    $beneficiariesType = $_POST['beneficiariesType'];
    $beneficiariesCount = $_POST['beneficiariesCount'];
    $requestThankingLetters = $_POST['requestThankingLetters'];
    $bylaws = $_POST['bylaws'];
    $amendment = $_POST['amendment'];
    $pan = $_POST['pan'];
    $womenSocietyRegistrationCopies = $_POST['womenSocietyRegistrationCopies'];
    $leaseAgreement = $_POST['leaseAgreement'];
    $monthlyReports = $_POST['monthlyReports'];
    $lsacMinutes = $_POST['lsacMinutes'];
    $lsacMinutesText = $_POST['lsacMinutesText'];

    try {
        // Begin database transaction
        $pdo->beginTransaction();

        // Update query for AssessmentDocumentation table
        $sql = "UPDATE AssessmentDocumentation
                SET feedbackReport = :feedbackReport,
                    annualReports = :annualReports,
                    beneficiariesData = :beneficiariesData,
                    beneficiariesType = :beneficiariesType,
                    beneficiariesCount = :beneficiariesCount,
                    requestThankingLetters = :requestThankingLetters,
                    bylaws = :bylaws,
                    amendment = :amendment,
                    pan = :pan,
                    womenSocietyRegistrationCopies = :womenSocietyRegistrationCopies,
                    leaseAgreement = :leaseAgreement,
                    monthlyReports = :monthlyReports,
                    lsacMinutes = :lsacMinutes,
                    lsacMinutesText = :lsacMinutesText
                WHERE keyID = :keyID";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
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
            ':lsacMinutesText' => $lsacMinutesText,
            ':keyID' => $keyID
        ]);

        // Commit the transaction
        $pdo->commit();

        // Fetch the updated data for logging
        $sqlFetch = "SELECT * FROM AssessmentDocumentation WHERE keyID = :keyID";
        $stmtFetch = $pdo->prepare($sqlFetch);
        $stmtFetch->execute([':keyID' => $keyID]);
        $updatedData = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        // Log activity to the database
        logActivityToDatabase($userId, $action, "success", "Documentation details updated successfully for keyID: $keyID.");

        // Log activity to the log file
        $logMessage = "User ID $userId updated documentation details successfully for keyID: $keyID.";
        logActivityToFile($logMessage, "info");

        // Store updated data in session for immediate use
        $_SESSION['documentationData'] = $updatedData;

        // Set success message and redirect
        $_SESSION['success'] = "Documentation details updated successfully.";
        header("Location: ../../View/Show/AssessmentCentreDetail.php?keyID=$keyID");
        exit();

    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $pdo->rollBack();

        // Log error to the database
        logActivityToDatabase($userId, $action, "error", $e->getMessage());

        // Log error to the log file
        $logMessage = "User ID $userId: Error updating documentation details for keyID $keyID - " . $e->getMessage();
        logActivityToFile($logMessage, "error");

        // Set error message and redirect back to the edit form
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        header("Location: ../../Edit/AssessmentDocumentationEdit.php?keyID=$keyID");
        exit();
    }
}
?>
