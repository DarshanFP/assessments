<?php
/**
 * Advanced Features Manager Class
 * Handles advanced functionality like notifications, search, and system monitoring
 */
require_once 'DatabaseManager.php';

class AdvancedFeaturesManager {
    
    /**
     * Send notification to user
     */
    public static function sendNotification($userId, $title, $message, $type = 'info', $actionUrl = null) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "INSERT INTO Notifications (user_id, title, message, type, action_url, created_at) 
                    VALUES (:user_id, :title, :message, :type, :action_url, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'action_url' => $actionUrl
            ]);
            
            return $pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Send notification error: " . $e->getMessage());
            return false;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get user notifications
     */
    public static function getUserNotifications($userId, $limit = 10, $unreadOnly = false) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT * FROM Notifications WHERE user_id = :user_id";
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            $sql .= " ORDER BY created_at DESC LIMIT :limit";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get notifications error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Mark notification as read
     */
    public static function markNotificationAsRead($notificationId) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "UPDATE Notifications SET is_read = 1 WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $notificationId]);
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Mark notification read error: " . $e->getMessage());
            return false;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Global search functionality
     */
    public static function globalSearch($query, $filters = []) {
        try {
            $pdo = getDatabaseConnection();
            $results = [];
            
            // Search in assessments
            if (empty($filters) || in_array('assessments', $filters)) {
                $sql = "SELECT 'Assessment' as type, keyID as id, Community as title, AssessorsName as subtitle, 
                        DateOfAssessment as date, CONCAT('Assessment in ', Community) as description
                        FROM Assessment 
                        WHERE Community LIKE :query OR AssessorsName LIKE :query OR AssessmentCentre LIKE :query
                        ORDER BY DateOfAssessment DESC LIMIT 10";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['query' => '%' . $query . '%']);
                $results['assessments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Search in projects
            if (empty($filters) || in_array('projects', $filters)) {
                $sql = "SELECT 'Project' as type, project_id as id, project_name as title, 
                        description as subtitle, start_date as date, description
                        FROM Projects 
                        WHERE project_name LIKE :query OR description LIKE :query
                        ORDER BY created_at DESC LIMIT 10";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['query' => '%' . $query . '%']);
                $results['projects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Search in users
            if (empty($filters) || in_array('users', $filters)) {
                $sql = "SELECT 'User' as type, id, full_name as title, username as subtitle, 
                        created_at as date, CONCAT(role, ' - ', COALESCE(community, 'No Community')) as description
                        FROM ssmntUsers 
                        WHERE full_name LIKE :query OR username LIKE :query OR email LIKE :query
                        ORDER BY created_at DESC LIMIT 10";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['query' => '%' . $query . '%']);
                $results['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Global search error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * System health check
     */
    public static function systemHealthCheck() {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'issues' => []
        ];
        
        try {
            // Check database connection
            $pdo = getDatabaseConnection();
            $health['checks']['database'] = 'connected';
            
            // Check table status
            $sql = "SHOW TABLE STATUS";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($tables as $table) {
                if ($table['Comment'] === 'corrupt') {
                    $health['issues'][] = "Table {$table['Name']} is corrupt";
                    $health['status'] = 'warning';
                }
            }
            
            // Check disk space (basic check)
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $diskUsage = (($diskTotal - $diskFree) / $diskTotal) * 100;
            
            if ($diskUsage > 90) {
                $health['issues'][] = "Disk usage is high: " . round($diskUsage, 2) . "%";
                $health['status'] = 'warning';
            }
            
            $health['checks']['disk_usage'] = round($diskUsage, 2) . '%';
            
            // Check PHP memory usage
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            $memoryUsagePercent = ($memoryUsage / self::parseMemoryLimit($memoryLimit)) * 100;
            
            if ($memoryUsagePercent > 80) {
                $health['issues'][] = "Memory usage is high: " . round($memoryUsagePercent, 2) . "%";
                $health['status'] = 'warning';
            }
            
            $health['checks']['memory_usage'] = round($memoryUsagePercent, 2) . '%';
            
            // Check session status
            if (session_status() === PHP_SESSION_ACTIVE) {
                $health['checks']['sessions'] = 'active';
            } else {
                $health['issues'][] = "Sessions are not active";
                $health['status'] = 'warning';
            }
            
        } catch (Exception $e) {
            $health['status'] = 'error';
            $health['issues'][] = "Database connection failed: " . $e->getMessage();
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
        
        return $health;
    }
    
    /**
     * Parse memory limit string
     */
    private static function parseMemoryLimit($limit) {
        $unit = strtolower(substr($limit, -1));
        $value = (int)substr($limit, 0, -1);
        
        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }
    
    /**
     * Generate system report
     */
    public static function generateSystemReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'system_info' => [],
            'performance' => [],
            'security' => [],
            'recommendations' => []
        ];
        
        // System information
        $report['system_info'] = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => php_uname(),
            'timezone' => date_default_timezone_get(),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ];
        
        // Performance metrics
        $report['performance'] = [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'disk_free_space' => disk_free_space('/'),
            'disk_total_space' => disk_total_space('/'),
            'load_average' => sys_getloadavg()
        ];
        
        // Security checks
        $report['security'] = [
            'session_secure' => ini_get('session.cookie_secure'),
            'session_httponly' => ini_get('session.cookie_httponly'),
            'session_samesite' => ini_get('session.cookie_samesite'),
            'display_errors' => ini_get('display_errors'),
            'log_errors' => ini_get('log_errors'),
            'error_reporting' => ini_get('error_reporting')
        ];
        
        // Generate recommendations
        $report['recommendations'] = self::generateRecommendations($report);
        
        return $report;
    }
    
    /**
     * Generate system recommendations
     */
    private static function generateRecommendations($report) {
        $recommendations = [];
        
        // Memory recommendations
        $memoryUsage = $report['performance']['memory_usage'];
        $memoryLimit = self::parseMemoryLimit($report['system_info']['memory_limit']);
        $memoryUsagePercent = ($memoryUsage / $memoryLimit) * 100;
        
        if ($memoryUsagePercent > 80) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => "Memory usage is high ({$memoryUsagePercent}%). Consider increasing memory_limit or optimizing code."
            ];
        }
        
        // Security recommendations
        if (!$report['security']['session_secure']) {
            $recommendations[] = [
                'type' => 'security',
                'priority' => 'high',
                'message' => "Enable secure session cookies for HTTPS environments."
            ];
        }
        
        if (!$report['security']['session_httponly']) {
            $recommendations[] = [
                'type' => 'security',
                'priority' => 'medium',
                'message' => "Enable HTTP-only session cookies to prevent XSS attacks."
            ];
        }
        
        if ($report['security']['display_errors']) {
            $recommendations[] = [
                'type' => 'security',
                'priority' => 'high',
                'message' => "Disable display_errors in production environment."
            ];
        }
        
        // Performance recommendations
        $diskUsage = (($report['performance']['disk_total_space'] - $report['performance']['disk_free_space']) / $report['performance']['disk_total_space']) * 100;
        
        if ($diskUsage > 90) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => "Disk usage is high ({$diskUsage}%). Consider cleaning up old files or expanding storage."
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Create backup schedule
     */
    public static function scheduleBackup($frequency = 'daily', $retention = 7) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "INSERT INTO BackupSchedule (frequency, retention_days, is_active, created_at) 
                    VALUES (:frequency, :retention, 1, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'frequency' => $frequency,
                'retention' => $retention
            ]);
            
            return $pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Schedule backup error: " . $e->getMessage());
            return false;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get system metrics
     */
    public static function getSystemMetrics() {
        $metrics = [
            'timestamp' => time(),
            'cpu_usage' => 0,
            'memory_usage' => memory_get_usage(true),
            'disk_usage' => 0,
            'active_users' => 0,
            'database_connections' => 0
        ];
        
        try {
            // Get active users count
            $pdo = getDatabaseConnection();
            $sql = "SELECT COUNT(DISTINCT user_id) as active_users FROM ActivityLog 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $metrics['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'];
            
            // Get database connection count
            $sql = "SHOW STATUS LIKE 'Threads_connected'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $metrics['database_connections'] = $stmt->fetch(PDO::FETCH_ASSOC)['Value'];
            
            // Calculate disk usage
            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $metrics['disk_usage'] = (($diskTotal - $diskFree) / $diskTotal) * 100;
            
        } catch (Exception $e) {
            error_log("Get system metrics error: " . $e->getMessage());
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
        
        return $metrics;
    }
    
    /**
     * Log system event
     */
    public static function logSystemEvent($event, $level = 'info', $details = null) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "INSERT INTO SystemEvents (event, level, details, created_at) 
                    VALUES (:event, :level, :details, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'event' => $event,
                'level' => $level,
                'details' => $details
            ]);
            
            return $pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Log system event error: " . $e->getMessage());
            return false;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
}
?>
