<?php
// Start session
session_start();
require_once '../includes/dbh.inc.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head class="text-center py-4">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
<link rel="stylesheet" type="text/css" href="../style.css">
<title>Assessment - Documentation</title>
</head>
<body class="flex flex-col h-screen">
    <!-- Include topbar and sidebar -->
    <?php include '../topbar.php'; ?>
    <div class="flex flex-1 min-h-0">
        <?php include '../sidebar.php'; ?>

        <!-- Main content with white background -->
        <div class="form-container p-6 flex-1 bg-white">
            <h1 class="text-2xl font-bold text-center mb-6">Particulars of the Documentation</h1><br><br>

            <form action="../Controller/assessment_documentation_process.php" method="POST" enctype="multipart/form-data">

                <!-- Date of Assessment -->
                <div class="form-group">
                    <label for="DateOfAssessment" class="block mb-2">Date Of Assessment:</label>
                    <input type="date" name="DateOfAssessment" required class="w-full px-3 py-2 mb-4 border rounded">
                </div>

                <!-- KeyID Dropdown Field -->
                <div class="form-group">
                    <label for="keyID" class="block mb-2">Select KeyID:</label>
                    <select name="keyID" id="keyID" required class="w-full px-3 py-2 mb-2 border">
                        <option disabled selected>-- Choose KeyID --</option>
                        <?php foreach ($keyIDs as $keyID): ?>
                            <option value="<?php echo htmlspecialchars($keyID['keyID']); ?>">
                                <?php echo htmlspecialchars($keyID['keyID']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h2 class="text-xl font-bold text-center mt-5">Particulars of the Documentation</h2><br>

                <!-- Feedback Report -->
                <div class="form-group">
                    <label for="feedbackReport" class="block mb-2">Feedback Report:</label>
                    <textarea name="feedbackReport" class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>

                <!-- Annual Reports -->
                <div class="form-group">
                    <label class="block mb-2">Annual Reports:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="annualReports" value="yes" class="mr-2"> Yes
                        <input type="radio" name="annualReports" value="no" class="mr-2"> No
                    </div>
                </div>

                <!-- Data of the Beneficiaries -->
                <div class="form-group">
                    <label class="block mb-2">Data of the Beneficiaries:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="beneficiariesData" value="yes" class="mr-2"> Yes
                        <input type="radio" name="beneficiariesData" value="no" class="mr-2"> No
                    </div>
                </div>

                <!-- Beneficiaries Type -->
                <div class="form-group">
                    <label for="beneficiariesType" class="block mb-2">Beneficiaries Type:</label>
                    <textarea name="beneficiariesType" class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>

                <!-- Number of Beneficiaries -->
                <div class="form-group">
                    <label for="beneficiariesCount" class="block mb-2">Number of Beneficiaries:</label>
                    <input type="number" name="beneficiariesCount" required class="w-full px-3 py-2 mb-4 border rounded">
                </div>

                <!-- Request & Thanking Letters -->
                <div class="form-group">
                    <label class="block mb-2">Request & Thanking Letters from the beneficiaries:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="requestThankingLetters" value="yes" class="mr-2"> Yes
                        <input type="radio" name="requestThankingLetters" value="no" class="mr-2"> No
                    </div>
                </div>

                <!-- Bye-laws -->
                <div class="form-group">
                    <label class="block mb-2">Bye-laws:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="bylaws" value="yes" class="mr-2"> Yes
                        <input type="radio" name="bylaws" value="no" class="mr-2"> No
                    </div>
                </div>
                <!-- Amendment -->
                <div class="form-group">
                    <label class="block mb-2">Amendment:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="amendment" value="yes" class="mr-2"> Yes
                        <input type="radio" name="amendment" value="no" class="mr-2"> No
                    </div>
                </div>

                <!-- PAN -->
                <div class="form-group">
                    <label class="block mb-2">PAN:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="pan" value="yes" class="mr-2"> Yes
                        <input type="radio" name="pan" value="no" class="mr-2"> No
                    </div>
                </div>

                <!-- Women Society Registration Copies -->
                <div class="form-group">
                    <label class="block mb-2">Women Society Registration Copies:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="womenSocietyRegistrationCopies" value="yes" class="mr-2"> Yes
                        <input type="radio" name="womenSocietyRegistrationCopies" value="no" class="mr-2"> No
                    </div>
                </div>

                <!-- Lease Agreement -->
                <div class="form-group">
                    <label class="block mb-2">Lease Agreement:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="leaseAgreement" value="yes" class="mr-2"> Yes
                        <input type="radio" name="leaseAgreement" value="no" class="mr-2"> No
                    </div>
                </div>

                <!-- Monthly Reports -->
                <div class="form-group">
                    <label class="block mb-2">Monthly Reports:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="monthlyReports" value="yes" class="mr-2"> Yes
                        <input type="radio" name="monthlyReports" value="no" class="mr-2"> No
                    </div>
                </div>
                <!-- LSAC Minutes (Yes/No) -->
                <div class="form-group">
                    <label class="block mb-2">LSAC Minutes:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" name="lsacMinutes" value="yes" class="mr-2"> Yes
                        <input type="radio" name="lsacMinutes" value="no" class="mr-2"> No
                    </div>
                </div>

                <!-- LSAC Minutes -->
                <div class="form-group">
                    <label for="lsacMinutesText" class="block mb-2">Comments on LSAC Minutes:</label>
                    <textarea name="lsacMinutesText" class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="form-group mt-5">
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white">Submit</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
