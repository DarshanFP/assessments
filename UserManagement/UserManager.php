<?php
/**
 * User Manager Class
 * Handles user management operations including CRUD operations
 */
class UserManager {
    
    /**
     * Get all users with optional filtering
     */
    public static function getAllUsers($role = null, $community = null) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT id, username, email, full_name, role, community, phone_number, created_at FROM ssmntUsers WHERE 1=1";
            $params = [];
            
            if ($role) {
                $sql .= " AND role = :role";
                $params['role'] = $role;
            }
            
            if ($community) {
                $sql .= " AND community = :community";
                $params['community'] = $community;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get user by ID
     */
    public static function getUserById($userId) {
        try {
            $pdo = getDatabaseConnection();
            
            $sql = "SELECT id, username, email, full_name, role, community, phone_number, created_at FROM ssmntUsers WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Create new user
     */
    public static function createUser($userData) {
        try {
            $pdo = getDatabaseConnection();
            
            // Validate required fields
            if (empty($userData['username']) || empty($userData['email']) || empty($userData['full_name']) || empty($userData['role'])) {
                throw new Exception("Required fields are missing");
            }
            
            // Check if username or email already exists
            $sql = "SELECT id FROM ssmntUsers WHERE username = :username OR email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['username' => $userData['username'], 'email' => $userData['email']]);
            
            if ($stmt->fetch()) {
                throw new Exception("Username or email already exists");
            }
            
            // Hash password if provided
            $hashedPassword = null;
            if (!empty($userData['password'])) {
                $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            }
            
            // Insert new user
            $sql = "INSERT INTO ssmntUsers (username, email, password, full_name, role, community, phone_number) 
                    VALUES (:username, :email, :password, :full_name, :role, :community, :phone_number)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => $hashedPassword,
                'full_name' => $userData['full_name'],
                'role' => $userData['role'],
                'community' => $userData['community'] ?? null,
                'phone_number' => $userData['phone_number'] ?? null
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Log the activity
            if (SessionManager::isLoggedIn()) {
                $currentUserId = SessionManager::getUserId();
                $logMessage = "User created: {$userData['full_name']} ({$userData['role']})";
                logActivityToDatabase($currentUserId, 'User Management', 'Success', $logMessage);
            }
            
            return $userId;
            
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Update user
     */
    public static function updateUser($userId, $userData) {
        try {
            $pdo = getDatabaseConnection();
            
            // Check if user exists
            $existingUser = self::getUserById($userId);
            if (!$existingUser) {
                throw new Exception("User not found");
            }
            
            // Build update query
            $updateFields = [];
            $params = ['id' => $userId];
            
            if (isset($userData['username'])) {
                $updateFields[] = "username = :username";
                $params['username'] = $userData['username'];
            }
            
            if (isset($userData['email'])) {
                $updateFields[] = "email = :email";
                $params['email'] = $userData['email'];
            }
            
            if (isset($userData['full_name'])) {
                $updateFields[] = "full_name = :full_name";
                $params['full_name'] = $userData['full_name'];
            }
            
            if (isset($userData['role'])) {
                $updateFields[] = "role = :role";
                $params['role'] = $userData['role'];
            }
            
            if (isset($userData['community'])) {
                $updateFields[] = "community = :community";
                $params['community'] = $userData['community'];
            }
            
            if (isset($userData['phone_number'])) {
                $updateFields[] = "phone_number = :phone_number";
                $params['phone_number'] = $userData['phone_number'];
            }
            
            if (empty($updateFields)) {
                throw new Exception("No fields to update");
            }
            
            $sql = "UPDATE ssmntUsers SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Log the activity
            if (SessionManager::isLoggedIn()) {
                $currentUserId = SessionManager::getUserId();
                $logMessage = "User updated: {$existingUser['full_name']}";
                logActivityToDatabase($currentUserId, 'User Management', 'Success', $logMessage);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Update user error: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Change user password
     */
    public static function changePassword($userId, $newPassword) {
        try {
            $pdo = getDatabaseConnection();
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE ssmntUsers SET password = :password WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);
            
            // Log the activity
            if (SessionManager::isLoggedIn()) {
                $currentUserId = SessionManager::getUserId();
                $user = self::getUserById($userId);
                $logMessage = "Password changed for user: {$user['full_name']}";
                logActivityToDatabase($currentUserId, 'User Management', 'Success', $logMessage);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Deactivate user
     */
    public static function deactivateUser($userId) {
        try {
            $pdo = getDatabaseConnection();
            
            // For now, we'll just update the role to indicate deactivation
            // In a real system, you might have a separate 'active' field
            $sql = "UPDATE ssmntUsers SET role = 'Inactive' WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $userId]);
            
            // Log the activity
            if (SessionManager::isLoggedIn()) {
                $currentUserId = SessionManager::getUserId();
                $user = self::getUserById($userId);
                $logMessage = "User deactivated: {$user['full_name']}";
                logActivityToDatabase($currentUserId, 'User Management', 'Success', $logMessage);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Deactivate user error: " . $e->getMessage());
            throw $e;
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Get user statistics
     */
    public static function getUserStats() {
        try {
            $pdo = getDatabaseConnection();
            
            $stats = [];
            
            // Total users
            $sql = "SELECT COUNT(*) as total_users FROM ssmntUsers";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
            
            // Users by role
            $sql = "SELECT role, COUNT(*) as count FROM ssmntUsers GROUP BY role";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['users_by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Users by community
            $sql = "SELECT community, COUNT(*) as count FROM ssmntUsers WHERE community IS NOT NULL GROUP BY community";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['users_by_community'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent registrations
            $sql = "SELECT COUNT(*) as recent_users FROM ssmntUsers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $stats['recent_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_users'];
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("User stats error: " . $e->getMessage());
            return [];
        } finally {
            if (isset($pdo)) {
                $dbManager = DatabaseManager::getInstance();
                $dbManager->releaseConnection($pdo);
            }
        }
    }
    
    /**
     * Validate user data
     */
    public static function validateUserData($userData, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate || isset($userData['username'])) {
            if (empty($userData['username'])) {
                $errors[] = "Username is required";
            } elseif (strlen($userData['username']) < 3) {
                $errors[] = "Username must be at least 3 characters";
            }
        }
        
        if (!$isUpdate || isset($userData['email'])) {
            if (empty($userData['email'])) {
                $errors[] = "Email is required";
            } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
        }
        
        if (!$isUpdate || isset($userData['full_name'])) {
            if (empty($userData['full_name'])) {
                $errors[] = "Full name is required";
            }
        }
        
        if (!$isUpdate || isset($userData['role'])) {
            if (empty($userData['role'])) {
                $errors[] = "Role is required";
            } elseif (!in_array($userData['role'], ['Councillor', 'Project In-Charge'])) {
                $errors[] = "Invalid role";
            }
        }
        
        if (!$isUpdate || isset($userData['password'])) {
            if (!$isUpdate && empty($userData['password'])) {
                $errors[] = "Password is required for new users";
            } elseif (!empty($userData['password']) && strlen($userData['password']) < 6) {
                $errors[] = "Password must be at least 6 characters";
            }
        }
        
        return $errors;
    }
}
?>
