-- Create Notifications table for tracking pending approvals and system notifications
CREATE TABLE IF NOT EXISTS Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('project_edit_request', 'expense_edit_request', 'activity_edit_request', 'system', 'info') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT NULL, -- ID of the related record (project_id, expense_id, activity_id, etc.)
    related_type VARCHAR(50) NULL, -- Type of related record ('project', 'expense', 'activity')
    action_url VARCHAR(500) NULL, -- URL to take action on the notification
    is_read BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES ssmntUsers(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_notifications_user_id (user_id),
    INDEX idx_notifications_type (notification_type),
    INDEX idx_notifications_is_read (is_read),
    INDEX idx_notifications_created_at (created_at),
    INDEX idx_notifications_priority (priority)
);

-- Create a view for easy notification queries
CREATE OR REPLACE VIEW NotificationsView AS
SELECT 
    n.*,
    u.username,
    u.full_name,
    CASE 
        WHEN n.notification_type = 'project_edit_request' THEN 'Project Edit Request'
        WHEN n.notification_type = 'expense_edit_request' THEN 'Expense Edit Request'
        WHEN n.notification_type = 'activity_edit_request' THEN 'Activity Edit Request'
        WHEN n.notification_type = 'system' THEN 'System Notification'
        WHEN n.notification_type = 'info' THEN 'Information'
        ELSE 'Unknown'
    END as type_display,
    CASE 
        WHEN n.priority = 'urgent' THEN 4
        WHEN n.priority = 'high' THEN 3
        WHEN n.priority = 'medium' THEN 2
        WHEN n.priority = 'low' THEN 1
        ELSE 0
    END as priority_order
FROM Notifications n
LEFT JOIN ssmntUsers u ON n.user_id = u.id;
