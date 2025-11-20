<?php
/**
 * Credential Manager
 * Handles secure storage and retrieval of auto-generated credentials
 */

class CredentialManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Store credentials temporarily with email status
     */
    public function storeCredentials($user_type, $user_id, $email, $plain_password, $email_sent = false) {
        $stmt = $this->conn->prepare("
            INSERT INTO temp_credentials 
            (user_type, user_id, email, plain_password, email_sent, email_sent_at, email_attempts) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        $sent_at = $email_sent ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param("sisssi", $user_type, $user_id, $email, $plain_password, $email_sent, $sent_at);
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Update email status after sending attempt
     */
    public function updateEmailStatus($user_type, $user_id, $success, $error_message = null) {
        $stmt = $this->conn->prepare("
            UPDATE temp_credentials 
            SET email_sent = ?, 
                email_sent_at = IF(? = 1, NOW(), email_sent_at),
                email_attempts = email_attempts + 1,
                last_attempt_at = NOW(),
                error_message = ?
            WHERE user_type = ? AND user_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        
        $stmt->bind_param("iissi", $success, $success, $error_message, $user_type, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get credentials for a user (for admin recovery)
     */
    public function getCredentials($user_type, $user_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM temp_credentials 
            WHERE user_type = ? AND user_id = ?
            AND expires_at > NOW()
            ORDER BY created_at DESC
            LIMIT 1
        ");
        
        $stmt->bind_param("si", $user_type, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $credentials = $result->fetch_assoc();
        $stmt->close();
        
        return $credentials;
    }
    
    /**
     * Get all unsent credentials for retry
     */
    public function getUnsentCredentials($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM temp_credentials 
            WHERE email_sent = 0 
            AND email_attempts < 5
            AND expires_at > NOW()
            ORDER BY created_at ASC
            LIMIT ?
        ");
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $credentials = [];
        while ($row = $result->fetch_assoc()) {
            $credentials[] = $row;
        }
        
        $stmt->close();
        return $credentials;
    }
    
    /**
     * Clean up expired credentials
     */
    public function cleanupExpired() {
        $result = $this->conn->query("DELETE FROM temp_credentials WHERE expires_at < NOW()");
        return $this->conn->affected_rows;
    }
    
    /**
     * Resend credentials email
     */
    public function resendCredentials($credential_id) {
        $stmt = $this->conn->prepare("SELECT * FROM temp_credentials WHERE id = ?");
        $stmt->bind_param("i", $credential_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cred = $result->fetch_assoc();
        $stmt->close();
        
        if (!$cred) {
            return ['success' => false, 'message' => 'Credentials not found'];
        }
        
        // Send email
        require_once __DIR__ . '/smtp_mailer.php';
        $mailer = new SMTPMailer();
        
        $subject = $cred['user_type'] === 'mentor' 
            ? 'Welcome to IdeaNest - Mentor Account Created'
            : 'Welcome to IdeaNest - Subadmin Access';
            
        $body = $this->getEmailTemplate($cred['user_type'], $cred['email'], $cred['plain_password']);
        
        $sent = $mailer->send($cred['email'], $subject, $body);
        
        // Update status
        $this->updateEmailStatus($cred['user_type'], $cred['user_id'], $sent, $sent ? null : 'Resend failed');
        
        return [
            'success' => $sent,
            'message' => $sent ? 'Email sent successfully' : 'Failed to send email'
        ];
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($user_type, $email, $password) {
        $role = ucfirst($user_type);
        $dashboard_url = $user_type === 'mentor' 
            ? getBaseUrl('mentor/dashboard.php')
            : getBaseUrl('Admin/subadmin/subadmin_dashboard.php');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .credentials { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to IdeaNest!</h1>
                    <p>Your {$role} Account Has Been Created</p>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>Your {$role} account has been successfully created on IdeaNest platform.</p>
                    
                    <div class='credentials'>
                        <h3>Your Login Credentials:</h3>
                        <p><strong>Email:</strong> {$email}</p>
                        <p><strong>Password:</strong> <code style='background: #f0f0f0; padding: 5px 10px; border-radius: 3px; font-size: 16px;'>{$password}</code></p>
                    </div>
                    
                    <p><strong>Important:</strong> Please change your password immediately after your first login for security purposes.</p>
                    
                    <div style='text-align: center;'>
                        <a href='{$dashboard_url}' class='button'>Login to Dashboard</a>
                    </div>
                    
                    <p>If you have any questions or need assistance, please contact the administrator.</p>
                    
                    <div class='footer'>
                        <p>This is an automated email. Please do not reply to this message.</p>
                        <p>&copy; " . date('Y') . " IdeaNest. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

// Helper function to get base URL
function getBaseUrl($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = $protocol . '://' . $host;
    
    // Remove script name from path if present
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    if ($script_dir !== '/') {
        $base .= $script_dir;
    }
    
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
