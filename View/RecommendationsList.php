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

$userId = $_SESSION['user_id'];
$userCommunity = $_SESSION['community'] ?? '';

// Fetch recommendations for the user's community
try {
    $sql = "SELECT 
                cr.id,
                cr.keyID,
                cr.recommendation_text,
                cr.recommendation_type,
                cr.priority,
                cr.status,
                cr.created_at,
                a.Community,
                a.AssessorsName,
                a.DateOfAssessment
            FROM CentreRecommendations cr
            INNER JOIN Assessment a ON cr.keyID = a.keyID
            WHERE a.Community = :community
            ORDER BY cr.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':community' => $userCommunity]);
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching recommendations: " . $e->getMessage();
    $recommendations = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Recommendations List - Assessment</title>
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
                    <h1 class="text-2xl font-bold text-center mt-5">Recommendations & Suggestions</h1>
                    <p class="text-center text-gray-600 mt-2">Community: <?php echo htmlspecialchars($userCommunity); ?></p>
                </div>

                <!-- Status Summary -->
                <div class="status-summary grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 mt-6">
                    <?php
                    $statusCounts = [
                        'Pending' => 0,
                        'In Progress' => 0,
                        'Completed' => 0,
                        'Rejected' => 0
                    ];
                    
                    foreach ($recommendations as $rec) {
                        $statusCounts[$rec['status']]++;
                    }
                    ?>
                    
                    <div class="bg-yellow-100 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-yellow-800"><?php echo $statusCounts['Pending']; ?></div>
                        <div class="text-sm text-yellow-600">Pending</div>
                    </div>
                    
                    <div class="bg-blue-100 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-blue-800"><?php echo $statusCounts['In Progress']; ?></div>
                        <div class="text-sm text-blue-600">In Progress</div>
                    </div>
                    
                    <div class="bg-green-100 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-green-800"><?php echo $statusCounts['Completed']; ?></div>
                        <div class="text-sm text-green-600">Completed</div>
                    </div>
                    
                    <div class="bg-red-100 p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold text-red-800"><?php echo $statusCounts['Rejected']; ?></div>
                        <div class="text-sm text-red-600">Rejected</div>
                    </div>
                </div>

                <!-- Recommendations Table -->
                <div class="table-container">
                    <?php if (empty($recommendations)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500 text-lg">No recommendations found for your community.</p>
                        </div>
                    <?php else: ?>
                        <table class="styled-table">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="col-small">Type</th>
                                    <th class="col-medium">Recommendation</th>
                                    <th class="col-small">Priority</th>
                                    <th class="col-small">Status</th>
                                    <th class="col-small">Assessor</th>
                                    <th class="col-small">Date</th>
                                    <th class="col-small">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recommendations as $rec): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2">
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
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($rec['recommendation_text']); ?>">
                                                <?php echo htmlspecialchars(substr($rec['recommendation_text'], 0, 100)) . (strlen($rec['recommendation_text']) > 100 ? '...' : ''); ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
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
                                        </td>
                                        <td class="px-4 py-2">
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
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <?php echo htmlspecialchars($rec['AssessorsName']); ?>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <?php echo date('d/m/Y', strtotime($rec['DateOfAssessment'])); ?>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex space-x-2">
                                                <a href="RecommendationDetail.php?id=<?php echo $rec['id']; ?>" 
                                                   class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">
                                                    View
                                                </a>
                                                <a href="AddRecommendationAction.php?id=<?php echo $rec['id']; ?>" 
                                                   class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600">
                                                    Action
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Back Button -->
                <div class="text-center mt-6">
                    <a href="../View/dashboard.php" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Back to Dashboard
                    </a>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
