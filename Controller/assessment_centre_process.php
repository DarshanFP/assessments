<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();

// Include the database connection file and logging functions
require_once '../includes/dbh.inc.php';
require_once '../includes/log_activity.php'; // Contains logActivityToDatabase()
require_once '../includes/logger.inc.php';   // Contains logActivityToFile()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null; // Get user ID from session
    $action = "Assessment Centre Submission";

    // Check if user is logged in
    if (!$userId) {
        $_SESSION['error'] = "User not logged in.";
        header("Location: ../index.php");
        exit();
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Get the community and generate the community code
        $community = $_POST['Community'];
        $communityCode = generateCommunityCode($community);

        // Get the current year and the next incremental number
        $year = date('Y');
        $incrementalNumber = getNextIncrementalNumber($pdo, $year);

        // Generate the keyID in the required format
        $keyID = sprintf("%s%s %02d", $communityCode, $year, $incrementalNumber);

        // Insert into the Assessment table using $keyID
        $sqlAssessment = "INSERT INTO Assessment (
                            keyID, DateOfAssessment, AssessorsName, Community, user_id
                          ) VALUES (
                            :keyID, :DateOfAssessment, :AssessorsName, :Community, :user_id
                          )";
        $stmtAssessment = $pdo->prepare($sqlAssessment);
        $stmtAssessment->execute([
            ':keyID'            => $keyID,
            ':DateOfAssessment' => $_POST['DateOfAssessment'],
            ':AssessorsName'    => $_POST['AssessorsName'],
            ':Community'        => $community,
            ':user_id'          => $userId
        ]);

        // Insert into AssessmentCentre table, including the keyID
        $sqlCentre = "INSERT INTO AssessmentCentre (
                        keyID, centreName, sistersInCharge,
                        centreHistory, feedbackReportRecord, shortTermGoals, commentShortTermGoals,
                        longTermGoals, commentLongTermGoals, inventory, commentInventory,
                        assetFile, commentAssetFiles
                      ) VALUES (
                        :keyID, :centreName, :sistersInCharge,
                        :centreHistory, :feedbackReportRecord, :shortTermGoals, :commentShortTermGoals,
                        :longTermGoals, :commentLongTermGoals, :inventory, :commentInventory,
                        :assetFile, :commentAssetFiles
                      )";

        $stmtCentre = $pdo->prepare($sqlCentre);

        // Bind parameters
        $stmtCentre->bindParam(':keyID', $keyID);
        $stmtCentre->bindParam(':centreName', $_POST['centreName']);
        $stmtCentre->bindParam(':sistersInCharge', $_POST['sistersInCharge']);
        $stmtCentre->bindParam(':centreHistory', $_POST['centreHistory']);
        $stmtCentre->bindParam(':feedbackReportRecord', $_POST['feedbackReportRecord']);
        $stmtCentre->bindParam(':shortTermGoals', $_POST['shortTermGoals']);
        $stmtCentre->bindParam(':commentShortTermGoals', $_POST['commentShortTermGoals']);
        $stmtCentre->bindParam(':longTermGoals', $_POST['longTermGoals']);
        $stmtCentre->bindParam(':commentLongTermGoals', $_POST['commentLongTermGoals']);
        $stmtCentre->bindParam(':inventory', $_POST['inventory']);
        $stmtCentre->bindParam(':commentInventory', $_POST['commentInventory']);
        $stmtCentre->bindParam(':assetFile', $_POST['assetFile']);
        $stmtCentre->bindParam(':commentAssetFiles', $_POST['commentAssetFiles']);

        // Execute the prepared statement
        $stmtCentre->execute();

        // Commit the transaction
        $pdo->commit();

        // Store keyID in session for use in subsequent forms
        $_SESSION['keyID'] = $keyID;

        // Log activity to the database
        logActivityToDatabase($userId, $action, "success", "Assessment Centre data saved successfully.");

        // Log activity to a file
        logActivityToFile("User ID $userId: Assessment Centre data saved successfully.", "info");

        // Set success message and redirect to Assessment Staff with the generated KeyID
        $_SESSION['success'] = "Assessment Centre data saved successfully. Please continue with Staff Assessment.";
        header("Location: ../View/AssessmentStaff.php?keyID=" . urlencode($keyID));
        exit();

    } catch (Exception $e) {
        // Roll back the transaction in case of error
        $pdo->rollBack();

        // Log activity to the database
        logActivityToDatabase($userId, $action, "error", $e->getMessage());

        // Log activity to a file
        logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

        // Set error message and redirect
        $_SESSION['error'] = "An error occurred while saving the data. Error: " . $e->getMessage();
        header("Location: ../View/AssessmentCentre.php");
        exit();
    }
} else {
    // Redirect if not a POST request
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../View/AssessmentCentre.php");
    exit();
}

// Function to generate community code
function generateCommunityCode($community) {
    // Convert to uppercase
    $community = strtoupper($community);

    // Define prefixes to remove
    $prefixes = ["ST. ANN'S ", "ST ANN'S ", "THE "];

    foreach ($prefixes as $prefix) {
        if (strpos($community, $prefix) === 0) {
            $community = substr($community, strlen($prefix));
            break;
        }
    }

    // Trim spaces
    $community = trim($community);

    // Get significant word (take last word)
    $words = explode(' ', $community);
    $significantWord = end($words);

    // Remove any non-alphabetic characters
    $significantWord = preg_replace("/[^A-Z]/", "", $significantWord);

    // Take first four letters
    $communityCode = substr($significantWord, 0, 4);

    // Ensure it's 4 characters
    $communityCode = str_pad($communityCode, 4, 'X');

    return $communityCode;
}

// Function to get the next incremental number for the current year
function getNextIncrementalNumber($pdo, $year) {
    $sql = "SELECT COUNT(*) FROM Assessment WHERE keyID LIKE :yearPattern";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':yearPattern' => "%$year%"]);
    $count = $stmt->fetchColumn();

    // Increment count by 1 and return a 2-digit formatted number
    return str_pad($count + 1, 2, '0', STR_PAD_LEFT);
}
