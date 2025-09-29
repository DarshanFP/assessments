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
<title>Centre - Assessment</title>
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
                

    <!-- Include Topbar -->
    <?php include '../topbar.php'; ?>

    <div class="flex flex-1 min-h-0">

        <div class="form-container flex-1 p-4">
            <h1 class="text-2xl font-bold text-center mt-5">Centre Assessment </h1> <br><br>

            <form action="../Controller/centre_process.php" method="POST" enctype="multipart/form-data">

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
                <div>
                <h2 class="text-xl font-bold text-center mt-5">Centre Assessment:</h2><br>
                </div>

                <!-- Text Areas for Assessment -->
                <?php
                $textAreas = [
                    'Strengths' => 'Strengths',
                    'AreasOfAttention' => 'Areas of Attention',
                    'ImmediateAttention' => 'Areas of Immediate Attention',
                    'OpportunitiesIdentified' => 'Opportunities Identified',
                    'ChallengesFaced' => 'Challenges Faced',
                    'StepsTaken' => 'Steps Taken to Overcome Challenges',
                    'HelpRequested' => 'Type of Help Requested',
                    'NewVentures' => 'New Ventures',
                    'ListOfActivities' => 'List of Activities'
                ];
                foreach ($textAreas as $name => $label) {
                    echo "
                    <div class='form-group'>
                        <label for='$name' class='block mb-2'>$label:</label>
                        <textarea name='$name' id='$name' required class='w-full px-3 py-2 mb-2 border'></textarea>
                    </div>
                    ";
                }
                ?>

                <!-- Dynamic Project Cards -->
                <div id="cardContainer">
                    <div class="card">
                        <div class="form-group">
                            <label for="ProjectName[]" class="block mb-2">Project Name:</label>
                            <input type="text" name="ProjectName[]" required class="w-full px-3 py-2 mb-2 border">
                        </div>

                        <div class="form-group">
                            <label for="NumberOfBeneficiaries[]" class="block mb-2">Number of Beneficiaries:</label>
                            <input type="number" name="NumberOfBeneficiaries[]" required class="w-full px-3 py-2 mb-2 border" min="0">
                        </div>

                        <div class="form-group">
                            <label for="ProjectSponsor[]" class="block mb-2">Project Sponsor:</label>
                            <select name="ProjectSponsor[]" required class="w-full px-3 py-2 mb-2 border">
                                <option disabled selected>-- Choose One --</option>
                                <option value="FC Project">FC Project</option>
                                <option value="Government Project">Government Project</option>
                                <option value="Trust Projects">Trust Projects</option>
                                <option value="Self Financed">Self Financed</option>
                                <option value="Sponsored by others">Sponsored by Others</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="OtherSponsor[]" class="block mb-2">Specify Sponsor:</label>
                            <input type="text" name="OtherSponsor[]" class="w-full px-3 py-2 mb-2 border">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button id="addCard" type="button" class="px-4 py-2 bg-green-500 text-white">Add More Project</button>
                </div>

                <!-- Dynamic Recommendation Cards -->
                <div>
                    <h3 class="text-lg font-bold mt-5">Recommendations / Suggestions / Feedback:</h3><br>
                </div>
                
                <div id="recommendationContainer">
                    <div class="recommendation-card bg-gray-50 p-4 mb-4 rounded border">
                        <div class="form-group">
                            <label for="recommendation_text[]" class="block mb-2 font-semibold">Recommendation Text:</label>
                            <textarea name="recommendation_text[]" required class="w-full px-3 py-2 mb-2 border" rows="3" placeholder="Enter your recommendation, suggestion, or feedback..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="recommendation_type[]" class="block mb-2">Type:</label>
                                <select name="recommendation_type[]" required class="w-full px-3 py-2 mb-2 border">
                                    <option disabled selected>-- Choose Type --</option>
                                    <option value="Recommendation">Recommendation</option>
                                    <option value="Suggestion">Suggestion</option>
                                    <option value="Feedback">Feedback</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="priority[]" class="block mb-2">Priority:</label>
                                <select name="priority[]" required class="w-full px-3 py-2 mb-2 border">
                                    <option disabled selected>-- Choose Priority --</option>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <button id="addRecommendation" type="button" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Add More Recommendation</button>
                    <button id="removeRecommendation" type="button" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 ml-2" style="display: none;">Remove Last</button>
                </div>

                <!-- Keep the old Comments field for backward compatibility -->
                <input type="hidden" name="Comments" value="">

                <input type="submit" value="Submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
            </form>
        </div>
    </div>

    <script>
        // Project cards functionality
        document.getElementById('addCard').addEventListener('click', function() {
            const newCard = document.querySelector('.card').cloneNode(true);
            document.getElementById('cardContainer').appendChild(newCard);
            newCard.querySelectorAll('input').forEach(input => input.value = '');
            newCard.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
        });

        // Recommendation cards functionality
        document.getElementById('addRecommendation').addEventListener('click', function() {
            const container = document.getElementById('recommendationContainer');
            const newCard = container.querySelector('.recommendation-card').cloneNode(true);
            
            // Clear the values
            newCard.querySelectorAll('textarea').forEach(textarea => textarea.value = '');
            newCard.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
            
            container.appendChild(newCard);
            
            // Show remove button if more than one card
            if (container.children.length > 1) {
                document.getElementById('removeRecommendation').style.display = 'inline-block';
            }
        });

        document.getElementById('removeRecommendation').addEventListener('click', function() {
            const container = document.getElementById('recommendationContainer');
            if (container.children.length > 1) {
                container.removeChild(container.lastElementChild);
                
                // Hide remove button if only one card left
                if (container.children.length === 1) {
                    this.style.display = 'none';
                }
            }
        });
    </script>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../footer.php'; ?>
        
    </div></body>
</html>
