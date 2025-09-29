<?php
require_once 'DatabaseManager.php';
require_once 'logger.inc.php';

function logActivityToDatabase($userId, $action, $status, $message = null) {
    try {
        // Get database connection from pool
        $pdo = getDatabaseConnection();
        
        // Prepare the SQL statement for logging
        $sql = "INSERT INTO ActivityLog (user_id, action, status, message) VALUES (:user_id, :action, :status, :message)";
        $stmt = $pdo->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':message', $message);

        // Execute the statement
        $stmt->execute();
        
        // Release connection back to pool
        $dbManager = DatabaseManager::getInstance();
        $dbManager->releaseConnection($pdo);
        
    } catch (PDOException $e) {
        // If logging fails, write to PHP error log
        error_log("Logging error: " . $e->getMessage());
    }
}
