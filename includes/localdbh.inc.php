<?php
// Secure database connection file (includes/dbh.inc.php)

// Database credentials
$host = 'localhost';
$dbname = 'u441625086_assessment';
$dbusername = 'root';
$dbpassword = 'root';
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
} catch (PDOException $e) {
    // Log the error to a file instead of displaying it to the user (security best practice)
    error_log("Database Connection Error: " . $e->getMessage());

    // Display a generic error message to the user
    die("A database connection error occurred. Please try again later.");
}
