<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Include required files
require_once '../../includes/dbh.inc.php';         // Database connection
require_once '../../includes/log_activity.php';    // Database logging
require_once '../../includes/logger.inc.php';      // File logging

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Retrieve user ID from session
$userId = $_SESSION['user_id'];
$action = "Fetch Data";

// Validate database connection
if (!$pdo) {
    logActivityToDatabase($userId, $action, "error", "Database connection failed.");
    logActivityToFile("User ID $userId: Database connection failed.", "error");
    $_SESSION['error'] = "Database connection failed.";
    header("Location: ../../View/dashboard.php");
    exit();
}

// Check if 'keyID' is provided via GET
if (!isset($_GET['keyID'])) {
    logActivityToDatabase($userId, $action, "error", "No KeyID provided.");
    logActivityToFile("User ID $userId: No KeyID provided.", "error");
    $_SESSION['error'] = "No KeyID provided.";
    header("Location: ../../View/dashboard.php");
    exit();
}

$keyID = $_GET['keyID'];

try {
    // Start a transaction
    $pdo->beginTransaction();

    // Fetch data from 'Assessment' table
    $sqlAssessment = "SELECT * FROM Assessment WHERE keyID = :keyID";
    $stmtAssessment = $pdo->prepare($sqlAssessment);
    $stmtAssessment->execute([':keyID' => $keyID]);
    $assessmentData = $stmtAssessment->fetch(PDO::FETCH_ASSOC);

    if (!$assessmentData) {
        logActivityToDatabase($userId, $action, "error", "No assessment data found for KeyID: $keyID.");
        logActivityToFile("User ID $userId: No assessment data found for KeyID: $keyID.", "error");
        $_SESSION['error'] = "No assessment data found for KeyID: $keyID.";
        header("Location: ../../View/dashboard.php");
        exit();
    }

    // Fetch data from 'AssessmentCentre'
    $sqlCentre = "SELECT * FROM AssessmentCentre WHERE keyID = :keyID";
    $stmtCentre = $pdo->prepare($sqlCentre);
    $stmtCentre->execute([':keyID' => $keyID]);
    $centreData = $stmtCentre->fetch(PDO::FETCH_ASSOC);

    if (!$centreData) {
        logActivityToDatabase($userId, $action, "error", "No centre data found for KeyID: $keyID.");
        logActivityToFile("User ID $userId: No centre data found for KeyID: $keyID.", "error");
        $_SESSION['error'] = "No centre data found for KeyID: $keyID.";
        header("Location: ../../View/dashboard.php");
        exit();
    }

    // Fetch additional data
    $sqlStaff = "SELECT * FROM AssessmentStaff WHERE keyID = :keyID";
    $stmtStaff = $pdo->prepare($sqlStaff);
    $stmtStaff->execute([':keyID' => $keyID]);
    $staffData = $stmtStaff->fetch(PDO::FETCH_ASSOC) ?? [];

    $sqlSisters = "SELECT * FROM AssessmentSisters WHERE keyID = :keyID";
    $stmtSisters = $pdo->prepare($sqlSisters);
    $stmtSisters->execute([':keyID' => $keyID]);
    $sistersData = $stmtSisters->fetch(PDO::FETCH_ASSOC) ?? [];

    $sqlSisterCards = "SELECT * FROM AssessmentSisterCards WHERE keyID = :keyID";
    $stmtSisterCards = $pdo->prepare($sqlSisterCards);
    $stmtSisterCards->execute([':keyID' => $keyID]);
    $sisterCardsData = $stmtSisterCards->fetchAll(PDO::FETCH_ASSOC) ?? [];

    $sqlCentreAssessment = "SELECT * FROM CentreAssessment WHERE keyID = :keyID";
    $stmtCentreAssessment = $pdo->prepare($sqlCentreAssessment);
    $stmtCentreAssessment->execute([':keyID' => $keyID]);
    $centreAssessmentData = $stmtCentreAssessment->fetch(PDO::FETCH_ASSOC) ?? [];

    $sqlCentreProjects = "SELECT * FROM CentreProjects WHERE keyID = :keyID";
    $stmtCentreProjects = $pdo->prepare($sqlCentreProjects);
    $stmtCentreProjects->execute([':keyID' => $keyID]);
    $centreProjectsData = $stmtCentreProjects->fetchAll(PDO::FETCH_ASSOC) ?? [];

    $sqlProgramme = "SELECT * FROM AssessmentProgramme WHERE keyID = :keyID";
    $stmtProgramme = $pdo->prepare($sqlProgramme);
    $stmtProgramme->execute([':keyID' => $keyID]);
    $programmeData = $stmtProgramme->fetch(PDO::FETCH_ASSOC) ?? [];

    $sqlFinance = "SELECT * FROM AssessmentFinance WHERE keyID = :keyID";
    $stmtFinance = $pdo->prepare($sqlFinance);
    $stmtFinance->execute([':keyID' => $keyID]);
    $financeData = $stmtFinance->fetch(PDO::FETCH_ASSOC) ?? [];

    $sqlFinanceProjects = "SELECT * FROM AssessmentFinanceProjects WHERE keyID = :keyID";
    $stmtFinanceProjects = $pdo->prepare($sqlFinanceProjects);
    $stmtFinanceProjects->execute([':keyID' => $keyID]);
    $financeProjectsData = $stmtFinanceProjects->fetchAll(PDO::FETCH_ASSOC) ?? [];

    $sqlDocumentation = "SELECT * FROM AssessmentDocumentation WHERE keyID = :keyID";
    $stmtDocumentation = $pdo->prepare($sqlDocumentation);
    $stmtDocumentation->execute([':keyID' => $keyID]);
    $documentationData = $stmtDocumentation->fetch(PDO::FETCH_ASSOC) ?? [];

    // Fetch recommendations data
    $sqlRecommendations = "SELECT * FROM CentreRecommendations WHERE keyID = :keyID ORDER BY created_at DESC";
    $stmtRecommendations = $pdo->prepare($sqlRecommendations);
    $stmtRecommendations->execute([':keyID' => $keyID]);
    $recommendationsData = $stmtRecommendations->fetchAll(PDO::FETCH_ASSOC) ?? [];

    // Commit the transaction
    $pdo->commit();

    // Store data in session
    $_SESSION['assessmentData'] = $assessmentData;
    $_SESSION['centreData'] = $centreData;
    $_SESSION['staffData'] = $staffData;
    $_SESSION['sistersData'] = $sistersData;
    $_SESSION['sisterCardsData'] = $sisterCardsData;
    $_SESSION['centreAssessmentData'] = $centreAssessmentData;
    $_SESSION['centreProjectsData'] = $centreProjectsData;
    $_SESSION['programmeData'] = $programmeData;
    $_SESSION['financeData'] = $financeData;
    $_SESSION['financeProjectsData'] = $financeProjectsData;
    $_SESSION['documentationData'] = $documentationData;
    $_SESSION['recommendationsData'] = $recommendationsData;

    // Log success
    logActivityToDatabase($userId, $action, "success", "Data fetched successfully for KeyID: $keyID.");
    logActivityToFile("User ID $userId: Data fetched successfully for KeyID: $keyID.", "info");

    // Redirect to the detail view
    header("Location: ../../View/Show/AssessmentCentreDetail.php");
    exit();

} catch (Exception $e) {
    // Rollback the transaction in case of an error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log error
    logActivityToDatabase($userId, $action, "error", "Error fetching data for KeyID $keyID: " . $e->getMessage());
    logActivityToFile("User ID $userId: Error fetching data for KeyID $keyID - " . $e->getMessage(), "error");

    // Set error message and redirect
    $_SESSION['error'] = "An error occurred while fetching the data.";
    header("Location: ../../View/dashboard.php");
    exit();
}
?>
