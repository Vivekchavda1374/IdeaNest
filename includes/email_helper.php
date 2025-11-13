<?php

require_once __DIR__ . '/smtp_mailer.php';

class EmailHelper {
    private $mailer;
    
    public function __construct() {
        $this->mailer = new SMTPMailer();
    }
    
    public function sendWelcomeEmail($to, $name) {
        $subject = "Welcome to IdeaNest!";
        $message = $this->getWelcomeTemplate($name);
        return $this->mailer->send($to, $subject, $message);
    }
    
    public function sendProjectApprovalEmail($to, $projectTitle, $status) {
        $subject = "Project Status Update - " . $projectTitle;
        $message = $this->getProjectStatusTemplate($projectTitle, $status);
        return $this->mailer->send($to, $subject, $message);
    }
    
    public function sendMentorAssignmentEmail($to, $studentName, $mentorName) {
        $subject = "Mentor Assignment Notification";
        $message = $this->getMentorAssignmentTemplate($studentName, $mentorName);
        return $this->mailer->send($to, $subject, $message);
    }
    
    public function sendPasswordResetEmail($to, $resetLink) {
        $subject = "Password Reset Request - IdeaNest";
        $message = $this->getPasswordResetTemplate($resetLink);
        return $this->mailer->send($to, $subject, $message);
    }
    
    public function sendWeeklyDigest($to, $projects, $ideas) {
        $subject = "Weekly Digest - New Projects & Ideas";
        $message = $this->getWeeklyDigestTemplate($projects, $ideas);
        return $this->mailer->send($to, $subject, $message);
    }
    
    private function getWelcomeTemplate($name) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #8B5CF6;'>Welcome to IdeaNest, {$name}!</h2>
                <p>Thank you for joining our academic project management platform.</p>
                <p>You can now:</p>
                <ul>
                    <li>Submit and manage your projects</li>
                    <li>Share innovative ideas</li>
                    <li>Connect with mentors</li>
                    <li>Collaborate with peers</li>
                </ul>
                <p>Get started by logging into your account and exploring the platform.</p>
                <p>Best regards,<br>The IdeaNest Team</p>
            </div>
        </body>
        </html>";
    }
    
    private function getProjectStatusTemplate($projectTitle, $status) {
        $statusColor = $status === 'approved' ? '#10B981' : '#EF4444';
        $statusText = ucfirst($status);
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #8B5CF6;'>Project Status Update</h2>
                <p>Your project <strong>{$projectTitle}</strong> has been <span style='color: {$statusColor}; font-weight: bold;'>{$statusText}</span>.</p>
                <p>Please log into your account to view more details.</p>
                <p>Best regards,<br>The IdeaNest Team</p>
            </div>
        </body>
        </html>";
    }
    
    private function getMentorAssignmentTemplate($studentName, $mentorName) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #8B5CF6;'>Mentor Assignment</h2>
                <p>Great news! {$studentName} has been paired with mentor {$mentorName}.</p>
                <p>You can now start your mentoring journey together.</p>
                <p>Best regards,<br>The IdeaNest Team</p>
            </div>
        </body>
        </html>";
    }
    
    private function getPasswordResetTemplate($resetLink) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #8B5CF6;'>Password Reset Request</h2>
                <p>You requested a password reset for your IdeaNest account.</p>
                <p>Click the link below to reset your password:</p>
                <p><a href='{$resetLink}' style='background: #8B5CF6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>Best regards,<br>The IdeaNest Team</p>
            </div>
        </body>
        </html>";
    }
    
    private function getWeeklyDigestTemplate($projects, $ideas) {
        $projectsHtml = '';
        foreach ($projects as $project) {
            $projectsHtml .= "<li><strong>{$project['title']}</strong> - {$project['category']}</li>";
        }
        
        $ideasHtml = '';
        foreach ($ideas as $idea) {
            $ideasHtml .= "<li><strong>{$idea['title']}</strong> by {$idea['author']}</li>";
        }
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #8B5CF6;'>Weekly Digest</h2>
                <h3>New Projects This Week:</h3>
                <ul>{$projectsHtml}</ul>
                <h3>New Ideas This Week:</h3>
                <ul>{$ideasHtml}</ul>
                <p>Visit IdeaNest to explore these and more!</p>
                <p>Best regards,<br>The IdeaNest Team</p>
            </div>
        </body>
        </html>";
    }
}

// Simple function for quick email sending
function sendQuickEmail($to, $subject, $message) {
    $mailer = new SMTPMailer();
    return $mailer->send($to, $subject, $message);
}