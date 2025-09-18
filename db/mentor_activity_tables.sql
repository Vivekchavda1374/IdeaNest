-- Create mentor activity logs table
CREATE TABLE IF NOT EXISTS mentor_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    student_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES register(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES register(id) ON DELETE SET NULL,
    INDEX idx_mentor_activity (mentor_id, created_at),
    INDEX idx_student_activity (student_id, created_at)
);

-- Insert sample data for testing
INSERT INTO mentor_activity_logs (mentor_id, activity_type, description, student_id) VALUES
(1, 'session_scheduled', 'Scheduled session for Jan 15, 2025 2:00 PM', 2),
(1, 'email_sent', 'Sent email: Project Review Feedback', 2),
(1, 'request_accepted', 'Accepted mentorship request', 3);