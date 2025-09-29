<?php
// Start session
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check if user is Project In-Charge
if ($_SESSION['role'] !== 'Project In-Charge') {
    $_SESSION['error'] = "Access denied. This page is for Project In-Charge users only.";
    header("Location: ../View/dashboard.php");
    exit();
}

// Check if keyID is provided
if (!isset($_GET['keyID'])) {
    $_SESSION['error'] = "No KeyID provided.";
    header("Location: ../View/dashboard.php");
    exit();
}

$keyID = $_GET['keyID'];
$userId = $_SESSION['user_id'];
$userCommunity = $_SESSION['community'] ?? '';

try {
    // Start a transaction
    $pdo->beginTransaction();

    // Fetch data from 'Assessment' table
    $sqlAssessment = "SELECT * FROM Assessment WHERE keyID = :keyID";
    $stmtAssessment = $pdo->prepare($sqlAssessment);
    $stmtAssessment->execute([':keyID' => $keyID]);
    $assessmentData = $stmtAssessment->fetch(PDO::FETCH_ASSOC);

    if (!$assessmentData) {
        throw new Exception("No assessment data found for KeyID: $keyID.");
    }

    // Check if user has access to this assessment (same community)
    if ($userCommunity !== $assessmentData['Community']) {
        throw new Exception("Access denied. This assessment is not for your community.");
    }

    // Fetch data from 'AssessmentCentre'
    $sqlCentre = "SELECT * FROM AssessmentCentre WHERE keyID = :keyID";
    $stmtCentre = $pdo->prepare($sqlCentre);
    $stmtCentre->execute([':keyID' => $keyID]);
    $centreData = $stmtCentre->fetch(PDO::FETCH_ASSOC);

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

} catch (Exception $e) {
    // Rollback the transaction in case of an error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Error fetching assessment data: " . $e->getMessage();
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
    <title>Assessment Details - Project In-Charge</title>
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
                
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="text-2xl font-bold text-center mt-5">Assessment Details</h1>
                    <p class="text-center text-gray-600 mt-2">KeyID: <?php echo htmlspecialchars($keyID); ?></p>
                </div>

                <!-- General Information -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">General Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Key ID:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($assessmentData['keyID']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date of Assessment:</label>
                            <div class="text-gray-900"><?php echo date('d/m/Y', strtotime($assessmentData['DateOfAssessment'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Assessor's Name:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($assessmentData['AssessorsName']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Community:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($assessmentData['Community']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Centre Details -->
                <?php if ($centreData): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Particulars of the Centre</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Name of the Centre:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($centreData['centreName']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sister(s) in Charge:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($centreData['sistersInCharge']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">History of the Centre:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($centreData['centreHistory']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Feedback Report Record:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($centreData['feedbackReportRecord']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Short Term Goals:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($centreData['shortTermGoals']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Long Term Goals:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($centreData['longTermGoals']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Inventory:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($centreData['inventory']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Asset Files:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($centreData['assetFile']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Staff Assessment -->
                <?php if ($staffData): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Staff Assessment</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Staff Bio Data:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($staffData['staffBioData']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Salary Book:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($staffData['salaryBook']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Attendance Register:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($staffData['attendanceRegister']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Appointment Letters:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($staffData['appointmentLetters']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Job Description:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($staffData['jobDescription']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Performance Appraisal:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($staffData['performanceAppraisal']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Minutes of Staff Meetings:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($staffData['minutesOfStaffMeetings']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Staff Records:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($staffData['staffRecords']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Feedback of Sisters:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($staffData['feedbackOfSisters']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Sisters Assessment -->
                <?php if ($sistersData): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Sisters Assessment</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Sisters:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($sistersData['NoOfSisters']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Chronicle:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($sistersData['Chronicle']); ?></div>
                        </div>
                    </div>

                    <?php if (!empty($sisterCardsData)): ?>
                    <h3 class="text-lg font-bold mt-6 mb-4">Sister Cards</h3>
                    <div class="overflow-x-auto">
                        <table class="styled-table">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="col-small">Serial No.</th>
                                    <th class="col-small">Date of Starting</th>
                                    <th class="col-medium">Programme</th>
                                    <th class="col-medium">Sister In Charge</th>
                                    <th class="col-small">No. of Staff</th>
                                    <th class="col-small">No. of Beneficiaries</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sisterCardsData as $card): ?>
                                <tr>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['SerialNumber']); ?></td>
                                    <td class="px-4 py-2"><?php echo date('d/m/Y', strtotime($card['DateOfStarting'])); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['Programme']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['SisterInCharge']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['NoOfStaff']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($card['NoOfBeneficiaries']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Centre Assessment -->
                <?php if ($centreAssessmentData): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Centre Assessment</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Strengths:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($centreAssessmentData['Strengths'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Areas of Attention:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($centreAssessmentData['AreasOfAttention'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Areas of Immediate Attention:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($centreAssessmentData['ImmediateAttention'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Opportunities Identified:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($centreAssessmentData['OpportunitiesIdentified'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Challenges Faced:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($centreAssessmentData['ChallengesFaced'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Steps Taken to Overcome Challenges:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($centreAssessmentData['StepsTaken'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Type of Help Requested:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($centreAssessmentData['HelpRequested'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">New Ventures:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($centreAssessmentData['NewVentures'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">List of Activities:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($centreAssessmentData['ListOfActivities'])); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Centre Projects -->
                <?php if (!empty($centreProjectsData)): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Centre Projects</h2>
                    <div class="overflow-x-auto">
                        <table class="styled-table">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="col-medium">Project Name</th>
                                    <th class="col-small">Number of Beneficiaries</th>
                                    <th class="col-medium">Project Sponsor</th>
                                    <th class="col-medium">Other Sponsor</th>
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
                    </div>
                </div>
                <?php endif; ?>

                <!-- Programme Assessment -->
                <?php if ($programmeData): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Programme Assessment</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Success Stories:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($programmeData['SuccessStories']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Media Coverage:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($programmeData['MediaCoverage']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Photographs:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($programmeData['Photographs']); ?></div>
                        </div>
                    </div>
                    <div class="space-y-4 mt-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Collaboration with Go/NGOs:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($programmeData['CollaborationGoNgos'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Field Inspection:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($programmeData['FieldInspection'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Activities List:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($programmeData['ActivitiesList'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Recommendations:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($programmeData['Recommendations'])); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Finance Assessment -->
                <?php if ($financeData): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Finance Assessment</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Approved Annual Budget:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($financeData['approvedAnnualBudget'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Audit Statements:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($financeData['AuditStatements'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Bank Statements:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($financeData['BankStatements'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Scope to Expand:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($financeData['scopeToExpand'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">New Initiatives:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($financeData['newInitiatives'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Help Needed:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($financeData['helpNeeded'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Courses and Trainings:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($financeData['CoursesAndTrainings'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Any Updates:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($financeData['AnyUpdates'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks of the Team:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($financeData['RemarksOfTheTeam'])); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Finance Projects -->
                <?php if (!empty($financeProjectsData)): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Finance Projects</h2>
                    <div class="overflow-x-auto">
                        <table class="styled-table">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="col-medium">Project Name</th>
                                    <th class="col-large">Impact of Project</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($financeProjectsData as $project): ?>
                                <tr>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($project['projectName']); ?></td>
                                    <td class="px-4 py-2"><?php echo htmlspecialchars($project['impactOfProject']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Documentation Assessment -->
                <?php if ($documentationData): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Documentation Assessment</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Annual Reports:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['annualReports']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Beneficiaries Data:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['beneficiariesData']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Request Thanking Letters:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['requestThankingLetters']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Bylaws:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['bylaws']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Amendment:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['amendment']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PAN:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['pan']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Women Society Registration Copies:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['womenSocietyRegistrationCopies']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Lease Agreement:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['leaseAgreement']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Monthly Reports:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['monthlyReports']); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">LSAC Minutes:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($documentationData['lsacMinutes']); ?></div>
                        </div>
                    </div>
                    <div class="space-y-4 mt-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Feedback Report:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($documentationData['feedbackReport'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Beneficiaries Type:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($documentationData['beneficiariesType'])); ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">LSAC Minutes Text:</label>
                            <div class="bg-gray-50 p-3 rounded border"><?php echo nl2br(htmlspecialchars($documentationData['lsacMinutesText'])); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recommendations / Suggestions / Feedback -->
                <?php if (!empty($recommendationsData)): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Recommendations / Suggestions / Feedback</h2>
                    <div class="space-y-4">
                        <?php foreach ($recommendationsData as $rec): ?>
                            <div class="bg-gray-50 p-4 rounded border">
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
                </div>
                <?php elseif ($centreAssessmentData && !empty($centreAssessmentData['Comments'])): ?>
                <!-- Fallback to old Comments field for backward compatibility -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Recommendations / Suggestions / Feedback</h2>
                    <div class="bg-gray-50 p-4 rounded border">
                        <div class="text-gray-900">
                            <?php echo nl2br(htmlspecialchars($centreAssessmentData['Comments'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="text-center space-x-4 mb-6">
                    <a href="<?php echo PathResolver::resolve('View/dashboard.php'); ?>" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Back to Dashboard
                    </a>
                    <a href="RecommendationsList.php" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        View Recommendations
                    </a>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
