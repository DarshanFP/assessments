<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../includes/dbh.inc.php';
require_once '../includes/NotificationManager.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['notification_id'])) {
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        exit();
    }
    
    $notificationId = intval($input['notification_id']);
    $userId = $_SESSION['user_id'];
    
    $success = NotificationManager::deleteNotification($notificationId, $userId);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Notification deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
    }
    
} catch (Exception $e) {
    error_log("Error in delete_notification.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
