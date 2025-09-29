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

// Define centres array with the same options as Community dropdown
$centres = [
    'Ajitsing Nagar',
    'Arul Colony',
    'Aurangabad',
    'Avanigadda',
    'Beed',
    'Chakan',
    'Chirala',
    'Deepthi Bhavan',
    'Ghodegaon',
    'Guntupalli',
    'Jaggayyapet',
    'Jahanuma',
    'Jyothi Nilayam',
    'Kapaerkheda',
    'Kashimira',
    'Kondapalli',
    'Kuttur',
    'Mangalagiri',
    'Mubaraspur',
    'Niuland',
    'Nunna',
    'Ponnur',
    'Prasanth Bhavan',
    'Rajavaram',
    'S.A.Peta',
    'Shapura',
    'Songaon',
    'St. Ann\'s Home',
    'St. Ann\'s Hospital',
    'Taylorpet',
    'Tiruvur',
    'Umwahlang',
    'Vasai'
];

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
<head class="text-center py-4">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
<title>Assessment - Centre</title>
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
                
    <div class="flex flex-1 min-h-0">
        <div class="form-container p-6 bg-gray-100 flex-1">
            <h1 class="text-2xl font-bold text-center mt-5">Particulars of the Centre</h1><br> </br>

            <!-- Display Session Messages -->
            <?php 
            $successMessage = $_SESSION['success'] ?? null;
            $errorMessage = $_SESSION['error'] ?? null;
            
            if ($successMessage): ?>
                <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form action="../Controller/assessment_centre_process.php" method="POST">
                <!-- Form Fields -->
                <div class="form-group">
                    <label for="DateOfAssessment">Date of Assessment:</label>
                    <input type="date" name="DateOfAssessment" id="DateOfAssessment" required class="w-full px-3 py-2 mb-2 border">
                </div>

                <div class="form-group">
                    <label for="AssessorsName">Assessor's Name:</label>
                    <input type="text" name="AssessorsName" id="AssessorsName" required class="w-full px-3 py-2 mb-2 border">
                </div>

                <div class="form-group">
                    <label for="Community">Community:</label>
                    <select name="Community" id="Community" required class="w-full px-3 py-2 mb-2 border">
                    <option disabled selected>-- Choose One --</option>
                    <option value="Ajitsing Nagar">Ajitsing Nagar</option>
                    <option value="Arul Colony">Arul Colony</option>
                    <option value="Aurangabad">Aurangabad</option>
                    <option value="Avanigadda">Avanigadda</option>
                    <option value="Beed">Beed</option>
                    <option value="Chakan">Chakan</option>
                    <option value="Chirala">Chirala</option>
                    <option value="Deepthi Bhavan">Deepthi Bhavan</option>
                    <option value="Ghodegaon">Ghodegaon</option>
                    <option value="Guntupalli">Guntupalli</option>
                    <option value="Jaggayyapet">Jaggayyapet</option>
                    <option value="Jahanuma">Jahanuma</option>
                    <option value="Jyothi Nilayam">Jyothi Nilayam</option>
                    <option value="Kapaerkheda">Kapaerkheda</option>
                    <option value="Kashimira">Kashimira</option>
                    <option value="Kondapalli">Kondapalli</option>
                    <option value="Kuttur">Kuttur</option>
                    <option value="Mangalagiri">Mangalagiri</option>
                    <option value="Mubaraspur">Mubaraspur</option>
                    <option value="Niuland">Niuland</option>
                    <option value="Nunna">Nunna</option>
                    <option value="Ponnur">Ponnur</option>
                    <option value="Prasanth Bhavan">Prasanth Bhavan</option>
                    <option value="Rajavaram">Rajavaram</option>
                    <option value="S.A.Peta">S.A.Peta</option>
                    <option value="Shapura">Shapura</option>
                    <option value="Songaon">Songaon</option>
                    <option value="St. Ann's Home">St. Ann's Home</option>
                    <option value="St. Ann's Hospital">St. Ann's Hospital</option>
                    <option value="Taylorpet">Taylorpet</option>
                    <option value="Tiruvur">Tiruvur</option>
                    <option value="Umwahlang">Umwahlang</option>
                    <option value="Vasai">Vasai</option>  
                    </select>
                </div>
                <div>
                <h2 class="text-xl font-bold text-center mt-5">Particulars of the Centre:</h2>
                <br>
                </div>
                <div class="form-group">
                    <label for="centreName">Name of the Centre:</label>
                    <select name="centreName" id="centreName" required class="w-full px-3 py-2 mb-2 border">
                        <option disabled selected>-- Choose One --</option>
                        <?php foreach ($centres as $centre): ?>
                            <option value="<?php echo htmlspecialchars($centre); ?>">
                                <?php echo htmlspecialchars($centre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sistersInCharge">Sister(s) in Charge:</label>
                    <select name="sistersInCharge" id="sistersInCharge" required class="w-full px-3 py-2 mb-2 border">
                        <option disabled selected>-- Choose One --</option>
                        <?php foreach ($sisters as $sister): ?>
                            <option value="<?php echo htmlspecialchars($sister['full_name']); ?>">
                                <?php echo htmlspecialchars($sister['full_name']); ?> (<?php echo htmlspecialchars($sister['username']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="centreHistory" class="block mb-2">History of the Centre:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="centreHistoryYes" name="centreHistory" value="yes" class="mr-2">
                        <label for="centreHistoryYes">Yes</label>

                        <input type="radio" id="centreHistoryNo" name="centreHistory" value="no" class="mr-2">
                        <label for="centreHistoryNo">No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="feedbackReportRecord" class="block mb-2">Feedback Report Record:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="feedbackReportRecordYes" name="feedbackReportRecord" value="yes" class="mr-2">
                        <label for="feedbackReportRecordYes">Yes</label>

                        <input type="radio" id="feedbackReportRecordNo" name="feedbackReportRecord" value="no" class="mr-2">
                        <label for="feedbackReportRecordNo">No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="shortTermGoals" class="block mb-2">Short Term Goals:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="shortTermGoalsYes" name="shortTermGoals" value="yes" class="mr-2">
                        <label for="shortTermGoalsYes">Yes</label>

                        <input type="radio" id="shortTermGoalsNo" name="shortTermGoals" value="no" class="mr-2">
                        <label for="shortTermGoalsNo">No</label>
                    </div>
                </div>


                <div class="form-group">
                    <label for="commentShortTermGoals">Comments on Short Term Goals:</label>
                    <textarea name="commentShortTermGoals" id="commentShortTermGoals" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>

                <div class="form-group">
                    <label for="longTermGoals" class="block mb-2">Long Term Goals:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="longTermGoalsYes" name="longTermGoals" value="yes" class="mr-2">
                        <label for="longTermGoalsYes">Yes</label>

                        <input type="radio" id="longTermGoalsNo" name="longTermGoals" value="no" class="mr-2">
                        <label for="longTermGoalsNo">No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="commentLongTermGoals">Comments on Long Term Goals:</label>
                    <textarea name="commentLongTermGoals" id="commentLongTermGoals" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>

                <div class="form-group">
                    <label for="inventory" class="block mb-2">Inventory:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="inventoryYes" name="inventory" value="yes" class="mr-2">
                        <label for="inventoryYes">Yes</label>

                        <input type="radio" id="inventoryNo" name="inventory" value="no" class="mr-2">
                        <label for="inventoryNo">No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="commentInventory">Comments on Inventory:</label>
                    <textarea name="commentInventory" id="commentInventory" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>

                <div class="form-group">
                    <label for="assetFile" class="block mb-2">Asset File:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="assetFileYes" name="assetFile" value="yes" class="mr-2">
                        <label for="assetFileYes">Yes</label>

                        <input type="radio" id="assetFileNo" name="assetFile" value="no" class="mr-2">
                        <label for="assetFileNo">No</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="commentAssetFiles">Comments on Asset Files:</label>
                    <textarea name="commentAssetFiles" id="commentAssetFiles" class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>

                <input type="submit" value="Submit" class="px-4 py-2 bg-green-500 text-white border-none cursor-pointer">
            </form>
        </div>
    </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
