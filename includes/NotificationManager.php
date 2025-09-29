<?php
/**
 * Notification Manager Class
 * Handles creation, retrieval, and management of notifications
 */

class NotificationManager {
    
    /**
     * Create a new notification
     */
    public static function createNotification($userId, $type, $title, $message, $relatedId = null, $relatedType = null, $actionUrl = null, $priority = 'medium', $expiresAt = null) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO Notifications (
                    user_id, notification_type, title, message, related_id, 
                    related_type, action_url, priority, expires_at
                ) VALUES (
                    :user_id, :notification_type, :title, :message, :related_id,
                    :related_type, :action_url, :priority, :expires_at
                )
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':notification_type' => $type,
                ':title' => $title,
                ':message' => $message,
                ':related_id' => $relatedId,
                ':related_type' => $relatedType,
                ':action_url' => $actionUrl,
                ':priority' => $priority,
                ':expires_at' => $expiresAt
            ]);
            
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notifications for a user
     */
    public static function getUserNotifications($userId, $limit = 10, $unreadOnly = false) {
        global $pdo;
        
        try {
            $sql = "
                SELECT * FROM NotificationsView 
                WHERE user_id = :user_id
            ";
            
            if ($unreadOnly) {
                $sql .= " AND is_read = FALSE";
            }
            
            $sql .= " ORDER BY priority_order DESC, created_at DESC";
            
            if ($limit > 0) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            
            if ($limit > 0) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unread notification count for a user
     */
    public static function getUnreadCount($userId) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM Notifications 
                WHERE user_id = :user_id AND is_read = FALSE
            ");
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Mark notification as read
     */
    public static function markAsRead($notificationId, $userId) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE Notifications 
                SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                WHERE notification_id = :notification_id AND user_id = :user_id
            ");
            $stmt->execute([
                ':notification_id' => $notificationId,
                ':user_id' => $userId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead($userId) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE Notifications 
                SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                WHERE user_id = :user_id AND is_read = FALSE
            ");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete notification
     */
    public static function deleteNotification($notificationId, $userId) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                DELETE FROM Notifications 
                WHERE notification_id = :notification_id AND user_id = :user_id
            ");
            $stmt->execute([
                ':notification_id' => $notificationId,
                ':user_id' => $userId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create notification for project edit request
     */
    public static function createProjectEditNotification($projectId, $requestedBy, $projectTitle) {
        // Get all Councillor users
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT id FROM ssmntUsers WHERE role = 'Councillor'
            ");
            $stmt->execute();
            $councillors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $title = "Project Edit Request";
            $message = "A project edit request has been submitted for '{$projectTitle}' by user ID {$requestedBy}";
            $actionUrl = "ProjectBudgets/Blade/project_edit_approvals.php";
            
            foreach ($councillors as $councillor) {
                self::createNotification(
                    $councillor['id'],
                    'project_edit_request',
                    $title,
                    $message,
                    $projectId,
                    'project',
                    $actionUrl,
                    'high'
                );
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error creating project edit notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create notification for expense edit request
     */
    public static function createExpenseEditNotification($expenseId, $requestedBy, $expenseDescription) {
        // Get all Councillor users
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT id FROM ssmntUsers WHERE role = 'Councillor'
            ");
            $stmt->execute();
            $councillors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $title = "Expense Edit Request";
            $message = "An expense edit request has been submitted for '{$expenseDescription}' by user ID {$requestedBy}";
            $actionUrl = "ProjectBudgets/Blade/expense_edit_approvals.php";
            
            foreach ($councillors as $councillor) {
                self::createNotification(
                    $councillor['id'],
                    'expense_edit_request',
                    $title,
                    $message,
                    $expenseId,
                    'expense',
                    $actionUrl,
                    'high'
                );
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error creating expense edit notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create notification for activity edit request
     */
    public static function createActivityEditNotification($activityId, $requestedBy, $activityTitle) {
        // Get all Councillor users
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT id FROM ssmntUsers WHERE role = 'Councillor'
            ");
            $stmt->execute();
            $councillors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $title = "Activity Edit Request";
            $message = "An activity edit request has been submitted for '{$activityTitle}' by user ID {$requestedBy}";
            $actionUrl = "ProjectBudgets/Blade/activity_approvals.php";
            
            foreach ($councillors as $councillor) {
                self::createNotification(
                    $councillor['id'],
                    'activity_edit_request',
                    $title,
                    $message,
                    $activityId,
                    'activity',
                    $actionUrl,
                    'high'
                );
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error creating activity edit notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear notifications when approval is processed
     */
    public static function clearApprovalNotifications($relatedId, $relatedType) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                DELETE FROM Notifications 
                WHERE related_id = :related_id AND related_type = :related_type
            ");
            $stmt->execute([
                ':related_id' => $relatedId,
                ':related_type' => $relatedType
            ]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error clearing approval notifications: " . $e->getMessage());
            return 0;
        }
    }
}
?>
