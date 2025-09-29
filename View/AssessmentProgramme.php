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

// Get the KeyID from URL parameter if available
$selectedKeyID = $_GET['keyID'] ?? null;

// Fetch the list of keyID values from the database
try {
    $sql = "SELECT keyID FROM Assessment ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $keyIDs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching keyID values: " . $e->getMessage();
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head class="text-center py-4">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">

<title>Assessment - Programme</title>
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
        
        <!-- Main content -->
        <div class="form-container p-6 flex-1 bg-white">

            <h1 class="text-2xl font-bold text-center mt-5">Particulars of the Programme</h1><br><br>

            <form action="../Controller/assessment_programme_process.php" method="POST" enctype="multipart/form-data">
                
                <!-- Date of Assessment -->
                <div class="form-group">
                    <label for="DateOfAssessment" class="block mb-2">Date Of Assessment:</label>
                    <input type="date" name="DateOfAssessment" id="DateOfAssessment" required class="w-full px-3 py-2 mb-2 border">
                </div>

                <!-- KeyID Dropdown Field -->
                <div class="form-group">
                    <label for="keyID" class="block mb-2">Select KeyID:</label>
                    <select name="keyID" id="keyID" required class="w-full px-3 py-2 mb-2 border">
                        <option disabled <?php echo !$selectedKeyID ? 'selected' : ''; ?>>-- Choose KeyID --</option>
                        <?php foreach ($keyIDs as $keyID): ?>
                            <option value="<?php echo htmlspecialchars($keyID['keyID']); ?>" 
                                    <?php echo ($selectedKeyID && $selectedKeyID === $keyID['keyID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($keyID['keyID']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h2 class="text-xl font-bold text-center mt-5">Particulars of the Programme</h2><br>

                <!-- Success Stories Record -->
                <div class="form-group">
                    <label for="SuccessStories" class="block mb-2">Success Stories Record:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="SuccessStoriesYes" name="SuccessStories" value="yes">
                        <label for="SuccessStoriesYes">Yes</label>
                        <input type="radio" id="SuccessStoriesNo" name="SuccessStories" value="no">
                        <label for="SuccessStoriesNo">No</label>
                    </div>
                </div>

                <!-- Media Coverage Record -->
                <div class="form-group">
                    <label for="MediaCoverage" class="block mb-2">Media Coverage Record:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="MediaCoverageYes" name="MediaCoverage" value="yes">
                        <label for="MediaCoverageYes">Yes</label>
                        <input type="radio" id="MediaCoverageNo" name="MediaCoverage" value="no">
                        <label for="MediaCoverageNo">No</label>
                    </div>  
                </div>

                <!-- Collaboration with Government/NGOs -->
                <div class="form-group">
                    <label for="CollaborationGoNgos" class="block mb-2">Collaboration with Government/NGOs:</label>
                    <textarea id="CollaborationGoNgos" name="CollaborationGoNgos" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>

                <!-- Photographs -->
                <div class="form-group">
                    <label for="Photographs" class="block mb-2">Photographs:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="PhotographsYes" name="Photographs" value="yes">
                        <label for="PhotographsYes">Yes</label>
                        <input type="radio" id="PhotographsNo" name="Photographs" value="no">
                        <label for="PhotographsNo">No</label>
                    </div>
                </div>

                <!-- Field Inspection -->
                <div class="form-group">
                    <label for="FieldInspection" class="block mb-2">Field Inspection:</label>
                    <textarea id="FieldInspection" name="FieldInspection" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>

                <!-- List of Activities -->
                <div class="form-group">
                    <label for="ActivitiesList" class="block mb-2">List of Activities:</label>
                    <textarea id="ActivitiesList" name="ActivitiesList" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>

                <!-- Recommendations -->
                <div class="form-group">
                    <label for="Recommendations" class="block mb-2">Recommendations:</label>
                    <textarea id="Recommendations" name="Recommendations" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="form-group mt-5">
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white">Submit</button>
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
