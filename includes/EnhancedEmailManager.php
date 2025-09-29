<?php
/**
 * Enhanced Email Manager using PHPMailer
 * Handles email sending using SMTP configuration with PHPMailer
 */

class EnhancedEmailManager {
    private $smtpHost = 'smtp.hostinger.com';
    private $smtpPort = 465;
    private $smtpUsername = 'assessments@salcompassion.org';
    private $smtpPassword = 'sybgYf9qeqgosycdos!';
    private $smtpEncryption = 'ssl';
    private $fromEmail = 'assessments@salcompassion.org';
    private $fromName = 'Assessment System';
    private $phpmailerAvailable = false;
    
    public function __construct() {
        // Check if PHPMailer is available
        if (file_exists('../vendor/autoload.php')) {
            require_once '../vendor/autoload.php';
            $this->phpmailerAvailable = class_exists('PHPMailer\PHPMailer\PHPMailer');
        }
    }
    
    /**
     * Send email using PHPMailer if available, otherwise fallback to mail()
     */
    public function sendEmail($to, $subject, $message, $isHtml = true) {
        // Try PHPMailer first if available
        if ($this->phpmailerAvailable) {
            return $this->sendEmailWithPHPMailer($to, $subject, $message, $isHtml);
        } else {
            // Fallback to basic mail() function
            return $this->sendEmailWithMail($to, $subject, $message, $isHtml);
        }
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendEmailWithPHPMailer($to, $subject, $message, $isHtml = true) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->smtpPort;
            
            // Recipients
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($this->fromEmail, $this->fromName);
            
            // Content
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            if (!$isHtml) {
                $mail->AltBody = strip_tags($message);
            }
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send email using PHP's built-in mail function
     */
    private function sendEmailWithMail($to, $subject, $message, $isHtml = true) {
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
     * Get email headers for mail() function
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
     * Test email configuration
     */
    public function testEmailConfiguration($testEmail = null) {
        if (!$testEmail) {
            $testEmail = 'test@example.com';
        }
        
        $testSubject = 'Test Email from Assessment System';
        $testMessage = $this->getTestEmailTemplate();
        
        $result = $this->sendEmail($testEmail, $testSubject, $testMessage, true);
        
        return [
            'success' => $result,
            'message' => $result ? 'Email configuration is working.' : 'Email configuration failed.',
            'method' => class_exists('PHPMailer\PHPMailer\PHPMailer') ? 'PHPMailer' : 'mail()'
        ];
    }
    
    /**
     * Get test email template
     */
    private function getTestEmailTemplate() {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Test Email</h2>
                </div>
                <div class='content'>
                    <p>This is a test email to verify the email configuration for the Assessment System.</p>
                    <p>If you received this email, the email system is working correctly.</p>
                    <p><strong>Configuration Details:</strong></p>
                    <ul>
                        <li>SMTP Host: {$this->smtpHost}</li>
                        <li>SMTP Port: {$this->smtpPort}</li>
                        <li>Encryption: {$this->smtpEncryption}</li>
                        <li>From Email: {$this->fromEmail}</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>This is an automated test message from the Assessment System.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
