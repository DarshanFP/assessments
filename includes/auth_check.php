<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($required_role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $required_role) {
        header("Location: ../access_denied.php");
        exit();
    }
}
?>
