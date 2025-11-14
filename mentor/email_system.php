<?php

require_once dirname(__DIR__) . "/includes/autoload_simple.php";

require_once '../Login/Login/db.php';


class MentorEmailSystem
{
    private $conn;
    private $mentor_id;

    public function __construct($db_connection, $mentor_id)
    {
        $this->conn = $db_connection;
        $this->mentor_id = $mentor_id;
    }

    private function setupMailer()
    {
        try {
            // Get SMTP settings from database
            $smtp_query = "SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure', 'from_email')";
            $smtp_result = $this->conn->query($smtp_query);
            $smtp_settings = [];
            if ($smtp_result) {
                while ($row = $smtp_result->fetch_assoc()) {
                    $smtp_settings[$row['setting_key']] = $row['setting_value'];
                }
            }

            return new SMTPMailer();
        } catch (Exception $e) {
            throw new Exception('Email configuration error');
        }
    }

    public function sendWelcomeMessage($student_id)
    {
        try {
            $student = $this->getStudentInfo($student_id);
            $mentor = $this->getMentorInfo();

            $mail = $this->setupMailer();
            
            $subject = "Welcome to Your Mentoring Journey - IdeaNest";
            $body = $this->getWelcomeTemplate($student, $mentor);
            
            $result = $mail->send($student['email'], $subject, $body);
            $this->logEmail('welcome_message', $student_id, $result ? 'sent' : 'failed');
            return $result;
        } catch (Exception $e) {
            $this->logEmail('welcome_message', $student_id, 'failed', $e->getMessage());
            return false;
        }
    }

    public function sendSessionInvitation($student_id, $session_data)
    {
        try {
            $student = $this->getStudentInfo($student_id);
            $mentor = $this->getMentorInfo();

            $mail = $this->setupMailer();
            
            $subject = "New Mentoring Session Scheduled - IdeaNest";
            $body = $this->getSessionInvitationTemplate($student, $mentor, $session_data);
            
            $result = $mail->send($student['email'], $subject, $body);
            $this->logEmail('session_invitation', $student_id, $result ? 'sent' : 'failed');
            return $result;
        } catch (Exception $e) {
            $this->logEmail('session_invitation', $student_id, 'failed', $e->getMessage());
            return false;
        }
    }

    public function sendProjectFeedback($student_id, $feedback_data)
    {
        try {
            $student = $this->getStudentInfo($student_id);
            $mentor = $this->getMentorInfo();

            $mail = $this->setupMailer();
            
            $subject = "Project Feedback from Your Mentor - IdeaNest";
            $body = $this->getProjectFeedbackTemplate($student, $mentor, $feedback_data);
            
            $result = $mail->send($student['email'], $subject, $body);
            $this->logEmail('project_feedback', $student_id, $result ? 'sent' : 'failed');
            return $result;
        } catch (Exception $e) {
            $this->logEmail('project_feedback', $student_id, 'failed', $e->getMessage());
            return false;
        }
    }

    public function sendProgressUpdate($student_id, $progress_data)
    {
        try {
            $student = $this->getStudentInfo($student_id);
            $mentor = $this->getMentorInfo();

            $mail = $this->setupMailer();
            
            $subject = "Progress Update from Your Mentor - IdeaNest";
            $body = $this->getProgressUpdateTemplate($student, $mentor, $progress_data);
            
            $result = $mail->send($student['email'], $subject, $body);
            $this->logEmail('progress_update', $student_id, $result ? 'sent' : 'failed');
            return $result;
        } catch (Exception $e) {
            $this->logEmail('progress_update', $student_id, 'failed', $e->getMessage());
            return false;
        }
    }

