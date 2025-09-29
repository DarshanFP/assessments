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

// Check if user is a councillor
if ($_SESSION['role'] !== 'Councillor') {
    $_SESSION['error'] = "Access denied. Only councillors can view assessment details.";
    header("Location: ../View/access_denied.php");
    exit();
}

// Get KeyID from URL parameter
$keyID = $_GET['keyID'] ?? null;

if (!$keyID) {
    $_SESSION['error'] = "No KeyID provided.";
    header("Location: AssessmentIndex.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch assessment data
try {
    // Fetch main assessment data
    $sql = "SELECT * FROM Assessment WHERE keyID = :keyID AND user_id = :userId";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID, ':userId' => $userId]);
    $assessmentData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assessmentData) {
        $_SESSION['error'] = "Assessment not found or access denied.";
        header("Location: AssessmentIndex.php");
        exit();
    }

    // Fetch centre data
    $sql = "SELECT * FROM AssessmentCentre WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $centreData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch staff data
    $sql = "SELECT * FROM AssessmentStaff WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $staffData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch sisters data
    $sql = "SELECT * FROM AssessmentSisters WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $sistersData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch sister cards data
    $sql = "SELECT * FROM AssessmentSisterCards WHERE keyID = :keyID ORDER BY SerialNumber";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $sisterCardsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch programme data
    $sql = "SELECT * FROM AssessmentProgramme WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $programmeData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch documentation data
    $sql = "SELECT * FROM AssessmentDocumentation WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $documentationData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch finance data
    $sql = "SELECT * FROM AssessmentFinance WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $financeData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch finance projects data
    $sql = "SELECT * FROM AssessmentFinanceProjects WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $financeProjectsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch centre assessment data
    $sql = "SELECT * FROM CentreAssessment WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $centreAssessmentData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch centre projects data
    $sql = "SELECT * FROM CentreProjects WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $centreProjectsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch recommendations data
    $sql = "SELECT * FROM CentreRecommendations WHERE keyID = :keyID ORDER BY priority DESC, created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $recommendationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching assessment data: " . $e->getMessage();
    header("Location: AssessmentIndex.php");
    exit();
}

// Calculate completion status
$completedForms = 0;
$totalForms = 7;

if ($centreData) $completedForms++;
if ($staffData) $completedForms++;
if ($sistersData) $completedForms++;
if ($programmeData) $completedForms++;
if ($documentationData) $completedForms++;
if ($financeData) $completedForms++;
if ($centreAssessmentData) $completedForms++;

