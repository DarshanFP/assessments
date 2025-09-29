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

// Check if recommendation ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No recommendation ID provided.";
    header("Location: RecommendationsList.php");
    exit();
}

$recommendationId = $_GET['id'];
$userId = $_SESSION['user_id'];
$userCommunity = $_SESSION['community'] ?? '';

// Fetch recommendation details to verify access
try {
    $sql = "SELECT 
                cr.*,
                a.Community
            FROM CentreRecommendations cr
            INNER JOIN Assessment a ON cr.keyID = a.keyID
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
    if ($userCommunity !== $recommendation['Community']) {
        $_SESSION['error'] = "Access denied. This recommendation is not for your community.";
        header("Location: RecommendationsList.php");
        exit();
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching recommendation: " . $e->getMessage();
    header("Location: RecommendationsList.php");
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
    <title>Add Action - Assessment</title>
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
                    <h1 class="text-2xl font-bold text-center mt-5">Add Action to Recommendation</h1>
                </div>

                <!-- Recommendation Summary -->
                <div class="bg-blue-50 p-4 rounded-lg mb-6">
                    <h3 class="font-semibold text-blue-900 mb-2">Recommendation Summary:</h3>
                    <p class="text-blue-800"><?php echo htmlspecialchars($recommendation['recommendation_text']); ?></p>
                    <div class="flex space-x-4 mt-2 text-sm">
                        <span class="text-blue-700">Type: <?php echo htmlspecialchars($recommendation['recommendation_type']); ?></span>
                        <span class="text-blue-700">Priority: <?php echo htmlspecialchars($recommendation['priority']); ?></span>
                        <span class="text-blue-700">Status: <?php echo htmlspecialchars($recommendation['status']); ?></span>
                    </div>
                </div>

                <!-- Action Form -->
                <div class="form-container">
                    <form action="../Controller/recommendation_action_process.php" method="POST">
                        <input type="hidden" name="recommendation_id" value="<?php echo $recommendationId; ?>">
                        
                        <div class="form-group">
                            <label for="action_taken" class="block mb-2 font-semibold">Action Taken:</label>
                            <textarea name="action_taken" id="action_taken" required 
                                      class="w-full px-3 py-2 mb-2 border" rows="4" 
                                      placeholder="Describe the action you have taken or plan to take..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="action_date" class="block mb-2">Action Date:</label>
                            <input type="date" name="action_date" id="action_date" required 
                                   class="w-full px-3 py-2 mb-2 border" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="comments" class="block mb-2">Additional Comments:</label>
                            <textarea name="comments" id="comments" 
                                      class="w-full px-3 py-2 mb-2 border" rows="3" 
                                      placeholder="Any additional comments or notes..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="new_status" class="block mb-2">Update Status:</label>
                            <select name="new_status" id="new_status" required class="w-full px-3 py-2 mb-2 border">
                                <option value="">-- Choose Status --</option>
                                <option value="Pending" <?php echo ($recommendation['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo ($recommendation['status'] === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo ($recommendation['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Rejected" <?php echo ($recommendation['status'] === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>

                        <div class="form-group text-center space-x-4">
                            <input type="submit" value="Submit Action" 
                                   class="px-6 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                            <a href="RecommendationDetail.php?id=<?php echo $recommendationId; ?>" 
                               class="px-6 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
