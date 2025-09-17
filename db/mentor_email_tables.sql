-- Create mentor email logs table
CREATE TABLE IF NOT EXISTS mentor_email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    recipient_id INT NOT NULL,
    email_type ENUM('welcome_message', 'session_invitation', 'session_reminder', 'project_feedback', 'progress_update') NOT NULL,
    status ENUM('sent', 'failed') NOT NULL,
    error_message TEXT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mentor_id (mentor_id),
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_sent_at (sent_at),
    FOREIGN KEY (mentor_id) REFERENCES register(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES register(id) ON DELETE CASCADE
);

-- Add email tracking columns to mentor_student_pairs table
ALTER TABLE mentor_student_pairs 
ADD COLUMN IF NOT EXISTS welcome_sent TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_progress_email TIMESTAMP NULL;

-- Add reminder tracking to mentoring_sessions table
ALTER TABLE mentoring_sessions 
ADD COLUMN IF NOT EXISTS reminder_sent TINYINT(1) DEFAULT 0;

-- Create email preferences table for students
CREATE TABLE IF NOT EXISTS student_email_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL UNIQUE,
    receive_session_reminders TINYINT(1) DEFAULT 1,
    receive_progress_updates TINYINT(1) DEFAULT 1,
    receive_project_feedback TINYINT(1) DEFAULT 1,
    receive_welcome_emails TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES register(id) ON DELETE CASCADE
);

-- Create email templates table for customization
CREATE TABLE IF NOT EXISTS mentor_email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    template_type ENUM('welcome_message', 'session_invitation', 'session_reminder', 'project_feedback', 'progress_update') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    template_content TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mentor_template (mentor_id, template_type),
    FOREIGN KEY (mentor_id) REFERENCES register(id) ON DELETE CASCADE
);

-- Insert default email preferences for existing students
INSERT IGNORE INTO student_email_preferences (student_id)
SELECT id FROM register WHERE role = 'student';

-- Create email queue table for batch processing
CREATE TABLE IF NOT EXISTS mentor_email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    recipient_id INT NOT NULL,
    email_type ENUM('welcome_message', 'session_invitation', 'session_reminder', 'project_feedback', 'progress_update') NOT NULL,
    email_data JSON NOT NULL,
    priority TINYINT DEFAULT 5,
    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_priority (status, priority),
    INDEX idx_scheduled_at (scheduled_at),
    FOREIGN KEY (mentor_id) REFERENCES register(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES register(id) ON DELETE CASCADE
);

-- Create email statistics table
CREATE TABLE IF NOT EXISTS mentor_email_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    date DATE NOT NULL,
    emails_sent INT DEFAULT 0,
    emails_failed INT DEFAULT 0,
    welcome_emails INT DEFAULT 0,
    session_invitations INT DEFAULT 0,
    session_reminders INT DEFAULT 0,
    project_feedback INT DEFAULT 0,
    progress_updates INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mentor_date (mentor_id, date),
    FOREIGN KEY (mentor_id) REFERENCES register(id) ON DELETE CASCADE
);