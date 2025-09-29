<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';
require_once '../../includes/role_based_sidebar.php';

// Ensure the user is logged in and is a Councillor
if (!isLoggedIn() || $_SESSION['role'] !== 'Councillor') {
    header("Location: ../../index.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];

try {
    // Fetch pending activity edit requests
    $stmt = $pdo->prepare("
        SELECT 
            aer.*,
            a.activity_title,
            a.activity_date,
            a.place,
            u.username as requested_by_name,
            u.full_name as requested_by_full_name
        FROM ActivityEditRequests aer
        LEFT JOIN Activities a ON aer.activity_id = a.activity_id
        LEFT JOIN ssmntUsers u ON aer.requested_by = u.id
        WHERE aer.status = 'pending'
        ORDER BY aer.requested_at DESC
    ");
    $stmt->execute();
    $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch recently processed requests
    $recentStmt = $pdo->prepare("
        SELECT 
            aer.*,
            a.activity_title,
            a.activity_date,
            a.place,
            u.username as requested_by_name,
            u.full_name as requested_by_full_name,
            approver.username as approved_by_name
        FROM ActivityEditRequests aer
        LEFT JOIN Activities a ON aer.activity_id = a.activity_id
        LEFT JOIN ssmntUsers u ON aer.requested_by = u.id
        LEFT JOIN ssmntUsers approver ON aer.approved_by = approver.id
        WHERE aer.status IN ('approved', 'rejected')
        ORDER BY aer.updated_at DESC
        LIMIT 10
    ");
    $recentStmt->execute();
    $recentRequests = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching approval requests: " . $e->getMessage();
    $pendingRequests = [];
    $recentRequests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Edit Approvals - Assessment System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
    <style>
        .change-item {
            padding: 8px 12px;
            margin: 4px 0;
            border-radius: 6px;
            border-left: 4px solid #e5e7eb;
        }
        .change-added {
            background-color: #d1fae5;
            border-left-color: #10b981;
        }
        .change-removed {
            background-color: #fee2e2;
            border-left-color: #ef4444;
        }
        .change-modified {
            background-color: #dbeafe;
            border-left-color: #3b82f6;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
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
                
                <!-- Page Header -->
                <div class="page-header mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Activity Edit Approvals</h1>
                    <p class="text-gray-600">Review and approve activity edit requests</p>
                </div>

                <!-- Display Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error mb-4"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success mb-4"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Pending Requests Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">Pending Approval Requests</h2>
                    
                    <?php if (empty($pendingRequests)): ?>
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <div class="text-gray-400 text-4xl mb-2">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-600 mb-2">No Pending Requests</h3>
                            <p class="text-gray-500">All activity edit requests have been processed.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-6">
                            <?php foreach ($pendingRequests as $request): ?>
                                <div class="bg-white rounded-lg shadow-md p-6">
                                    
                                    <!-- Request Header -->
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                <?php echo htmlspecialchars($request['activity_title']); ?>
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                Requested by <?php echo htmlspecialchars($request['requested_by_full_name'] ?: $request['requested_by_name']); ?>
                                                on <?php echo date('M d, Y H:i', strtotime($request['requested_at'])); ?>
                                            </p>
                                        </div>
                                        <span class="status-badge status-pending">Pending</span>
                                    </div>

                                    <!-- Activity Context -->
                                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                        <h4 class="font-semibold text-gray-700 mb-2">Activity Context</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <span class="font-medium">Date:</span>
                                                <?php echo date('M d, Y', strtotime($request['activity_date'])); ?>
                                            </div>
                                            <div>
                                                <span class="font-medium">Place:</span>
                                                <?php echo htmlspecialchars($request['place']); ?>
                                            </div>
                                            <div>
                                                <span class="font-medium">Request Type:</span>
                                                <?php echo ucfirst($request['request_type']); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Changes Comparison -->
                                    <div class="mb-6">
                                        <h4 class="font-semibold text-gray-700 mb-3">Requested Changes</h4>
                                        
                                        <?php
                                        $originalData = json_decode($request['original_data'], true);
                                        $requestedChanges = json_decode($request['requested_changes'], true);
                                        
                                        $fields = [
                                            'activity_title' => 'Activity Title',
                                            'funding_source' => 'Funding Source',
                                            'funding_source_other' => 'Funding Source (Other)',
                                            'organization_id' => 'Organization',
                                            'project_id' => 'Project',
                                            'activity_date' => 'Activity Date',
                                            'place' => 'Place',
                                            'conducted_for' => 'Conducted For',
                                            'number_of_participants' => 'Number of Participants',
                                            'is_collaboration' => 'Is Collaboration',
                                            'collaborator_organization' => 'Collaborator Organization',
                                            'collaborator_name' => 'Collaborator Name',
                                            'collaborator_position' => 'Collaborator Position',
                                            'immediate_outcome' => 'Immediate Outcome',
                                            'long_term_impact' => 'Long Term Impact'
                                        ];
                                        
                                        $hasChanges = false;
                                        foreach ($fields as $field => $label) {
                                            $original = $originalData[$field] ?? '';
                                            $requested = $requestedChanges[$field] ?? '';
                                            
                                            if ($original != $requested) {
                                                $hasChanges = true;
                                                echo '<div class="change-item change-modified">';
                                                echo '<div class="font-medium text-gray-700">' . $label . '</div>';
                                                echo '<div class="text-sm text-gray-600 mt-1">';
                                                echo '<div class="change-removed mb-1">';
                                                echo '<span class="font-medium">Current:</span> ' . htmlspecialchars($original ?: 'Not set');
                                                echo '</div>';
                                                echo '<div class="change-added">';
                                                echo '<span class="font-medium">Requested:</span> ' . htmlspecialchars($requested ?: 'Not set');
                                                echo '</div>';
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                        }
                                        
                                        if (!$hasChanges) {
                                            echo '<div class="text-gray-500 text-sm">No changes detected.</div>';
                                        }
                                        ?>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex gap-4">
                                        <button onclick="approveRequest(<?php echo $request['request_id']; ?>)" 
                                                class="btn btn-success">
                                            <i class="fas fa-check mr-2"></i>Approve
                                        </button>
                                        <button onclick="rejectRequest(<?php echo $request['request_id']; ?>)" 
                                                class="btn btn-danger">
                                            <i class="fas fa-times mr-2"></i>Reject
                                        </button>
                                        <a href="activity_detail.php?id=<?php echo $request['activity_id']; ?>" 
                                           class="btn btn-outline">
                                            <i class="fas fa-eye mr-2"></i>View Activity
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Requests Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">Recently Processed Requests</h2>
                    
                    <?php if (empty($recentRequests)): ?>
                        <div class="text-center py-8 bg-gray-50 rounded-lg">
                            <div class="text-gray-400 text-4xl mb-2">
                                <i class="fas fa-history"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-600 mb-2">No Recent Activity</h3>
                            <p class="text-gray-500">No activity edit requests have been processed yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested By</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($recentRequests as $request): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($request['activity_title']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($request['requested_by_full_name'] ?: $request['requested_by_name']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($request['approved_by_name'] ?: 'System'); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo date('M d, Y H:i', strtotime($request['updated_at'])); ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>

    <!-- Approval/Rejection Modal -->
    <div id="approvalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">Approve Request</h3>
                <form id="approvalForm" method="POST" action="../Controller/activity_edit_approval_process.php">
                    <input type="hidden" name="request_id" id="modalRequestId">
                    <input type="hidden" name="action" id="modalAction">
                    
                    <div id="rejectionReason" class="mb-4" style="display: none;">
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Rejection Reason (Required)
                        </label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                  placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function approveRequest(requestId) {
            document.getElementById('modalTitle').textContent = 'Approve Request';
            document.getElementById('modalRequestId').value = requestId;
            document.getElementById('modalAction').value = 'approve';
            document.getElementById('rejectionReason').style.display = 'none';
            document.getElementById('modalSubmitBtn').textContent = 'Approve';
            document.getElementById('modalSubmitBtn').className = 'btn btn-success';
            document.getElementById('approvalModal').classList.remove('hidden');
        }

        function rejectRequest(requestId) {
            document.getElementById('modalTitle').textContent = 'Reject Request';
            document.getElementById('modalRequestId').value = requestId;
            document.getElementById('modalAction').value = 'reject';
            document.getElementById('rejectionReason').style.display = 'block';
            document.getElementById('modalSubmitBtn').textContent = 'Reject';
            document.getElementById('modalSubmitBtn').className = 'btn btn-danger';
            document.getElementById('approvalModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('approvalModal').classList.add('hidden');
            document.getElementById('rejection_reason').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('approvalModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Form validation for rejection
        document.getElementById('approvalForm').addEventListener('submit', function(e) {
            const action = document.getElementById('modalAction').value;
            const reason = document.getElementById('rejection_reason').value;
            
            if (action === 'reject' && !reason.trim()) {
                e.preventDefault();
                alert('Please provide a reason for rejection.');
                document.getElementById('rejection_reason').focus();
            }
        });
    </script>
</body>
</html>
