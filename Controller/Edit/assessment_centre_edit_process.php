<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/dbh.inc.php';
require_once '../../includes/log_activity.php'; // Contains logActivityToDatabase()
require_once '../../includes/logger.inc.php';   // Contains logActivityToFile()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null; // Get user ID from session
    $action = "Edit Assessment Centre Data";

    // Check if user is logged in
    if (!$userId) {
        $_SESSION['error'] = "User not logged in.";
        header("Location: ../../index.php");
        exit();
    }

    // Retrieve form data
    $keyID = $_POST['keyID'];
    $centreName = $_POST['centreName'];
    $sistersInCharge = $_POST['sistersInCharge'];
    $centreHistory = $_POST['centreHistory'];
    $feedbackReportRecord = $_POST['feedbackReportRecord'];
    $shortTermGoals = $_POST['shortTermGoals'];
    $commentShortTermGoals = $_POST['commentShortTermGoals'];
    $longTermGoals = $_POST['longTermGoals'];
    $commentLongTermGoals = $_POST['commentLongTermGoals'];
    $inventory = $_POST['inventory'];
    $commentInventory = $_POST['commentInventory'];
    $assetFile = $_POST['assetFile'];
    $commentAssetFiles = $_POST['commentAssetFiles'];

    try {
        $pdo->beginTransaction();
    
        // Update query
        $sql = "UPDATE AssessmentCentre
                SET centreName = :centreName,
                    sistersInCharge = :sistersInCharge,
                    centreHistory = :centreHistory,
                    feedbackReportRecord = :feedbackReportRecord,
                    shortTermGoals = :shortTermGoals,
                    commentShortTermGoals = :commentShortTermGoals,
                    longTermGoals = :longTermGoals,
                    commentLongTermGoals = :commentLongTermGoals,
                    inventory = :inventory,
                    commentInventory = :commentInventory,
                    assetFile = :assetFile,
                    commentAssetFiles = :commentAssetFiles
                WHERE keyID = :keyID";
    
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':centreName' => $_POST['centreName'],
            ':sistersInCharge' => $_POST['sistersInCharge'],
            ':centreHistory' => $_POST['centreHistory'],
            ':feedbackReportRecord' => $_POST['feedbackReportRecord'],
            ':shortTermGoals' => $_POST['shortTermGoals'],
            ':commentShortTermGoals' => $_POST['commentShortTermGoals'],
            ':longTermGoals' => $_POST['longTermGoals'],
            ':commentLongTermGoals' => $_POST['commentLongTermGoals'],
            ':inventory' => $_POST['inventory'],
            ':commentInventory' => $_POST['commentInventory'],
            ':assetFile' => $_POST['assetFile'],
            ':commentAssetFiles' => $_POST['commentAssetFiles'],
            ':keyID' => $keyID
        ]);
    
        // Fetch the updated data
        $sqlFetch = "SELECT * FROM AssessmentCentre WHERE keyID = :keyID";
        $stmtFetch = $pdo->prepare($sqlFetch);
        $stmtFetch->execute([':keyID' => $keyID]);
        $centreData = $stmtFetch->fetch(PDO::FETCH_ASSOC);
    
        if ($centreData) {
            $_SESSION['centreData'] = $centreData;
        }
    
        $pdo->commit();
    
        // Log activity
        logActivityToDatabase($userId, "Edit Assessment Centre", "success", "Assessment Centre - Updated successfully keyID: $keyID.");
        logActivityToFile("User ID $userId Assessment Centre - Updated successfully keyID: $keyID.", "info");
    
        // Redirect to the details page
        header("Location: ../../View/Show/AssessmentCentreDetail.php?keyID=$keyID");
        exit();
    
    } catch (Exception $e) {
        $pdo->rollBack();
    
        // Log the error
        logActivityToDatabase($userId, "Edit Assessment Centre", "error", $e->getMessage());
        logActivityToFile("User ID $userId: Error - " . $e->getMessage(), "error");
    
        // Redirect back to the edit form with an error message
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        header("Location: ../../Edit/AssessmentCentreEdit.php?keyID=$keyID");
        exit();
    }
    
}