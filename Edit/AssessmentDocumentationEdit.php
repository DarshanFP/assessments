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
    $sql = "SELECT * FROM AssessmentDocumentation WHERE keyID = :keyID";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':keyID' => $keyID]);
    $documentationData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$documentationData) {
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
<title>Edit Documentation Details</title>
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
            <h1 class="text-2xl font-bold text-center mb-5">Edit Particulars of the Documentation</h1>
            <div class="text-left mb-5">
                <a href="../Controller/Show/AssessmentCentreDetailController.php?keyID=<?php echo urlencode($documentationData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Back to View
                </a>
            </div>

            <form action="../Controller/Edit/assessment_documentation_edit_process.php" method="POST">
                <input type="hidden" name="keyID" value="<?php echo htmlspecialchars($documentationData['keyID']); ?>">

                <!-- Feedback Report -->
                <div class="form-group mb-4">
                    <label for="feedbackReport" class="block mb-2">Feedback Report:</label>
                    <textarea id="feedbackReport" name="feedbackReport" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($documentationData['feedbackReport']); ?></textarea>
                </div>

                <!-- Annual Reports -->
                <div class="form-group mb-4">
                    <label>Annual Reports:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="annualReportsYes" name="annualReports" value="yes" <?php echo ($documentationData['annualReports'] === 'yes') ? 'checked' : ''; ?>>
                        <label for="annualReportsYes">Yes</label>
                        <input type="radio" id="annualReportsNo" name="annualReports" value="no" <?php echo ($documentationData['annualReports'] === 'no') ? 'checked' : ''; ?>>
                        <label for="annualReportsNo">No</label>
                    </div>
                </div>

                <!-- Data of the Beneficiaries -->
                <div class="form-group mb-4">
                    <label>Data of the Beneficiaries:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="beneficiariesDataYes" name="beneficiariesData" value="yes" <?php echo ($documentationData['beneficiariesData'] === 'yes') ? 'checked' : ''; ?>>
                        <label for="beneficiariesDataYes">Yes</label>
                        <input type="radio" id="beneficiariesDataNo" name="beneficiariesData" value="no" <?php echo ($documentationData['beneficiariesData'] === 'no') ? 'checked' : ''; ?>>
                        <label for="beneficiariesDataNo">No</label>
                    </div>
                </div>

                <!-- Beneficiaries Type -->
                <div class="form-group mb-4">
                    <label for="beneficiariesType" class="block mb-2">Beneficiaries Type:</label>
                    <textarea id="beneficiariesType" name="beneficiariesType" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($documentationData['beneficiariesType']); ?></textarea>
                </div>

                <!-- Number of Beneficiaries -->
                <div class="form-group mb-4">
                    <label for="beneficiariesCount" class="block mb-2">Number of Beneficiaries:</label>
                    <input type="number" id="beneficiariesCount" name="beneficiariesCount" value="<?php echo htmlspecialchars($documentationData['beneficiariesCount']); ?>" class="w-full px-3 py-2 border rounded">
                </div>

                <!-- Additional Fields -->
                <?php
                $fields = [
                    'requestThankingLetters' => 'Request & Thanking Letters',
                    'bylaws' => 'Bye-laws',
                    'amendment' => 'Amendment',
                    'pan' => 'PAN',
                    'womenSocietyRegistrationCopies' => 'Registration Copies (Women Society)',
                    'leaseAgreement' => 'Lease Agreement',
                    'monthlyReports' => 'Monthly Reports',
                    'lsacMinutes' => 'LSAC Minutes'
                ];

                foreach ($fields as $field => $label) {
                    echo "<div class='form-group mb-4'>
                            <label>$label:</label>
                            <div class='flex items-center space-x-4'>
                                <input type='radio' id='{$field}Yes' name='{$field}' value='yes' " . ($documentationData[$field] === 'yes' ? 'checked' : '') . ">
                                <label for='{$field}Yes'>Yes</label>
                                <input type='radio' id='{$field}No' name='{$field}' value='no' " . ($documentationData[$field] === 'no' ? 'checked' : '') . ">
                                <label for='{$field}No'>No</label>
                            </div>
                          </div>";
                }
                ?>

                <!-- Comments on LSAC Minutes -->
                <div class="form-group mb-4">
                    <label for="lsacMinutesText" class="block mb-2">Comments on LSAC Minutes:</label>
                    <textarea id="lsacMinutesText" name="lsacMinutesText" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($documentationData['lsacMinutesText']); ?></textarea>
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
