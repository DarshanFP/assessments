<?php
/**
 * Database Optimizer Class
 * Handles database performance optimization and maintenance
 */
require_once 'DatabaseManager.php';

class DatabaseOptimizer {
    
    /**
     * Get database performance statistics
     */
    public static function getPerformanceStats() {
        try {
            $pdo = getDatabaseConnection();
            
            $stats = [];
            
            // Table sizes
            $sql = "SELECT 
                        table_name,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb',
                        table_rows
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE()
                    ORDER BY (data_length + index_length) DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['table_sizes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Connection stats
            $sql = "SHOW STATUS LIKE 'Connections'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['connections'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Slow queries
            $sql = "SHOW STATUS LIKE 'Slow_queries'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['slow_queries'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Query cache stats
            $sql = "SHOW STATUS LIKE 'Qcache%'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['query_cache'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Database performance stats error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Analyze table performance
     */
    public static function analyzeTable($tableName) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "ANALYZE TABLE " . $pdo->quote($tableName);
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Table analysis error: " . $e->getMessage());
            return false;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Optimize table
     */
    public static function optimizeTable($tableName) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "OPTIMIZE TABLE " . $pdo->quote($tableName);
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Table optimization error: " . $e->getMessage());
            return false;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Check table status
     */
    public static function checkTableStatus($tableName) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "CHECK TABLE " . $pdo->quote($tableName);
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Table status check error: " . $e->getMessage());
            return false;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Repair table
     */
    public static function repairTable($tableName) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "REPAIR TABLE " . $pdo->quote($tableName);
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Table repair error: " . $e->getMessage());
            return false;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get slow query log
     */
    public static function getSlowQueries($limit = 10) {
        try {
            $pdo = getDatabaseConnection();
            
            // This requires slow query log to be enabled
            $sql = "SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Slow queries error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get database variables
     */
    public static function getDatabaseVariables() {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SHOW VARIABLES";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Database variables error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Clean old log entries
     */
    public static function cleanOldLogs($daysToKeep = 30) {
        try {
            $pdo = getDatabaseConnection();
            
            // Clean activity log
            $sql = "DELETE FROM ActivityLog WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':days', $daysToKeep, PDO::PARAM_INT);
            $stmt->execute();
            
            $deletedRows = $stmt->rowCount();
            
            // Log the cleanup
            if (SessionManager::isLoggedIn()) {
                $currentUserId = SessionManager::getUserId();
                $logMessage = "Cleaned old log entries older than {$daysToKeep} days. Deleted {$deletedRows} rows.";
                logActivityToDatabase($currentUserId, 'Database Maintenance', 'Success', $logMessage);
            }
            
            return $deletedRows;
            
        } catch (PDOException $e) {
            error_log("Clean old logs error: " . $e->getMessage());
            return 0;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Backup database (basic implementation)
     */
    public static function backupDatabase($backupPath = null) {
        try {
            if (!$backupPath) {
                $backupPath = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
            }
            
            // Ensure backup directory exists
            $backupDir = dirname($backupPath);
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Get database configuration
            $dbConfig = self::getDatabaseConfig();
            
            // Create backup command
            $command = "mysqldump -h {$dbConfig['host']} -u {$dbConfig['username']} -p{$dbConfig['password']} {$dbConfig['database']} > {$backupPath}";
            
            // Execute backup
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                // Log the backup
                if (SessionManager::isLoggedIn()) {
                    $currentUserId = SessionManager::getUserId();
                    $logMessage = "Database backup created: " . basename($backupPath);
                    logActivityToDatabase($currentUserId, 'Database Maintenance', 'Success', $logMessage);
                }
                
                return [
                    'success' => true,
                    'file' => $backupPath,
                    'size' => filesize($backupPath)
                ];
            } else {
                throw new Exception("Backup command failed with return code: {$returnCode}");
            }
            
        } catch (Exception $e) {
            error_log("Database backup error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get database configuration
     */
    private static function getDatabaseConfig() {
        // Production database configuration
        return [
            'host' => 'srv1281.hstgr.io',
            'username' => 'u441625086_assessments',
            'password' => 'tySsuz-bistoj-zuxne8',
            'database' => 'u441625086_assessments'
        ];
    }
    
    /**
     * Monitor database connections
     */
    public static function monitorConnections() {
        try {
            $pdo = getDatabaseConnection();
            
            $stats = [];
            
            // Current connections
            $sql = "SHOW STATUS LIKE 'Threads_connected'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['current_connections'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Max connections
            $sql = "SHOW VARIABLES LIKE 'max_connections'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['max_connections'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Connection usage percentage
            $current = (int)$stats['current_connections']['Value'];
            $max = (int)$stats['max_connections']['Value'];
            $stats['usage_percentage'] = round(($current / $max) * 100, 2);
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Connection monitoring error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get index usage statistics
     */
    public static function getIndexStats() {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT 
                        table_name,
                        index_name,
                        cardinality,
                        sub_part,
                        packed,
                        null,
                        index_type
                    FROM information_schema.statistics 
                    WHERE table_schema = DATABASE()
                    ORDER BY table_name, index_name";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Index stats error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Suggest optimizations
     */
    public static function suggestOptimizations() {
        $suggestions = [];
        
        try {
            // Check table sizes
            $tableSizes = self::getPerformanceStats()['table_sizes'] ?? [];
            foreach ($tableSizes as $table) {
                if ($table['size_mb'] > 100) {
                    $suggestions[] = [
                        'type' => 'table_size',
                        'table' => $table['table_name'],
                        'message' => "Large table detected: {$table['table_name']} ({$table['size_mb']} MB). Consider archiving old data.",
                        'priority' => 'medium'
                    ];
                }
            }
            
            // Check connection usage
            $connectionStats = self::monitorConnections();
            if ($connectionStats['usage_percentage'] > 80) {
                $suggestions[] = [
                    'type' => 'connections',
                    'message' => "High connection usage: {$connectionStats['usage_percentage']}%. Consider increasing max_connections or optimizing queries.",
                    'priority' => 'high'
                ];
            }
            
            // Check for tables without indexes
            $indexStats = self::getIndexStats();
            $tablesWithIndexes = array_unique(array_column($indexStats, 'table_name'));
            $allTables = array_column(self::getPerformanceStats()['table_sizes'] ?? [], 'table_name');
            
            foreach ($allTables as $table) {
                if (!in_array($table, $tablesWithIndexes)) {
                    $suggestions[] = [
                        'type' => 'indexes',
                        'table' => $table,
                        'message' => "Table {$table} has no indexes. Consider adding indexes for frequently queried columns.",
                        'priority' => 'medium'
                    ];
                }
            }
            
        } catch (Exception $e) {
            error_log("Optimization suggestions error: " . $e->getMessage());
        }
        
        return $suggestions;
    }
}
?>
