<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

// Check if assessment data is set
if (!isset($_SESSION['centreData'])) {
    $_SESSION['error'] = "No data available.";
    header("Location: ../dashboard.php");
    exit();
}

// Get the assessment data
$assessmentData = $_SESSION['assessmentData'];
$centreData = $_SESSION['centreData'];
$staffData = $_SESSION['staffData'] ?? [];
$centreAssessmentData = $_SESSION['centreAssessmentData'] ?? [];
$centreProjectsData = $_SESSION['centreProjectsData'] ?? [];
$sistersData = $_SESSION['sistersData'] ?? [];
$sisterCardsData = $_SESSION['sisterCardsData'] ?? [];
$programmeData = $_SESSION['programmeData'] ?? [];
$financeData = $_SESSION['financeData'] ?? [];
$financeProjectsData = $_SESSION['financeProjectsData'] ?? [];
$documentationData = $_SESSION['documentationData'] ?? [];
$recommendationsData = $_SESSION['recommendationsData'] ?? [];

// Optionally, unset the data from session to avoid stale data
//unset($_SESSION['centreData'], $_SESSION['staffData'], $_SESSION['centreAssessmentData'], $_SESSION['centreProjectsData'], $_SESSION['sistersData'], $_SESSION['sisterCardsData'], $_SESSION['programmeData'], $_SESSION['financeData'], $_SESSION['financeProjectsData'], $_SESSION['documentationData']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Assessment Centre Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../../unified.css">
    <style>
    /* Common Table Style */
    .styled-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 16px;
        text-align: left;
    }

    .styled-table thead tr {
        background-color: #f3f4f6;
        color: #333;
        text-align: left;
    }

    .styled-table th,
    .styled-table td {
        padding: 12px 15px;
        border: 1px solid #ddd;
    }

    .styled-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .styled-table tbody tr:hover {
        background-color: #e2e8f0;
    }

    .styled-table th {
        background-color: #4a5568;
        color: #fff;
    }
    /* Fixed Column Sizes */
    .col-small {
        width: 25%;
    }

    .col-medium {
        width: 40%;
    }

    .col-large {
        width: 50%;
        
    }
    
</style>

