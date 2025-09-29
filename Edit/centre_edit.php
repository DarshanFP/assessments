<?php
session_start();
require_once '../includes/dbh.inc.php';

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

try {
    $sqlAssessment = "SELECT * FROM CentreAssessment WHERE keyID = :keyID";
    $stmtAssessment = $pdo->prepare($sqlAssessment);
    $stmtAssessment->execute([':keyID' => $keyID]);
    $assessmentData = $stmtAssessment->fetch(PDO::FETCH_ASSOC);

    $sqlProjects = "SELECT * FROM CentreProjects WHERE keyID = :keyID";
    $stmtProjects = $pdo->prepare($sqlProjects);
    $stmtProjects->execute([':keyID' => $keyID]);
    $projectsData = $stmtProjects->fetchAll(PDO::FETCH_ASSOC);

    if (!$assessmentData) {
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
<title>Edit Centre Assessment</title>
</head>
<body class="flex flex-col h-screen">
    <?php include '../topbar.php'; ?>
    <div class="flex flex-1 min-h-0">
        <div class="form-container p-6 flex-1 bg-white">
            <h1 class="text-2xl font-bold text-center">Edit Centre Assessment</h1><br>
            <div class="text-left mt-5">
                <a href="../Controller/Show/AssessmentCentreDetailController.php?keyID=<?php echo urlencode($assessmentData['keyID']); ?>" class="px-4 py-2 bg-blue-500 text-white rounded">
                    Back to View <br>
                </a>

            <form action="../Controller/Edit/centre_edit_process.php" method="POST">
                <input type="hidden" name="keyID" value="<?php echo htmlspecialchars($keyID); ?>">

                <!-- Centre Assessment Fields -->
                <?php
                $fields = [
                    'Strengths' => 'Strengths',
                    'AreasOfAttention' => 'Areas of Attention',
                    'ImmediateAttention' => 'Areas of Immediate Attention',
                    'OpportunitiesIdentified' => 'Opportunities Identified',
                    'ChallengesFaced' => 'Challenges Faced',
                    'StepsTaken' => 'Steps Taken to Overcome Challenges',
                    'HelpRequested' => 'Type of Help Requested',
                    'NewVentures' => 'New Ventures',
                    'ListOfActivities' => 'List of Activities',
                    'Comments' => 'Recommendations / Suggestions / Feedback'
                ];

                foreach ($fields as $name => $label) {
                    echo "
                    <div class='form-group'>
                        <label for='$name' class='block mb-2'>$label:</label>
                        <textarea name='$name' id='$name' class='w-full px-3 py-2 mb-4 border rounded'>".htmlspecialchars($assessmentData[$name])."</textarea>
                    </div>
                    ";
                }
                ?>

                <!-- Project Cards Section -->
                <h2 class="text-xl font-bold mb-4">Project Impact Details</h2>
                <div id="projectContainer">
                    <?php foreach ($projectsData as $index => $project): ?>
                        <div class="project-card mb-6 border p-4 rounded bg-gray-100">
                            <input type="hidden" name="projectID[]" value="<?php echo htmlspecialchars($project['id']); ?>">

                            <div class="form-group mb-2">
                                <label for="ProjectName[]">Project Name:</label>
                                <input type="text" name="ProjectName[]" value="<?php echo htmlspecialchars($project['ProjectName']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div class="form-group mb-2">
                                <label for="NumberOfBeneficiaries[]">Number of Beneficiaries:</label>
                                <input type="number" name="NumberOfBeneficiaries[]" value="<?php echo htmlspecialchars($project['NumberOfBeneficiaries']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div class="form-group mb-2">
                                <label for="ProjectSponsor[]">Project Sponsor:</label>
                                <input type="text" name="ProjectSponsor[]" value="<?php echo htmlspecialchars($project['ProjectSponsor']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div class="form-group mb-2">
                                <label for="OtherSponsor[]">Specify Sponsor:</label>
                                <input type="text" name="OtherSponsor[]" value="<?php echo htmlspecialchars($project['OtherSponsor']); ?>" class="w-full px-3 py-2 border rounded">
                            </div>

                            <button type="button" class="removeCard px-4 py-2 bg-red-500 text-white mt-2">Remove Project</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" id="addCard" class="px-4 py-2 bg-green-500 text-white mb-6">Add More Project</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('addCard').addEventListener('click', function() {
            const newCard = document.querySelector('.project-card').cloneNode(true);
            newCard.querySelectorAll('input').forEach(input => input.value = '');
            document.getElementById('projectContainer').appendChild(newCard);
        });

        document.getElementById('projectContainer').addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('removeCard')) {
                if (document.querySelectorAll('.project-card').length > 1) {
                    e.target.closest('.project-card').remove();
                } else {
                    alert("At least one project card must remain.");
                }
            }
        });
    </script>
</body>
</html>
