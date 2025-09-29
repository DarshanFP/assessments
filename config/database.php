<?php
/**
 * Database Configuration File
 * Centralized configuration for database settings
 */

// Production Database Configuration
define('DB_HOST', 'srv1281.hstgr.io');
define('DB_NAME', 'u441625086_assessments');
define('DB_USER', 'u441625086_assessments');
define('DB_PASS', 'tySsuz-bistoj-zuxne8');
define('DB_CHARSET', 'utf8mb4');

// Database Connection Settings
define('DB_MAX_CONNECTIONS', 10);
define('DB_TIMEOUT', 30);
define('DB_PERSISTENT', false);

// Environment Settings
define('ENVIRONMENT', 'production'); // Change to 'development' for local development
define('DEBUG_MODE', false); // Set to true for debugging

// Backup Settings
define('BACKUP_ENABLED', true);
define('BACKUP_RETENTION_DAYS', 7);
define('BACKUP_PATH', '../backups/');

// Logging Settings
define('LOG_ENABLED', true);
define('LOG_RETENTION_DAYS', 30);
define('LOG_LEVEL', 'info'); // debug, info, warning, error

// Security Settings
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('SESSION_REGENERATION_TIME', 1800); // 30 minutes
define('PASSWORD_HASH_COST', 12);

// Application Settings
define('APP_NAME', 'Assessment System');
define('APP_VERSION', '2.0.0');
define('APP_TIMEZONE', 'Asia/Kolkata');
define('CURRENCY_SYMBOL', '₹');

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
define('UPLOAD_PATH', '../uploads/');

// Email Settings (if needed)
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_SECURE', 'tls');

// Cache Settings
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour
define('CACHE_PATH', '../cache/');

// API Settings (if needed)
define('API_ENABLED', false);
define('API_RATE_LIMIT', 100); // requests per hour
define('API_KEY_REQUIRED', true);

/**
 * Get database configuration array
 */
function getDatabaseConfig() {
    return [
        'host' => DB_HOST,
        'dbname' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => DB_CHARSET,
        'max_connections' => DB_MAX_CONNECTIONS,
        'timeout' => DB_TIMEOUT,
        'persistent' => DB_PERSISTENT
    ];
}

/**
 * Check if running in development mode
 */
function isDevelopment() {
    return ENVIRONMENT === 'development';
}

/**
 * Check if debugging is enabled
 */
function isDebugMode() {
    return DEBUG_MODE && isDevelopment();
}

/**
 * Get application settings
 */
function getAppSettings() {
    return [
        'name' => APP_NAME,
        'version' => APP_VERSION,
        'timezone' => APP_TIMEZONE,
        'currency_symbol' => CURRENCY_SYMBOL,
        'environment' => ENVIRONMENT
    ];
}
?>
