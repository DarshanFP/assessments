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
    $userId = $_SESSION['user_id'];
    
    $count = NotificationManager::markAllAsRead($userId);
    
    echo json_encode([
        'success' => true, 
        'message' => "Marked {$count} notifications as read"
    ]);
    
} catch (Exception $e) {
    error_log("Error in mark_all_notifications_read.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