$completionPercentage = round(($completedForms / $totalForms) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Assessment Detail - <?php echo htmlspecialchars($keyID); ?></title>
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
                <div class="page-header mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">Assessment Detail</h1>
                            <p class="text-gray-600">KeyID: <?php echo htmlspecialchars($keyID); ?></p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="AssessmentIndex.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Back to Index
                            </a>
                            <a href="AssessmentCentreEdit.php?keyID=<?php echo urlencode($keyID); ?>" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Edit Assessment
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Completion Status -->
                <div class="mb-6 bg-white p-4 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Assessment Progress</h3>
                    <div class="flex items-center">
                        <div class="flex-1 bg-gray-200 rounded-full h-2 mr-4">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $completionPercentage; ?>%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-700"><?php echo $completionPercentage; ?>% Complete</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-2"><?php echo $completedForms; ?> of <?php echo $totalForms; ?> forms completed</p>
                </div>

                <!-- Assessment Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Assessment Information</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="font-medium text-gray-700">KeyID:</span>
                                <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($assessmentData['keyID']); ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Assessment Date:</span>
                                <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($assessmentData['DateOfAssessment']); ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Assessor:</span>
                                <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($assessmentData['AssessorsName']); ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Community:</span>
                                <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($assessmentData['Community']); ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Created:</span>
                                <span class="ml-2 text-gray-900"><?php echo date('F j, Y g:i A', strtotime($assessmentData['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Centre Information</h3>
                        <?php if ($centreData): ?>
                            <div class="space-y-3">
                                <div>
                                    <span class="font-medium text-gray-700">Centre Name:</span>
                                    <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($centreData['centreName']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Sister in Charge:</span>
                                    <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($centreData['sistersInCharge']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Centre History:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($centreData['centreHistory'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Feedback Report Record:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($centreData['feedbackReportRecord'])); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500">Centre data not available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Form Status Overview -->
                <div class="bg-white p-6 rounded-lg shadow mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Form Completion Status</h3>
                    <p class="text-sm text-gray-600 mb-4">Click on any form to continue or edit it</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        <a href="AssessmentCentre.php?keyID=<?php echo urlencode($keyID); ?>" class="text-center hover:transform hover:scale-105 transition-transform duration-200">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center <?php echo $centreData ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> cursor-pointer">
                                <span class="text-lg font-bold"><?php echo $centreData ? '✓' : '✗'; ?></span>
                            </div>
                            <p class="text-sm font-medium text-gray-700 hover:text-gray-900">Centre</p>
                        </a>
                        <a href="AssessmentStaff.php?keyID=<?php echo urlencode($keyID); ?>" class="text-center hover:transform hover:scale-105 transition-transform duration-200">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center <?php echo $staffData ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> cursor-pointer">
                                <span class="text-lg font-bold"><?php echo $staffData ? '✓' : '✗'; ?></span>
                            </div>
                            <p class="text-sm font-medium text-gray-700 hover:text-gray-900">Staff</p>
                        </a>
                        <a href="AssessmentSisters.php?keyID=<?php echo urlencode($keyID); ?>" class="text-center hover:transform hover:scale-105 transition-transform duration-200">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center <?php echo $sistersData ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> cursor-pointer">
                                <span class="text-lg font-bold"><?php echo $sistersData ? '✓' : '✗'; ?></span>
                            </div>
                            <p class="text-sm font-medium text-gray-700 hover:text-gray-900">Sisters</p>
                        </a>
                        <a href="AssessmentProgramme.php?keyID=<?php echo urlencode($keyID); ?>" class="text-center hover:transform hover:scale-105 transition-transform duration-200">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center <?php echo $programmeData ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> cursor-pointer">
                                <span class="text-lg font-bold"><?php echo $programmeData ? '✓' : '✗'; ?></span>
                            </div>
                            <p class="text-sm font-medium text-gray-700 hover:text-gray-900">Programme</p>
                        </a>
                        <a href="AssessmentDocumentation.php?keyID=<?php echo urlencode($keyID); ?>" class="text-center hover:transform hover:scale-105 transition-transform duration-200">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center <?php echo $documentationData ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> cursor-pointer">
                                <span class="text-lg font-bold"><?php echo $documentationData ? '✓' : '✗'; ?></span>
                            </div>
                            <p class="text-sm font-medium text-gray-700 hover:text-gray-900">Documentation</p>
                        </a>
                        <a href="AssessmentFinance.php?keyID=<?php echo urlencode($keyID); ?>" class="text-center hover:transform hover:scale-105 transition-transform duration-200">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center <?php echo $financeData ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> cursor-pointer">
                                <span class="text-lg font-bold"><?php echo $financeData ? '✓' : '✗'; ?></span>
                            </div>
                            <p class="text-sm font-medium text-gray-700 hover:text-gray-900">Finance</p>
                        </a>
                        <a href="centre.php?keyID=<?php echo urlencode($keyID); ?>" class="text-center hover:transform hover:scale-105 transition-transform duration-200">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-full flex items-center justify-center <?php echo $centreAssessmentData ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> cursor-pointer">
                                <span class="text-lg font-bold"><?php echo $centreAssessmentData ? '✓' : '✗'; ?></span>
                            </div>
                            <p class="text-sm font-medium text-gray-700 hover:text-gray-900">Centre Assessment</p>
                        </a>
                    </div>
                </div>

                <!-- Continue with detailed sections -->
                <div class="space-y-8">
                    <!-- Staff Assessment Section -->
                    <?php if ($staffData): ?>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Staff Assessment</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-medium text-gray-700">Staff Bio-data:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($staffData['staffBioData'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Salary Book:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($staffData['salaryBook'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Attendance Register:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($staffData['attendanceRegister'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Appointment Letters:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($staffData['appointmentLetters'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Sisters Assessment Section -->
                    <?php if ($sistersData): ?>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sisters Assessment</h3>
                            <div class="mb-4">
                                <span class="font-medium text-gray-700">Number of Sisters:</span>
                                <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($sistersData['NoOfSisters']); ?></span>
                            </div>
                            <div class="mb-4">
                                <span class="font-medium text-gray-700">Chronicle:</span>
                                <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($sistersData['Chronicle'])); ?></span>
                            </div>
                            <?php if (!empty($sisterCardsData)): ?>
                                <h4 class="font-medium text-gray-700 mb-2">Sister Details:</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Serial</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Programme</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sister In Charge</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Staff Count</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Beneficiaries</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($sisterCardsData as $card): ?>
                                                <tr>
                                                    <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($card['SerialNumber']); ?></td>
                                                    <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($card['DateOfStarting']); ?></td>
                                                    <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($card['Programme']); ?></td>
                                                    <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($card['SisterInCharge']); ?></td>
                                                    <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($card['NoOfStaff']); ?></td>
                                                    <td class="px-3 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($card['NoOfBeneficiaries']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Programme Assessment Section -->
                    <?php if ($programmeData): ?>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Programme Assessment</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-medium text-gray-700">Success Stories Record:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($programmeData['SuccessStories'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Media Coverage Record:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($programmeData['MediaCoverage'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Collaboration with Go/NGOs:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($programmeData['CollaborationGoNgos'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Photographs:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($programmeData['Photographs'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Documentation Assessment Section -->
                    <?php if ($documentationData): ?>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Documentation Assessment</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-medium text-gray-700">Annual Reports:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($documentationData['annualReports'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Beneficiaries Data:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($documentationData['beneficiariesData'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Bylaws:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($documentationData['bylaws'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">PAN:</span>
                                    <span class="ml-2 text-gray-900"><?php echo ucfirst(htmlspecialchars($documentationData['pan'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Finance Assessment Section -->
                    <?php if ($financeData): ?>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Finance Assessment</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="font-medium text-gray-700">Audit Statements:</span>
                                    <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($financeData['AuditStatements']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Bank Statements:</span>
                                    <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($financeData['BankStatements']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Courses and Trainings:</span>
                                    <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($financeData['CoursesAndTrainings']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Assessors:</span>
                                    <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($financeData['NameOfTheAssessors']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Centre Assessment Section -->
                    <?php if ($centreAssessmentData): ?>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Centre Assessment</h3>
                            <div class="space-y-4">
                                <div>
                                    <span class="font-medium text-gray-700">Strengths:</span>
                                    <p class="mt-1 text-gray-900"><?php echo nl2br(htmlspecialchars($centreAssessmentData['Strengths'])); ?></p>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Areas of Attention:</span>
                                    <p class="mt-1 text-gray-900"><?php echo nl2br(htmlspecialchars($centreAssessmentData['AreasOfAttention'])); ?></p>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Challenges Faced:</span>
                                    <p class="mt-1 text-gray-900"><?php echo nl2br(htmlspecialchars($centreAssessmentData['ChallengesFaced'])); ?></p>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">New Ventures:</span>
                                    <p class="mt-1 text-gray-900"><?php echo nl2br(htmlspecialchars($centreAssessmentData['NewVentures'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Recommendations Section -->
                    <?php if (!empty($recommendationsData)): ?>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recommendations</h3>
                            <div class="space-y-4">
                                <?php foreach ($recommendationsData as $recommendation): ?>
                                    <div class="border-l-4 border-blue-500 pl-4">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <p class="text-gray-900"><?php echo htmlspecialchars($recommendation['recommendation_text']); ?></p>
                                                <div class="mt-2 flex space-x-4 text-sm text-gray-600">
                                                    <span>Type: <?php echo htmlspecialchars($recommendation['recommendation_type']); ?></span>
                                                    <span>Priority: <?php echo htmlspecialchars($recommendation['priority']); ?></span>
                                                    <span>Status: <?php echo htmlspecialchars($recommendation['status']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
