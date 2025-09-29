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

// Check if recommendation ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No recommendation ID provided.";
    header("Location: RecommendationsList.php");
    exit();
}

$recommendationId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Fetch recommendation details
try {
    $sql = "SELECT 
                cr.*,
                a.Community,
                a.AssessorsName,
                a.DateOfAssessment,
                u.full_name as created_by_name
            FROM CentreRecommendations cr
            INNER JOIN Assessment a ON cr.keyID = a.keyID
            INNER JOIN ssmntUsers u ON cr.created_by = u.id
            WHERE cr.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $recommendationId]);
    $recommendation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$recommendation) {
        $_SESSION['error'] = "Recommendation not found.";
        header("Location: RecommendationsList.php");
        exit();
    }
    
    // Check if user has access to this recommendation (same community)
    if ($_SESSION['role'] === 'Project In-Charge' && $_SESSION['community'] !== $recommendation['Community']) {
        $_SESSION['error'] = "Access denied. This recommendation is not for your community.";
        header("Location: RecommendationsList.php");
        exit();
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching recommendation: " . $e->getMessage();
    header("Location: RecommendationsList.php");
    exit();
}

// Fetch actions taken on this recommendation
try {
    $sqlActions = "SELECT 
                    ra.*,
                    u.full_name as action_by_name
                   FROM RecommendationActions ra
                   INNER JOIN ssmntUsers u ON ra.action_by = u.id
                   WHERE ra.recommendation_id = :recommendation_id
                   ORDER BY ra.created_at DESC";
    
    $stmtActions = $pdo->prepare($sqlActions);
    $stmtActions->execute([':recommendation_id' => $recommendationId]);
    $actions = $stmtActions->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching actions: " . $e->getMessage();
    $actions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Recommendation Detail - Assessment</title>
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
                    <h1 class="text-2xl font-bold text-center mt-5">Recommendation Detail</h1>
                </div>

                <!-- Recommendation Information -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-bold mb-4">Recommendation Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Recommendation Text:</label>
                            <div class="bg-gray-50 p-3 rounded border">
                                <?php echo nl2br(htmlspecialchars($recommendation['recommendation_text'])); ?>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Type:</label>
                            <span class="px-3 py-1 rounded text-sm font-semibold
                                <?php 
                                switch($recommendation['recommendation_type']) {
                                    case 'Recommendation': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'Suggestion': echo 'bg-green-100 text-green-800'; break;
                                    case 'Feedback': echo 'bg-purple-100 text-purple-800'; break;
                                }
                                ?>">
                                <?php echo htmlspecialchars($recommendation['recommendation_type']); ?>
                            </span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Priority:</label>
                            <span class="px-3 py-1 rounded text-sm font-semibold
                                <?php 
                                switch($recommendation['priority']) {
                                    case 'Low': echo 'bg-gray-100 text-gray-800'; break;
                                    case 'Medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'High': echo 'bg-orange-100 text-orange-800'; break;
                                    case 'Critical': echo 'bg-red-100 text-red-800'; break;
                                }
                                ?>">
                                <?php echo htmlspecialchars($recommendation['priority']); ?>
                            </span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status:</label>
                            <span class="px-3 py-1 rounded text-sm font-semibold
                                <?php 
                                switch($recommendation['status']) {
                                    case 'Pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'In Progress': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'Completed': echo 'bg-green-100 text-green-800'; break;
                                    case 'Rejected': echo 'bg-red-100 text-red-800'; break;
                                }
                                ?>">
                                <?php echo htmlspecialchars($recommendation['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Community:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($recommendation['Community']); ?></div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Assessor:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($recommendation['AssessorsName']); ?></div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Assessment Date:</label>
                            <div class="text-gray-900"><?php echo date('d/m/Y', strtotime($recommendation['DateOfAssessment'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Created By:</label>
                            <div class="text-gray-900"><?php echo htmlspecialchars($recommendation['created_by_name']); ?></div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Created Date:</label>
                            <div class="text-gray-900"><?php echo date('d/m/Y H:i', strtotime($recommendation['created_at'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Actions Taken -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Actions Taken</h2>
                        <?php if ($_SESSION['role'] === 'Project In-Charge'): ?>
                            <a href="AddRecommendationAction.php?id=<?php echo $recommendationId; ?>" 
                               class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                                Add Action
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($actions)): ?>
                        <div class="text-center py-8">
                            <p class="text-gray-500">No actions have been taken on this recommendation yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($actions as $action): ?>
                                <div class="border-l-4 border-blue-500 pl-4 py-3 bg-gray-50 rounded">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="font-semibold text-gray-900">
                                            Action by: <?php echo htmlspecialchars($action['action_by_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <?php echo date('d/m/Y', strtotime($action['action_date'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Action Taken:</label>
                                        <div class="text-gray-900"><?php echo nl2br(htmlspecialchars($action['action_taken'])); ?></div>
                                    </div>
                                    
                                    <?php if (!empty($action['comments'])): ?>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Comments:</label>
                                            <div class="text-gray-900"><?php echo nl2br(htmlspecialchars($action['comments'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="text-xs text-gray-500 mt-2">
                                        Added on: <?php echo date('d/m/Y H:i', strtotime($action['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="text-center space-x-4">
                    <a href="RecommendationsList.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Back to List
                    </a>
                    
                    <?php if ($_SESSION['role'] === 'Project In-Charge'): ?>
                        <a href="AddRecommendationAction.php?id=<?php echo $recommendationId; ?>" 
                           class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                            Add Action
                        </a>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