</head>
<body>
    <!-- Layout Container -->
    <div class="layout-container">
        
        <!-- Topbar -->
        <?php include '../../topbar.php'; ?>
        
        <!-- Sidebar -->
        <?php include '../../includes/role_based_sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            
            <!-- Content Container -->
            <div class="content-container">
                
                <!-- Page Content Goes Here -->
                
    <?php include '../../topbar.php'; ?>
    <div class="flex flex-1 min-h-0">
        <div class="p-6 bg-gray-100 flex-1">
            <br> <br><h1 class="text-2xl font-bold text-center mt-5">Assessment Centre Detail</h1>

            <div class="mt-5">
                <a href="../../View/dashboard.php" class="px-4 py-2 bg-blue-500 text-white">Back to Dashboard</a>
            </div>

            <!-- Display the Assessment Data -->
            <h2 class="text-xl font-bold mt-5">General Information</h2>
            <table class="styled-table">
                <tbody>
                    <tr>
                        <th class="col-medium">Key ID</th>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($assessmentData['keyID']); ?></td>
                    <tr>
                        <th class="col-medium">Date of Assessment</th>
                        <td class="px-4 py-2"><?php echo htmlspecialchars( $assessmentData['DateOfAssessment']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Assessor's Name</th>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($assessmentData['AssessorsName']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Community</th>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($assessmentData['Community']); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Particulars of the Centre -->
            <h2 class="text-xl font-bold mt-5">Particulars of the Centre</h2>
            <table class="styled-table">
                <tbody>
                    <tr>
                        <th class="col-medium">Name of the Centre</th>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($centreData['centreName']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Sister(s) in Charge</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['sistersInCharge']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">History of the Centre</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['centreHistory']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Feedback Report Record</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['feedbackReportRecord']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Short Term Goals</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['shortTermGoals']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Comments on Short Term Goals</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['commentShortTermGoals']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Long Term Goals</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['longTermGoals']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Comments on Long Term Goals</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['commentLongTermGoals']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Inventory</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['inventory']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Comments on Inventory</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['commentInventory']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Asset File</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['assetFile']); ?></td>
                    </tr>
                    <tr>
                        <th class="col-medium">Comments on Asset Files</th>
                        <td class="px-4 py-2"><?php echo  htmlspecialchars($centreData['commentAssetFiles']); ?></td>
                    </tr>
                </tbody>
            </table>
            <!-- Edit Button -->
            <div class="text-center mt-5">
                <a href="../../Edit/AssessmentCentreEdit.php?keyID=<?php echo urlencode($financeData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Edit Assessment Center 
                </a>
            </div>
            <!-- Staff Details -->
            <?php if ($staffData): ?>
                <h2 class="text-xl font-bold mt-5">Particulars of the Staff</h2>
                <table class="styled-table">
                    <tbody>
                        <tr>
                            <th class="col-medium">Staff's Bio-data</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['staffBioData']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Salary Book</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['salaryBook']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comment on Salary book</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['commentSalaryBook']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Salary Particulars</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['salaryParticulars']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comment on salary particulars</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['commentSalaryParticulars']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Attendance Register</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['attendanceRegister']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comment on Attendance register</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['commentAttendanceRegister']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Appointment Letters to the Staff</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['appointmentLetters']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comment on Appointment letter</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['commentAppointmentLetters']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Job Description to the Staff</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['jobDescription']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comment on Job description</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['commentJobDescription']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Performance Appraisal of the Staff</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['performanceAppraisal']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comment on performance appraisal of the staff</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['commentPerformanceAppraisal']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Minutes of Staff Meetings</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['minutesOfStaffMeetings']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comment on minutes of staff meetings</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['commentMinutesOfStaffMeetings']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Staff Records (Monthly Feedback to the Staff)</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['staffRecords']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comment on staff records (monthly feedback to the staff)</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['commentStaffRecords']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Feedback of Sisters for Themselves</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['feedbackOfSisters']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comment on feedback of sisters for themselves</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($staffData['commentFeedbackOfSisters']); ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
            <!-- edit button STAFF-->
            <div class="text-center mt-5">
                <a href="../../Edit/AssessmentStaffEdit.php?keyID=<?php echo urlencode($staffData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Edit Assessment Staff</a>
            </div>
         
            <!-- Sisters Details -->
            <?php if ($sistersData): ?>
                <h2 class="text-xl font-bold mt-5">Particulars of the Sisters</h2>
                <table class="styled-table">
                    <tbody>
                        <tr>
                            <th class="col-medium">Number of Sisters in the Centre</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($sistersData['NoOfSisters']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Chronicle for each sister (to be maintained)</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($sistersData['Chronicle']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comments on Chronicle for each sister (to be maintained)</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($sistersData['chronicleComment']); ?></td>
                        </tr>
                        
                    </tbody>
                </table>

                <!-- Sister Cards -->
                <?php if ($sisterCardsData): ?>
                    <h3 class="text-lg font-bold mt-3">Sister Cards</h3>
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th class="px-4 py-2">Serial Number</th>
                                <th class="px-4 py-2">Date of Starting</th>
                                <th class="px-4 py-2">Programme</th>
                                <th class="px-4 py-2">Sister In Charge</th>
                                <th class="px-4 py-2">Number of Staff</th>
                                <th class="px-4 py-2">Number of Beneficiaries</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sisterCardsData as $card): ?>
                                <tr>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['SerialNumber']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['DateOfStarting']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['Programme']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['SisterInCharge']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['NoOfStaff']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['NoOfBeneficiaries']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
            <!-- Edit Button for "Particulars of the Sisters" Section -->
            <div cclass="text-center mt-5">
                <a href="../../Edit/AssessmentSistersEdit.php?keyID=<?php echo urlencode($centreData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Edit Assessmemnt Sisters
                </a>
            </div>
            

            <!-- Programme Details -->
            <?php if ($programmeData): ?>
                <h2 class="text-xl font-bold mt-5">Particulars of the Programme</h2>
                <table class="styled-table">
                    <tbody>
                        <tr>
                            <th class="col-medium">Success Stories Record</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($programmeData['SuccessStories']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Media Coverage Record</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($programmeData['MediaCoverage']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Collaboration with Government/NGOs</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($programmeData['CollaborationGoNgos' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Photographs</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($programmeData['Photographs']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Field Inspection</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($programmeData['FieldInspection']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">List of Activities</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($programmeData['ActivitiesList']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Recommendations</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($programmeData['Recommendations']); ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
            <!-- Edit Button -->
            <div class="text-center mt-5">
                <a href="../../Edit/AssessmentProgrammeEdit.php?keyID=<?php echo urlencode($programmeData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Edit Programme Details
                </a>
            </div>
            
            <!-- Documentation Details -->
            <?php if ($documentationData): ?>
                <h2 class="text-xl font-bold mt-5">Particulars of the Documentation</h2>
                <table class="styled-table">
                    <tbody>
                        <tr>
                            <th class="col-medium">Feedback Report</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($documentationData['feedbackReport' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Annual Reports</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['annualReports']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Data of the Beneficiaries</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['beneficiariesData']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Beneficiaries Type</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($documentationData['beneficiariesType' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Number of Beneficiaries</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['beneficiariesCount']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Request & Thanking Letters from the beneficiaries</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['requestThankingLetters']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Bye-laws</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['bylaws']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Amendment</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['amendment']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">PAN</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['pan']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Registration copies (originals) - applicable only for women society</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['womenSocietyRegistrationCopies']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Lease Agreement</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['leaseAgreement']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Monthly Reports</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['monthlyReports']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">LSAC Minutes</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($documentationData['lsacMinutes']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Comments on LSAC Minutes</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($documentationData['lsacMinutesText' ]); ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
            <!-- Edit Button for Assessment Documentation -->
            <div class="text-center mt-5">
                <a href="../../Edit/AssessmentDocumentationEdit.php?keyID=<?php echo urlencode($documentationData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Edit Documentation Details
                </a>

            </div>


            <!-- Finance Details -->
            <!-- Finance Assessment Details -->
            <?php if ($financeData): ?>
                <h2 class="text-xl font-bold mt-5">Particulars of the Finance (Projects & Women Society)</h2>
                <table class="styled-table">
                    <tbody>
                        <tr>
                            <th class="col-medium">Approved Annual Budget</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($financeData['approvedAnnualBudget' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Audit Statements</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($financeData['AuditStatements' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Bank Statements</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($financeData['BankStatements' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Scope to Expand/Strengthen Initiatives</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($financeData['scopeToExpand' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">New Initiatives</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($financeData['newInitiatives' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Type of Help Needed</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($financeData['helpNeeded' ]); ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Finance Projects Details (Moved before Additional Fields) -->
            <?php if ($financeProjectsData): ?>
                <h3 class="text-lg font-bold mt-5">Financed Projects List</h3>
                <table class="styled-table">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="col-medium">Project Name</th>
                            <th class="col-medium">Impact of Project</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($financeProjectsData as $project): ?>
                            <tr>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($project['projectName']); ?></td>
                                <td class="px-4 py-2"><?php echo  htmlspecialchars($project['impactOfProject' ]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Additional Finance Details -->
            <?php if ($financeData): ?>
                <table class="styled-table">
                    <tbody>
                        <tr>
                            <th class="col-medium">Courses and Trainings Conducted</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($financeData['CoursesAndTrainings' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Any Updates</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($financeData['AnyUpdates' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Remarks of the Team</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($financeData['RemarksOfTheTeam' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Name of the Assessors</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($financeData['NameOfTheAssessors']); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Name of the Sister In-Charge</th>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($financeData['NameOfTheSisterIncharge']); ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
            <!-- Edit Button for Finance Details and Projects -->
<div class="text-center mt-5">
    <a href="../../Edit/FinanceEdit.php?keyID=<?php echo urlencode($financeData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
        Edit Finance Details and Projects
    </a>
</div>



            <!-- Centre Assessment Details -->
            <?php if ($centreAssessmentData): ?>
                <h2 class="text-xl font-bold mt-5">Centre Assessment</h2>
                <table class="styled-table">
                    <tbody>
                        <tr>
                            <th class="col-medium">Strengths</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($centreAssessmentData['Strengths' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Areas of Attention</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($centreAssessmentData['AreasOfAttention' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Areas of Immediate Attention</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($centreAssessmentData['ImmediateAttention' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Opportunities Identified</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($centreAssessmentData['OpportunitiesIdentified' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Challenges Faced</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($centreAssessmentData['ChallengesFaced' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Steps Taken to Overcome Challenges</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($centreAssessmentData['StepsTaken' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">Type of Help Requested</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($centreAssessmentData['HelpRequested' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">New Ventures</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($centreAssessmentData['NewVentures' ]); ?></td>
                        </tr>
                        <tr>
                            <th class="col-medium">List of Activities</th>
                            <td class="px-4 py-2"><?php echo  htmlspecialchars($centreAssessmentData['ListOfActivities' ]); ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Centre Projects Details -->
            <?php if ($centreProjectsData): ?>
                <h3 class="text-lg font-bold mt-5">Centre Projects</h3>
                <table class="styled-table">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="col-small">Project Name</th>
                            <th class="col-small">Number of Beneficiaries</th>
                            <th class="col-small">Project Sponsor</th>
                            <th class="col-small">Other Sponsor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($centreProjectsData as $project): ?>
                            <tr>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($project['ProjectName']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($project['NumberOfBeneficiaries']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($project['ProjectSponsor']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($project['OtherSponsor']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
           

            <!-- Recommendations / Suggestions / Feedback -->
            <?php if (!empty($recommendationsData)): ?>
                <h2 class="text-xl font-bold mt-5">Recommendations / Suggestions / Feedback</h2>
                <div class="space-y-4">
                    <?php foreach ($recommendationsData as $rec): ?>
                        <div class="bg-white p-4 rounded-lg shadow border">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex space-x-2">
                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                        <?php 
                                        switch($rec['recommendation_type']) {
                                            case 'Recommendation': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'Suggestion': echo 'bg-green-100 text-green-800'; break;
                                            case 'Feedback': echo 'bg-purple-100 text-purple-800'; break;
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($rec['recommendation_type']); ?>
                                    </span>
                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                        <?php 
                                        switch($rec['priority']) {
                                            case 'Low': echo 'bg-gray-100 text-gray-800'; break;
                                            case 'Medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'High': echo 'bg-orange-100 text-orange-800'; break;
                                            case 'Critical': echo 'bg-red-100 text-red-800'; break;
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($rec['priority']); ?>
                                    </span>
                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                        <?php 
                                        switch($rec['status']) {
                                            case 'Pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'In Progress': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'Completed': echo 'bg-green-100 text-green-800'; break;
                                            case 'Rejected': echo 'bg-red-100 text-red-800'; break;
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($rec['status']); ?>
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($rec['created_at'])); ?>
                                </div>
                            </div>
                            <div class="text-gray-900">
                                <?php echo nl2br(htmlspecialchars($rec['recommendation_text'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($centreAssessmentData && !empty($centreAssessmentData['Comments'])): ?>
                <!-- Fallback to old Comments field for backward compatibility -->
                <h2 class="text-xl font-bold mt-5">Recommendations / Suggestions / Feedback</h2>
                <div class="bg-white p-4 rounded-lg shadow border">
                    <div class="text-gray-900">
                        <?php echo nl2br(htmlspecialchars($centreAssessmentData['Comments'])); ?>
                    </div>
                </div>
            <?php endif; ?>

                <!-- Edit Button Centre Assessment -->
            <div class="text-center mt-5">
                <a href="../../Edit/centre_edit.php?keyID=<?php echo urlencode($centreData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">Edit Centre Assessment</a>
            </div>
            

            
            

           

            <!-- Back Button -->
            <div class="mt-5">
                <a href="../../View/dashboard.php" class="px-4 py-2 bg-blue-500 text-white">Back to Dashboard</a>
            </div>
        </div>
    </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div></body>
</html>
