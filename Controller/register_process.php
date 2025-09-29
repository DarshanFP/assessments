<?php
session_start();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include the database connection
    require_once '../includes/dbh.inc.php';
    require_once '../includes/log_activity.php'; // Database logging
    require_once '../includes/logger.inc.php'; // File logging

    // Extract and sanitize user input
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone_number = trim($_POST['phone_number']);
    $community = trim($_POST['community']); // Updated to match the form field name
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);

    // Validate required fields
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $_SESSION['error'] = "All required fields must be filled out.";
        header("Location: ../View/register.php");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: ../View/register.php");
        exit();
    }

    // Validate role
    $allowed_roles = ['Project In-Charge', 'Councillor'];
    if (!in_array($role, $allowed_roles)) {
        $_SESSION['error'] = "Invalid role selected.";
        header("Location: ../View/register.php");
        exit();
    }

    // Check if passwords match
    if ($password !== $password_confirm) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: ../View/register.php");
        exit();
    }

    // Hash the password for secure storage
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check if username or email already exists
        $sql_check = "SELECT COUNT(*) FROM ssmntUsers WHERE username = :username OR email = :email";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute(['username' => $username, 'email' => $email]);
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            $_SESSION['error'] = "Username or email already exists.";
            header("Location: ../View/register.php");
            exit();
        }

        // Prepare an SQL statement to insert form data into the database
        $sql = "INSERT INTO ssmntUsers (username, full_name, email, phone_number, community, role, password) VALUES (:username, :full_name, :email, :phone_number, :community, :role, :password)";
        $stmt = $pdo->prepare($sql);

        // Bind parameters to prevent SQL injection
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
        $stmt->bindParam(':community', $community, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);

        // Execute the statement
        if ($stmt->execute()) {
            // Log the registration activity
            logActivityToDatabase(null, 'Registration', 'Success', "New user registered: $username with role $role");
            logActivityToFile("New user registered: $username with role $role", "info");

            $_SESSION['success'] = "Registration successful. Please log in.";
            header("Location: ../View/CouncillorDashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Registration failed. Please try again.";
            header("Location: ../View/register.php");
            exit();
        }
    } catch (PDOException $e) {
        // Check for duplicate entry error
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = "An account with this username or email already exists.";
        } else {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
        // Log the error
        logActivityToDatabase(null, 'Registration', 'Error', $e->getMessage());
        logActivityToFile("Database error during registration: " . $e->getMessage(), "error");

        header("Location: ../View/register.php");
        exit();
    }
} else {
    // Redirect to registration form if the script is accessed directly
    header("Location: ../View/register.php");
    exit();
}
?>
