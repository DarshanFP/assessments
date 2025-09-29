<?php
session_start();
require_once '../../includes/dbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../../View/dashboard.php");
    exit();
}

$keyID = $_POST['keyID'];

try {
    $pdo->beginTransaction();

    $fields = [
        'Strengths', 'AreasOfAttention', 'ImmediateAttention', 'OpportunitiesIdentified',
        'ChallengesFaced', 'StepsTaken', 'HelpRequested', 'NewVentures',
        'ListOfActivities', 'Comments'
    ];

    $updateFields = [];
    $updateData = [':keyID' => $keyID];

    foreach ($fields as $field) {
        $updateFields[] = "$field = :$field";
        $updateData[":$field"] = $_POST[$field];
    }

    $sqlUpdate = "UPDATE CentreAssessment SET " . implode(', ', $updateFields) . " WHERE keyID = :keyID";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute($updateData);

    $deleteProjects = "DELETE FROM CentreProjects WHERE keyID = :keyID";
    $stmtDelete = $pdo->prepare($deleteProjects);
    $stmtDelete->execute([':keyID' => $keyID]);

    $insertProject = "INSERT INTO CentreProjects (keyID, ProjectName, NumberOfBeneficiaries, ProjectSponsor, OtherSponsor)
                      VALUES (:keyID, :ProjectName, :NumberOfBeneficiaries, :ProjectSponsor, :OtherSponsor)";
    $stmtInsert = $pdo->prepare($insertProject);

    foreach ($_POST['ProjectName'] as $index => $projectName) {
        $stmtInsert->execute([
            ':keyID' => $keyID,
            ':ProjectName' => $projectName,
            ':NumberOfBeneficiaries' => $_POST['NumberOfBeneficiaries'][$index],
            ':ProjectSponsor' => $_POST['ProjectSponsor'][$index],
            ':OtherSponsor' => $_POST['OtherSponsor'][$index]
        ]);
    }

    $pdo->commit();
    $_SESSION['success'] = "Centre assessment and projects updated successfully.";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error updating data: " . $e->getMessage();
}

header("Location: ../../View/Show/AssessmentCentreDetail.php?keyID=$keyID");
exit();
?>
