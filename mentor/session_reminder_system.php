<?php
require_once __DIR__ . '/includes/security_init.php';
/**
 * Session Reminder System
 * Automated email reminders for upcoming mentoring sessions
 */

require_once '../Login/Login/db.php';
require_once '../includes/email_helper.php';

class SessionReminderSystem {
    
    private $conn;
    private $emailHelper;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->emailHelper = new EmailHelper();
    }
    
    /**
     * Send reminders for upcoming sessions
     * Should be run via cron job
     */
    public function sendUpcomingReminders() {
        // Get sessions happening in the next 24 hours
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
            // Send reminder to mentor
            $this->sendReminderEmail(
                $session['mentor_email'],
                $session['mentor_name'],
                'mentor',
                $session
            );
            
            // Send reminder to student
            $this->sendReminderEmail(
                $session['student_email'],
                $session['student_name'],
                'student',
                $session
            );
            
            // Mark reminder as sent
            $updateQuery = "UPDATE mentoring_sessions SET reminder_sent = 1 WHERE id = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bind_param("i", $session['id']);
            $stmt->execute();
            
            $sent++;
        }
        
        return $sent;
    }
    
    /**
     * Send reminder email
     */
    private function sendReminderEmail($email, $name, $role, $session) {
        $sessionDate = date('l, F j, Y', strtotime($session['session_date']));
        $sessionTime = date('g:i A', strtotime($session['session_date']));
        $duration = $session['duration_minutes'] ?? 60;
        
        $otherPerson = $role === 'mentor' ? $session['student_name'] : $session['mentor_name'];
        
        $subject = "Reminder: Mentoring Session Tomorrow at $sessionTime";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .session-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { margin: 10px 0; }
                .label { font-weight: bold; color: #667eea; }
                .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
            </style>
            <link rel="stylesheet" href="assets/css/loader.css">
    <link rel="stylesheet" href="assets/css/loading.css">
</head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔔 Session Reminder</h2>
                </div>
                <div class='content'>
                    <p>Hi $name,</p>
                    <p>This is a friendly reminder about your upcoming mentoring session.</p>
                    
                    <div class='session-details'>
                        <h3>Session Details</h3>
                        <div class='detail-row'>
                            <span class='label'>Date:</span> $sessionDate
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Time:</span> $sessionTime
                        </div>
                        <div class='detail-row'>
                            <span class='label'>Duration:</span> $duration minutes
                        </div>
                        <div class='detail-row'>
                            <span class='label'>" . ($role === 'mentor' ? 'Student' : 'Mentor') . ":</span> $otherPerson
                        </div>
                        " . (!empty($session['project_name']) ? "
                        <div class='detail-row'>
                            <span class='label'>Project:</span> {$session['project_name']}
                        </div>
                        " : "") . "
                        " . (!empty($session['meeting_link']) ? "
                        <div class='detail-row'>
                            <span class='label'>Meeting Link:</span> <a href='{$session['meeting_link']}'>{$session['meeting_link']}</a>
                        </div>
                        " : "") . "
                        " . (!empty($session['notes']) ? "
                        <div class='detail-row'>
                            <span class='label'>Notes:</span> {$session['notes']}
                        </div>
                        " : "") . "
                    </div>
                    
                    <p>Please make sure you're prepared and available at the scheduled time.</p>
                    
                    " . (!empty($session['meeting_link']) ? "
                    <a href='{$session['meeting_link']}' class='button'>Join Meeting</a>
                    " : "") . "
                    
                    <p>If you need to reschedule, please contact the other party as soon as possible.</p>
                    
                    <div class='footer'>
                        <p>This is an automated reminder from IdeaNest</p>
                        <p>© " . date('Y') . " IdeaNest. All rights reserved.</p>
                    </div>
                </div>
            </div>
        
<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="assets/js/loader.js"></script>
<script src="assets/js/loading.js"></script>
</body>
        </html>
        ";
        
        return $this->emailHelper->send($email, $subject, $message);
    }
    
    /**
     * Send immediate session notification (for sessions starting in 1 hour)
     */
    public function sendImmediateNotifications() {
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
            AND ms.session_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
            AND ms.immediate_reminder_sent = 0";
        
        $result = $this->conn->query($query);
        $sent = 0;
        
        while ($session = $result->fetch_assoc()) {
            // Send immediate notification
            $this->sendImmediateEmail($session['mentor_email'], $session['mentor_name'], 'mentor', $session);
            $this->sendImmediateEmail($session['student_email'], $session['student_name'], 'student', $session);
            
            // Mark as sent
            $updateQuery = "UPDATE mentoring_sessions SET immediate_reminder_sent = 1 WHERE id = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bind_param("i", $session['id']);
            $stmt->execute();
            
            $sent++;
        }
        
        return $sent;
    }
    
    /**
     * Send immediate notification email
     */
    private function sendImmediateEmail($email, $name, $role, $session) {
        $sessionTime = date('g:i A', strtotime($session['session_date']));
        $otherPerson = $role === 'mentor' ? $session['student_name'] : $session['mentor_name'];
        
        $subject = "🚨 Your mentoring session starts in 1 hour!";
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #667eea;'>Session Starting Soon!</h2>
                <p>Hi $name,</p>
                <p><strong>Your mentoring session with $otherPerson starts in approximately 1 hour at $sessionTime.</strong></p>
                " . (!empty($session['meeting_link']) ? "
                <p><a href='{$session['meeting_link']}' style='display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Join Meeting Now</a></p>
                " : "") . "
                <p>See you soon!</p>
            </div>
        
<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="assets/js/loader.js"></script>
<script src="assets/js/loading.js"></script>
</body>
        </html>
        ";
        
        return $this->emailHelper->send($email, $subject, $message);
    }
}

// If run directly (via cron), execute reminders
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
