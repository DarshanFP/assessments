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

<title>Assessment - Sisters</title>
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

        <!-- Main Content -->
        <div class="form-container p-6 bg-gray-100 flex-1">
            <h1 class="text-2xl font-bold text-center mb-5">Particulars of the Sisters</h1><br><br>

            <form action="../Controller/assessment_sisters_process.php" method="POST" enctype="multipart/form-data">
                <!-- @csrf -->

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

                <!-- Sisters' Details -->
                <h2 class="text-xl font-bold text-center mt-5">Particulars of the Sisters</h2><br>

                <!-- Number of Sisters -->
                <div class="form-group">
                    <label for="NoOfSisters" class="block mb-2">Number of Sisters in the Centre:</label>
                    <input type="number" name="NoOfSisters" id="NoOfSisters" required class="w-full px-3 py-2 mb-2 border">
                </div>

                <!-- Chronicle Details --> 
                <div class="form-group">
                    <label for="Chronicle" class="block mb-2">Chronicle for each sister (to be maintained):</label>
                    <div class="flex items-center space-x-4">                    
                        <input type="radio" id="ChronicleYes" name="Chronicle" value="yes">
                        <label for="ChronicleYes">Yes</label>
                        <input type="radio" id="ChronicleNo" name="Chronicle" value="no">
                        <label for="ChronicleNo">No</label>
                    </div>
                </div>

                <!-- Chronicle Comment -->
                <div class="form-group">
                    <label for="chronicleComment" class="block mb-2">Comments on Chronicle for each sister (to be maintained):</label>
                    <textarea id="chronicleComment" name="chronicleComment" required class="w-full px-3 py-2 mb-2 border"></textarea>
                </div>

                <!-- Dynamic Sister Cards -->
                <div id="sisterCards">
                    <div class="card">
                        <div class="form-group">
                            <label for="SerialNumber[]" class="block mb-2">Serial Number:</label>
                            <input type="number" name="SerialNumber[]" required class="w-full px-3 py-2 mb-2 border serial-number" readonly>

                        </div>
                        <div class="form-group">
                            <label for="DateOfStarting[]" class="block mb-2">Date of Starting:</label>
                            <input type="date" name="DateOfStarting[]" required class="w-full px-3 py-2 mb-2 border">
                        </div>
                        <div class="form-group">
                            <label for="Programme[]" class="block mb-2">Programme:</label>
                            <input type="text" name="Programme[]" required class="w-full px-3 py-2 mb-2 border">
                        </div>
                        <div class="form-group">
                            <label for="SisterInCharge[]" class="block mb-2">Sister In Charge:</label>
                            <input type="text" name="SisterInCharge[]" required class="w-full px-3 py-2 mb-2 border">
                        </div>
                        <div class="form-group">
                            <label for="NoOfStaff[]" class="block mb-2">Number of Staff:</label>
                            <input type="number" name="NoOfStaff[]" required class="w-full px-3 py-2 mb-2 border">
                        </div>
                        <div class="form-group">
                            <label for="NoOfBeneficiaries[]" class="block mb-2">Number of Beneficiaries:</label>
                            <input type="number" name="NoOfBeneficiaries[]" required class="w-full px-3 py-2 mb-2 border">
                        </div>
                        <button type="button" class="removeCard px-4 py-2 bg-red-500 text-white">Remove</button>
                    </div>
                </div>

                <button id="addCard" type="button" class="px-4 py-2 bg-blue-500 text-white">Add More</button>

                <!-- Submit Button -->
                <div class="form-group mt-5">
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Function to update the Serial Number for each card
    function updateSerialNumbers() {
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            const serialNumberInput = card.querySelector('.serial-number');
            serialNumberInput.value = index + 1; // Auto-populate the Serial Number
        });
    }

    // Add a new card when 'Add More' button is clicked
    document.getElementById('addCard').addEventListener('click', function() {
        const card = document.querySelector('.card').cloneNode(true);
        
        // Reset input values in the cloned card except Serial Number
        card.querySelectorAll('input').forEach(input => {
            if (!input.classList.contains('serial-number')) {
                input.value = '';
            }
        });

        document.getElementById('sisterCards').appendChild(card);
        updateSerialNumbers(); // Update Serial Numbers
    });

    // Remove a card when 'Remove' button is clicked
    document.getElementById('sisterCards').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('removeCard')) {
            e.target.closest('.card').remove();
            updateSerialNumbers(); // Update Serial Numbers after removing
        }
    });

    // Initialize Serial Numbers on page load
    updateSerialNumbers();
</script>

<!-- <script>
    document.getElementById('addCard').addEventListener('click', function() {
        const card = document.querySelector('.card').cloneNode(true);
        document.getElementById('sisterCards').appendChild(card);
    });

    document.getElementById('sisterCards').addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('removeCard')) {
            e.target.closest('.card').remove();
        }
    });
</script> -->

