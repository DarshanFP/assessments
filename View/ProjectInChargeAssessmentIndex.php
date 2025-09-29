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

// Check if user is Project In-Charge
if ($_SESSION['role'] !== 'Project In-Charge') {
    $_SESSION['error'] = "Access denied. Only Project In-Charge users can view this page.";
    header("Location: ../View/access_denied.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userCommunity = $_SESSION['community'] ?? '';

if (!$userCommunity) {
    $_SESSION['error'] = "Community information not found. Please contact administrator.";
    header("Location: ../View/ProjectInChargeDashboard.php");
    exit();
}

// Fetch all assessments for this Project In-Charge's community
try {
    $sql = "SELECT 
                a.keyID,
                a.DateOfAssessment,
                a.AssessorsName,
                a.Community,
                a.created_at,
                ac.centreName,
                CASE 
                    WHEN ac.keyID IS NOT NULL THEN 'Completed'
                    ELSE 'Incomplete'
                END as centre_status,
                CASE 
                    WHEN ast.keyID IS NOT NULL THEN 'Completed'
                    ELSE 'Incomplete'
                END as staff_status,
                CASE 
                    WHEN asis.keyID IS NOT NULL THEN 'Completed'
                    ELSE 'Incomplete'
                END as sisters_status,
                CASE 
                    WHEN ap.keyID IS NOT NULL THEN 'Completed'
                    ELSE 'Incomplete'
                END as programme_status,
                CASE 
                    WHEN ad.keyID IS NOT NULL THEN 'Completed'
                    ELSE 'Incomplete'
                END as documentation_status,
                CASE 
                    WHEN af.keyID IS NOT NULL THEN 'Completed'
                    ELSE 'Incomplete'
                END as finance_status,
                CASE 
                    WHEN ca.keyID IS NOT NULL THEN 'Completed'
                    ELSE 'Incomplete'
                END as centre_assessment_status
            FROM Assessment a
            LEFT JOIN AssessmentCentre ac ON a.keyID = ac.keyID
            LEFT JOIN AssessmentStaff ast ON a.keyID = ast.keyID
            LEFT JOIN AssessmentSisters asis ON a.keyID = asis.keyID
            LEFT JOIN AssessmentProgramme ap ON a.keyID = ap.keyID
            LEFT JOIN AssessmentDocumentation ad ON a.keyID = ad.keyID
            LEFT JOIN AssessmentFinance af ON a.keyID = af.keyID
            LEFT JOIN CentreAssessment ca ON a.keyID = ca.keyID
            WHERE a.Community = :community
            ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':community' => $userCommunity]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching assessments: " . $e->getMessage();
    $assessments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Assessment Index - Project In-Charge</title>
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
                    <h1 class="text-3xl font-bold text-gray-800">Assessment Overview</h1>
                    <p class="text-gray-600">View and manage assessment data for <?php echo htmlspecialchars($userCommunity); ?></p>
                </div>

                <!-- Display Session Messages -->
                <?php 
                $successMessage = $_SESSION['success'] ?? null;
                $errorMessage = $_SESSION['error'] ?? null;
                
                if ($successMessage): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <?php
                // Clear session messages after displaying them
                unset($_SESSION['success'], $_SESSION['error']);
                ?>

                <!-- Community Information -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Community: <?php echo htmlspecialchars($userCommunity); ?>
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>You can view all assessments conducted for your community center.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assessments Table -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Serial No.
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Key ID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Community
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Assessor's Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date of Assessment
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($assessments)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            No assessments found for <?php echo htmlspecialchars($userCommunity); ?>.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($assessments as $index => $assessment): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $index + 1; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($assessment['keyID']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($assessment['Community']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($assessment['AssessorsName']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($assessment['DateOfAssessment']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $completedForms = 0;
                                                $totalForms = 7;
                                                
                                                if ($assessment['centre_status'] === 'Completed') $completedForms++;
                                                if ($assessment['staff_status'] === 'Completed') $completedForms++;
                                                if ($assessment['sisters_status'] === 'Completed') $completedForms++;
                                                if ($assessment['programme_status'] === 'Completed') $completedForms++;
                                                if ($assessment['documentation_status'] === 'Completed') $completedForms++;
                                                if ($assessment['finance_status'] === 'Completed') $completedForms++;
                                                if ($assessment['centre_assessment_status'] === 'Completed') $completedForms++;
                                                
                                                $percentage = round(($completedForms / $totalForms) * 100);
                                                
                                                if ($percentage === 100) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Complete</span>';
                                                } elseif ($percentage > 50) {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">In Progress</span>';
                                                } else {
                                                    echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Started</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="ProjectInChargeAssessmentView.php?keyID=<?php echo urlencode($assessment['keyID']); ?>" 
                                                   class="text-blue-600 hover:text-blue-900">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Assessment Progress Summary -->
                <?php if (!empty($assessments)): ?>
                    <div class="mt-8 bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Assessment Progress Summary</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <div class="text-2xl font-bold text-blue-600"><?php echo count($assessments); ?></div>
                                <div class="text-sm text-gray-600">Total Assessments</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <div class="text-2xl font-bold text-green-600">
                                    <?php 
                                    $completedCount = 0;
                                    foreach ($assessments as $assessment) {
                                        if ($assessment['centre_assessment_status'] === 'Completed') {
                                            $completedCount++;
                                        }
                                    }
                                    echo $completedCount;
                                    ?>
                                </div>
                                <div class="text-sm text-gray-600">Completed</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <div class="text-2xl font-bold text-yellow-600">
                                    <?php echo count($assessments) - $completedCount; ?>
                                </div>
                                <div class="text-sm text-gray-600">In Progress</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <div class="text-2xl font-bold text-purple-600">
                                    <?php echo $completedCount > 0 ? round(($completedCount / count($assessments)) * 100) : 0; ?>%
                                </div>
                                <div class="text-sm text-gray-600">Completion Rate</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
