<?php
require_once __DIR__ . '/../includes/security_init.php';
require_once '../Login/Login/db.php';
require_once '../includes/email_helper.php';

class SessionReminderSystem {
    
    private $conn;
    private $emailHelper;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->emailHelper = new EmailHelper();
    }
    
    public function sendUpcomingReminders() {
        $query = "SELECT 
            ms.*,
            r_mentor.name as mentor_name,
            r_mentor.email as mentor_email,
            r_student.name as student_name,
            r_student.email as student_email,
            p.project_name
            FROM mentoring_sessions ms
            JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
            JOIN register r_mentor ON msp.mentor_id = r_mentor.id
            JOIN register r_student ON msp.student_id = r_student.id
            LEFT JOIN projects p ON msp.project_id = p.id
            WHERE ms.status = 'scheduled'
            AND ms.session_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
            AND ms.reminder_sent = 0";
        
        $result = $this->conn->query($query);
        $sent = 0;
        
        while ($session = $result->fetch_assoc()) {
            $this->sendReminderEmail($session['mentor_email'], $session['mentor_name'], 'mentor', $session);
            $this->sendReminderEmail($session['student_email'], $session['student_name'], 'student', $session);
            
            $updateQuery = "UPDATE mentoring_sessions SET reminder_sent = 1 WHERE id = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bind_param("i", $session['id']);
            $stmt->execute();
            
            $sent++;
        }
        
        return $sent;
    }
    
    private function sendReminderEmail($email, $name, $role, $session) {
        $sessionDate = date('l, F j, Y', strtotime($session['session_date']));
        $sessionTime = date('g:i A', strtotime($session['session_date']));
        $duration = $session['duration_minutes'] ?? 60;
        $otherPerson = $role === 'mentor' ? $session['student_name'] : $session['mentor_name'];
        
        $subject = "Reminder: Mentoring Session Tomorrow at $sessionTime";
        
        $message = "<html><body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>🔔 Session Reminder</h2>
                <p>Hi $name,</p>
                <p>This is a friendly reminder about your upcoming mentoring session.</p>
                <div style='background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3>Session Details</h3>
                    <p><strong>Date:</strong> $sessionDate</p>
                    <p><strong>Time:</strong> $sessionTime</p>
                    <p><strong>Duration:</strong> $duration minutes</p>
                    <p><strong>" . ($role === 'mentor' ? 'Student' : 'Mentor') . ":</strong> $otherPerson</p>
                    " . (!empty($session['meeting_link']) ? "<p><a href='{$session['meeting_link']}' style='display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Join Meeting</a></p>" : "") . "
                </div>
                <p>See you soon!</p>
            </div>
        </body></html>";
        
        return $this->emailHelper->send($email, $subject, $message);
    }
    
    public function sendImmediateNotifications() {
        $query = "SELECT 
            ms.*,
            r_mentor.name as mentor_name,
            r_mentor.email as mentor_email,
            r_student.name as student_name,
            r_student.email as student_email
            FROM mentoring_sessions ms
            JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
            JOIN register r_mentor ON msp.mentor_id = r_mentor.id
            JOIN register r_student ON msp.student_id = r_student.id
            WHERE ms.status = 'scheduled'
            AND ms.session_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
            AND ms.immediate_reminder_sent = 0";
        
        $result = $this->conn->query($query);
        $sent = 0;
        
        while ($session = $result->fetch_assoc()) {
            $this->sendImmediateEmail($session['mentor_email'], $session['mentor_name'], 'mentor', $session);
            $this->sendImmediateEmail($session['student_email'], $session['student_name'], 'student', $session);
            
            $updateQuery = "UPDATE mentoring_sessions SET immediate_reminder_sent = 1 WHERE id = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bind_param("i", $session['id']);
            $stmt->execute();
            
            $sent++;
        }
        
        return $sent;
    }
    
    private function sendImmediateEmail($email, $name, $role, $session) {
        $sessionTime = date('g:i A', strtotime($session['session_date']));
        $otherPerson = $role === 'mentor' ? $session['student_name'] : $session['mentor_name'];
        
        $subject = "🚨 Your mentoring session starts in 1 hour!";
        
        $message = "<html><body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>Session Starting Soon!</h2>
                <p>Hi $name,</p>
                <p><strong>Your mentoring session with $otherPerson starts in approximately 1 hour at $sessionTime.</strong></p>
                " . (!empty($session['meeting_link']) ? "<p><a href='{$session['meeting_link']}' style='display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Join Meeting Now</a></p>" : "") . "
                <p>See you soon!</p>
            </div>
        </body></html>";
        
        return $this->emailHelper->send($email, $subject, $message);
    }
}

if (php_sapi_name() === 'cli' || (isset($argv) && $argv[0] === basename(__FILE__))) {
    $reminderSystem = new SessionReminderSystem($conn);
    
    echo "Sending 24-hour reminders...\n";
    $sent24h = $reminderSystem->sendUpcomingReminders();
    echo "Sent $sent24h reminders.\n";
    
    echo "Sending 1-hour notifications...\n";
    $sent1h = $reminderSystem->sendImmediateNotifications();
    echo "Sent $sent1h immediate notifications.\n";
    
    echo "Done!\n";
}
?>
