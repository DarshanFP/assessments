<?php
session_start();
require_once '../includes/dbh.inc.php';
require_once '../includes/path_resolver.php';
require_once '../includes/log_activity.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$keyID = $_GET['keyID'] ?? null;

if (!$keyID) {
    $_SESSION['error'] = "Invalid request. Missing keyID.";
    header("Location: ../View/dashboard.php");
    exit();
}

try {
    // Fetch data from `AssessmentSisters` table
    $sqlSisters = "SELECT * FROM AssessmentSisters WHERE keyID = :keyID";
    $stmtSisters = $pdo->prepare($sqlSisters);
    $stmtSisters->execute([':keyID' => $keyID]);
    $sistersData = $stmtSisters->fetch(PDO::FETCH_ASSOC);

    // Fetch data from `AssessmentSisterCards` table
    $sqlCards = "SELECT * FROM AssessmentSisterCards WHERE keyID = :keyID ORDER BY SerialNumber ASC";
    $stmtCards = $pdo->prepare($sqlCards);
    $stmtCards->execute([':keyID' => $keyID]);
    $sisterCardsData = $stmtCards->fetchAll(PDO::FETCH_ASSOC);

    if (!$sistersData) {
        $_SESSION['error'] = "No data found for the given keyID in `AssessmentSisters` table.";
        header("Location: ../View/dashboard.php");
        exit();
    }
} catch (Exception $e) {
    logActivityToDatabase($userId, "Fetch Assessment Sisters Data", "error", "Error fetching data: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching the data. Please try again later.";
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
    <title>Edit Particulars of the Sisters</title>
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
            <h1 class="text-2xl font-bold text-center mb-5">Edit Particulars of the Sisters</h1>
            <div class="text-left mt-5">
                <a href="../Controller/Show/AssessmentCentreDetailController.php?keyID=<?php echo urlencode($sistersData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Back to View
                </a>


            </div>
            <form action="../Controller/Edit/assessment_sisters_edit_process.php" method="POST">
                <input type="hidden" name="keyID" value="<?php echo htmlspecialchars($sistersData['keyID']); ?>">

                <!-- Number of Sisters -->
                <div class="form-group mb-4">
                    <label for="NoOfSisters">Number of Sisters in the Centre:</label>
                    <input type="number" name="NoOfSisters" id="NoOfSisters" value="<?php echo htmlspecialchars($sistersData['NoOfSisters']); ?>" class="w-full px-3 py-2 border rounded">
                </div>

                <!-- Chronicle -->
                <div class="form-group mb-4">
                    <label>Chronicle for each sister:</label>
                    <div class="flex items-center space-x-4">
                        <input type="radio" id="ChronicleYes" name="Chronicle" value="yes" <?php echo ($sistersData['Chronicle'] === 'yes') ? 'checked' : ''; ?>>
                        <label for="ChronicleYes">Yes</label>
                        <input type="radio" id="ChronicleNo" name="Chronicle" value="no" <?php echo ($sistersData['Chronicle'] === 'no') ? 'checked' : ''; ?>>
                        <label for="ChronicleNo">No</label>
                    </div>
                </div>

                <!-- Chronicle Comments -->
                <div class="form-group mb-4">
                    <label for="chronicleComment">Comments on Chronicle:</label>
                    <textarea name="chronicleComment" id="chronicleComment" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($sistersData['chronicleComment']); ?></textarea>
                </div>

                <!-- Dynamic Sister Cards -->
                <h2 class="text-xl font-bold mb-4">Edit Sister Cards</h2>
                <div id="sisterCards">
                    <?php foreach ($sisterCardsData as $index => $card): ?>
                        <div class="card mb-6 p-4 bg-gray-200 rounded">
                            <input type="hidden" name="sisterCards[<?php echo $index; ?>][SerialNumber]" value="<?php echo htmlspecialchars($card['SerialNumber']); ?>">

                            <div class="form-group mb-2">
                                <label>Date of Starting:</label>
                                <input type="date" name="sisterCards[<?php echo $index; ?>][DateOfStarting]" value="<?php echo htmlspecialchars($card['DateOfStarting']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div class="form-group mb-2">
                                <label>Programme:</label>
                                <input type="text" name="sisterCards[<?php echo $index; ?>][Programme]" value="<?php echo htmlspecialchars($card['Programme']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div class="form-group mb-2">
                                <label>Sister In Charge:</label>
                                <input type="text" name="sisterCards[<?php echo $index; ?>][SisterInCharge]" value="<?php echo htmlspecialchars($card['SisterInCharge']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div class="form-group mb-2">
                                <label>Number of Staff:</label>
                                <input type="number" name="sisterCards[<?php echo $index; ?>][NoOfStaff]" value="<?php echo htmlspecialchars($card['NoOfStaff']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div class="form-group mb-2">
                                <label>Number of Beneficiaries:</label>
                                <input type="number" name="sisterCards[<?php echo $index; ?>][NoOfBeneficiaries]" value="<?php echo htmlspecialchars($card['NoOfBeneficiaries']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <button type="button" class="removeCard px-4 py-2 bg-red-500 text-white mt-2">Remove</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" id="addCard" class="px-4 py-2 bg-blue-500 text-white mb-4">Add More Sister Card</button>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white">Save Changes</button>
            </form>
        </div>
    </div>

<script>
    function updateSerialNumbers() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.querySelector('input[name^="sisterCards"]').name = `sisterCards[${index}][SerialNumber]`;
        });
    }

    document.getElementById('addCard').addEventListener('click', () => {
        const card = document.querySelector('.card').cloneNode(true);
        card.querySelectorAll('input').forEach(input => input.value = '');
        document.getElementById('sisterCards').appendChild(card);
        updateSerialNumbers();
    });

    document.getElementById('sisterCards').addEventListener('click', (e) => {
        if (e.target.classList.contains('removeCard')) {
            e.target.closest('.card').remove();
            updateSerialNumbers();
        }
    });

    updateSerialNumbers();
</script>


            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
