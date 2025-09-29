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

$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];

try {
    // Build query based on user role
    if ($currentUserRole === 'Councillor') {
        // Councillor can see all activities
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                u.username as created_by_name,
                o.organization_name,
                p.project_name,
                COUNT(aa.attachment_id) as photo_count
            FROM Activities a
            LEFT JOIN ssmntUsers u ON a.created_by = u.id
            LEFT JOIN Organizations o ON a.organization_id = o.organization_id
            LEFT JOIN Projects p ON a.project_id = p.project_id
            LEFT JOIN ActivityAttachments aa ON a.activity_id = aa.activity_id
            GROUP BY a.activity_id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute();
    } else {
        // Project In-Charge can see only their activities
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                u.username as created_by_name,
                o.organization_name,
                p.project_name,
                COUNT(aa.attachment_id) as photo_count
            FROM Activities a
            LEFT JOIN ssmntUsers u ON a.created_by = u.id
            LEFT JOIN Organizations o ON a.organization_id = o.organization_id
            LEFT JOIN Projects p ON a.project_id = p.project_id
            LEFT JOIN ActivityAttachments aa ON a.activity_id = aa.activity_id
            WHERE a.created_by = :user_id
            GROUP BY a.activity_id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([':user_id' => $currentUserId]);
    }
    
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching activities: " . $e->getMessage();
    $activities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activities List - Assessment System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-draft { background-color: #fef3c7; color: #92400e; }
        .status-submitted { background-color: #dbeafe; color: #1e40af; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        
        .activity-card {
            transition: all 0.3s ease;
        }
        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
                <div class="page-header mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Activities</h1>
                            <p class="text-gray-600">Manage and track project activities</p>
                        </div>
                        <a href="activity_entry_form.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Add New Activity
                        </a>
                    </div>
                </div>

                <!-- Display Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error mb-4"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success mb-4"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Activities Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($activities)): ?>
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-400 text-6xl mb-4">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Activities Found</h3>
                            <p class="text-gray-500 mb-4">Start by adding your first activity report.</p>
                            <a href="activity_entry_form.php" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>Add New Activity
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-card bg-white rounded-lg shadow-md p-6">
                                
                                <!-- Activity Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                            <?php echo htmlspecialchars($activity['activity_title']); ?>
                                        </h3>
                                        <div class="flex items-center space-x-2 mb-2">
                                            <span class="status-badge status-<?php echo $activity['status']; ?>">
                                                <?php echo ucfirst($activity['status']); ?>
                                            </span>
                                            <?php if ($activity['photo_count'] > 0): ?>
                                                <span class="text-sm text-gray-500">
                                                    <i class="fas fa-camera mr-1"></i><?php echo $activity['photo_count']; ?> photos
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Details -->
                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-calendar-alt w-4 mr-2"></i>
                                        <span><?php echo date('M d, Y', strtotime($activity['activity_date'])); ?></span>
                                    </div>
                                    
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt w-4 mr-2"></i>
                                        <span><?php echo htmlspecialchars($activity['place']); ?></span>
                                    </div>
                                    
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-users w-4 mr-2"></i>
                                        <span><?php echo $activity['number_of_participants']; ?> participants</span>
                                    </div>
                                    
                                    <div class="flex items-center text-sm text-gray-600">
                                        <i class="fas fa-money-bill-wave w-4 mr-2"></i>
                                        <span><?php echo htmlspecialchars($activity['funding_source']); ?></span>
                                    </div>
                                    
                                    <?php if ($activity['is_collaboration']): ?>
                                        <div class="flex items-center text-sm text-blue-600">
                                            <i class="fas fa-handshake w-4 mr-2"></i>
                                            <span>Collaboration Activity</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Activity Outcomes Preview -->
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-1">Immediate Outcome:</h4>
                                    <p class="text-sm text-gray-600 line-clamp-2">
                                        <?php echo htmlspecialchars(substr($activity['immediate_outcome'], 0, 100)); ?>
                                        <?php if (strlen($activity['immediate_outcome']) > 100): ?>...<?php endif; ?>
                                    </p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <a href="activity_detail.php?id=<?php echo $activity['activity_id']; ?>" 
                                       class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                    
                                    <?php if ($activity['status'] === 'draft' || ($activity['status'] === 'rejected' && $activity['created_by'] == $currentUserId)): ?>
                                        <a href="activity_edit_form.php?id=<?php echo $activity['activity_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($activity['status'] === 'submitted' && $currentUserRole === 'Councillor'): ?>
                                        <a href="activity_approvals.php" 
                                           class="btn btn-sm btn-secondary">
                                            <i class="fas fa-check mr-1"></i>Review
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($activity['status'] === 'draft' && $activity['created_by'] == $currentUserId): ?>
                                        <button onclick="confirmSubmit(<?php echo $activity['activity_id']; ?>)" 
                                                class="btn btn-sm btn-success">
                                            <i class="fas fa-paper-plane mr-1"></i>Submit
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Activity Meta -->
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="flex justify-between items-center text-xs text-gray-500">
                                        <span>Created by <?php echo htmlspecialchars($activity['created_by_name']); ?></span>
                                        <span><?php echo date('M d, Y', strtotime($activity['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>

    <!-- JavaScript -->
    <script>
        function confirmSubmit(activityId) {
            if (confirm('Are you sure you want to submit this activity report? Once submitted, it cannot be edited without approval.')) {
                // Create a form to submit the activity
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../Controller/activity_submit_process.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'activity_id';
                input.value = activityId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
