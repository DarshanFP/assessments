<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use absolute path for includes
require_once __DIR__ . '/auth_check.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . dirname($_SERVER['PHP_SELF']) . "/../index.php");
    exit();
}

// Get the user's role from the session
$userRole = $_SESSION['role'] ?? 'Guest';

// Include the appropriate sidebar based on the user's role
if ($userRole === 'Councillor') {
    include __DIR__ . '/../sidebar_councillor.php';
} elseif ($userRole === 'Project In-Charge') {
    include __DIR__ . '/../sidebar_project_incharge.php';
} else {
    echo "<p class='text-red-500'>Access Denied: Invalid Role</p>";
    exit();
}
?>
