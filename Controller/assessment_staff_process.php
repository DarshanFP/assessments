<?php
// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();

// Include required files
require_once '../includes/dbh.inc.php'; // Database connection
require_once '../includes/log_activity.php'; // Log activity functions
require_once '../includes/logger.inc.php'; // File logging

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../View/AssessmentStaff.php");
    exit();
}

// Retrieve user ID from session
$userId = $_SESSION['user_id'] ?? null;
$action = "Assessment - Staff";

// Check if user is logged in
if (!$userId) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: ../index.php");
    exit();
}

try {
    // Begin a database transaction
    $pdo->beginTransaction();

    // Retrieve form data
    $keyID = $_POST['keyID'] ?? null;
    $date_of_assessment = $_POST['DateOfAssessment'] ?? null;

    // Map form field names to database field names
    $fields_map = [
        'staffBioData' => 'staffBioData',
        'salaryBook' => 'salaryBook',
        'commentSalaryBook' => 'commentSalaryBook',
        'salaryParticulars' => 'salaryParticulars',
        'commentSalaryParticulars' => 'commentSalaryParticulars',
        'attendanceRegister' => 'attendanceRegister',
        'commentAttendanceRegister' => 'commentAttendanceRegister',
        'appointmentLetters' => 'appointmentLetters',
        'commentAppointmentLetters' => 'commentAppointmentLetters',
        'jobDescription' => 'jobDescription',
        'commentJobDescription' => 'commentJobDescription',
        'performanceAppraisal' => 'performanceAppraisal',
        'commentPerformanceAppraisal' => 'commentPerformanceAppraisal',
        'minutesOfStaffMeetings' => 'minutesOfStaffMeetings',
        'commentMinutesOfStaffMeetings' => 'commentMinutesOfStaffMeetings',
        'staffRecords' => 'staffRecords',
        'commentStaffRecords' => 'commentStaffRecords',
        'feedbackOfSisters' => 'feedbackOfSisters',
        'commentFeedbackOfSisters' => 'commentFeedbackOfSisters',
    ];

    // Initialize $fields array
    $fields = [];

    // Loop through the fields and set values from POST request, using NULL if not provided
    foreach ($fields_map as $form_field => $db_field) {
        $fields[$db_field] = $_POST[$form_field] ?? null;
    }

    // Prepare the SQL statement dynamically
    $sql_fields = implode(", ", array_keys($fields));
    $sql_placeholders = ":" . implode(", :", array_keys($fields));

    $sql = "INSERT INTO AssessmentStaff (
                keyID, dateOfAssessment, $sql_fields
            ) VALUES (
                :keyID, :dateOfAssessment, $sql_placeholders
            )";

    // Prepare the SQL statement
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $params = [
        ':keyID' => $keyID,
        ':dateOfAssessment' => $date_of_assessment,
    ];

    foreach ($fields as $db_field => $value) {
        $params[':' . $db_field] = $value;
    }

    // Execute the statement
    $stmt->execute($params);

    // Commit the transaction
    $pdo->commit();

    // Log activity on successful form submission
    logActivityToDatabase($userId, $action, "success", "Assessment staff form submitted successfully.");
    logActivityToFile("User ID $userId: Assessment staff form submitted successfully.", "info");

    // Set success message and redirect to Assessment Sisters with the KeyID
    $_SESSION['success'] = "Assessment Staff data saved successfully. Please continue with Sisters Assessment.";
    header("Location: ../View/AssessmentSisters.php?keyID=" . urlencode($keyID));
    exit();

} catch (Exception $e) {
    // Roll back the transaction in case of an error
    $pdo->rollBack();

    // Log the error
    logActivityToDatabase($userId, $action, "error", $e->getMessage());
    logActivityToFile("User ID $userId: Error occurred - " . $e->getMessage(), "error");

    // Set error message and redirect back to the form
    $_SESSION['error'] = "An error occurred while saving the data. Please try again.";
    header("Location: ../View/AssessmentStaff.php");
    exit();
}
