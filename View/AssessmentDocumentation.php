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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../unified.css">
    <title>Assessment - Documentation</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">Particulars of the Documentation</h1>
                    <p class="text-gray-600">Assessment documentation form</p>
                </div>
                
                <!-- Main Form -->
                <form action="../Controller/assessment_documentation_process.php" method="POST" enctype="multipart/form-data">

                    <!-- Date of Assessment -->
                    <div class="form-group">
                        <label for="DateOfAssessment">Date Of Assessment:</label>
                        <input type="date" name="DateOfAssessment" required>
                    </div>

                    <!-- KeyID Dropdown Field -->
                    <div class="form-group">
                        <label for="keyID">Select KeyID:</label>
                        <select name="keyID" id="keyID" required>
                            <option disabled <?php echo !$selectedKeyID ? 'selected' : ''; ?>>-- Choose KeyID --</option>
                            <?php foreach ($keyIDs as $keyID): ?>
                                <option value="<?php echo htmlspecialchars($keyID['keyID']); ?>" 
                                        <?php echo ($selectedKeyID && $selectedKeyID === $keyID['keyID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($keyID['keyID']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h2 class="text-xl font-bold text-center mt-5">Particulars of the Documentation</h2>

                    <!-- Feedback Report -->
                    <div class="form-group">
                        <label for="feedbackReport">Feedback Report:</label>
                        <textarea name="feedbackReport"></textarea>
                    </div>

                    <!-- Annual Reports -->
                    <div class="form-group">
                        <label>Annual Reports:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="annualReports" value="yes" class="mr-2"> Yes
                            <input type="radio" name="annualReports" value="no" class="mr-2"> No
                        </div>
                    </div>

                    <!-- Data of the Beneficiaries -->
                    <div class="form-group">
                        <label>Data of the Beneficiaries:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="beneficiariesData" value="yes" class="mr-2"> Yes
                            <input type="radio" name="beneficiariesData" value="no" class="mr-2"> No
                        </div>
                    </div>

                    <!-- Beneficiaries Type -->
                    <div class="form-group">
                        <label for="beneficiariesType">Beneficiaries Type:</label>
                        <textarea name="beneficiariesType"></textarea>
                    </div>

                    <!-- Number of Beneficiaries -->
                    <div class="form-group">
                        <label for="beneficiariesCount">Number of Beneficiaries:</label>
                        <input type="number" name="beneficiariesCount" required>
                    </div>

                    <!-- Request & Thanking Letters -->
                    <div class="form-group">
                        <label>Request & Thanking Letters from the beneficiaries:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="requestThankingLetters" value="yes" class="mr-2"> Yes
                            <input type="radio" name="requestThankingLetters" value="no" class="mr-2"> No
                        </div>
                    </div>

                    <!-- Bye-laws -->
                    <div class="form-group">
                        <label>Bye-laws:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="bylaws" value="yes" class="mr-2"> Yes
                            <input type="radio" name="bylaws" value="no" class="mr-2"> No
                        </div>
                    </div>
                    
                    <!-- Amendment -->
                    <div class="form-group">
                        <label>Amendment:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="amendment" value="yes" class="mr-2"> Yes
                            <input type="radio" name="amendment" value="no" class="mr-2"> No
                        </div>
                    </div>

                    <!-- PAN -->
                    <div class="form-group">
                        <label>PAN:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="pan" value="yes" class="mr-2"> Yes
                            <input type="radio" name="pan" value="no" class="mr-2"> No
                        </div>
                    </div>

                    <!-- Women Society Registration Copies -->
                    <div class="form-group">
                        <label>Registration copies (originals) - applicable only for women society:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="womenSocietyRegistrationCopies" value="yes" class="mr-2"> Yes
                            <input type="radio" name="womenSocietyRegistrationCopies" value="no" class="mr-2"> No
                        </div>
                    </div>

                    <!-- Lease Agreement -->
                    <div class="form-group">
                        <label>Lease Agreement:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="leaseAgreement" value="yes" class="mr-2"> Yes
                            <input type="radio" name="leaseAgreement" value="no" class="mr-2"> No
                        </div>
                    </div>

                    <!-- Monthly Reports -->
                    <div class="form-group">
                        <label>Monthly Reports:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="monthlyReports" value="yes" class="mr-2"> Yes
                            <input type="radio" name="monthlyReports" value="no" class="mr-2"> No
                        </div>
                    </div>
                    
                    <!-- LSAC Minutes (Yes/No) -->
                    <div class="form-group">
                        <label>LSAC Minutes:</label>
                        <div class="flex items-center space-x-4">
                            <input type="radio" name="lsacMinutes" value="yes" class="mr-2"> Yes
                            <input type="radio" name="lsacMinutes" value="no" class="mr-2"> No
                        </div>
                    </div>

                    <!-- LSAC Minutes -->
                    <div class="form-group">
                        <label for="lsacMinutesText">Comments on LSAC Minutes:</label>
                        <textarea name="lsacMinutesText"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group mt-5">
                        <button type="submit" class="btn btn-success">Submit</button>
                    </div>
                </form>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div>
</body>
</html>
