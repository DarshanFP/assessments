<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';
require_once '../../includes/role_based_sidebar.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "User not logged in.";
    header("Location: ../../index.php");
    exit();
}

// Ensure only 'Councillor' users can access this page
checkRole('Councillor');

try {
    // Fetch all organizations
    $stmt = $pdo->prepare("
        SELECT organization_id, organization_code, organization_name, full_name, description, is_active, color_theme, created_at
        FROM Organizations
        ORDER BY organization_name
    ");
    $stmt->execute();
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get organization statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            o.organization_id,
            o.organization_name,
            COUNT(p.project_id) as project_count,
            IFNULL(SUM(p.total_budget), 0) as total_budget,
            IFNULL(SUM(ee.amount_expensed), 0) as total_expensed
        FROM Organizations o
        LEFT JOIN Projects p ON o.organization_id = p.organization_id
        LEFT JOIN ExpenseEntries ee ON p.project_id = ee.project_id
        WHERE o.is_active = 1
        GROUP BY o.organization_id, o.organization_name
        ORDER BY o.organization_name
    ");
    $statsStmt->execute();
    $organizationStats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error fetching organization data: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Management - Assessment System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
    <style>
        .enhanced-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            margin: 20px 0;
        }
        
        .enhanced-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .enhanced-table thead {
            background: #4a6fd1;
            color: white;
        }
        
        .enhanced-table th {
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        
        .enhanced-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .enhanced-table tbody tr:hover {
            background-color: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .enhanced-table td {
            padding: 16px 12px;
            border: none;
            font-size: 14px;
            color: #374151;
        }
        
        .enhanced-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .page-header {
            background: #4a6fd1;
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #4a6fd1;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #4a6fd1;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .action-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #4a6fd1;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            cursor: pointer;
        }
        
        .action-btn:hover {
            background: #3b5998;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(74, 111, 209, 0.3);
        }
        
        .action-btn.edit {
            background: #059669;
        }
        
        .action-btn.edit:hover {
            background: #047857;
        }
        
        .action-btn.delete {
            background: #dc2626;
        }
        
        .action-btn.delete:hover {
            background: #b91c1c;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .color-theme-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 8px;
            border: 2px solid #e5e7eb;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
    </style>
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
                
                <!-- Enhanced Page Header -->
                <div class="page-header">
                    <h1>Organization Management</h1>
                </div>

                <!-- Display Error or Success Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($organizations); ?></div>
                        <div class="stat-label">Total Organizations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($organizations, function($org) { return $org['is_active']; })); ?></div>
                        <div class="stat-label">Active Organizations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo array_sum(array_column($organizationStats, 'project_count')); ?></div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">₹<?php echo number_format(array_sum(array_column($organizationStats, 'total_budget')), 2); ?></div>
                        <div class="stat-label">Total Budget</div>
                    </div>
                </div>

                <!-- Add Organization Button -->
                <div class="mb-4">
                    <button onclick="openAddModal()" class="action-btn">Add New Organization</button>
                </div>

                <!-- Organizations Table -->
                <div class="enhanced-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Organization Code</th>
                                <th>Organization Name</th>
                                <th>Full Name</th>
                                <th>Color Theme</th>
                                <th>Status</th>
                                <th>Projects</th>
                                <th>Total Budget</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($organizations as $org): ?>
                                <?php 
                                $stats = array_filter($organizationStats, function($stat) use ($org) {
                                    return $stat['organization_id'] == $org['organization_id'];
                                });
                                $orgStats = !empty($stats) ? array_values($stats)[0] : ['project_count' => 0, 'total_budget' => 0, 'total_expensed' => 0];
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($org['organization_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($org['organization_name']); ?></td>
                                    <td><?php echo htmlspecialchars($org['full_name']); ?></td>
                                    <td>
                                        <div class="flex items-center">
                                            <div class="color-theme-badge" style="background-color: <?php echo $org['color_theme'] === 'blue' ? '#3b82f6' : ($org['color_theme'] === 'pink' ? '#ec4899' : '#10b981'); ?>"></div>
                                            <?php echo ucfirst($org['color_theme']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $org['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $org['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $orgStats['project_count']; ?></td>
                                    <td>₹<?php echo number_format($orgStats['total_budget'], 2); ?></td>
                                    <td>
                                        <div class="flex gap-2">
                                            <button onclick="editOrganization(<?php echo $org['organization_id']; ?>)" class="action-btn edit">Edit</button>
                                            <?php if ($orgStats['project_count'] == 0): ?>
                                                <button onclick="deleteOrganization(<?php echo $org['organization_id']; ?>)" class="action-btn delete">Delete</button>
                                            <?php else: ?>
                                                <button onclick="toggleOrganizationStatus(<?php echo $org['organization_id']; ?>, <?php echo $org['is_active'] ? 'false' : 'true'; ?>)" class="action-btn">
                                                    <?php echo $org['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>

    <!-- Add/Edit Organization Modal -->
    <div id="organizationModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Organization</h2>
            <form id="organizationForm" action="../Controller/organization_management_process.php" method="POST">
                <input type="hidden" id="organizationId" name="organization_id" value="">
                <input type="hidden" id="action" name="action" value="add">
                
                <div class="form-group">
                    <label for="organization_code">Organization Code:</label>
                    <input type="text" id="organization_code" name="organization_code" required maxlength="10">
                </div>
                
                <div class="form-group">
                    <label for="organization_name">Organization Name:</label>
                    <input type="text" id="organization_name" name="organization_name" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="color_theme">Color Theme:</label>
                    <select id="color_theme" name="color_theme" required>
                        <option value="blue">Blue</option>
                        <option value="pink">Pink</option>
                        <option value="green">Green</option>
                        <option value="purple">Purple</option>
                        <option value="orange">Orange</option>
                        <option value="red">Red</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                        Active Organization
                    </label>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="action-btn">Save Organization</button>
                    <button type="button" onclick="closeModal()" class="action-btn" style="background: #6b7280;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Organization';
            document.getElementById('action').value = 'add';
            document.getElementById('organizationId').value = '';
            document.getElementById('organizationForm').reset();
            document.getElementById('is_active').checked = true;
            document.getElementById('organizationModal').style.display = 'block';
        }

        function editOrganization(id) {
            // This would need to be implemented with AJAX to fetch organization data
            // For now, we'll redirect to an edit page
            window.location.href = 'edit_organization.php?id=' + id;
        }

        function deleteOrganization(id) {
            if (confirm('Are you sure you want to delete this organization? This action cannot be undone.')) {
                window.location.href = '../Controller/organization_management_process.php?action=delete&id=' + id;
            }
        }

        function toggleOrganizationStatus(id, newStatus) {
            const action = newStatus === 'true' ? 'activate' : 'deactivate';
            if (confirm('Are you sure you want to ' + action + ' this organization?')) {
                window.location.href = '../Controller/organization_management_process.php?action=toggle&id=' + id + '&status=' + newStatus;
            }
        }

        function closeModal() {
            document.getElementById('organizationModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('organizationModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
