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

// Fetch existing finance data and projects
try {
    $sqlFinance = "SELECT * FROM AssessmentFinance WHERE keyID = :keyID";
    $stmtFinance = $pdo->prepare($sqlFinance);
    $stmtFinance->execute([':keyID' => $keyID]);
    $financeData = $stmtFinance->fetch(PDO::FETCH_ASSOC);

    $sqlProjects = "SELECT * FROM AssessmentFinanceProjects WHERE keyID = :keyID";
    $stmtProjects = $pdo->prepare($sqlProjects);
    $stmtProjects->execute([':keyID' => $keyID]);
    $projects = $stmtProjects->fetchAll(PDO::FETCH_ASSOC);

    if (!$financeData) {
        $_SESSION['error'] = "No finance data found for the given keyID.";
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
<title>Edit Finance Details and Projects</title>
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
            <h1 class="text-2xl font-bold text-center mb-5">Edit Finance Details and Projects</h1>
            <div class="text-left mb-5">
                <a href="../Controller/Show/AssessmentCentreDetailController.php?keyID=<?php echo urlencode($financeData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Back to View
                </a>
            </div>

            <form action="../Controller/Edit/finance_edit_process.php" method="POST">
                <input type="hidden" name="keyID" value="<?php echo htmlspecialchars($keyID); ?>">

                <!-- Finance Particulars Section -->
                <h2 class="text-xl font-bold text-center mb-5">Particulars of the Finance (Projects & Women Society)</h2>

                <?php
                $fields = [
                    'approvedAnnualBudget' => 'Approved Annual Budget',
                    'AuditStatements' => 'Audit Statements',
                    'BankStatements' => 'Bank Statements',
                    'scopeToExpand' => 'Scope to Expand',
                    'newInitiatives' => 'New Initiatives',
                    'helpNeeded' => 'Help Needed'
                ];

                foreach ($fields as $field => $label) {
                    echo "<div class='form-group mb-4'>
                            <label for='$field'>$label:</label>
                            <textarea id='$field' name='$field' class='w-full px-3 py-2 border rounded'>".htmlspecialchars($financeData[$field])."</textarea>
                          </div>";
                }
                ?>

                <!-- Project Impact Details Section -->
                <h2 class="text-xl font-bold text-center mb-5">Impact of Services</h2>
                <div id="projectCards">
                    <?php foreach ($projects as $index => $project): ?>
                        <div class="card mb-6 p-4 bg-gray-200 rounded">
                            <input type="hidden" name="projectID[]" value="<?php echo htmlspecialchars($project['id']); ?>">

                            <div class="form-group mb-2">
                                <label for="projectName[]">Project Name:</label>
                                <input type="text" name="projectName[]" value="<?php echo htmlspecialchars($project['projectName']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div class="form-group mb-2">
                                <label for="impactOfProject[]">Impact of Our Services:</label>
                                <textarea name="impactOfProject[]" class="w-full px-3 py-2 border rounded"><?php echo htmlspecialchars($project['impactOfProject']); ?></textarea>
                            </div>

                            <button type="button" class="removeCard px-4 py-2 bg-red-500 text-white mt-2">Remove Project</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" id="addProjectCard" class="px-4 py-2 bg-blue-500 text-white mb-6">Add Project Card</button>

                <!-- Additional Information Section -->
                <h2 class="text-xl font-bold text-center mb-5">Additional Information</h2>

                <?php
                $additionalFields = [
                    'CoursesAndTrainings' => 'Courses and Trainings Conducted',
                    'AnyUpdates' => 'Any Updates',
                    'RemarksOfTheTeam' => 'Remarks of the Team',
                    'NameOfTheAssessors' => 'Name of the Assessors',
                    'NameOfTheSisterIncharge' => 'Name of the Sister Incharge'
                ];

                foreach ($additionalFields as $field => $label) {
                    echo "<div class='form-group mb-4'>
                            <label for='$field'>$label:</label>
                            <textarea id='$field' name='$field' class='w-full px-3 py-2 border rounded'>".htmlspecialchars($financeData[$field])."</textarea>
                          </div>";
                }
                ?>

                <!-- Submit Button -->
                <div class="text-center mt-5">
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('addProjectCard').addEventListener('click', () => {
            const card = document.querySelector('.card').cloneNode(true);
            card.querySelectorAll('input, textarea').forEach(el => el.value = '');
            document.getElementById('projectCards').appendChild(card);
        });

        document.getElementById('projectCards').addEventListener('click', (e) => {
            if (e.target.classList.contains('removeCard')) {
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
