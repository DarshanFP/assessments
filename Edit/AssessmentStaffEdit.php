<?php
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get the keyID from the URL
$keyID = $_GET['keyID'] ?? null;

if (!$keyID) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../View/dashboard.php");
    exit();
}

// Fetch the existing data for the given keyID
try {
    $sql = "SELECT * FROM AssessmentStaff WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $staffData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staffData) {
        $_SESSION['error'] = "No data found for the given keyID.";
        header("Location: ../View/dashboard.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching data: " . $e->getMessage();
    header("Location: ../View/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Edit Assessment Staff</title>
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
        <div class="form-container p-6 bg-gray-100 flex-1">
            <h1 class="text-2xl font-bold text-center mt-5">Edit Particulars of the Staff</h1>
            <div class="text-left mt-5">
            <a href="../Controller/Show/AssessmentCentreDetailController.php?keyID=<?php echo urlencode($staffData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                Back to View
            </a>

            </div>
            <form action="../Controller/Edit/assessment_staff_edit_process.php" method="POST">
                <input type="hidden" name="keyID" value="<?php echo htmlspecialchars($staffData['keyID']); ?>">

                <?php
                // Function to render radio button fields
                function renderRadioField($name, $value) {
                    return "
                        <div class='form-group'>
                            <label for='$name' class='block mb-2'>".ucwords(str_replace('_', ' ', $name)).":</label>
                            <div class='flex items-center space-x-4'>
                                <input type='radio' id='{$name}Yes' name='$name' value='yes' class='mr-2' ".($value === 'yes' ? 'checked' : '').">
                                <label for='{$name}Yes'>Yes</label>
                                <input type='radio' id='{$name}No' name='$name' value='no' class='mr-2' ".($value === 'no' ? 'checked' : '').">
                                <label for='{$name}No'>No</label>
                            </div>
                        </div>
                    ";
                }
                ?>

                <!-- Render all fields -->

                <?= renderRadioField('staffBioData', $staffData['staffBioData']) ?>
                <?= renderRadioField('salaryBook', $staffData['salaryBook']) ?>
                <div class="form-group">
                    <label for="commentSalaryBook">Comment on Salary Book:</label>
                    <textarea name="commentSalaryBook" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($staffData['commentSalaryBook']); ?></textarea>
                </div>

                <?= renderRadioField('salaryParticulars', $staffData['salaryParticulars']) ?>
                <div class="form-group">
                    <label for="commentSalaryParticulars">Comment on Salary Particulars:</label>
                    <textarea name="commentSalaryParticulars" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($staffData['commentSalaryParticulars']); ?></textarea>
                </div>

                <?= renderRadioField('attendanceRegister', $staffData['attendanceRegister']) ?>
                <div class="form-group">
                    <label for="commentAttendanceRegister">Comment on Attendance Register:</label>
                    <textarea name="commentAttendanceRegister" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($staffData['commentAttendanceRegister']); ?></textarea>
                </div>

                <?= renderRadioField('appointmentLetters', $staffData['appointmentLetters']) ?>
                <div class="form-group">
                    <label for="commentAppointmentLetters">Comment on Appointment Letters:</label>
                    <textarea name="commentAppointmentLetters" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($staffData['commentAppointmentLetters']); ?></textarea>
                </div>

                <?= renderRadioField('jobDescription', $staffData['jobDescription']) ?>
                <div class="form-group">
                    <label for="commentJobDescription">Comment on Job Description:</label>
                    <textarea name="commentJobDescription" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($staffData['commentJobDescription']); ?></textarea>
                </div>

                <?= renderRadioField('performanceAppraisal', $staffData['performanceAppraisal']) ?>
                <div class="form-group">
                    <label for="commentPerformanceAppraisal">Comment on Performance Appraisal:</label>
                    <textarea name="commentPerformanceAppraisal" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($staffData['commentPerformanceAppraisal']); ?></textarea>
                </div>

                <?= renderRadioField('minutesOfStaffMeetings', $staffData['minutesOfStaffMeetings']) ?>
                <div class="form-group">
                    <label for="commentMinutesOfStaffMeetings">Comment on Minutes of Staff Meetings:</label>
                    <textarea name="commentMinutesOfStaffMeetings" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($staffData['commentMinutesOfStaffMeetings']); ?></textarea>
                </div>

                <?= renderRadioField('staffRecords', $staffData['staffRecords']) ?>
                <div class="form-group">
                    <label for="commentStaffRecords">Comment on Staff Records:</label>
                    <textarea name="commentStaffRecords" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($staffData['commentStaffRecords']); ?></textarea>
                </div>

                <?= renderRadioField('feedbackOfSisters', $staffData['feedbackOfSisters']) ?>
                <div class="form-group">
                    <label for="commentFeedbackOfSisters">Comment on Feedback of Sisters:</label>
                    <textarea name="commentFeedbackOfSisters" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($staffData['commentFeedbackOfSisters']); ?></textarea>
                </div>

                <!-- Submit Button -->
                <div class="form-group mt-5">
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white">Save Changes</button>
                    <a href="../View/Show/AssessmentCentreDetail.php?keyID=<?php echo urlencode($staffData['keyID']); ?>" class="px-4 py-2 bg-gray-500 text-white">Cancel</a>
                </div>
            </form>
        </div>
    </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