    private function getStudentInfo($student_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM register WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function getMentorInfo()
    {
        $stmt = $this->conn->prepare("SELECT * FROM register WHERE id = ?");
        $stmt->bind_param("i", $this->mentor_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function logEmail($type, $recipient_id, $status, $error = null)
    {
        try {
            // Check if table exists first
            $table_check = $this->conn->query("SHOW TABLES LIKE 'mentor_email_logs'");
            if ($table_check && $table_check->num_rows > 0) {
                $stmt = $this->conn->prepare("INSERT INTO mentor_email_logs (mentor_id, recipient_id, email_type, status, error_message, sent_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("iisss", $this->mentor_id, $recipient_id, $type, $status, $error);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log('Email logging error: ' . $e->getMessage());
        }
    }

    private function getWelcomeTemplate($student, $mentor)
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1>🎉 Welcome to Your Mentoring Journey!</h1>
                <p>IdeaNest Mentor System</p>
            </div>
            <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;'>
                <h2>Hello " . htmlspecialchars($student['name']) . "!</h2>
                <p>Welcome to IdeaNest! I'm <strong>" . htmlspecialchars($mentor['name']) . "</strong>, and I'm excited to be your mentor.</p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #8b5cf6;'>
                    <h3>👨🏫 Your Mentor</h3>
                    <p><strong>Name:</strong> " . htmlspecialchars($mentor['name']) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($mentor['email']) . "</p>
                </div>
                
                <p style='text-align: center; margin: 30px 0;'>

                    <a href='https://ictmu.in/hcd/IdeaNest/user/dashboard.php' style='background: #8b5cf6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Get Started</a>

                </p>
                
                <p>Looking forward to working with you!<br>
                <strong>" . htmlspecialchars($mentor['name']) . "</strong></p>
            </div>
        </div>";
    }

    private function getSessionInvitationTemplate($student, $mentor, $session_data)
    {
        $meeting_link = !empty($session_data['meeting_link']) ? 
            "<p><strong>Meeting Link:</strong> <a href='" . htmlspecialchars($session_data['meeting_link']) . "'>" . htmlspecialchars($session_data['meeting_link']) . "</a></p>" : '';
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1>🎓 New Mentoring Session</h1>
                <p>You've been invited to a mentoring session</p>
            </div>
            <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;'>
                <h2>Hello " . htmlspecialchars($student['name']) . "!</h2>
                <p>Your mentor <strong>" . htmlspecialchars($mentor['name']) . "</strong> has scheduled a session with you.</p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;'>
                    <h3>📅 Session Details</h3>
                    <p><strong>Date:</strong> " . date('F j, Y', strtotime($session_data['session_date'])) . "</p>
                    <p><strong>Time:</strong> " . date('g:i A', strtotime($session_data['session_date'])) . "</p>
                    <p><strong>Topic:</strong> " . htmlspecialchars($session_data['topic'] ?? 'General Mentoring') . "</p>
                    " . $meeting_link . "
                </div>
                
                <p>See you soon!<br>
                <strong>" . htmlspecialchars($mentor['name']) . "</strong></p>
            </div>
        </div>";
    }

    private function getProjectFeedbackTemplate($student, $mentor, $feedback_data)
    {
        $rating_stars = '';
        if ($feedback_data['rating'] > 0) {
            $rating_stars = str_repeat('⭐', $feedback_data['rating']) . ' (' . $feedback_data['rating'] . '/5)';
        }
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1>📝 Project Feedback</h1>
                <p>Your mentor has provided feedback on your project</p>
            </div>
            <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;'>
                <h2>Hello " . htmlspecialchars($student['name']) . "!</h2>
                <p>Your mentor <strong>" . htmlspecialchars($mentor['name']) . "</strong> has reviewed your project and provided feedback.</p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>
                    <h3>💬 Feedback</h3>
                    <p>" . nl2br(htmlspecialchars($feedback_data['feedback_message'])) . "</p>
                    " . ($rating_stars ? "<p><strong>Rating:</strong> " . $rating_stars . "</p>" : '') . "
                </div>
                
                <p>Keep up the great work!<br>
                <strong>" . htmlspecialchars($mentor['name']) . "</strong></p>
            </div>
        </div>";
    }

    private function getProgressUpdateTemplate($student, $mentor, $progress_data)
    {
        $achievements = !empty($progress_data['achievements']) ? 
            "<h4>🎯 Achievements:</h4><ul><li>" . implode('</li><li>', array_map('htmlspecialchars', explode("\n", $progress_data['achievements']))) . "</li></ul>" : '';
        
        $next_steps = !empty($progress_data['next_steps']) ? 
            "<h4>📋 Next Steps:</h4><ul><li>" . implode('</li><li>', array_map('htmlspecialchars', explode("\n", $progress_data['next_steps']))) . "</li></ul>" : '';
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1>📊 Progress Update</h1>
                <p>Your mentor has shared a progress update</p>
            </div>
            <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;'>
                <h2>Hello " . htmlspecialchars($student['name']) . "!</h2>
                <p>Your mentor <strong>" . htmlspecialchars($mentor['name']) . "</strong> has provided an update on your progress.</p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
                    <h3>📈 Progress Report</h3>
                    <p><strong>Completion:</strong> " . $progress_data['completion_percentage'] . "%</p>
                    <div style='background: #e9ecef; border-radius: 10px; height: 20px; margin: 10px 0;'>
                        <div style='background: #ffc107; height: 20px; width: " . $progress_data['completion_percentage'] . "%; border-radius: 10px;'></div>
                    </div>
                    " . $achievements . "
                    " . $next_steps . "
                </div>
                
                <p>Keep up the excellent work!<br>
                <strong>" . htmlspecialchars($mentor['name']) . "</strong></p>
            </div>
        </div>";
    }
}
