<?php
session_start();
require_once '../../includes/dbh.inc.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/log_activity.php';

// Ensure the user is logged in
if (!isLoggedIn()) {
    header("Location: ../../index.php");
    exit();
}

$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];
$activityId = isset($_POST['activity_id']) ? (int)$_POST['activity_id'] : 0;

if (!$activityId) {
    $_SESSION['error'] = "Invalid activity ID.";
    header("Location: ../Blade/activities_list.php");
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify activity exists and user has permission
    $stmt = $pdo->prepare("
        SELECT * FROM Activities 
        WHERE activity_id = :activity_id 
        AND (created_by = :user_id OR :user_role = 'Councillor')
    ");
    $stmt->execute([
        ':activity_id' => $activityId,
        ':user_id' => $currentUserId,
        ':user_role' => $currentUserRole
    ]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$activity) {
        throw new Exception("Activity not found or you don't have permission to edit it.");
    }
    
    // Get form data
    $activityTitle = trim($_POST['activity_title']);
    $fundingSource = $_POST['funding_source'];
    $fundingSourceOther = isset($_POST['funding_source_other']) ? trim($_POST['funding_source_other']) : null;
    $organizationId = isset($_POST['organization_id']) && !empty($_POST['organization_id']) ? $_POST['organization_id'] : null;
    $projectId = isset($_POST['project_id']) && !empty($_POST['project_id']) ? $_POST['project_id'] : null;
    $activityDate = $_POST['activity_date'];
    $place = trim($_POST['place']);
    $conductedFor = trim($_POST['conducted_for']);
    $numberOfParticipants = (int)$_POST['number_of_participants'];
    $isCollaboration = isset($_POST['is_collaboration']) ? 1 : 0;
    $collaboratorOrganization = isset($_POST['collaborator_organization']) ? trim($_POST['collaborator_organization']) : null;
    $collaboratorName = isset($_POST['collaborator_name']) ? trim($_POST['collaborator_name']) : null;
    $collaboratorPosition = isset($_POST['collaborator_position']) ? trim($_POST['collaborator_position']) : null;
    $immediateOutcome = trim($_POST['immediate_outcome']);
    $longTermImpact = trim($_POST['long_term_impact']);
    $action = $_POST['action']; // 'save_draft' or 'submit'
    
    // Determine status based on action
    $status = ($action === 'submit') ? 'submitted' : 'draft';
    $submittedAt = ($action === 'submit') ? date('Y-m-d H:i:s') : null;
    
    // Validate required fields
    if (empty($activityTitle) || empty($fundingSource) || empty($activityDate) || 
        empty($place) || empty($conductedFor) || empty($numberOfParticipants) || 
        empty($immediateOutcome) || empty($longTermImpact)) {
        throw new Exception("All required fields must be filled.");
    }
    
    // Validate funding source specific fields
    if ($fundingSource === 'Organisation funded' && empty($organizationId)) {
        throw new Exception("Please select an organisation for organisation funded activities.");
    }
    
    if ($fundingSource === 'Project' && empty($projectId)) {
        throw new Exception("Please select a project for project funded activities.");
    }
    
    if ($fundingSource === 'Others' && empty($fundingSourceOther)) {
        throw new Exception("Please specify the funding source for 'Others' option.");
    }
    
    // Validate collaboration fields if collaboration is selected
    if ($isCollaboration) {
        if (empty($collaboratorOrganization) || empty($collaboratorName) || empty($collaboratorPosition)) {
            throw new Exception("All collaboration fields are required when collaboration is selected.");
        }
    }
    
    // Update activity record
    $stmt = $pdo->prepare("
        UPDATE Activities SET
            activity_title = :activity_title,
            funding_source = :funding_source,
            funding_source_other = :funding_source_other,
            organization_id = :organization_id,
            project_id = :project_id,
            activity_date = :activity_date,
            place = :place,
            conducted_for = :conducted_for,
            number_of_participants = :number_of_participants,
            is_collaboration = :is_collaboration,
            collaborator_organization = :collaborator_organization,
            collaborator_name = :collaborator_name,
            collaborator_position = :collaborator_position,
            immediate_outcome = :immediate_outcome,
            long_term_impact = :long_term_impact,
            status = :status,
            submitted_at = :submitted_at,
            updated_at = CURRENT_TIMESTAMP
        WHERE activity_id = :activity_id
    ");
    
    $stmt->execute([
        ':activity_title' => $activityTitle,
        ':funding_source' => $fundingSource,
        ':funding_source_other' => $fundingSourceOther,
        ':organization_id' => $organizationId,
        ':project_id' => $projectId,
        ':activity_date' => $activityDate,
        ':place' => $place,
        ':conducted_for' => $conductedFor,
        ':number_of_participants' => $numberOfParticipants,
        ':is_collaboration' => $isCollaboration,
        ':collaborator_organization' => $collaboratorOrganization,
        ':collaborator_name' => $collaboratorName,
        ':collaborator_position' => $collaboratorPosition,
        ':immediate_outcome' => $immediateOutcome,
        ':long_term_impact' => $longTermImpact,
        ':status' => $status,
        ':submitted_at' => $submittedAt,
        ':activity_id' => $activityId
    ]);
    
    // Handle new photo uploads
    if (isset($_FILES['activity_photos']) && !empty($_FILES['activity_photos']['name'][0])) {
        $uploadDir = '../../uploads/activities/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $maxPhotos = 4;
        $uploadedCount = 0;
        
        for ($i = 0; $i < count($_FILES['activity_photos']['name']) && $uploadedCount < $maxPhotos; $i++) {
            if ($_FILES['activity_photos']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['activity_photos']['name'][$i];
                $fileTmpName = $_FILES['activity_photos']['tmp_name'][$i];
                $fileSize = $_FILES['activity_photos']['size'][$i];
                $fileType = $_FILES['activity_photos']['type'][$i];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    continue; // Skip invalid file types
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = 'activity_' . $activityId . '_' . time() . '_' . $uploadedCount . '.' . $fileExtension;
                $filePath = $uploadDir . $uniqueFileName;
                
                // Move uploaded file
                if (move_uploaded_file($fileTmpName, $filePath)) {
                    // Insert attachment record
                    $attachmentStmt = $pdo->prepare("
                        INSERT INTO ActivityAttachments (
                            activity_id, file_name, original_name, file_path, file_size, file_type, uploaded_by
                        ) VALUES (
                            :activity_id, :file_name, :original_name, :file_path, :file_size, :file_type, :uploaded_by
                        )
                    ");
                    
                    $attachmentStmt->execute([
                        ':activity_id' => $activityId,
                        ':file_name' => $uniqueFileName,
                        ':original_name' => $fileName,
                        ':file_path' => $filePath,
                        ':file_size' => $fileSize,
                        ':file_type' => $fileType,
                        ':uploaded_by' => $currentUserId
                    ]);
                    
                    $uploadedCount++;
                }
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log activity
    $logMessage = "Activity '{$activityTitle}' " . ($action === 'submit' ? 'submitted' : 'updated') . " by user ID {$currentUserId}";
    logActivity($currentUserId, 'activity_edit', $logMessage);
    
    // Set success message
    if ($action === 'submit') {
        $_SESSION['success'] = "Activity report updated and submitted successfully!";
    } else {
        $_SESSION['success'] = "Activity updated successfully!";
    }
    
    // Redirect to activities list
    header("Location: ../Blade/activities_list.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log("Activity edit error: " . $e->getMessage());
    
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: ../Blade/activity_edit_form.php?id=" . $activityId);
    exit();
}
?>
