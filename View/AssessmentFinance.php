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
<title>Assessment - Finance</title>
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
        <div class="form-container p-6 bg-white-100 flex-1">
            <h1 class="text-2xl font-bold text-center mb-6">Particulars of the Finance </h1> 
            <h1 class="text-xl font- text-center mb-6">Projects & Women Society </h1> <br><br>
            <form action="../Controller/assessment_finance_process.php" method="POST" enctype="multipart/form-data">

                <!-- General Information -->
                <div class="form-group">
                    <label for="DateOfAssessment" class="block mb-2">Date Of Assessment:</label>
                    <input type="date" name="DateOfAssessment" id="DateOfAssessment" required class="w-full px-3 py-2 mb-4 border rounded">
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

                <!-- Finance Particulars -->
                <h2 class="text-xl font-bold text-center mt-5">Particulars of the Finance (Projects & Women Society) </h2><br>
                <div class="form-group">
                    <label for="approvedAnnualBudget" class="block mb-2">Approved annual budget:</label>
                    <textarea id="approvedAnnualBudget" name="approvedAnnualBudget" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>
                <div class="form-group">
                    <label for="AuditStatements" class="block mb-2">Audit statements:</label>
                    <textarea id="AuditStatements" name="AuditStatements" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>
                <div class="form-group">
                    <label for="BankStatements" class="block mb-2">Bank statements:</label>
                    <textarea id="BankStatements" name="BankStatements" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>
                <div class="form-group">
                    <label for="scopeToExpand" class="block mb-2">What scope do you find to expand / strengthen/initiate new and relevant programmes etc.?</label>
                    <textarea id="scopeToExpand" name="scopeToExpand" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>
                <div class="form-group">
                    <label for="newInitiatives" class="block mb-2">Mention any new initiatives:</label>
                    <textarea id="newInitiatives" name="newInitiatives" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>
                <div class="form-group">
                    <label for="helpNeeded" class="block mb-2">What type of help do you need?</label>
                    <textarea id="helpNeeded" name="helpNeeded" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>

                <!-- Project Impact Details -->
                <h2 class="text-xl font-bold text-center mt-5">Impact of Services</h2>
                <div id="projectCards">
                    <div class="card mb-6">
                        <div class="form-group">
                            <label for="projectName[]" class="block mb-2">Project name:</label>
                            <input type="text" name="projectName[]" required class="w-full px-3 py-2 mb-4 border rounded">
                        </div>
                        <div class="form-group">
                            <label for="impactOfProject[]" class="block mb-2">Impact of our services:</label>
                            <textarea name="impactOfProject[]" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                        </div>
                        <button type="button" class="removeCard px-4 py-2 bg-red-500 text-white mb-4">Remove</button>
                    </div>
                </div>
                <button type="button" id="addProjectCard" class="px-4 py-2 bg-blue-500 text-white mb-6">Add Project Card</button>

                <!-- Additional Fields -->
                <div class="form-group">
                    <label for="CoursesAndTrainings" class="block mb-2">Courses and trainings conducted:</label>
                    <textarea id="CoursesAndTrainings" name="CoursesAndTrainings" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>
                <div class="form-group">
                    <label for="AnyUpdates" class="block mb-2">Any updates:</label>
                    <textarea id="AnyUpdates" name="AnyUpdates" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>
                <div class="form-group">
                    <label for="RemarksOfTheTeam" class="block mb-2">Remarks of the team:</label>
                    <textarea id="RemarksOfTheTeam" name="RemarksOfTheTeam" required class="w-full px-3 py-2 mb-4 border rounded"></textarea>
                </div>
                <div class="form-group">
                    <label for="NameOfTheAssessors" class="block mb-2">Name of the assessors:</label>
                    <input type="text" name="NameOfTheAssessors" required class="w-full px-3 py-2 mb-4 border rounded">
                </div>
                <div class="form-group">
                    <label for="NameOfTheSisterIncharge" class="block mb-2">Name of the Sister incharge:</label>
                    <input type="text" name="NameOfTheSisterIncharge" required class="w-full px-3 py-2 mb-4 border rounded">
                </div>

                <!-- Submit Button -->
                <input type="submit" value="Submit" class="px-4 py-2 bg-green-500 text-white cursor-pointer">
            </form>
        </div>
    </div>

    <script>
        // // JavaScript for dynamically adding and removing project impact cards
        // document.getElementById('addProjectCard').addEventListener('click', () => {
        //     const card = document.querySelector('.card').cloneNode(true);
        //     document.getElementById('projectCards').appendChild(card);
        // });

        // document.getElementById('projectCards').addEventListener('click', (e) => {
        //     if (e.target && e.target.classList.contains('removeCard')) {
        //         e.target.closest('.card').remove();
        //     }
        // });
        // JavaScript for dynamically adding and removing project impact cards
document.getElementById('addProjectCard').addEventListener('click', () => {
    // Clone the first project card
    const card = document.querySelector('.card').cloneNode(true);

    // Clear the values of the cloned card's input fields and textareas
    const inputs = card.querySelectorAll('input');
    const textareas = card.querySelectorAll('textarea');

    inputs.forEach(input => {
        input.value = '';
    });

    textareas.forEach(textarea => {
        textarea.value = '';
    });

    // Append the cleared cloned card to the project cards container
    document.getElementById('projectCards').appendChild(card);
});

// JavaScript for removing project impact cards
document.getElementById('projectCards').addEventListener('click', (e) => {
    if (e.target && e.target.classList.contains('removeCard')) {
        e.target.closest('.card').remove();
    }
});

    </script>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
