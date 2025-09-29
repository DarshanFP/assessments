<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';
// require_once '../../includes/role_based_sidebar.php';

// Ensure the user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit();
}

// Get project ID from URL
$projectId = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($projectId <= 0) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: all_projects.php");
    exit();
}

// Fetch the logged-in user's details
$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];
$currentUserCommunity = $_SESSION['community'];

try {
    // Fetch project details
    $projectStmt = $pdo->prepare("
        SELECT p.*, o.organization_name, o.organization_code, o.color_theme, u.full_name as incharge_name
        FROM Projects p
        LEFT JOIN Organizations o ON p.organization_id = o.organization_id
        LEFT JOIN ssmntUsers u ON p.project_incharge = u.id
        WHERE p.project_id = :project_id AND p.is_active = 1
    ");
    $projectStmt->execute([':project_id' => $projectId]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        $_SESSION['error'] = "Project not found or has been deactivated.";
        header("Location: all_projects.php");
        exit();
    }

    // Check permissions - only project in-charge or councillor can edit
    $canEditDirectly = false;
    $canRequestEdit = false;
    
    if ($currentUserRole === 'Councillor') {
        $canEditDirectly = true;
    } else {
        // Check if user is assigned to this project
        $assignmentStmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM ProjectAssignments 
            WHERE project_id = :project_id 
            AND project_incharge_id = :user_id 
            AND is_active = 1
        ");
        $assignmentStmt->execute([':project_id' => $projectId, ':user_id' => $currentUserId]);
        $isAssigned = $assignmentStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($isAssigned) {
            $canRequestEdit = true;
        } else {
            $_SESSION['error'] = "You don't have permission to edit this project.";
            header("Location: my_projects.php");
            exit();
        }
    }

    // Fetch budget entries for this project
    $budgetStmt = $pdo->prepare("
        SELECT * FROM BudgetEntries 
        WHERE project_id = :project_id 
        ORDER BY entry_id
    ");
    $budgetStmt->execute([':project_id' => $projectId]);
    $budgetEntries = $budgetStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch users for project in-charge selection
    if ($currentUserRole === 'Councillor') {
        $stmt = $pdo->prepare("SELECT id, full_name, community FROM ssmntUsers WHERE role = 'Project In-Charge'");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name FROM ssmntUsers WHERE role = 'Project In-Charge' AND community = :community AND id != :user_id");
        $stmt->execute([':community' => $currentUserCommunity, ':user_id' => $currentUserId]);
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch active organizations
    $orgStmt = $pdo->prepare("SELECT organization_id, organization_code, organization_name, full_name, color_theme FROM Organizations WHERE is_active = 1 ORDER BY organization_name");
    $orgStmt->execute();
    $organizations = $orgStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching project data: " . $e->getMessage();
    header("Location: all_projects.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Assessment System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
</head>
<body>
    <!-- Layout Container -->
    <div class="layout-container">
        
        <!-- Topbar -->
        <?php include '../../topbar.php'; ?>
        
        <!-- Sidebar -->
        <?php include '../../includes/role_based_sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            
            <!-- Content Container -->
            <div class="content-container">
                
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Edit Project: <?php echo htmlspecialchars($project['project_name']); ?></h1>
                    <p class="text-gray-600">Update project details and budget entries</p>
                    <?php if ($canRequestEdit && !$canEditDirectly): ?>
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mt-4">
                            <strong>Note:</strong> As a Project In-Charge, your changes will require approval from a Councillor before being applied.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Display Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <form action="../Controller/<?php echo $canEditDirectly ? 'project_edit_process.php' : 'project_edit_request_process.php'; ?>" method="POST">
                    <input type="hidden" name="project_id" value="<?php echo $projectId; ?>">
                    
                    <!-- Project Details Section -->
                    <div class="form-section">
                        <h2 class="text-xl font-semibold mb-4">Project Details</h2>

                        <div class="form-group">
                            <label for="project_name">Project Name:</label>
                            <input type="text" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="project_center">Project Center:</label>
                            <input type="text" id="project_center" name="project_center" value="<?php echo htmlspecialchars($project['project_center']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="organization_id">Operating Organization:</label>
                            <select id="organization_id" name="organization_id" required>
                                <option value="" disabled>Select Organization</option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?php echo $org['organization_id']; ?>" 
                                            data-color="<?php echo $org['color_theme']; ?>"
                                            <?php echo $org['organization_id'] == $project['organization_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($org['organization_name'] . ' (' . $org['organization_code'] . ') - ' . $org['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Project In-Charge Selection -->
                        <div class="form-group">
                            <label for="project_incharge">Project In-Charge:</label>
                            <?php if ($currentUserRole === 'Councillor'): ?>
                                <!-- Councillor can select any Project In-Charge -->
                                <select id="project_incharge" name="project_incharge" required>
                                    <option value="" disabled>Select In-Charge</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo $user['id'] == $project['project_incharge'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name']) . " (" . htmlspecialchars($user['community']) . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <!-- Project In-Charge sees their own name and can select others from their community -->
                                <input type="hidden" name="project_incharge" value="<?php echo $currentUserId; ?>">
                                <p>Assigned In-Charge: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                                <select id="project_incharge_other" name="project_incharge_other">
                                    <option value="" disabled selected>Select Another In-Charge from Your Community</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo $user['id'] == $project['project_incharge'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="total_budget">Total Budget (Rs):</label>
                            <input type="number" id="total_budget" name="total_budget" step="0.01" value="<?php echo $project['total_budget']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="start_date">Start Date:</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $project['start_date']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="end_date">End Date:</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $project['end_date']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="funding_source">Funding Source:</label>
                            <textarea id="funding_source" name="funding_source" placeholder="Enter details about the funding source (e.g., government grant, private donation, etc.)"><?php echo htmlspecialchars($project['funding_source']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="fund_type">Fund Type:</label>
                            <input type="text" id="fund_type" name="fund_type" value="<?php echo htmlspecialchars($project['fund_type']); ?>" placeholder="Enter fund type (e.g., Grant, Donation, Loan, etc.)">
                        </div>

                        <div class="form-group">
                            <label for="remarks">Remarks:</label>
                            <textarea id="remarks" name="remarks"><?php echo htmlspecialchars($project['remarks']); ?></textarea>
                        </div>
                    </div>

                    <!-- Budget Entries Section -->
                    <div class="form-section">
                        <h2 class="text-xl font-semibold mb-4">Budget Entries</h2>
                        <div class="table-container">
                            <table id="budgetEntriesTable">
                                <thead>
                                    <tr>
                                        <th>Particular</th>
                                        <th>Rate</th>
                                        <th>Quantity</th>
                                        <th>Duration</th>
                                        <th>Amount (Rs)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($budgetEntries)): ?>
                                        <?php foreach ($budgetEntries as $index => $entry): ?>
                                            <tr>
                                                <td>
                                                    <input type="text" name="budget_entries[<?php echo $index; ?>][particular]" 
                                                           value="<?php echo htmlspecialchars($entry['particular']); ?>" 
                                                           placeholder="Enter Particular" required>
                                                    <input type="hidden" name="budget_entries[<?php echo $index; ?>][entry_id]" 
                                                           value="<?php echo $entry['entry_id']; ?>">
                                                </td>
                                                <td>
                                                    <input type="number" name="budget_entries[<?php echo $index; ?>][rate]" 
                                                           step="0.01" value="<?php echo $entry['rate_multiplier']; ?>" 
                                                           placeholder="Enter Rate" required oninput="updateRowTotal(this)">
                                                </td>
                                                <td>
                                                    <input type="number" name="budget_entries[<?php echo $index; ?>][rate_quantity]" 
                                                           step="0.01" value="<?php echo $entry['rate_quantity']; ?>" 
                                                           placeholder="Enter Quantity" required oninput="updateRowTotal(this)">
                                                </td>
                                                <td>
                                                    <input type="number" name="budget_entries[<?php echo $index; ?>][rate_duration]" 
                                                           value="<?php echo $entry['rate_duration']; ?>" 
                                                           placeholder="Enter Duration" required oninput="updateRowTotal(this)">
                                                </td>
                                                <td>
                                                    <input type="number" name="budget_entries[<?php echo $index; ?>][amount_this_phase]" 
                                                           value="<?php echo $entry['amount_this_phase']; ?>" readonly>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger" onclick="removeBudgetEntryRow(this)">Delete</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Default row if no entries exist -->
                                        <tr>
                                            <td><input type="text" name="budget_entries[0][particular]" placeholder="Enter Particular" required></td>
                                            <td><input type="number" name="budget_entries[0][rate]" step="0.01" placeholder="Enter Rate" required oninput="updateRowTotal(this)"></td>
                                            <td><input type="number" name="budget_entries[0][rate_quantity]" step="0.01" placeholder="Enter Quantity" required oninput="updateRowTotal(this)"></td>
                                            <td><input type="number" name="budget_entries[0][rate_duration]" placeholder="Enter Duration" required oninput="updateRowTotal(this)"></td>
                                            <td><input type="number" name="budget_entries[0][amount_this_phase]" readonly></td>
                                            <td><button type="button" class="btn btn-danger" onclick="removeBudgetEntryRow(this)">Delete</button></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th class="text-right">Total:</th>
                                        <th><input type="number" id="totalRate" readonly></th>
                                        <th><input type="number" id="totalQuantity" readonly></th>
                                        <th><input type="number" id="totalDuration" readonly></th>
                                        <th><input type="number" id="overallTotal" readonly></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <button type="button" id="addEntryButton" class="btn btn-success mt-4">Add Budget Entry</button>
                    </div>
                    
                    <!-- Submit Button Section -->
                    <div class="form-section">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                            <?php echo $canEditDirectly ? 'Update Project and Budget Entries' : 'Submit Edit Request for Approval'; ?>
                        </button>
                    </div>
                </form>

                <!-- JavaScript for Dynamic Row Addition and Calculations -->
                <script>
                    document.getElementById('addEntryButton').addEventListener('click', addBudgetEntryRow);

                    function addBudgetEntryRow() {
                        const tableBody = document.querySelector('#budgetEntriesTable tbody');
                        const rowCount = tableBody.rows.length;
                        const row = tableBody.insertRow();

                        row.innerHTML = `
                            <td><input type="text" name="budget_entries[${rowCount}][particular]" class="w-full px-2 py-1 border" placeholder="Enter Particular" required></td>
                            <td><input type="number" name="budget_entries[${rowCount}][rate]" class="w-full px-2 py-1 border" step="0.01" placeholder="Enter Rate" required oninput="updateRowTotal(this)"></td>
                            <td><input type="number" name="budget_entries[${rowCount}][rate_quantity]" class="w-full px-2 py-1 border" step="0.01" placeholder="Enter Quantity" required oninput="updateRowTotal(this)"></td>
                            <td><input type="number" name="budget_entries[${rowCount}][rate_duration]" class="w-full px-2 py-1 border" placeholder="Enter Duration" required oninput="updateRowTotal(this)"></td>
                            <td><input type="number" name="budget_entries[${rowCount}][amount_this_phase]" class="w-full px-2 py-1 border" readonly></td>
                            <td><button type="button" class="text-red-500 hover:underline" onclick="removeBudgetEntryRow(this)">Delete</button></td>
                        `;

                        updateColumnTotals();
                    }

                    function updateRowTotal(inputElement) {
                        const row = inputElement.closest('tr');
                        const rate = parseFloat(row.querySelector('[name*="rate"]').value) || 0;
                        const quantity = parseFloat(row.querySelector('[name*="rate_quantity"]').value) || 0;
                        const duration = parseInt(row.querySelector('[name*="rate_duration"]').value) || 1;
                        const amount = rate * quantity * duration;

                        row.querySelector('[name*="amount_this_phase"]').value = amount.toFixed(2);

                        updateColumnTotals();
                    }

                    function updateColumnTotals() {
                        const tableBody = document.querySelector('#budgetEntriesTable tbody');
                        let totalRate = 0, totalQuantity = 0, totalDuration = 0, overallTotal = 0;

                        tableBody.querySelectorAll('tr').forEach(row => {
                            const rate = parseFloat(row.querySelector('[name*="rate"]').value) || 0;
                            const quantity = parseFloat(row.querySelector('[name*="rate_quantity"]').value) || 0;
                            const duration = parseInt(row.querySelector('[name*="rate_duration"]').value) || 1;
                            const amount = parseFloat(row.querySelector('[name*="amount_this_phase"]').value) || 0;

                            totalRate += rate;
                            totalQuantity += quantity;
                            totalDuration += duration;
                            overallTotal += amount;
                        });

                        document.getElementById('totalRate').value = totalRate.toFixed(2);
                        document.getElementById('totalQuantity').value = totalQuantity.toFixed(2);
                        document.getElementById('totalDuration').value = totalDuration.toFixed(2);
                        document.getElementById('overallTotal').value = overallTotal.toFixed(2);

                        // Update the total budget input field
                        document.getElementById('total_budget').value = overallTotal.toFixed(2);
                    }

                    function removeBudgetEntryRow(button) {
                        button.closest('tr').remove();
                        updateColumnTotals();
                    }

                    // Initialize totals on page load
                    document.addEventListener('DOMContentLoaded', function() {
                        updateColumnTotals();
                    });
                </script>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>
</body>
</html>
