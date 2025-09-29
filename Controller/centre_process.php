<?php
// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();

// Include required files
require_once '../includes/dbh.inc.php'; // Database connection
require_once '../includes/log_activity.php'; // Log activity
require_once '../includes/logger.inc.php'; // File logging

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    $action = "Centre Assessment";

    // Check if user is logged in
    if (!$userId) {
        $_SESSION['error'] = "User not logged in.";
        header("Location: ../index.php");
        exit();
    }

    try {
        // Begin a transaction
        $pdo->beginTransaction();

        // Retrieve form data
        $keyID = $_POST['keyID'] ?? null;
        $dateOfAssessment = $_POST['DateOfAssessment'] ?? null;
        $strengths = $_POST['Strengths'] ?? null;
        $areasOfAttention = $_POST['AreasOfAttention'] ?? null;
        $immediateAttention = $_POST['ImmediateAttention'] ?? null;
        $opportunitiesIdentified = $_POST['OpportunitiesIdentified'] ?? null;
        $challengesFaced = $_POST['ChallengesFaced'] ?? null;
        $stepsTaken = $_POST['StepsTaken'] ?? null;
        $helpRequested = $_POST['HelpRequested'] ?? null;
        $newVentures = $_POST['NewVentures'] ?? null;
        $listOfActivities = $_POST['ListOfActivities'] ?? null;
        $comments = $_POST['Comments'] ?? null;
        
        // Retrieve recommendation data
        $recommendationTexts = $_POST['recommendation_text'] ?? [];
        $recommendationTypes = $_POST['recommendation_type'] ?? [];
        $priorities = $_POST['priority'] ?? [];

        // Insert general assessment data into CentreAssessment
        $sql = "INSERT INTO CentreAssessment (
                    keyID, DateOfAssessment, Strengths, AreasOfAttention,
                    ImmediateAttention, OpportunitiesIdentified, ChallengesFaced,
                    StepsTaken, HelpRequested, NewVentures, ListOfActivities, Comments
                ) VALUES (
                    :keyID, :DateOfAssessment, :Strengths, :AreasOfAttention,
                    :ImmediateAttention, :OpportunitiesIdentified, :ChallengesFaced,
                    :StepsTaken, :HelpRequested, :NewVentures, :ListOfActivities, :Comments
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':keyID' => $keyID,
            ':DateOfAssessment' => $dateOfAssessment,
            ':Strengths' => $strengths,
            ':AreasOfAttention' => $areasOfAttention,
            ':ImmediateAttention' => $immediateAttention,
            ':OpportunitiesIdentified' => $opportunitiesIdentified,
            ':ChallengesFaced' => $challengesFaced,
            ':StepsTaken' => $stepsTaken,
            ':HelpRequested' => $helpRequested,
            ':NewVentures' => $newVentures,
            ':ListOfActivities' => $listOfActivities,
            ':Comments' => $comments
        ]);

        // Insert recommendation data into CentreRecommendations
        for ($i = 0; $i < count($recommendationTexts); $i++) {
            if (!empty(trim($recommendationTexts[$i]))) {
                $sqlRecommendation = "INSERT INTO CentreRecommendations (
                                        keyID, recommendation_text, recommendation_type,
                                        priority, status, created_by
                                    ) VALUES (
                                        :keyID, :recommendation_text, :recommendation_type,
                                        :priority, :status, :created_by
                                    )";

                $stmtRecommendation = $pdo->prepare($sqlRecommendation);
                $stmtRecommendation->execute([
                    ':keyID' => $keyID,
                    ':recommendation_text' => trim($recommendationTexts[$i]),
                    ':recommendation_type' => $recommendationTypes[$i],
                    ':priority' => $priorities[$i],
                    ':status' => 'Pending',
                    ':created_by' => $userId
                ]);
            }
        }

        // Insert project data into CentreProjects
        $projectNames = $_POST['ProjectName'] ?? [];
        $numberOfBeneficiaries = $_POST['NumberOfBeneficiaries'] ?? [];
        $projectSponsors = $_POST['ProjectSponsor'] ?? [];
        $otherSponsors = $_POST['OtherSponsor'] ?? [];

        for ($i = 0; $i < count($projectNames); $i++) {
            $sqlProject = "INSERT INTO CentreProjects (
                            keyID, ProjectName, NumberOfBeneficiaries,
                            ProjectSponsor, OtherSponsor
                        ) VALUES (
                            :keyID, :ProjectName, :NumberOfBeneficiaries,
                            :ProjectSponsor, :OtherSponsor
                        )";

            $stmtProject = $pdo->prepare($sqlProject);
            $stmtProject->execute([
                ':keyID' => $keyID,
                ':ProjectName' => $projectNames[$i],
                ':NumberOfBeneficiaries' => $numberOfBeneficiaries[$i],
                ':ProjectSponsor' => $projectSponsors[$i],
                ':OtherSponsor' => $otherSponsors[$i]
            ]);
        }

        // Commit the transaction
        $pdo->commit();

        // Log the activity
        logActivityToDatabase($userId, $action, "success", "Centre assessment data saved successfully.");
        $_SESSION['success'] = "Assessment completed successfully! All assessment forms have been submitted.";
        header("Location: ../View/CouncillorDashboard.php");
        exit();

    } catch (Exception $e) {
        // Roll back the transaction in case of an error
        $pdo->rollBack();
        logActivityToDatabase($userId, $action, "error", $e->getMessage());
        $_SESSION['error'] = "An error occurred while saving the data. Error: " . $e->getMessage();
        header("Location: ../View/centre.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../View/centre.php");
    exit();
}
?>
