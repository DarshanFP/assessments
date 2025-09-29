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
$activityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$activityId) {
    $_SESSION['error'] = "Invalid activity ID.";
    header("Location: activities_list.php");
    exit();
}

try {
    // Fetch activity details
    $stmt = $pdo->prepare("
        SELECT a.*, u.username as created_by_name
        FROM Activities a
        LEFT JOIN ssmntUsers u ON a.created_by = u.id
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
    $canEditDirectly = ($currentUserRole === 'Councillor') || 
                      ($activity['created_by'] == $currentUserId && $activity['status'] === 'draft');
    $canRequestEdit = ($activity['created_by'] == $currentUserId && $activity['status'] === 'rejected');
    
    if (!$canEditDirectly && !$canRequestEdit) {
        $_SESSION['error'] = "You don't have permission to edit this activity.";
        header("Location: activities_list.php");
        exit();
    }
    
    // Fetch existing attachments
    $attachmentStmt = $pdo->prepare("
        SELECT * FROM ActivityAttachments 
        WHERE activity_id = :activity_id 
        ORDER BY uploaded_at ASC
    ");
    $attachmentStmt->execute([':activity_id' => $activityId]);
    $attachments = $attachmentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch active organizations
    $orgStmt = $pdo->prepare("SELECT organization_id, organization_code, organization_name, full_name, color_theme FROM Organizations WHERE is_active = 1 ORDER BY organization_name");
    $orgStmt->execute();
    $organizations = $orgStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch projects for the current user
    if ($currentUserRole === 'Councillor') {
        $projectStmt = $pdo->prepare("
            SELECT p.project_id, p.project_name, o.organization_name 
            FROM Projects p
            LEFT JOIN Organizations o ON p.organization_id = o.organization_id
            WHERE p.is_active = 1
            ORDER BY o.organization_name, p.project_name
        ");
        $projectStmt->execute();
    } else {
        $projectStmt = $pdo->prepare("
            SELECT p.project_id, p.project_name, o.organization_name 
            FROM Projects p
            LEFT JOIN Organizations o ON p.organization_id = o.organization_id
            INNER JOIN ProjectAssignments pa ON p.project_id = pa.project_id
            WHERE p.is_active = 1 AND pa.project_incharge_id = :user_id AND pa.is_active = 1
            ORDER BY o.organization_name, p.project_name
        ");
        $projectStmt->execute([':user_id' => $currentUserId]);
    }
    $projects = $projectStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Edit Activity - Assessment System</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.0.3/tailwind.min.css">
    <link rel="stylesheet" href="../../unified.css">
    <style>
        .photo-preview {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        .photo-item {
            position: relative;
            display: inline-block;
            margin: 10px;
        }
        .remove-photo {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 12px;
        }
        .funding-source-group {
            display: none;
        }
        .funding-source-group.active {
            display: block;
        }
        .existing-photo {
            border: 2px solid #10b981;
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
                    <h1 class="text-2xl font-bold text-gray-800">Edit Activity</h1>
                    <p class="text-gray-600">Update activity information and outcomes</p>
                </div>

                <!-- Display Session Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <!-- Approval Notice for Project In-Charges -->
                <?php if ($currentUserRole === 'Project In-Charge' && $activity['status'] === 'submitted'): ?>
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Approval Required:</strong> Your changes will require Councillor approval before being applied.
                    </div>
                <?php endif; ?>

                <form action="<?php echo $canEditDirectly ? '../Controller/activity_edit_process.php' : '../Controller/activity_edit_request_process.php'; ?>" 
                      method="POST" enctype="multipart/form-data" id="activityForm">
                    
                    <input type="hidden" name="activity_id" value="<?php echo $activityId; ?>">
                    
                    <!-- Activity Details Section -->
                    <div class="form-section">
                        <h2 class="text-xl font-semibold mb-4">Activity Details</h2>

                        <div class="form-group">
                            <label for="activity_title">Activity Title:</label>
                            <input type="text" id="activity_title" name="activity_title" 
                                   value="<?php echo htmlspecialchars($activity['activity_title']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="funding_source">Funding Source:</label>
                            <select id="funding_source" name="funding_source" required onchange="toggleFundingSourceFields()">
                                <option value="" disabled>Select Funding Source</option>
                                <option value="Project" <?php echo $activity['funding_source'] === 'Project' ? 'selected' : ''; ?>>Project</option>
                                <option value="Donations" <?php echo $activity['funding_source'] === 'Donations' ? 'selected' : ''; ?>>Donations</option>
                                <option value="Support from collaborator" <?php echo $activity['funding_source'] === 'Support from collaborator' ? 'selected' : ''; ?>>Support from collaborator</option>
                                <option value="Center funded" <?php echo $activity['funding_source'] === 'Center funded' ? 'selected' : ''; ?>>Center funded</option>
                                <option value="Congregation funded" <?php echo $activity['funding_source'] === 'Congregation funded' ? 'selected' : ''; ?>>Congregation funded</option>
                                <option value="Organisation funded" <?php echo $activity['funding_source'] === 'Organisation funded' ? 'selected' : ''; ?>>Organisation funded</option>
                                <option value="Others" <?php echo $activity['funding_source'] === 'Others' ? 'selected' : ''; ?>>Others</option>
                            </select>
                        </div>

                        <!-- Organisation funded dropdown -->
                        <div class="form-group funding-source-group" id="organization_funded_group">
                            <label for="organization_id">Select Organisation:</label>
                            <select id="organization_id" name="organization_id">
                                <option value="" disabled>Select Organisation</option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?php echo $org['organization_id']; ?>" 
                                            <?php echo $activity['organization_id'] == $org['organization_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($org['organization_name'] . ' (' . $org['organization_code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Project funded dropdown -->
                        <div class="form-group funding-source-group" id="project_funded_group">
                            <label for="project_id">Select Project:</label>
                            <select id="project_id" name="project_id">
                                <option value="" disabled>Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?php echo $project['project_id']; ?>" 
                                            <?php echo $activity['project_id'] == $project['project_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($project['project_name'] . ' (' . $project['organization_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Others specify -->
                        <div class="form-group funding-source-group" id="others_funded_group">
                            <label for="funding_source_other">Please Specify:</label>
                            <input type="text" id="funding_source_other" name="funding_source_other" 
                                   value="<?php echo htmlspecialchars($activity['funding_source_other']); ?>" 
                                   placeholder="Specify funding source">
                        </div>

                        <div class="form-group">
                            <label for="activity_date">Date of Activity:</label>
                            <input type="date" id="activity_date" name="activity_date" 
                                   value="<?php echo $activity['activity_date']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="place">Place of Activity:</label>
                            <input type="text" id="place" name="place" 
                                   value="<?php echo htmlspecialchars($activity['place']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="conducted_for">For Whom It Was Conducted/Organised:</label>
                            <input type="text" id="conducted_for" name="conducted_for" 
                                   value="<?php echo htmlspecialchars($activity['conducted_for']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="number_of_participants">Number of Participants:</label>
                            <input type="number" id="number_of_participants" name="number_of_participants" 
                                   value="<?php echo $activity['number_of_participants']; ?>" min="1" required>
                        </div>
                    </div>

                    <!-- Collaboration Details Section -->
                    <div class="form-section">
                        <h2 class="text-xl font-semibold mb-4">Collaboration Details</h2>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="is_collaboration" name="is_collaboration" value="1" 
                                       <?php echo $activity['is_collaboration'] ? 'checked' : ''; ?> 
                                       onchange="toggleCollaborationFields()">
                                Was it a collaboration activity?
                            </label>
                        </div>

                        <div id="collaboration_fields" style="<?php echo $activity['is_collaboration'] ? 'display: block;' : 'display: none;'; ?>">
                            <div class="form-group">
                                <label for="collaborator_organization">Collaborator's Organisation Name:</label>
                                <input type="text" id="collaborator_organization" name="collaborator_organization" 
                                       value="<?php echo htmlspecialchars($activity['collaborator_organization']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="collaborator_name">Collaborator's Name:</label>
                                <input type="text" id="collaborator_name" name="collaborator_name" 
                                       value="<?php echo htmlspecialchars($activity['collaborator_name']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="collaborator_position">Collaborator's Position in Organisation:</label>
                                <input type="text" id="collaborator_position" name="collaborator_position" 
                                       value="<?php echo htmlspecialchars($activity['collaborator_position']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Outcomes Section -->
                    <div class="form-section">
                        <h2 class="text-xl font-semibold mb-4">Activity Outcomes</h2>

                        <div class="form-group">
                            <label for="immediate_outcome">Immediate Outcome of the Activity:</label>
                            <textarea id="immediate_outcome" name="immediate_outcome" rows="4" required><?php echo htmlspecialchars($activity['immediate_outcome']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="long_term_impact">Long Term Impact of the Activity:</label>
                            <textarea id="long_term_impact" name="long_term_impact" rows="4" required><?php echo htmlspecialchars($activity['long_term_impact']); ?></textarea>
                        </div>
                    </div>

                    <!-- Existing Photos Section -->
                    <?php if (!empty($attachments)): ?>
                    <div class="form-section">
                        <h2 class="text-xl font-semibold mb-4">Existing Photos</h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach ($attachments as $attachment): ?>
                                <div class="photo-item">
                                    <img src="<?php echo $attachment['file_path']; ?>" 
                                         class="photo-preview existing-photo" 
                                         alt="<?php echo htmlspecialchars($attachment['original_name']); ?>">
                                    <button type="button" class="remove-photo" 
                                            onclick="removeExistingPhoto(<?php echo $attachment['attachment_id']; ?>)">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Photo Attachments Section -->
                    <div class="form-section">
                        <h2 class="text-xl font-semibold mb-4">Add More Photos (Optional)</h2>

                        <div class="form-group">
                            <label for="activity_photos">Upload Additional Photos:</label>
                            <input type="file" id="activity_photos" name="activity_photos[]" multiple accept="image/*" onchange="previewPhotos(this)">
                            <small class="text-gray-600">Select additional photos (JPG, PNG, GIF formats)</small>
                        </div>

                        <div id="photo_previews" class="mt-4">
                            <!-- Photo previews will be displayed here -->
                        </div>
                    </div>
                    
                    <!-- Action Buttons Section -->
                    <div class="form-section">
                        <div class="flex gap-4">
                            <?php if ($canEditDirectly): ?>
                                <button type="submit" name="action" value="save_draft" class="btn btn-secondary">
                                    Save as Draft
                                </button>
                                <button type="submit" name="action" value="submit" class="btn btn-primary">
                                    Update Activity
                                </button>
                            <?php else: ?>
                                <button type="submit" name="action" value="request_edit" class="btn btn-primary">
                                    Submit Edit Request
                                </button>
                            <?php endif; ?>
                        </div>
                        <small class="text-gray-600 mt-2 block">
                            <?php if ($canEditDirectly): ?>
                                <strong>Save as Draft:</strong> Save your progress and continue editing later<br>
                                <strong>Update Activity:</strong> Apply changes immediately
                            <?php else: ?>
                                <strong>Submit Edit Request:</strong> Request changes that require Councillor approval
                            <?php endif; ?>
                        </small>
                    </div>
                </form>
                
            </div>
        </div>
        
        <!-- Footer -->
        <?php include '../../footer.php'; ?>
        
    </div>

    <!-- JavaScript -->
    <script>
        function toggleFundingSourceFields() {
            const fundingSource = document.getElementById('funding_source').value;
            
            // Hide all funding source groups
            document.querySelectorAll('.funding-source-group').forEach(group => {
                group.classList.remove('active');
            });
            
            // Show relevant group
            if (fundingSource === 'Organisation funded') {
                document.getElementById('organization_funded_group').classList.add('active');
            } else if (fundingSource === 'Project') {
                document.getElementById('project_funded_group').classList.add('active');
            } else if (fundingSource === 'Others') {
                document.getElementById('others_funded_group').classList.add('active');
            }
        }

        function toggleCollaborationFields() {
            const isCollaboration = document.getElementById('is_collaboration').checked;
            const collaborationFields = document.getElementById('collaboration_fields');
            
            if (isCollaboration) {
                collaborationFields.style.display = 'block';
            } else {
                collaborationFields.style.display = 'none';
            }
        }

        function previewPhotos(input) {
            const previewContainer = document.getElementById('photo_previews');
            previewContainer.innerHTML = '';
            
            if (input.files && input.files.length > 0) {
                const maxPhotos = 4;
                const filesToShow = Math.min(input.files.length, maxPhotos);
                
                for (let i = 0; i < filesToShow; i++) {
                    const file = input.files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const photoItem = document.createElement('div');
                        photoItem.className = 'photo-item';
                        photoItem.innerHTML = `
                            <img src="${e.target.result}" class="photo-preview" alt="Preview">
                            <button type="button" class="remove-photo" onclick="removePhoto(${i})">×</button>
                        `;
                        previewContainer.appendChild(photoItem);
                    };
                    
                    reader.readAsDataURL(file);
                }
                
                if (input.files.length > maxPhotos) {
                    const warning = document.createElement('div');
                    warning.className = 'text-yellow-600 text-sm mt-2';
                    warning.textContent = `Only the first ${maxPhotos} photos will be uploaded.`;
                    previewContainer.appendChild(warning);
                }
            }
        }

        function removePhoto(index) {
            const photoItems = document.querySelectorAll('#photo_previews .photo-item');
            if (photoItems[index]) {
                photoItems[index].remove();
            }
        }

        function removeExistingPhoto(attachmentId) {
            if (confirm('Are you sure you want to remove this photo?')) {
                // Create a form to remove the photo
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../Controller/activity_remove_photo_process.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'attachment_id';
                input.value = attachmentId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Initialize form on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleFundingSourceFields();
        });
    </script>
</body>
</html>
