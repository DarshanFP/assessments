<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/dbh.inc.php';
require_once '../../includes/log_activity.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'] ?? null;
    $action = "Edit Assessment Staff Data";

    // Check if user is logged in
    if (!$userId) {
        $_SESSION['error'] = "User not logged in.";
        header("Location: ../../index.php");
        exit();
    }

    // Retrieve form data
    $keyID = $_POST['keyID'];
    $fields = [
        'staffBioData' => $_POST['staffBioData'] ?? null,
        'salaryBook' => $_POST['salaryBook'] ?? null,
        'commentSalaryBook' => $_POST['commentSalaryBook'] ?? null,
        'salaryParticulars' => $_POST['salaryParticulars'] ?? null,
        'commentSalaryParticulars' => $_POST['commentSalaryParticulars'] ?? null,
        'attendanceRegister' => $_POST['attendanceRegister'] ?? null,
        'commentAttendanceRegister' => $_POST['commentAttendanceRegister'] ?? null,
        'appointmentLetters' => $_POST['appointmentLetters'] ?? null,
        'commentAppointmentLetters' => $_POST['commentAppointmentLetters'] ?? null,
        'jobDescription' => $_POST['jobDescription'] ?? null,
        'commentJobDescription' => $_POST['commentJobDescription'] ?? null,
        'performanceAppraisal' => $_POST['performanceAppraisal'] ?? null,
        'commentPerformanceAppraisal' => $_POST['commentPerformanceAppraisal'] ?? null,
        'minutesOfStaffMeetings' => $_POST['minutesOfStaffMeetings'] ?? null,
        'commentMinutesOfStaffMeetings' => $_POST['commentMinutesOfStaffMeetings'] ?? null,
        'staffRecords' => $_POST['staffRecords'] ?? null,
        'commentStaffRecords' => $_POST['commentStaffRecords'] ?? null,
        'feedbackOfSisters' => $_POST['feedbackOfSisters'] ?? null,
        'commentFeedbackOfSisters' => $_POST['commentFeedbackOfSisters'] ?? null,
    ];

    try {
        // Begin database transaction
        $pdo->beginTransaction();

        // Prepare the update SQL statement dynamically
        $updateFields = [];
        foreach ($fields as $field => $value) {
            $updateFields[] = "$field = :$field";
        }
        $sql = "UPDATE AssessmentStaff SET " . implode(', ', $updateFields) . " WHERE keyID = :keyID";

        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $params = [':keyID' => $keyID];
        foreach ($fields as $field => $value) {
            $params[":$field"] = $value;
        }

        // Execute the statement
        $stmt->execute($params);

        // Commit the transaction
        $pdo->commit();

        // Log activity and redirect on success
        logActivityToDatabase($userId, $action, "success", "Staff data updated successfully for keyID: $keyID.");
        $_SESSION['success'] = "Data updated successfully.";
        header("Location: ../../View/Show/AssessmentCentreDetail.php?keyID=$keyID");
        exit();

    } catch (Exception $e) {
        // Roll back the transaction in case of an error
        $pdo->rollBack();

        // Log the error and redirect back with an error message
        logActivityToDatabase($userId, $action, "error", $e->getMessage());
        $_SESSION['error'] = "An error occurred: " . $e->getMessage();
        header("Location: ../../Edit/AssessmentStaffEdit.php?keyID=$keyID");
        exit();
    }
} else {
    // Redirect if not a POST request
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../../index.php");
    exit();
}
