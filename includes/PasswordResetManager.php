<?php
/**
 * Password Reset Manager
 * Handles password reset functionality including token generation, email sending, and validation
 */

require_once 'DatabaseManager.php';
require_once 'log_activity.php';
require_once 'logger.inc.php';
require_once 'EnhancedEmailManager.php';

class PasswordResetManager {
    private $pdo;
    private $tokenExpiryHours = 24; // Token expires in 24 hours
    
    public function __construct() {
        $this->pdo = getDatabaseConnection();
    }
    
    /**
     * Generate a password reset token for a user
     */
    public function generateResetToken($email) {
        try {
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT id, full_name FROM ssmntUsers WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'No account found with this email address.'];
            }
            
            // Generate a secure random token
            $token = bin2hex(random_bytes(32));
            
            // Set expiry time
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->tokenExpiryHours} hours"));
            
            // Delete any existing tokens for this user
            $stmt = $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user['id']]);
            
            // Insert new token
            $stmt = $this->pdo->prepare("
                INSERT INTO password_reset_tokens (user_id, token, email, expires_at) 
                VALUES (:user_id, :token, :email, :expires_at)
            ");
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token' => $token,
                ':email' => $email,
                ':expires_at' => $expiresAt
            ]);
            
            // Send reset email
            $emailSent = $this->sendResetEmail($email, $user['full_name'], $token);
            
            if ($emailSent) {
                logActivityToDatabase($user['id'], 'Password Reset Request', 'success', 'Password reset token generated');
                logActivityToFile("Password reset token generated for user ID {$user['id']} ({$email})", "info");
                return ['success' => true, 'message' => 'Password reset instructions have been sent to your email.'];
            } else {
                return ['success' => false, 'message' => 'Failed to send reset email. Please try again.'];
            }
            
        } catch (PDOException $e) {
            logActivityToFile("Database error in generateResetToken: " . $e->getMessage(), "error");
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    /**
     * Validate a password reset token
     */
    public function validateToken($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT prt.*, u.full_name, u.email 
                FROM password_reset_tokens prt 
                JOIN ssmntUsers u ON prt.user_id = u.id 
                WHERE prt.token = :token AND prt.used = 0 AND prt.expires_at > NOW()
            ");
            $stmt->execute([':token' => $token]);
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tokenData) {
                return ['valid' => false, 'message' => 'Invalid or expired reset token.'];
            }
            
            return ['valid' => true, 'user' => $tokenData];
            
        } catch (PDOException $e) {
            logActivityToFile("Database error in validateToken: " . $e->getMessage(), "error");
            return ['valid' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    /**
     * Reset password using a valid token
     */
    public function resetPassword($token, $newPassword) {
        try {
            // Validate token first
            $validation = $this->validateToken($token);
            if (!$validation['valid']) {
                return $validation;
            }
            
            $user = $validation['user'];
            
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update user password
            $stmt = $this->pdo->prepare("UPDATE ssmntUsers SET password = :password WHERE id = :id");
            $stmt->execute([':password' => $hashedPassword, ':id' => $user['user_id']]);
            
            // Mark token as used
            $stmt = $this->pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = :token");
            $stmt->execute([':token' => $token]);
            
            // Log the activity
            logActivityToDatabase($user['user_id'], 'Password Reset', 'success', 'Password reset completed successfully');
            logActivityToFile("Password reset completed for user ID {$user['user_id']} ({$user['email']})", "info");
            
            return ['success' => true, 'message' => 'Password has been reset successfully.'];
            
        } catch (PDOException $e) {
            logActivityToFile("Database error in resetPassword: " . $e->getMessage(), "error");
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    /**
     * Send password reset email
     */
    private function sendResetEmail($email, $fullName, $token) {
        try {
            $resetLink = $this->getResetLink($token);
            
            $subject = "Password Reset Request - Assessment System";
            $message = $this->getEmailTemplate($fullName, $resetLink);
            
            // Use EnhancedEmailManager to send the email
            $emailManager = new EnhancedEmailManager();
            $mailSent = $emailManager->sendEmail($email, $subject, $message, true);
            
            if (!$mailSent) {
                // Log the failure but don't expose it to the user
                logActivityToFile("Failed to send password reset email to {$email}", "warning");
            }
            
            return $mailSent;
            
        } catch (Exception $e) {
            logActivityToFile("Error sending password reset email: " . $e->getMessage(), "error");
            return false;
        }
    }
    
    /**
     * Get the password reset link
     */
    private function getResetLink($token) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // For production, use the correct path
        if ($host === 'assessments.salcompassion.org') {
            return "{$protocol}://{$host}/View/reset_password_self.php?token={$token}";
        } else {
            // For local development - go up one level from Controller directory
            $currentPath = $_SERVER['REQUEST_URI'];
            $path = dirname(dirname($currentPath));
            return "{$protocol}://{$host}{$path}/View/reset_password_self.php?token={$token}";
        }
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($fullName, $resetLink) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset Request</h2>
                </div>
                <div class='content'>
                    <p>Dear {$fullName},</p>
                    <p>We received a request to reset your password for the Assessment System. If you didn't make this request, you can safely ignore this email.</p>
                    <p>To reset your password, click the button below:</p>
                    <p style='text-align: center;'>
                        <a href='{$resetLink}' class='button'>Reset Password</a>
                    </p>
                    <p>This link will expire in {$this->tokenExpiryHours} hours.</p>
                    <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                    <p>{$resetLink}</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the Assessment System. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    

    
    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens() {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM password_reset_tokens WHERE expires_at < NOW()");
            $stmt->execute();
            
            $deletedCount = $stmt->rowCount();
            if ($deletedCount > 0) {
                logActivityToFile("Cleaned up {$deletedCount} expired password reset tokens", "info");
            }
            
        } catch (PDOException $e) {
            logActivityToFile("Error cleaning up expired tokens: " . $e->getMessage(), "error");
        }
    }
}
?>
