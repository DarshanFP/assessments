<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/dbh.inc.php';
require_once '../../includes/log_activity.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../../View/AssessmentProgramme.php");
    exit();
}

$userId = $_SESSION['user_id'] ?? null;
$action = "Edit Assessment - Programme";

if (!$userId) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: ../../index.php");
    exit();
}

try {
    $pdo->beginTransaction();

    $keyID = $_POST['keyID'];
    $successStories = $_POST['SuccessStories'] ?? null;
    $mediaCoverage = $_POST['MediaCoverage'] ?? null;
    $collaborationGoNgos = $_POST['CollaborationGoNgos'] ?? null;
    $photographs = $_POST['Photographs'] ?? null;
    $fieldInspection = $_POST['FieldInspection'] ?? null;
    $activitiesList = $_POST['ActivitiesList'] ?? null;
    $recommendations = $_POST['Recommendations'] ?? null;

    $sql = "UPDATE AssessmentProgramme SET
                SuccessStories = :SuccessStories,
                MediaCoverage = :MediaCoverage,
                CollaborationGoNgos = :CollaborationGoNgos,
                Photographs = :Photographs,
                FieldInspection = :FieldInspection,
                ActivitiesList = :ActivitiesList,
                Recommendations = :Recommendations
            WHERE keyID = :keyID";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':SuccessStories' => $successStories,
        ':MediaCoverage' => $mediaCoverage,
        ':CollaborationGoNgos' => $collaborationGoNgos,
        ':Photographs' => $photographs,
        ':FieldInspection' => $fieldInspection,
        ':ActivitiesList' => $activitiesList,
        ':Recommendations' => $recommendations,
        ':keyID' => $keyID
    ]);

    $pdo->commit();

    logActivityToDatabase($userId, $action, "success", "Programme details updated successfully for keyID: $keyID.");
    $_SESSION['success'] = "Programme details updated successfully.";
    header("Location: ../../View/dashboard.php");
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    $_SESSION['error'] = "An error occurred: " . $e->getMessage();
    header("Location: ../../Edit/AssessmentProgrammeEdit.php?keyID=$keyID");
    exit();
}
?>
