<?php
// Start session
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: ../index.php");
    exit();
}

// Get the KeyID from URL parameter if available
$selectedKeyID = $_GET['keyID'] ?? null;

// Fetch the list of keyID values from the database
try {
    $sql = "SELECT keyID FROM Assessment ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $keyIDs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching keyID values: " . $e->getMessage();
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head class="text-center py-4">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">

<title>Assessment - Staff</title>
</head>
<body>
    <!-- Layout Container -->
    <div class="layout-container">
        
        <!-- Topbar -->
        <?php include '../topbar.php'; ?>
        
        <!-- Sidebar -->
        <?php include '../includes/role_based_sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            
            <!-- Content Container -->
            <div class="content-container">
                
                <!-- Page Content Goes Here -->
                
    <?php include '../topbar.php'; ?>
    <div class="flex flex-1 min-h-0">
            <!-- Main content here -->

        <div class="form-container p-6 bg-gray-100 flex-1">
        <h1 class="text-2xl font-bold text-center mt-5">Particulars of the Staff</h1><br> </br>

            <form action="../Controller/assessment_staff_process.php" method="POST" enctype="multipart/form-data">
                
                <!-- Date of Assessment -->
                <div class="form-group">
                    <label for="DateOfAssessment" class="block mb-2">Date Of Assessment:</label>
                    <input type="date" name="DateOfAssessment" id="DateOfAssessment" required class="w-full px-3 py-2 mb-2 border">
                </div>
                <!-- KeyID Dropdown Field -->
                <div class="form-group">
                    <label for="keyID" class="block mb-2">Select KeyID:</label>
                    <select name="keyID" id="keyID" required class="w-full px-3 py-2 mb-2 border">
                        <option disabled <?php echo !$selectedKeyID ? 'selected' : ''; ?>>-- Choose KeyID --</option>
                        <?php foreach ($keyIDs as $keyID): ?>
                            <option value="<?php echo htmlspecialchars($keyID['keyID']); ?>" 
                                    <?php echo ($selectedKeyID && $selectedKeyID === $keyID['keyID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($keyID['keyID']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                <h2 class="text-xl font-bold text-center mt-5">Particulars of the Staff:</h2><br>
                </div>
                <div class="form-group">
                    <label for="staffBioData" class="block mb-2">Staff's Bio-data:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="staffBioDataYes" name="staffBioData" value="yes" class="mr-2">
                        <label for="staffBioDataYes">Yes</label>
                        <input type="radio" id="staffBioDataNo" name="staffBioData" value="no" class="mr-2">
                        <label for="staffBioDataNo">No</label>
                    </div>
                </div>
                <div class="form-group"> 
                    <label for="salaryBook" class="block mb-2">Salary Book:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="salaryBookYes" name="salaryBook" value="yes" class="mr-2">
                        <label for="salaryBookYes">Yes</label>
                        <input type="radio" id="salaryBookNo" name="salaryBook" value="no" class="mr-2">
                        <label for="salaryBookNo">No</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="commentSalaryBook" class="block mb-2">Comment on Salary book:</label>
                    <textarea id="commentSalaryBook" name="commentSalaryBook" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>
                <div class="form-group">
                    <label for="salaryParticulars" class="block mb-2">Salary Particulars:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="salaryParticularsYes" name="salaryParticulars" value="yes" class="mr-2">
                        <label for="salaryParticularsYes">Yes</label>
                        <input type="radio" id="salaryParticularsNo" name="salaryParticulars" value="no" class="mr-2">
                        <label for="salaryParticularsNo">No</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="commentSalaryParticulars" class="block mb-2">Comment on salary particulars:</label>
                    <textarea id="commentSalaryParticulars" name="commentSalaryParticulars" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>
                <div class="form-group">
                    <label for="attendanceRegister" class="block mb-2">Attendance Register:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="attendanceRegisterYes" name="attendanceRegister" value="yes" class="mr-2">
                        <label for="attendanceRegisterYes">Yes</label>
                        <input type="radio" id="attendanceRegisterNo" name="attendanceRegister" value="no" class="mr-2">
                        <label for="attendanceRegisterNo">No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="commentAttendanceRegister" class="block mb-2">Comment on Attendance register:</label>
                    <textarea id="commentAttendanceRegister" name="commentAttendanceRegister" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>
                <div class="form-group">
                    <label for="appointmentLetters" class="block mb-2">Appointment Letters to the Staff:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="appointmentLettersYes" name="appointmentLetters" value="yes" class="mr-2">
                        <label for="appointmentLettersYes">Yes</label>
                        <input type="radio" id="appointmentLettersNo" name="appointmentLetters" value="no" class="mr-2">
                        <label for="appointmentLettersNo">No</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="commentAppointmentLetters" class="block mb-2">Comment on Appointment letter:</label>
                    <textarea id="commentAppointmentLetters" name="commentAppointmentLetters" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>
                <div class="form-group">
                    <label for="jobDescription" class="block mb-2">Job Description to the Staff:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="jobDescriptionYes" name="jobDescription" value="yes" class="mr-2">
                        <label for="jobDescriptionYes">Yes</label>
                        <input type="radio" id="jobDescriptionNo" name="jobDescription" value="no" class="mr-2">
                        <label for="jobDescriptionNo">No</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="commentJobDescription" class="block mb-2">Comment on Job description:</label>
                    <textarea id="commentJobDescription" name="commentJobDescription" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>
                <div class="form-group">
                    <label for="performanceAppraisal" class="block mb-2">Performance Appraisal of the Staff:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="performanceAppraisalYes" name="performanceAppraisal" value="yes" class="mr-2">
                        <label for="performanceAppraisalYes">Yes</label>
                        <input type="radio" id="performanceAppraisalNo" name="performanceAppraisal" value="no" class="mr-2">
                        <label for="performanceAppraisalNo">No</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="commentPerformanceAppraisal" class="block mb-2">Comment on performance appraisal of the staff:</label>
                    <textarea id="commentPerformanceAppraisal" name="commentPerformanceAppraisal" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>
                <div class="form-group">
                    <label for="minutesOfStaffMeetings" class="block mb-2">Minutes of Staff Meetings:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="minutesOfStaffMeetingsYes" name="minutesOfStaffMeetings" value="yes" class="mr-2">
                        <label for="minutesOfStaffMeetingsYes">Yes</label>
                        <input type="radio" id="minutesOfStaffMeetingsNo" name="minutesOfStaffMeetings" value="no" class="mr-2">
                        <label for="minutesOfStaffMeetingsNo">No</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="commentMinutesOfStaffMeetings" class="block mb-2">Comment on minutes of staff meetings:</label>
                    <textarea id="commentMinutesOfStaffMeetings" name="commentMinutesOfStaffMeetings" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>
                <div class="form-group">
                    <label for="staffRecords" class="block mb-2">Staff Records (Monthly Feedback to the Staff):</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="staffRecordsYes" name="staffRecords" value="yes" class="mr-2">
                        <label for="staffRecordsYes">Yes</label>
                        <input type="radio" id="staffRecordsNo" name="staffRecords" value="no" class="mr-2">
                        <label for="staffRecordsNo">No</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="commentStaffRecords" class="block mb-2">Comment on staff records (monthly feedback to the staff):</label>
                    <textarea id="commentStaffRecords" name="commentStaffRecords" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>
                <div class="form-group">
                    <label for="feedbackOfSisters" class="block mb-2">Feedback of Sisters for Themselves:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="feedbackOfSistersYes" name="feedbackOfSisters" value="yes" class="mr-2">
                        <label for="feedbackOfSistersYes">Yes</label>
                        <input type="radio" id="feedbackOfSistersNo" name="feedbackOfSisters" value="no" class="mr-2">
                        <label for="feedbackOfSistersNo">No</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="commentFeedbackOfSisters" class="block mb-2">Comment on feedback of sisters for themselves:</label>
                    <textarea id="commentFeedbackOfSisters" name="commentFeedbackOfSisters" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>
                
                    <input type="submit" value="Submit" class="px-4 py-2 bg-green-500 text-white border-none cursor-pointer">
            </form>
        </div>
    
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>

