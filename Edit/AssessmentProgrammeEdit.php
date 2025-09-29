<?php
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$keyID = $_GET['keyID'] ?? null;

if (!$keyID) {
    $_SESSION['error'] = "Invalid request. Missing keyID.";
    header("Location: ../View/dashboard.php");
    exit();
}

// Fetch existing data for the given keyID
try {
    $sql = "SELECT * FROM AssessmentProgramme WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $programmeData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$programmeData) {
        $_SESSION['error'] = "No data found for the given keyID.";
        header("Location: ../View/dashboard.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching data: " . $e->getMessage();
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
<title>Edit Assessment Programme</title>
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

        <div class="form-container p-6 bg-gray-100 flex-1">
            <h1 class="text-2xl font-bold text-center mb-5">Edit Particulars of the Programme</h1>
            <div class="text-left mb-5">
                <a href="../Controller/Show/AssessmentCentreDetailController.php?keyID=<?php echo urlencode($programmeData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Back to View
                </a>
            </div>

            <form action="../Controller/Edit/assessment_programme_edit_process.php" method="POST">
                <input type="hidden" name="keyID" value="<?php echo htmlspecialchars($keyID); ?>">

                <!-- Success Stories Record -->
                <div class="form-group mb-4">
                    <label for="SuccessStories" class="block mb-2">Success Stories Record:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="SuccessStoriesYes" name="SuccessStories" value="yes" <?php echo ($programmeData['SuccessStories'] === 'yes') ? 'checked' : ''; ?>>
                        <label for="SuccessStoriesYes">Yes</label>
                        <input type="radio" id="SuccessStoriesNo" name="SuccessStories" value="no" <?php echo ($programmeData['SuccessStories'] === 'no') ? 'checked' : ''; ?>>
                        <label for="SuccessStoriesNo">No</label>
                    </div>
                </div>

                <!-- Media Coverage Record -->
                <div class="form-group mb-4">
                    <label for="MediaCoverage" class="block mb-2">Media Coverage Record:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="MediaCoverageYes" name="MediaCoverage" value="yes" <?php echo ($programmeData['MediaCoverage'] === 'yes') ? 'checked' : ''; ?>>
                        <label for="MediaCoverageYes">Yes</label>
                        <input type="radio" id="MediaCoverageNo" name="MediaCoverage" value="no" <?php echo ($programmeData['MediaCoverage'] === 'no') ? 'checked' : ''; ?>>
                        <label for="MediaCoverageNo">No</label>
                    </div>
                </div>

                <!-- Collaboration with Government/NGOs -->
                <div class="form-group mb-4">
                    <label for="CollaborationGoNgos" class="block mb-2">Collaboration with Government/NGOs:</label>
                    <textarea id="CollaborationGoNgos" name="CollaborationGoNgos" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($programmeData['CollaborationGoNgos']); ?></textarea>
                </div>

                <!-- Photographs -->
                <div class="form-group mb-4">
                    <label for="Photographs" class="block mb-2">Photographs:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="PhotographsYes" name="Photographs" value="yes" <?php echo ($programmeData['Photographs'] === 'yes') ? 'checked' : ''; ?>>
                        <label for="PhotographsYes">Yes</label>
                        <input type="radio" id="PhotographsNo" name="Photographs" value="no" <?php echo ($programmeData['Photographs'] === 'no') ? 'checked' : ''; ?>>
                        <label for="PhotographsNo">No</label>
                    </div>
                </div>

                <!-- Field Inspection -->
                <div class="form-group mb-4">
                    <label for="FieldInspection" class="block mb-2">Field Inspection:</label>
                    <textarea id="FieldInspection" name="FieldInspection" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($programmeData['FieldInspection']); ?></textarea>
                </div>

                <!-- List of Activities -->
                <div class="form-group mb-4">
                    <label for="ActivitiesList" class="block mb-2">List of Activities:</label>
                    <textarea id="ActivitiesList" name="ActivitiesList" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($programmeData['ActivitiesList']); ?></textarea>
                </div>

                <!-- Recommendations -->
                <div class="form-group mb-4">
                    <label for="Recommendations" class="block mb-2">Recommendations:</label>
                    <textarea id="Recommendations" name="Recommendations" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($programmeData['Recommendations']); ?></textarea>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-5">
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Update</button>
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
