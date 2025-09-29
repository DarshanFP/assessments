<?php
/**
 * Session Manager Class
 * Handles session management with security and timeout features
 */
class SessionManager {
    private static $instance = null;
    private static $sessionTimeout = 1800; // 30 minutes
    private static $sessionRegenerationTime = 1800; // 30 minutes
    
    private function __construct() {
        // Private constructor to prevent direct instantiation
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start session with security measures
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            // Start session only if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            self::regenerateSessionIfNeeded();
            self::checkTimeout();
        }
    }
    
    /**
     * Regenerate session ID if needed for security
     */
    public static function regenerateSessionIfNeeded() {
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > self::$sessionRegenerationTime) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    /**
     * Check session timeout
     */
    public static function checkTimeout() {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > self::$sessionTimeout)) {
            self::destroySession();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Set user session data
     */
    public static function setUserSession($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['community'] = $userData['community'] ?? '';
        $_SESSION['full_name'] = $userData['full_name'];
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && self::checkTimeout();
    }
    
    /**
     * Get current user role
     */
    public static function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user full name
     */
    public static function getUserFullName() {
        return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Unknown User';
    }
    
    /**
     * Get current user community
     */
    public static function getUserCommunity() {
        return $_SESSION['community'] ?? null;
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($requiredRole) {
        return self::isLoggedIn() && $_SESSION['role'] === $requiredRole;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public static function hasAnyRole($allowedRoles) {
        return self::isLoggedIn() && in_array($_SESSION['role'], $allowedRoles);
    }
    
    /**
     * Destroy session
     */
    public static function destroySession() {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        self::destroySession();
        header("Location: index.php");
        exit();
    }
    
    /**
     * Set session message
     */
    public static function setMessage($type, $message) {
        $_SESSION[$type] = $message;
    }
    
    /**
     * Get and clear session message
     */
    public static function getMessage($type) {
        $message = $_SESSION[$type] ?? null;
        unset($_SESSION[$type]);
        return $message;
    }
    
    /**
     * Get session statistics
     */
    public static function getSessionStats() {
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'created' => $_SESSION['created'] ?? null,
            'timeout_remaining' => isset($_SESSION['last_activity']) ? 
                (self::$sessionTimeout - (time() - $_SESSION['last_activity'])) : null
        ];
    }
}
?>
