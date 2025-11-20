<?php
/**
 * Email Logger
 * Logs all email attempts for debugging and monitoring
 */

class EmailLogger {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Log email attempt
     */
    public function logEmail($recipient, $subject, $email_type, $status, $error_message = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO email_logs 
            (recipient_email, subject, email_type, status, error_message, sent_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $sent_at = ($status === 'sent') ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param("ssssss", $recipient, $subject, $email_type, $status, $error_message, $sent_at);
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Get recent email logs
     */
    public function getRecentLogs($limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT * FROM email_logs 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        $stmt->close();
        return $logs;
    }
    
    /**
     * Get failed emails
     */
    public function getFailedEmails($limit = 20) {
        $stmt = $this->conn->prepare("
            SELECT * FROM email_logs 
            WHERE status = 'failed'
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        $stmt->close();
        return $logs;
    }
    
    /**
     * Get email statistics
     */
    public function getStats($days = 7) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM email_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = $result->fetch_assoc();
        $stmt->close();
        
        return $stats;
    }
}
