<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/path_resolver.php';
require_once '../../includes/role_based_sidebar.php';

// Ensure the user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit();
}

// Ensure only 'Councillor' users can access this page
checkRole('Councillor');

try {
    // Fetch all pending edit requests
    $stmt = $pdo->prepare("
        SELECT 
            per.*,
            p.project_name,
            o.organization_name,
            u.full_name as requested_by_name,
            u.community as requested_by_community
        FROM ProjectEditRequests per
        LEFT JOIN Projects p ON per.project_id = p.project_id
        LEFT JOIN Organizations o ON p.organization_id = o.organization_id
        LEFT JOIN ssmntUsers u ON per.requested_by = u.id
        WHERE per.status = 'pending'
        ORDER BY per.requested_at DESC
    ");
    $stmt->execute();
    $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch recent approved/rejected requests for reference
    $recentStmt = $pdo->prepare("
        SELECT 
            per.*,
            p.project_name,
            o.organization_name,
            u.full_name as requested_by_name,
            approver.full_name as approved_by_name
        FROM ProjectEditRequests per
        LEFT JOIN Projects p ON per.project_id = p.project_id
        LEFT JOIN Organizations o ON p.organization_id = o.organization_id
        LEFT JOIN ssmntUsers u ON per.requested_by = u.id
        LEFT JOIN ssmntUsers approver ON per.approved_by = approver.id
        WHERE per.status IN ('approved', 'rejected')
        ORDER BY per.updated_at DESC
        LIMIT 10
    ");
    $recentStmt->execute();
    $recentRequests = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching edit requests: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Edit Approvals - Assessment System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
    <style>
        .request-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #f59e0b;
        }
        
        .approved-card {
            border-left-color: #10b981;
        }
        
        .rejected-card {
            border-left-color: #ef4444;
        }
        
        .change-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin: 8px 0;
        }
        
        .original-value {
            color: #ef4444;
            text-decoration: line-through;
        }
        
        .new-value {
            color: #10b981;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
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
                
                <!-- Page Header -->
                <div class="page-header mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">Project Edit Approvals</h1>
                    <p class="text-gray-600">Review and approve/reject project edit requests from Project In-Charges</p>
                </div>

                <!-- Display Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Pending Requests -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">Pending Edit Requests (<?php echo count($pendingRequests); ?>)</h2>
                    
                    <?php if (!empty($pendingRequests)): ?>
                        <?php foreach ($pendingRequests as $request): ?>
                            <div class="request-card">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($request['project_name']); ?></h3>
                                        <p class="text-sm text-gray-600">
                                            Organization: <?php echo htmlspecialchars($request['organization_name']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Requested by: <?php echo htmlspecialchars($request['requested_by_name']); ?> 
                                            (<?php echo htmlspecialchars($request['requested_by_community']); ?>)
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Requested on: <?php echo date('d M Y, H:i', strtotime($request['requested_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="status-badge status-pending">Pending</span>
                                </div>

                                <!-- Requested Changes -->
                                <div class="mb-4">
                                    <h4 class="font-semibold mb-2">Requested Changes:</h4>
                                    <?php
                                    $originalData = json_decode($request['original_data'], true);
                                    $requestedChanges = json_decode($request['requested_changes'], true);
                                    $originalProject = $originalData['project'];
                                    ?>
                                    
                                    <!-- Project Details Changes -->
                                    <?php if ($originalProject['project_name'] !== $requestedChanges['project_name']): ?>
                                        <div class="change-item">
                                            <strong>Project Name:</strong><br>
                                            <span class="original-value"><?php echo htmlspecialchars($originalProject['project_name']); ?></span><br>
                                            <span class="new-value"><?php echo htmlspecialchars($requestedChanges['project_name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($originalProject['project_center'] !== $requestedChanges['project_center']): ?>
                                        <div class="change-item">
                                            <strong>Project Center:</strong><br>
                                            <span class="original-value"><?php echo htmlspecialchars($originalProject['project_center']); ?></span><br>
                                            <span class="new-value"><?php echo htmlspecialchars($requestedChanges['project_center']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($originalProject['total_budget'] != $requestedChanges['total_budget']): ?>
                                        <div class="change-item">
                                            <strong>Total Budget:</strong><br>
                                            <span class="original-value">₹<?php echo number_format($originalProject['total_budget'], 2); ?></span><br>
                                            <span class="new-value">₹<?php echo number_format($requestedChanges['total_budget'], 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($originalProject['funding_source'] !== $requestedChanges['funding_source']): ?>
                                        <div class="change-item">
                                            <strong>Funding Source:</strong><br>
                                            <span class="original-value"><?php echo htmlspecialchars($originalProject['funding_source']); ?></span><br>
                                            <span class="new-value"><?php echo htmlspecialchars($requestedChanges['funding_source']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($originalProject['fund_type'] !== $requestedChanges['fund_type']): ?>
                                        <div class="change-item">
                                            <strong>Fund Type:</strong><br>
                                            <span class="original-value"><?php echo htmlspecialchars($originalProject['fund_type']); ?></span><br>
                                            <span class="new-value"><?php echo htmlspecialchars($requestedChanges['fund_type']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-4">
                                    <form action="../Controller/project_edit_approval_process.php" method="POST" class="inline">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to approve these changes?')">
                                            Approve Changes
                                        </button>
                                    </form>
                                    
                                    <button onclick="showRejectionForm(<?php echo $request['request_id']; ?>)" class="btn btn-danger">
                                        Reject Request
                                    </button>
                                    
                                    <a href="project_edit_form.php?project_id=<?php echo $request['project_id']; ?>" class="btn btn-secondary">
                                        View Project
                                    </a>
                                </div>

                                <!-- Rejection Form (Hidden) -->
                                <div id="rejectionForm_<?php echo $request['request_id']; ?>" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded">
                                    <form action="../Controller/project_edit_approval_process.php" method="POST">
                                        <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <label for="rejection_reason_<?php echo $request['request_id']; ?>" class="block text-sm font-medium text-gray-700 mb-2">
                                            Reason for rejection:
                                        </label>
                                        <textarea 
                                            id="rejection_reason_<?php echo $request['request_id']; ?>" 
                                            name="rejection_reason" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md" 
                                            rows="3" 
                                            required
                                            placeholder="Please provide a reason for rejecting this request..."
                                        ></textarea>
                                        <div class="flex gap-2 mt-3">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this request?')">
                                                Reject Request
                                            </button>
                                            <button type="button" onclick="hideRejectionForm(<?php echo $request['request_id']; ?>)" class="btn btn-secondary">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>No pending edit requests found.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Requests -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">Recent Requests</h2>
                    
                    <?php if (!empty($recentRequests)): ?>
                        <?php foreach ($recentRequests as $request): ?>
                            <div class="request-card <?php echo $request['status'] === 'approved' ? 'approved-card' : 'rejected-card'; ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($request['project_name']); ?></h3>
                                        <p class="text-sm text-gray-600">
                                            Requested by: <?php echo htmlspecialchars($request['requested_by_name']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo $request['status'] === 'approved' ? 'Approved' : 'Rejected'; ?> by: <?php echo htmlspecialchars($request['approved_by_name']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('d M Y, H:i', strtotime($request['updated_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>
                                
                                <?php if ($request['status'] === 'rejected' && !empty($request['rejection_reason'])): ?>
                                    <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded">
                                        <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($request['rejection_reason']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>No recent requests found.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>

    <!-- JavaScript -->
    <script>
        function showRejectionForm(requestId) {
            document.getElementById('rejectionForm_' + requestId).classList.remove('hidden');
        }
        
        function hideRejectionForm(requestId) {
            document.getElementById('rejectionForm_' + requestId).classList.add('hidden');
        }
    </script>
</body>
</html>
