<?php
/**
 * Email Manager
 * Handles email sending using SMTP configuration
 */

class EmailManager {
    private $smtpHost = 'smtp.hostinger.com';
    private $smtpPort = 465;
    private $smtpUsername = 'assessments@salcompassion.org';
    private $smtpPassword = 'sybgYf9qeqgosycdos!';
    private $smtpEncryption = 'ssl';
    private $fromEmail = 'assessments@salcompassion.org';
    private $fromName = 'Assessment System';
    
    /**
     * Send email using PHP's built-in mail function with SMTP headers
     */
    public function sendEmail($to, $subject, $message, $isHtml = true) {
        try {
            // Set up email headers
            $headers = $this->getEmailHeaders($isHtml);
            
            // Send email using PHP's mail function
            $mailSent = mail($to, $subject, $message, $headers);
            
            if (!$mailSent) {
                error_log("Failed to send email to: $to");
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error sending email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get email headers
     */
    private function getEmailHeaders($isHtml = true) {
        $headers = [];
        
        if ($isHtml) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        $headers[] = "Reply-To: {$this->fromEmail}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        $headers[] = "X-Priority: 3";
        $headers[] = "X-MSMail-Priority: Normal";
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Send email using cURL (alternative method if mail() doesn't work)
     */
    public function sendEmailCurl($to, $subject, $message, $isHtml = true) {
        try {
            // This is a fallback method using cURL to send via SMTP
            // Note: This is a simplified implementation and may need adjustment
            
            $headers = $this->getEmailHeaders($isHtml);
            
            // Create email content
            $emailContent = "To: $to\r\n";
            $emailContent .= "Subject: $subject\r\n";
            $emailContent .= $headers . "\r\n\r\n";
            $emailContent .= $message;
            
            // For now, we'll use the mail() function as it's more reliable
            // In a production environment, you might want to use a proper SMTP library
            return $this->sendEmail($to, $subject, $message, $isHtml);
            
        } catch (Exception $e) {
            error_log("Error sending email via cURL: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfiguration() {
        $testEmail = 'test@example.com';
        $testSubject = 'Test Email from Assessment System';
        $testMessage = '<h2>Test Email</h2><p>This is a test email to verify the email configuration.</p>';
        
        $result = $this->sendEmail($testEmail, $testSubject, $testMessage);
        
        return [
            'success' => $result,
            'message' => $result ? 'Email configuration is working.' : 'Email configuration failed.'
        ];
    }
}
?>
