<?php
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Get the keyID from the URL
$keyID = $_GET['keyID'] ?? null;

if (!$keyID) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../View/dashboard.php");
    exit();
}

// Fetch the existing data for the given keyID
try {
    $sql = "SELECT * FROM AssessmentCentre WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $centreData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$centreData) {
        $_SESSION['error'] = "No data found for the given keyID.";
        header("Location: ../View/dashboard.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching data: " . $e->getMessage();
    header("Location: ../View/dashboard.php");
    exit();
}

// Fetch centres for dropdown
try {
    $sql = "SELECT id, centre_name FROM centres ORDER BY centre_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $centres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If centres table doesn't exist, create empty array
    $centres = [];
}

// Fetch users for sisters dropdown
try {
    $sql = "SELECT id, full_name, username FROM ssmntUsers WHERE role = 'Project In-Charge' ORDER BY full_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $sisters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If error occurs, create empty array
    $sisters = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Edit Assessment Centre</title>
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
            <h1 class="text-2xl font-bold text-center mt-5">Edit Particulars of the Centre</h1>
            
            <div class="text-left mt-5">
            <a href="../Controller/Show/AssessmentCentreDetailController.php?keyID=<?php echo urlencode($centreData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                Back to View
            </a> 
            </div>

            <form action="../Controller/Edit/assessment_centre_edit_process.php" method="POST">
                <input type="hidden" name="keyID" value="<?php echo htmlspecialchars($centreData['keyID']); ?>">

                <!-- Centre Name -->
                <div class="form-group">
                    <label for="centreName">Name of the Centre:</label>
                    <select name="centreName" id="centreName" class="w-full px-3 py-2 mb-2 border">
                        <option disabled>-- Choose One --</option>
                        <?php foreach ($centres as $centre): ?>
                            <option value="<?php echo htmlspecialchars($centre['centre_name']); ?>" 
                                    <?php echo ($centreData['centreName'] === $centre['centre_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($centre['centre_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sisters in Charge -->
                <div class="form-group">
                    <label for="sistersInCharge">Sister(s) in Charge:</label>
                    <select name="sistersInCharge" id="sistersInCharge" class="w-full px-3 py-2 mb-2 border">
                        <option disabled>-- Choose One --</option>
                        <?php foreach ($sisters as $sister): ?>
                            <option value="<?php echo htmlspecialchars($sister['full_name']); ?>" 
                                    <?php echo ($centreData['sistersInCharge'] === $sister['full_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sister['full_name']); ?> (<?php echo htmlspecialchars($sister['username']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Centre History -->
                <div class="form-group">
                    <label for="centreHistory">History of the Centre:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="centreHistoryYes" name="centreHistory" value="yes" <?php echo ($centreData['centreHistory'] === 'yes') ? 'checked' : ''; ?> class="mr-2">
                        <label for="centreHistoryYes">Yes</label>
                        <input type="radio" id="centreHistoryNo" name="centreHistory" value="no" <?php echo ($centreData['centreHistory'] === 'no') ? 'checked' : ''; ?> class="mr-2">
                        <label for="centreHistoryNo">No</label>
                    </div>
                </div>

                <!-- Feedback Report Record -->
                <div class="form-group">
                    <label for="feedbackReportRecord">Feedback Report Record:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="feedbackReportRecordYes" name="feedbackReportRecord" value="yes" <?php echo ($centreData['feedbackReportRecord'] === 'yes') ? 'checked' : ''; ?> class="mr-2">
                        <label for="feedbackReportRecordYes">Yes</label>
                        <input type="radio" id="feedbackReportRecordNo" name="feedbackReportRecord" value="no" <?php echo ($centreData['feedbackReportRecord'] === 'no') ? 'checked' : ''; ?> class="mr-2">
                        <label for="feedbackReportRecordNo">No</label>
                    </div>
                </div>

                <!-- Short Term Goals -->
                <div class="form-group">
                    <label for="shortTermGoals" class="block mb-2">Short Term Goals:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="shortTermGoalsYes" name="shortTermGoals" value="yes" <?php echo ($centreData['shortTermGoals'] === 'yes') ? 'checked' : ''; ?> class="mr-2">
                        <label for="shortTermGoalsYes">Yes</label>
                        <input type="radio" id="shortTermGoalsNo" name="shortTermGoals" value="no" <?php echo ($centreData['shortTermGoals'] === 'no') ? 'checked' : ''; ?> class="mr-2">
                        <label for="shortTermGoalsNo">No</label>
                    </div>
                </div>

                <!-- Comments on Short Term Goals -->
                <div class="form-group">
                    <label for="commentShortTermGoals">Comments on Short Term Goals:</label>
                    <textarea name="commentShortTermGoals" id="commentShortTermGoals" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($centreData['commentShortTermGoals']); ?></textarea>
                </div>

                <!-- Long Term Goals -->
                <div class="form-group">
                    <label for="longTermGoals" class="block mb-2">Long Term Goals:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="longTermGoalsYes" name="longTermGoals" value="yes" <?php echo ($centreData['longTermGoals'] === 'yes') ? 'checked' : ''; ?> class="mr-2">
                        <label for="longTermGoalsYes">Yes</label>
                        <input type="radio" id="longTermGoalsNo" name="longTermGoals" value="no" <?php echo ($centreData['longTermGoals'] === 'no') ? 'checked' : ''; ?> class="mr-2">
                        <label for="longTermGoalsNo">No</label>
                    </div>
                </div>

                <!-- Comments on Long Term Goals -->
                <div class="form-group">
                    <label for="commentLongTermGoals">Comments on Long Term Goals:</label>
                    <textarea name="commentLongTermGoals" id="commentLongTermGoals" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($centreData['commentLongTermGoals']); ?></textarea>
                </div>

                <!-- Inventory -->
                <div class="form-group">
                    <label for="inventory" class="block mb-2">Inventory:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="inventoryYes" name="inventory" value="yes" <?php echo ($centreData['inventory'] === 'yes') ? 'checked' : ''; ?> class="mr-2">
                        <label for="inventoryYes">Yes</label>
                        <input type="radio" id="inventoryNo" name="inventory" value="no" <?php echo ($centreData['inventory'] === 'no') ? 'checked' : ''; ?> class="mr-2">
                        <label for="inventoryNo">No</label>
                    </div>
                </div>

                <!-- Comments on Inventory -->
                <div class="form-group">
                    <label for="commentInventory">Comments on Inventory:</label>
                    <textarea name="commentInventory" id="commentInventory" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($centreData['commentInventory']); ?></textarea>
                </div>

                <!-- Asset File -->
                <div class="form-group">
                    <label for="assetFile" class="block mb-2">Asset File:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="assetFileYes" name="assetFile" value="yes" <?php echo ($centreData['assetFile'] === 'yes') ? 'checked' : ''; ?> class="mr-2">
                        <label for="assetFileYes">Yes</label>
                        <input type="radio" id="assetFileNo" name="assetFile" value="no" <?php echo ($centreData['assetFile'] === 'no') ? 'checked' : ''; ?> class="mr-2">
                        <label for="assetFileNo">No</label>
                    </div>
                </div>


                <!-- Comments on Asset Files -->
                <div class="form-group">
                    <label for="commentAssetFiles">Comments on Asset Files:</label>
                    <textarea name="commentAssetFiles" id="commentAssetFiles" class="w-full px-3 py-2 mb-2 border"><?php echo htmlspecialchars($centreData['commentAssetFiles']); ?></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="form-group mt-5">
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white">Save Changes</button>
                    <a href="../View/AssessmentCentreDetail.php?keyID=<?php echo urlencode($centreData['keyID']); ?>" class="px-4 py-2 bg-gray-500 text-white">Cancel</a>
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
