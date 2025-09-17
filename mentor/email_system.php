<?php
require_once '../vendor/autoload.php';
require_once '../Login/Login/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MentorEmailSystem {
    private $conn;
    private $mentor_id;
    
    public function __construct($db_connection, $mentor_id) {
        $this->conn = $db_connection;
        $this->mentor_id = $mentor_id;
    }
    
    private function setupMailer() {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ideanest.ict@gmail.com';
        $mail->Password = 'luou xlhs ojuw auvx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('ideanest.ict@gmail.com', 'IdeaNest Mentor System');
        return $mail;
    }
    
    public function sendWelcomeMessage($student_id) {
        try {
            $student = $this->getStudentInfo($student_id);
            $mentor = $this->getMentorInfo();
            
            $mail = $this->setupMailer();
            $mail->addAddress($student['email'], $student['name']);
            $mail->Subject = "Welcome to Your Mentoring Journey - IdeaNest";
            $mail->Body = $this->getWelcomeTemplate($student, $mentor);
            $mail->isHTML(true);
            
            $result = $mail->send();
            $this->logEmail('welcome_message', $student_id, $result ? 'sent' : 'failed');
            return $result;
        } catch (Exception $e) {
            $this->logEmail('welcome_message', $student_id, 'failed', $e->getMessage());
            return false;
        }
    }
    
    public function sendSessionInvitation($student_id, $session_data) {
        try {
            $student = $this->getStudentInfo($student_id);
            $mentor = $this->getMentorInfo();
            
            $mail = $this->setupMailer();
            $mail->addAddress($student['email'], $student['name']);
            $mail->Subject = "New Mentoring Session Scheduled - IdeaNest";
            $mail->Body = $this->getSessionInvitationTemplate($student, $mentor, $session_data);
            $mail->isHTML(true);
            
            $result = $mail->send();
            $this->logEmail('session_invitation', $student_id, $result ? 'sent' : 'failed');
            return $result;
        } catch (Exception $e) {
            $this->logEmail('session_invitation', $student_id, 'failed', $e->getMessage());
            return false;
        }
    }
    
    private function getStudentInfo($student_id) {
        $stmt = $this->conn->prepare("SELECT * FROM register WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function getMentorInfo() {
        $stmt = $this->conn->prepare("SELECT * FROM register WHERE id = ?");
        $stmt->bind_param("i", $this->mentor_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    private function logEmail($type, $recipient_id, $status, $error = null) {
        $stmt = $this->conn->prepare("INSERT INTO mentor_email_logs (mentor_id, recipient_id, email_type, status, error_message, sent_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisss", $this->mentor_id, $recipient_id, $type, $status, $error);
        $stmt->execute();
    }
    
    private function getWelcomeTemplate($student, $mentor) {
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
                    <a href='http://localhost/IdeaNest/user/dashboard.php' style='background: #8b5cf6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Get Started</a>
                </p>
                
                <p>Looking forward to working with you!<br>
                <strong>" . htmlspecialchars($mentor['name']) . "</strong></p>
            </div>
        </div>";
    }
    
    private function getSessionInvitationTemplate($student, $mentor, $session_data) {
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
                </div>
                
                <p>See you soon!<br>
                <strong>" . htmlspecialchars($mentor['name']) . "</strong></p>
            </div>
        </div>";
    }
}
?>