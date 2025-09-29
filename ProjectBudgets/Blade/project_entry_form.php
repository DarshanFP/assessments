<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';
// require_once '../../includes/role_based_sidebar.php'; // Include the sidebar based on user role

// Ensure the user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit();
}

// Fetch the logged-in user's details
$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];
$currentUserCommunity = $_SESSION['community'];

try {
    if ($currentUserRole === 'Councillor') {
        // Fetch all users with role 'Project In-Charge'
        $stmt = $pdo->prepare("SELECT id, full_name, community FROM ssmntUsers WHERE role = 'Project In-Charge'");
        $stmt->execute();
    } else {
        // Fetch Project In-Charge users from the same community as the current user
        $stmt = $pdo->prepare("SELECT id, full_name FROM ssmntUsers WHERE role = 'Project In-Charge' AND community = :community AND id != :user_id");
        $stmt->execute([':community' => $currentUserCommunity, ':user_id' => $currentUserId]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch active organizations
    $orgStmt = $pdo->prepare("SELECT organization_id, organization_code, organization_name, full_name, color_theme FROM Organizations WHERE is_active = 1 ORDER BY organization_name");
    $orgStmt->execute();
    $organizations = $orgStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching data: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Entry Form - Assessment System</title>
    
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
                    <h1 class="text-2xl font-bold text-gray-800">Add New Project with Budget Entries</h1>
                    <p class="text-gray-600">Create a new project and add budget details</p>
                </div>

                <!-- Display Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                                <form action="../Controller/project_entry_process.php" method="POST">
                  <!-- Project Details Section -->
                  <div class="form-section">
                      <h2 class="text-xl font-semibold mb-4">Project Details</h2>

                      <div class="form-group">
                          <label for="project_name">Project Name:</label>
                          <input type="text" id="project_name" name="project_name" required>
                      </div>

                      <div class="form-group">
                          <label for="project_center">Project Center:</label>
                          <input type="text" id="project_center" name="project_center" required>
                      </div>

                      <div class="form-group">
                          <label for="organization_id">Operating Organization:</label>
                          <select id="organization_id" name="organization_id" required>
                              <option value="" disabled selected>Select Organization</option>
                              <?php foreach ($organizations as $org): ?>
                                  <option value="<?php echo $org['organization_id']; ?>" data-color="<?php echo $org['color_theme']; ?>">
                                      <?php echo htmlspecialchars($org['organization_name'] . ' (' . $org['organization_code'] . ') - ' . $org['full_name']); ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>

                      <!-- Project In-Charge Selection -->
                      <div class="form-group">
                          <label for="project_incharge">Primary Project In-Charge:</label>
                          <?php if ($currentUserRole === 'Councillor'): ?>
                              <!-- Councillor can select any Project In-Charge -->
                              <select id="project_incharge" name="project_incharge" required>
                                  <option value="" disabled selected>Select Primary In-Charge</option>
                                  <?php foreach ($users as $user): ?>
                                      <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']) . " (" . htmlspecialchars($user['community']) . ")"; ?></option>
                                  <?php endforeach; ?>
                              </select>
                          <?php else: ?>
                              <!-- Project In-Charge sees their own name and can select others from their community -->
                              <input type="hidden" name="project_incharge" value="<?php echo $currentUserId; ?>">
                              <p>Primary In-Charge: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                          <?php endif; ?>
                      </div>

                      <!-- Additional Project In-Charges -->
                      <div class="form-group">
                          <label>Additional Project In-Charges (Optional):</label>
                          <div id="additionalIncharges">
                              <!-- Additional in-charges will be added here dynamically -->
                          </div>
                          <button type="button" id="addInchargeBtn" class="btn btn-secondary mt-2">Add Another In-Charge</button>
                          <small class="text-gray-600">You can assign multiple Project In-Charges to manage this project together.</small>
                      </div>

                      <div class="form-group">
                          <label for="total_budget">Total Budget (Rs):</label>
                          <input type="number" id="total_budget" name="total_budget" step="0.01" required>
                      </div>

                      <div class="form-group">
                          <label for="start_date">Start Date:</label>
                          <input type="date" id="start_date" name="start_date">
                      </div>

                      <div class="form-group">
                          <label for="end_date">End Date:</label>
                          <input type="date" id="end_date" name="end_date">
                      </div>

                      <div class="form-group">
                          <label for="funding_source">Funding Source:</label>
                          <textarea id="funding_source" name="funding_source" placeholder="Enter details about the funding source (e.g., government grant, private donation, etc.)"></textarea>
                      </div>

                      <div class="form-group">
                          <label for="fund_type">Fund Type:</label>
                          <input type="text" id="fund_type" name="fund_type" placeholder="Enter fund type (e.g., Grant, Donation, Loan, etc.)">
                      </div>

                      <div class="form-group">
                          <label for="remarks">Remarks:</label>
                          <textarea id="remarks" name="remarks"></textarea>
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
                                  <!-- First Row (Always Available) -->
                                  <tr>
                                      <td><input type="text" name="budget_entries[0][particular]" placeholder="Enter Particular" required></td>
                                      <td><input type="number" name="budget_entries[0][rate]" step="0.01" placeholder="Enter Rate" required oninput="updateRowTotal(this)"></td>
                                      <td><input type="number" name="budget_entries[0][rate_quantity]" step="0.01" placeholder="Enter Quantity" required oninput="updateRowTotal(this)"></td>
                                      <td><input type="number" name="budget_entries[0][rate_duration]" placeholder="Enter Duration" required oninput="updateRowTotal(this)"></td>
                                      <td><input type="number" name="budget_entries[0][amount_this_phase]" readonly></td>
                                      <td><button type="button" class="btn btn-danger" onclick="removeBudgetEntryRow(this)">Delete</button></td>
                                  </tr>
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
                          Save Project and Budget Entries now
                      </button>
                  </div>
            </form>

            <!-- JavaScript for Dynamic Row Addition and Calculations -->
            <script>
                document.getElementById('addEntryButton').addEventListener('click', addBudgetEntryRow);
                document.getElementById('addInchargeBtn').addEventListener('click', addAdditionalIncharge);

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

                function addAdditionalIncharge() {
                    const container = document.getElementById('additionalIncharges');
                    const count = container.children.length;
                    
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-2 mb-2';
                    div.innerHTML = `
                        <select name="additional_incharges[${count}]" class="flex-1 px-3 py-2 border border-gray-300 rounded-md" required>
                            <option value="" disabled selected>Select Additional In-Charge</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']) . " (" . htmlspecialchars($user['community']) . ")"; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-danger" onclick="removeAdditionalIncharge(this)">Remove</button>
                    `;
                    
                    container.appendChild(div);
                }

                function removeAdditionalIncharge(button) {
                    button.closest('div').remove();
                }
            </script>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>
</body>
</html>
