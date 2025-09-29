<?php
// Start session and include database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/dbh.inc.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Retrieve keyID from the form submission
$keyID = $_POST['keyID'] ?? null;

if (!$keyID) {
    $_SESSION['error'] = "Invalid request. Missing keyID.";
    header("Location: ../../View/dashboard.php");
    exit();
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Update AssessmentFinance table
    $sqlFinanceUpdate = "
        UPDATE AssessmentFinance
        SET
            approvedAnnualBudget = :approvedAnnualBudget,
            AuditStatements = :AuditStatements,
            BankStatements = :BankStatements,
            scopeToExpand = :scopeToExpand,
            newInitiatives = :newInitiatives,
            helpNeeded = :helpNeeded,
            CoursesAndTrainings = :CoursesAndTrainings,
            AnyUpdates = :AnyUpdates,
            RemarksOfTheTeam = :RemarksOfTheTeam,
            NameOfTheAssessors = :NameOfTheAssessors,
            NameOfTheSisterIncharge = :NameOfTheSisterIncharge,
            updated_at = NOW()
        WHERE keyID = :keyID
    ";

    $stmtFinance = $pdo->prepare($sqlFinanceUpdate);
    $stmtFinance->execute([
        ':approvedAnnualBudget' => $_POST['approvedAnnualBudget'],
        ':AuditStatements' => $_POST['AuditStatements'],
        ':BankStatements' => $_POST['BankStatements'],
        ':scopeToExpand' => $_POST['scopeToExpand'],
        ':newInitiatives' => $_POST['newInitiatives'],
        ':helpNeeded' => $_POST['helpNeeded'],
        ':CoursesAndTrainings' => $_POST['CoursesAndTrainings'],
        ':AnyUpdates' => $_POST['AnyUpdates'],
        ':RemarksOfTheTeam' => $_POST['RemarksOfTheTeam'],
        ':NameOfTheAssessors' => $_POST['NameOfTheAssessors'],
        ':NameOfTheSisterIncharge' => $_POST['NameOfTheSisterIncharge'],
        ':keyID' => $keyID,
    ]);

    // Update AssessmentFinanceProjects table
    $projectIDs = $_POST['projectID'] ?? [];
    $projectNames = $_POST['projectName'] ?? [];
    $impactOfProjects = $_POST['impactOfProject'] ?? [];

    // Loop through project data and update or insert as needed
    foreach ($projectIDs as $index => $projectID) {
        $projectName = $projectNames[$index] ?? '';
        $impactOfProject = $impactOfProjects[$index] ?? '';

        if ($projectID) {
            // Update existing project record
            $sqlProjectUpdate = "
                UPDATE AssessmentFinanceProjects
                SET
                    projectName = :projectName,
                    impactOfProject = :impactOfProject,
                    updated_at = NOW()
                WHERE id = :projectID AND keyID = :keyID
            ";
            $stmtProject = $pdo->prepare($sqlProjectUpdate);
            $stmtProject->execute([
                ':projectName' => $projectName,
                ':impactOfProject' => $impactOfProject,
                ':projectID' => $projectID,
                ':keyID' => $keyID,
            ]);
        } else {
            // Insert new project record
            $sqlProjectInsert = "
                INSERT INTO AssessmentFinanceProjects (keyID, projectName, impactOfProject, created_at, updated_at)
                VALUES (:keyID, :projectName, :impactOfProject, NOW(), NOW())
            ";
            $stmtProject = $pdo->prepare($sqlProjectInsert);
            $stmtProject->execute([
                ':keyID' => $keyID,
                ':projectName' => $projectName,
                ':impactOfProject' => $impactOfProject,
            ]);
        }
    }

    // Commit transaction
    $pdo->commit();

    // Log the update action
    $logMessage = "Finance details and projects updated for keyID: $keyID by user: {$_SESSION['user_id']}";
    $logSql = "
        INSERT INTO ActivityLog (userID, action, description, timestamp)
        VALUES (:userID, 'Update', :description, NOW())
    ";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        ':userID' => $_SESSION['user_id'],
        ':description' => $logMessage,
    ]);

    $_SESSION['success'] = "Finance details and projects updated successfully.";
    header("Location: ../../View/Show/AssessmentCentreDetail.php?keyID=$keyID");
    exit();

} catch (Exception $e) {
    // Roll back transaction in case of an error
    $pdo->rollBack();
    $_SESSION['error'] = "Error updating finance details: " . $e->getMessage();
    header("Location: ../../View/dashboard.php");
    exit();
}
?>
