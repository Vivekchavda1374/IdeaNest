<?php
require_once '../Login/Login/db.php';
require_once 'email_system.php';

class AutomatedMentorEmails {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function sendSessionReminders() {
        // Send reminders 24 hours before sessions
        $query = "SELECT ms.*, msp.mentor_id, msp.student_id, r.name as student_name
                  FROM mentoring_sessions ms
                  JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
                  JOIN register r ON msp.student_id = r.id
                  WHERE ms.status = 'scheduled' 
                  AND ms.session_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 25 HOUR)
                  AND ms.session_date > DATE_ADD(NOW(), INTERVAL 23 HOUR)
                  AND ms.reminder_sent = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($sessions as $session) {
            $email_system = new MentorEmailSystem($this->conn, $session['mentor_id']);
            
            $session_data = [
                'session_date' => $session['session_date'],
                'duration' => $session['duration'],
                'topic' => $session['topic'],
                'meeting_link' => $session['meeting_link']
            ];
            
            if ($email_system->sendSessionReminder($session['student_id'], $session_data)) {
                // Mark reminder as sent
                $update_query = "UPDATE mentoring_sessions SET reminder_sent = 1 WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bind_param("i", $session['id']);
                $update_stmt->execute();
                
                echo "Reminder sent for session ID: " . $session['id'] . "\n";
            }
        }
    }
    
    public function sendWeeklyProgressUpdates() {
        // Send weekly progress updates to active students
        $query = "SELECT msp.*, r.name as student_name, m.name as mentor_name
                  FROM mentor_student_pairs msp
                  JOIN register r ON msp.student_id = r.id
                  JOIN register m ON msp.mentor_id = m.id
                  WHERE msp.status = 'active'
                  AND (msp.last_progress_email IS NULL OR msp.last_progress_email < DATE_SUB(NOW(), INTERVAL 7 DAY))";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $pairs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($pairs as $pair) {
            $email_system = new MentorEmailSystem($this->conn, $pair['mentor_id']);
            
            // Calculate progress data
            $progress_data = $this->calculateStudentProgress($pair['student_id'], $pair['mentor_id']);
            
            if ($email_system->sendProgressUpdate($pair['student_id'], $progress_data)) {
                // Update last progress email timestamp
                $update_query = "UPDATE mentor_student_pairs SET last_progress_email = NOW() WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bind_param("i", $pair['id']);
                $update_stmt->execute();
                
                echo "Progress update sent to: " . $pair['student_name'] . "\n";
            }
        }
    }
    
    public function sendWelcomeToNewPairs() {
        // Send welcome emails to newly paired students
        $query = "SELECT msp.*, r.name as student_name
                  FROM mentor_student_pairs msp
                  JOIN register r ON msp.student_id = r.id
                  WHERE msp.status = 'active'
                  AND msp.welcome_sent = 0
                  AND msp.paired_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $pairs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($pairs as $pair) {
            $email_system = new MentorEmailSystem($this->conn, $pair['mentor_id']);
            
            if ($email_system->sendWelcomeMessage($pair['student_id'])) {
                // Mark welcome as sent
                $update_query = "UPDATE mentor_student_pairs SET welcome_sent = 1 WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bind_param("i", $pair['id']);
                $update_stmt->execute();
                
                echo "Welcome email sent to: " . $pair['student_name'] . "\n";
            }
        }
    }
    
    private function calculateStudentProgress($student_id, $mentor_id) {
        // Get student's projects and sessions
        $projects_query = "SELECT COUNT(*) as total_projects, 
                          SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_projects
                          FROM projects WHERE user_id = ?";
        $stmt = $this->conn->prepare($projects_query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $project_stats = $stmt->get_result()->fetch_assoc();
        
        $sessions_query = "SELECT COUNT(*) as total_sessions
                          FROM mentoring_sessions ms
                          JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
                          WHERE msp.student_id = ? AND msp.mentor_id = ? AND ms.status = 'completed'";
        $stmt = $this->conn->prepare($sessions_query);
        $stmt->bind_param("ii", $student_id, $mentor_id);
        $stmt->execute();
        $session_stats = $stmt->get_result()->fetch_assoc();
        
        // Calculate completion percentage based on projects and sessions
        $completion_percentage = min(100, ($project_stats['approved_projects'] * 20) + ($session_stats['total_sessions'] * 10));
        
        return [
            'completion_percentage' => $completion_percentage,
            'achievements' => [
                "Completed {$session_stats['total_sessions']} mentoring sessions",
                "Submitted {$project_stats['total_projects']} projects",
                "Got {$project_stats['approved_projects']} projects approved"
            ],
            'next_steps' => [
                "Continue working on current projects",
                "Schedule regular mentoring sessions",
                "Focus on project quality and documentation"
            ],
            'notes' => "Keep up the great work! Your progress is being tracked and you're doing well."
        ];
    }
}

// Run automated emails if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $automated_emails = new AutomatedMentorEmails($conn);
    
    echo "Starting automated mentor email system...\n";
    
    // Send session reminders
    echo "Sending session reminders...\n";
    $automated_emails->sendSessionReminders();
    
    // Send welcome emails to new pairs
    echo "Sending welcome emails...\n";
    $automated_emails->sendWelcomeToNewPairs();
    
    // Send weekly progress updates (only on Sundays)
    if (date('w') == 0) {
        echo "Sending weekly progress updates...\n";
        $automated_emails->sendWeeklyProgressUpdates();
    }
    
    echo "Automated email system completed.\n";
}
?>