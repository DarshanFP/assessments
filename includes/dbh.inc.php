<?php
// Secure database connection file for server environment

// Database credentials
$host = 'srv1281.hstgr.io';
$dbname = 'u441625086_assessments';
$dbusername = 'u441625086_assessments';
$dbpassword = 'tySsuz-bistoj-zuxne8';
$charset = 'utf8mb4'; // Use utf8mb4 for full UTF-8 support (including emojis)

// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exception mode for errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulated prepared statements
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $dbusername, $dbpassword, $options);

    // Optional: Log successful connection for debugging (remove in production)
    // error_log("Database connection established successfully.");
} catch (PDOException $e) {
    // Log the error to a file instead of displaying it to the user (security best practice)
    error_log("Database Connection Error: " . $e->getMessage());

    // Display a generic error message to the user
    die("A database connection error occurred. Please try again later.");
}
