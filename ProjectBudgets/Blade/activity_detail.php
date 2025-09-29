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

$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$activityId) {
    $_SESSION['error'] = "Invalid activity ID.";
    header("Location: activities_list.php");
    exit();
}

try {
    // Fetch activity details
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            u.username as created_by_name,
            u.full_name as created_by_full_name,
            o.organization_name,
            p.project_name
        FROM Activities a
        LEFT JOIN ssmntUsers u ON a.created_by = u.id
        LEFT JOIN Organizations o ON a.organization_id = o.organization_id
        LEFT JOIN Projects p ON a.project_id = p.project_id
        WHERE a.activity_id = :activity_id
    ");
    $stmt->execute([':activity_id' => $activityId]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        $_SESSION['error'] = "Activity not found.";
        header("Location: activities_list.php");
        exit();
    }
    
    // Check permissions
    $canView = ($currentUserRole === 'Councillor') || ($activity['created_by'] == $currentUserId);
    
    if (!$canView) {
        $_SESSION['error'] = "You don't have permission to view this activity.";
        header("Location: activities_list.php");
        exit();
    }
    
    // Fetch activity attachments
    $attachmentStmt = $pdo->prepare("
        SELECT * FROM ActivityAttachments 
        WHERE activity_id = :activity_id 
        ORDER BY uploaded_at ASC
    ");
    $attachmentStmt->execute([':activity_id' => $activityId]);
    $attachments = $attachmentStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching activity data: " . $e->getMessage();
    header("Location: activities_list.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Details - Assessment System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
    <style>
        .status-badge {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-draft { background-color: #fef3c7; color: #92400e; }
        .status-submitted { background-color: #dbeafe; color: #1e40af; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        
        .detail-card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .detail-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        
        .detail-value {
            color: #6b7280;
            margin-bottom: 16px;
        }
        
        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .photo-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .photo-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .photo-item:hover img {
            transform: scale(1.05);
        }
        
        .photo-caption {
            padding: 8px 12px;
            background: #f9fafb;
            font-size: 12px;
            color: #6b7280;
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
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Activity Details</h1>
                            <p class="text-gray-600">View complete activity information</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="activities_list.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left mr-2"></i>Back to List
                            </a>
                            <?php if (($activity['status'] === 'draft' && $activity['created_by'] == $currentUserId) || 
                                      ($activity['status'] === 'rejected' && $activity['created_by'] == $currentUserId)): ?>
                                <a href="activity_edit_form.php?id=<?php echo $activityId; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit mr-2"></i>Edit Activity
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Display Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error mb-4"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success mb-4"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Activity Status -->
                <div class="detail-card">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($activity['activity_title']); ?></h2>
                        <span class="status-badge status-<?php echo $activity['status']; ?>">
                            <?php echo ucfirst($activity['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="detail-card">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="detail-label">Activity Date</div>
                            <div class="detail-value">
                                <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                <?php echo date('F d, Y', strtotime($activity['activity_date'])); ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="detail-label">Place</div>
                            <div class="detail-value">
                                <i class="fas fa-map-marker-alt mr-2 text-green-500"></i>
                                <?php echo htmlspecialchars($activity['place']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="detail-label">Conducted For</div>
                            <div class="detail-value">
                                <i class="fas fa-users mr-2 text-purple-500"></i>
                                <?php echo htmlspecialchars($activity['conducted_for']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="detail-label">Number of Participants</div>
                            <div class="detail-value">
                                <i class="fas fa-user-friends mr-2 text-orange-500"></i>
                                <?php echo $activity['number_of_participants']; ?> participants
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Funding Information -->
                <div class="detail-card">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Funding Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="detail-label">Funding Source</div>
                            <div class="detail-value">
                                <i class="fas fa-money-bill-wave mr-2 text-green-500"></i>
                                <?php echo htmlspecialchars($activity['funding_source']); ?>
                            </div>
                        </div>
                        
                        <?php if ($activity['funding_source'] === 'Others' && $activity['funding_source_other']): ?>
                            <div>
                                <div class="detail-label">Funding Source Details</div>
                                <div class="detail-value">
                                    <?php echo htmlspecialchars($activity['funding_source_other']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($activity['organization_name']): ?>
                            <div>
                                <div class="detail-label">Organization</div>
                                <div class="detail-value">
                                    <i class="fas fa-building mr-2 text-blue-500"></i>
                                    <?php echo htmlspecialchars($activity['organization_name']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($activity['project_name']): ?>
                            <div>
                                <div class="detail-label">Project</div>
                                <div class="detail-value">
                                    <i class="fas fa-project-diagram mr-2 text-purple-500"></i>
                                    <?php echo htmlspecialchars($activity['project_name']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Collaboration Information -->
                <?php if ($activity['is_collaboration']): ?>
                <div class="detail-card">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Collaboration Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <div class="detail-label">Collaborator Organization</div>
                            <div class="detail-value">
                                <i class="fas fa-handshake mr-2 text-blue-500"></i>
                                <?php echo htmlspecialchars($activity['collaborator_organization']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="detail-label">Collaborator Name</div>
                            <div class="detail-value">
                                <i class="fas fa-user mr-2 text-green-500"></i>
                                <?php echo htmlspecialchars($activity['collaborator_name']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="detail-label">Collaborator Position</div>
                            <div class="detail-value">
                                <i class="fas fa-briefcase mr-2 text-purple-500"></i>
                                <?php echo htmlspecialchars($activity['collaborator_position']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Activity Outcomes -->
                <div class="detail-card">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Activity Outcomes</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <div class="detail-label">Immediate Outcome</div>
                            <div class="detail-value">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <?php echo nl2br(htmlspecialchars($activity['immediate_outcome'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="detail-label">Long Term Impact</div>
                            <div class="detail-value">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <?php echo nl2br(htmlspecialchars($activity['long_term_impact'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Photo Attachments -->
                <?php if (!empty($attachments)): ?>
                <div class="detail-card">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Photo Attachments</h3>
                    
                    <div class="photo-gallery">
                        <?php foreach ($attachments as $attachment): ?>
                            <div class="photo-item">
                                <img src="<?php echo $attachment['file_path']; ?>" 
                                     alt="<?php echo htmlspecialchars($attachment['original_name']); ?>"
                                     onclick="openImageModal('<?php echo $attachment['file_path']; ?>', '<?php echo htmlspecialchars($attachment['original_name']); ?>')">
                                <div class="photo-caption">
                                    <?php echo htmlspecialchars($attachment['original_name']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Activity Meta Information -->
                <div class="detail-card">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Activity Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="detail-label">Created By</div>
                            <div class="detail-value">
                                <i class="fas fa-user mr-2 text-blue-500"></i>
                                <?php echo htmlspecialchars($activity['created_by_full_name'] ?: $activity['created_by_name']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="detail-label">Created On</div>
                            <div class="detail-value">
                                <i class="fas fa-clock mr-2 text-gray-500"></i>
                                <?php echo date('F d, Y H:i', strtotime($activity['created_at'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($activity['submitted_at']): ?>
                            <div>
                                <div class="detail-label">Submitted On</div>
                                <div class="detail-value">
                                    <i class="fas fa-paper-plane mr-2 text-green-500"></i>
                                    <?php echo date('F d, Y H:i', strtotime($activity['submitted_at'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($activity['approved_at']): ?>
                            <div>
                                <div class="detail-label">Approved On</div>
                                <div class="detail-value">
                                    <i class="fas fa-check-circle mr-2 text-green-500"></i>
                                    <?php echo date('F d, Y H:i', strtotime($activity['approved_at'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="max-w-4xl max-h-full p-4">
            <div class="relative">
                <button onclick="closeImageModal()" class="absolute top-2 right-2 text-white text-2xl font-bold bg-black bg-opacity-50 rounded-full w-10 h-10 flex items-center justify-center hover:bg-opacity-75">
                    ×
                </button>
                <img id="modalImage" src="" alt="" class="max-w-full max-h-full rounded-lg">
                <div class="text-center mt-2">
                    <p id="modalCaption" class="text-white text-sm"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function openImageModal(imageSrc, caption) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('modalCaption').textContent = caption;
            document.getElementById('imageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>
