<?php
// Start session before any output (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/SessionManager.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// Set the timezone and get the current date and time
date_default_timezone_set('Asia/Kolkata'); // Update the timezone as needed
$currentDateTime = date('l, d F Y h:i A'); // Format: Day, DD Month YYYY HH:MM AM/PM
?>
<!-- Top Bar -->
<div class="top-bar-container">
    <!-- Left Section: System Title -->
    <div class="system-title">Assessment System</div>

    <!-- Middle Section: Empty for balance -->
    <div class="date-time"></div>

    <!-- Right Section: User Info -->
    <div class="user-info">
        <?php if (SessionManager::isLoggedIn()): ?>
            <span class="emoji">🌼</span><span class="greetings">Greetings</span><span class="emoji">🌼</span><span class="user-name"><?php echo htmlspecialchars(SessionManager::getUserFullName()); ?></span>
        <?php else: ?>
            <a href="/assessments/index.php" class="user-button">Login</a>
        <?php endif; ?>
    </div>
</div>
