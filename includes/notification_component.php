<?php
/**
 * Notification Component
 * Displays notifications on the dashboard
 */

// Ensure the user is logged in and is a Councillor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Councillor') {
    return;
}

require_once __DIR__ . '/NotificationManager.php';

$currentUserId = $_SESSION['user_id'];

// Get notifications for the current user
$notifications = NotificationManager::getUserNotifications($currentUserId, 5, true); // Get 5 unread notifications
$unreadCount = NotificationManager::getUnreadCount($currentUserId);
?>

<!-- Notifications Section -->
<div class="notifications-section mb-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-bell mr-2 text-blue-500"></i>
                Notifications
                <?php if ($unreadCount > 0): ?>
                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1 ml-2">
                        <?php echo $unreadCount; ?>
                    </span>
                <?php endif; ?>
            </h2>
            <?php if ($unreadCount > 0): ?>
                <button onclick="markAllAsRead()" class="btn btn-sm btn-outline">
                    <i class="fas fa-check-double mr-1"></i>Mark All Read
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="text-center py-8">
                <div class="text-gray-400 text-4xl mb-2">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">No New Notifications</h3>
                <p class="text-gray-500">You're all caught up! No pending approvals or notifications.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors <?php echo $notification['is_read'] ? 'opacity-75' : 'bg-blue-50 border-blue-200'; ?>">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="notification-type-badge notification-type-<?php echo $notification['notification_type']; ?>">
                                        <?php echo $notification['type_display']; ?>
                                    </span>
                                    <?php if ($notification['priority'] === 'urgent' || $notification['priority'] === 'high'): ?>
                                        <span class="priority-badge priority-<?php echo $notification['priority']; ?> ml-2">
                                            <?php echo ucfirst($notification['priority']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h4 class="font-semibold text-gray-800 mb-1">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                </h4>
                                
                                <p class="text-gray-600 text-sm mb-2">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?>
                                    </span>
                                    
                                    <div class="flex space-x-2">
                                        <?php if ($notification['action_url']): ?>
                                            <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-external-link-alt mr-1"></i>Take Action
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button onclick="markAsRead(<?php echo $notification['notification_id']; ?>)" 
                                                class="btn btn-sm btn-outline">
                                            <i class="fas fa-check mr-1"></i>Mark Read
                                        </button>
                                        
                                        <button onclick="deleteNotification(<?php echo $notification['notification_id']; ?>)" 
                                                class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash mr-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($unreadCount > 5): ?>
                <div class="text-center mt-4">
                    <a href="notifications.php" class="btn btn-outline">
                        <i class="fas fa-list mr-2"></i>View All Notifications (<?php echo $unreadCount; ?> unread)
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Notification Styles -->
<style>
.notification-type-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.notification-type-project_edit_request {
    background-color: #dbeafe;
    color: #1e40af;
}

.notification-type-expense_edit_request {
    background-color: #fef3c7;
    color: #92400e;
}

.notification-type-activity_edit_request {
    background-color: #d1fae5;
    color: #065f46;
}

.notification-type-system {
    background-color: #f3f4f6;
    color: #374151;
}

.notification-type-info {
    background-color: #e0e7ff;
    color: #3730a3;
}

.priority-badge {
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-urgent {
    background-color: #fee2e2;
    color: #991b1b;
}

.priority-high {
    background-color: #fef3c7;
    color: #92400e;
}

.notification-item {
    transition: all 0.3s ease;
}

.notification-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>

<!-- Notification JavaScript -->
<script>
function markAsRead(notificationId) {
    fetch('ajax/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the notification from the UI
            const notificationElement = document.querySelector(`[onclick*="${notificationId}"]`).closest('.notification-item');
            notificationElement.style.opacity = '0.5';
            notificationElement.style.backgroundColor = '#f9fafb';
            
            // Update unread count
            updateUnreadCount();
        } else {
            alert('Error marking notification as read');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error marking notification as read');
    });
}

function markAllAsRead() {
    fetch('ajax/mark_all_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to update notifications
            location.reload();
        } else {
            alert('Error marking all notifications as read');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error marking all notifications as read');
    });
}

function deleteNotification(notificationId) {
    if (confirm('Are you sure you want to delete this notification?')) {
        fetch('ajax/delete_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the notification from the UI
                const notificationElement = document.querySelector(`[onclick*="${notificationId}"]`).closest('.notification-item');
                notificationElement.remove();
                
                // Update unread count
                updateUnreadCount();
            } else {
                alert('Error deleting notification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting notification');
        });
    }
}

function updateUnreadCount() {
    // This would typically make an AJAX call to get the updated count
    // For now, we'll just reload the notifications section
    location.reload();
}
</script>
