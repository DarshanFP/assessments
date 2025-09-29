<?php
// Start session before any output
session_start();

require_once '../includes/SessionManager.php';
require_once '../includes/DatabaseManager.php';
require_once '../includes/RoleMiddleware.php';
require_once '../includes/SidebarManager.php';
require_once '../includes/path_resolver.php';

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

// Fetch data from the Assessment table
try {
    $pdo = getDatabaseConnection();
    $sql = "SELECT keyID, Community, AssessorsName, DateOfAssessment FROM Assessment ORDER BY DateOfAssessment DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    SessionManager::setMessage('error', "Failed to fetch assessment data.");
    header("Location: ../index.php");
    exit();
} finally {
    if (isset($pdo)) {
        $dbManager = DatabaseManager::getInstance();
        $dbManager->releaseConnection($pdo);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Assessment System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../layout.css">
    <link rel="stylesheet" href="../style.css">
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
                <div class="page-header mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard - Assessment Overview</h1>
                    <p class="text-gray-600">View and manage assessment data</p>
                </div>
                
                <!-- Display Session Messages -->
                <?php 
                $successMessage = SessionManager::getMessage('success');
                $errorMessage = SessionManager::getMessage('error');
                
                if ($successMessage): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Assessment Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Serial No.</th>
                                <th>Key ID</th>
                                <th>Community</th>
                                <th>Assessor's Name</th>
                                <th>Date of Assessment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assessments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No assessments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assessments as $index => $assessment): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($assessment['keyID']); ?></td>
                                        <td><?php echo htmlspecialchars($assessment['Community']); ?></td>
                                        <td><?php echo htmlspecialchars($assessment['AssessorsName']); ?></td>
                                        <td><?php echo htmlspecialchars($assessment['DateOfAssessment']); ?></td>
                                        <td>
                                            <a href="../Controller/Show/AssessmentCentreDetailController.php?keyID=<?php echo urlencode($assessment['keyID']); ?>" 
                                               class="btn btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
