<?php
/**
 * Database Manager Class
 * Implements singleton pattern for database connection pooling
 */
class DatabaseManager {
    private static $instance = null;
    private $connection = null;
    private static $connectionPool = [];
    private static $maxConnections = 10;
    private static $currentConnections = 0;
    
    // Database configuration - will be loaded from config file
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    private $timeout;
    
    private function __construct() {
        // Load configuration from config file
        if (file_exists(__DIR__ . '/../config/database.php')) {
            require_once __DIR__ . '/../config/database.php';
            $config = getDatabaseConfig();
            
            $this->host = $config['host'];
            $this->dbname = $config['dbname'];
            $this->username = $config['username'];
            $this->password = $config['password'];
            $this->charset = $config['charset'];
            $this->timeout = $config['timeout'];
            self::$maxConnections = $config['max_connections'];
        } else {
            // Fallback to hardcoded values if config file doesn't exist
            $this->host = 'srv1281.hstgr.io';
            $this->dbname = 'u441625086_assessments';
            $this->username = 'u441625086_assessments';
            $this->password = 'tySsuz-bistoj-zuxne8';
            $this->charset = 'utf8mb4';
            $this->timeout = 30;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        // Check if we have available connections in the pool
        if (!empty(self::$connectionPool)) {
            $connection = array_pop(self::$connectionPool);
            if ($this->isConnectionValid($connection)) {
                return $connection;
            }
        }
        
        // Create new connection if pool is empty or max connections not reached
        if (self::$currentConnections < self::$maxConnections) {
            return $this->createConnection();
        }
        
        // Wait for available connection
        return $this->waitForConnection();
    }
    
    public function releaseConnection($connection) {
        if ($connection && $this->isConnectionValid($connection)) {
            self::$connectionPool[] = $connection;
            self::$currentConnections--;
        }
    }
    
    private function createConnection() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                PDO::ATTR_PERSISTENT => false, // Disable persistent connections for better control
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_TIMEOUT => $this->timeout, // Use configured timeout
            ];
            
            $connection = new PDO($dsn, $this->username, $this->password, $options);
            self::$currentConnections++;
            
            return $connection;
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function isConnectionValid($connection) {
        try {
            if ($connection instanceof PDO) {
                $connection->query('SELECT 1');
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function waitForConnection() {
        $maxWaitTime = 30; // 30 seconds max wait time
        $waitTime = 0;
        $waitInterval = 1; // 1 second intervals
        
        while ($waitTime < $maxWaitTime) {
            if (!empty(self::$connectionPool)) {
                $connection = array_pop(self::$connectionPool);
                if ($this->isConnectionValid($connection)) {
                    return $connection;
                }
            }
            
            sleep($waitInterval);
            $waitTime += $waitInterval;
        }
        
        throw new Exception("Timeout waiting for database connection");
    }
    
    public function getConnectionStats() {
        return [
            'current_connections' => self::$currentConnections,
            'pool_size' => count(self::$connectionPool),
            'max_connections' => self::$maxConnections,
            'available_connections' => self::$maxConnections - self::$currentConnections
        ];
    }
    
    public function closeAllConnections() {
        foreach (self::$connectionPool as $connection) {
            $connection = null;
        }
        self::$connectionPool = [];
        self::$currentConnections = 0;
    }
}

/**
 * Global function for backward compatibility
 */
function getDatabaseConnection() {
    return DatabaseManager::getInstance()->getConnection();
}

// Initialize global $pdo for backward compatibility
$pdo = getDatabaseConnection();
?>
