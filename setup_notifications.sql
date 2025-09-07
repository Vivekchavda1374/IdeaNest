-- Add email notification settings to user table
ALTER TABLE register ADD COLUMN email_notifications TINYINT(1) DEFAULT 1;
ALTER TABLE register ADD COLUMN last_notification_sent DATETIME DEFAULT NULL;

-- Create notification log table
CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    projects_count INT DEFAULT 0,
    ideas_count INT DEFAULT 0,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    FOREIGN KEY (user_id) REFERENCES register(id) ON DELETE CASCADE
);