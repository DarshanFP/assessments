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
    $_SESSION['error'] = "Access denied. Only councillors can view assessments.";
    header("Location: ../View/access_denied.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch all assessments created by this councillor
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
            WHERE a.user_id = :userId
            ORDER BY a.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':userId' => $userId]);
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
    <title>Assessment Index</title>
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
                    <h1 class="text-3xl font-bold text-gray-800">Assessment Index</h1>
                    <p class="text-gray-600">View and manage all your conducted assessments</p>
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

                <!-- Action Buttons -->
                <div class="mb-6">
                    <a href="AssessmentCentre.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Create New Assessment
                    </a>
                </div>

                <!-- Assessments Table -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        KeyID
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Assessment Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Assessor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Community
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Centre Name
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
                                            No assessments found. <a href="AssessmentCentre.php" class="text-blue-500 hover:text-blue-700">Create your first assessment</a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($assessments as $assessment): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($assessment['keyID']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($assessment['DateOfAssessment']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($assessment['AssessorsName']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($assessment['Community']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($assessment['centreName'] ?? 'N/A'); ?>
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
                                                <div class="flex space-x-2">
                                                    <a href="AssessmentCentreDetail.php?keyID=<?php echo urlencode($assessment['keyID']); ?>" 
                                                       class="text-blue-600 hover:text-blue-900">View</a>
                                                    <a href="AssessmentCentreEdit.php?keyID=<?php echo urlencode($assessment['keyID']); ?>" 
                                                       class="text-green-600 hover:text-green-900">Edit</a>
                                                </div>
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
