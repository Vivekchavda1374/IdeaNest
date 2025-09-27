<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include "../Login/db.php";

// Include PHPMailer
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Function to get setting value
function getSetting($conn, $key, $default = '')
{
    $query = "SELECT setting_value FROM admin_settings WHERE setting_key = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['setting_value'];
    }
    return $default;
}

// Function to check if email notifications are enabled
function isEmailNotificationsEnabled($conn)
{
    return getSetting($conn, 'email_notifications', '1') == '1';
}

// Function to check if project approval emails are enabled
function isProjectApprovalEmailsEnabled($conn)
{
    return getSetting($conn, 'project_approval_emails', '1') == '1';
}

// Function to check if project rejection emails are enabled
function isProjectRejectionEmailsEnabled($conn)
{
    return getSetting($conn, 'project_rejection_emails', '1') == '1';
}

// Function to check if new user notifications are enabled
function isNewUserNotificationsEnabled($conn)
{
    return getSetting($conn, 'new_user_notifications', '0') == '1';
}

// Function to send email using PHPMailer
function sendEmail($to_email, $to_name, $subject, $html_body, $conn)
{
    try {
        $mail = new PHPMailer(true);

        // Get email settings from database
        $smtp_host = getSetting($conn, 'smtp_host', 'smtp.gmail.com');
        $smtp_port = getSetting($conn, 'smtp_port', '587');
        $smtp_username = getSetting($conn, 'smtp_username', 'ideanest.ict@gmail.com');
        $smtp_password = getSetting($conn, 'smtp_password', 'luou xlhs ojuw auvx');
        $smtp_secure = getSetting($conn, 'smtp_secure', 'tls');
        $from_email = getSetting($conn, 'from_email', 'ideanest.ict@gmail.com');
        $site_name = getSetting($conn, 'site_name', 'IdeaNest');

        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_secure;
        $mail->Port = $smtp_port;

        // XAMPP compatibility settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Timeout settings
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom($from_email, $site_name . ' Admin');
        $mail->addAddress($to_email, $to_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html_body));

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}

// Function to send project approval email
function sendProjectApprovalEmail($project_id, $conn)
{
    if (!isEmailNotificationsEnabled($conn) || !isProjectApprovalEmailsEnabled($conn)) {
        return ['success' => false, 'message' => 'Email notifications are disabled'];
    }

    // Get project and user details
    $query = "SELECT p.*, r.email, r.name 
              FROM projects p 
              JOIN register r ON p.user_id = r.id 
              WHERE p.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return ['success' => false, 'message' => 'Project not found'];
    }

    $project = $result->fetch_assoc();
    $user_email = $project['email'];
    $user_name = $project['name'];
    $project_name = $project['project_name'];
    $project_type = $project['project_type'];
    $submission_date = date('F j, Y', strtotime($project['submission_date']));
    $approval_date = date('F j, Y');

    $site_name = getSetting($conn, 'site_name', 'IdeaNest');
    $support_email = getSetting($conn, 'from_email', 'ideanest.ict@gmail.com');
    $dashboard_url = getSetting($conn, 'site_url', 'https://ictmu.in/hcd/IdeaNest') . '/user/index.php';

    $subject = "Congratulations! Your Project \"{$project_name}\" Has Been Approved";

    $html_body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Project Approved</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .header { background-color: #43ee68; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #ffffff; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; font-size: 12px; color: #777; background-color: #f9f9f9; }
            .button { display: inline-block; background-color: #43ee68; color: white !important; padding: 12px 24px; 
                text-decoration: none; border-radius: 4px; margin-top: 15px; font-weight: bold; }
            .button:hover { background-color: #43ee68; }
            .project-details { background-color: #f0f4f8; padding: 15px; border-radius: 4px; margin: 15px 0; }
            @media (max-width: 600px) {
                .container { width: 100% !important; }
                .content { padding: 15px !important; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1 style="margin: 0; padding: 0;">Project Approved!</h1>
            </div>
            <div class="content">
                <p>Hello ' . $user_name . ',</p>
                <p>Great news! Your project <strong>"' . $project_name . '"</strong> has been approved by our team.</p>
                <p>Your project is now published on ' . $site_name . ' and visible to the community. We\'re excited to see how others engage with your work!</p>
                
                <div class="project-details">
                    <h3 style="margin-top: 0; color: #2d3748;">Project Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; width: 40%;"><strong>Project Name:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . $project_name . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Type:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . $project_type . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Category:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . ($project['classification'] ?: 'Not specified') . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Submitted On:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . $submission_date . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0;"><strong>Approved On:</strong></td>
                            <td style="padding: 8px 0;">' . $approval_date . '</td>
                        </tr>
                    </table>
                </div>
                
                <p>Thank you for contributing to our growing community of innovators and creators.</p>
                <p style="text-align: center;">
                    <a href="' . $dashboard_url . '" class="button">View Your Projects</a>
                </p>
                <p>Keep creating amazing things!</p>
                <p>Best regards,<br>The ' . $site_name . ' Team</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' ' . $site_name . '. All rights reserved.</p>
                <p>If you have any questions, please contact our support team at ' . $support_email . '</p>
            </div>
        </div>
    </body>
    </html>';

    return sendEmail($user_email, $user_name, $subject, $html_body, $conn);
}

// Function to send project rejection email
function sendProjectRejectionEmail($project_id, $rejection_reason, $conn)
{
    if (!isEmailNotificationsEnabled($conn) || !isProjectRejectionEmailsEnabled($conn)) {
        return ['success' => false, 'message' => 'Email notifications are disabled'];
    }

    // Get project and user details
    $query = "SELECT p.*, r.email, r.name 
              FROM projects p 
              JOIN register r ON p.user_id = r.id 
              WHERE p.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return ['success' => false, 'message' => 'Project not found'];
    }

    $project = $result->fetch_assoc();
    $user_email = $project['email'];
    $user_name = $project['name'];
    $project_name = $project['project_name'];
    $project_type = $project['project_type'];
    $submission_date = date('F j, Y', strtotime($project['submission_date']));
    $rejection_date = date('F j, Y');

    $site_name = getSetting($conn, 'site_name', 'IdeaNest');
    $support_email = getSetting($conn, 'from_email', 'ideanest.ict@gmail.com');
    $submission_url = getSetting($conn, 'site_url', 'https://ictmu.in/hcd/IdeaNest') . '/user/forms/new_project_add.php';

    $subject = "Important Update About Your Project \"{$project_name}\"";

    $html_body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Project Update</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .header { background-color: #c10909; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #ffffff; }
            .reason { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #c10909; margin: 15px 0; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; font-size: 12px; color: #c10909; background-color: #f9f9f9; }
            .button { display: inline-block; background-color: #4361ee; color: white !important; padding: 12px 24px; 
                text-decoration: none; border-radius: 4px; margin-top: 15px; font-weight: bold; }
            .button:hover { background-color: #3651d4; }
            .project-details { background-color: #f0f4f8; padding: 15px; border-radius: 4px; margin: 15px 0; }
            @media (max-width: 600px) {
                .container { width: 100% !important; }
                .content { padding: 15px !important; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1 style="margin: 0; padding: 0;">Project Update</h1>
            </div>
            <div class="content">
                <p>Hello ' . $user_name . ',</p>
                <p>Thank you for submitting your project <strong>"' . $project_name . '"</strong> to ' . $site_name . '.</p>
                <p>After careful review, our team has decided not to approve this project at this time. Here\'s the feedback from our review team:</p>
                
                <div class="reason">
                    <p><strong>Reason:</strong> ' . $rejection_reason . '</p>
                </div>
                
                <div class="project-details">
                    <h3 style="margin-top: 0; color: #2d3748;">Project Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; width: 40%;"><strong>Project Name:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . $project_name . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Type:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . $project_type . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Category:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . ($project['classification'] ?: 'Not specified') . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Submitted On:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . $submission_date . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0;"><strong>Reviewed On:</strong></td>
                            <td style="padding: 8px 0;">' . $rejection_date . '</td>
                        </tr>
                    </table>
                </div>
                
                <p>We encourage you to revise your project based on this feedback and resubmit. Many successful projects on our platform went through multiple iterations!</p>
                <p style="text-align: center;">
                    <a href="' . $submission_url . '" class="button">Submit Another Project</a>
                </p>
                <p>If you have any questions about the review process or need clarification on the feedback, please don\'t hesitate to contact us.</p>
                <p>Best regards,<br>The ' . $site_name . ' Team</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' ' . $site_name . '. All rights reserved.</p>
                <p>If you have any questions, please contact our support team at ' . $support_email . '</p>
            </div>
        </div>
    </body>
    </html>';

    return sendEmail($user_email, $user_name, $subject, $html_body, $conn);
}

// Function to send new user notification to admin
function sendNewUserNotificationToAdmin($user_id, $conn)
{
    if (!isEmailNotificationsEnabled($conn) || !isNewUserNotificationsEnabled($conn)) {
        return ['success' => false, 'message' => 'Email notifications are disabled'];
    }

    // Get user details
    $query = "SELECT * FROM register WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return ['success' => false, 'message' => 'User not found'];
    }

    $user = $result->fetch_assoc();
    $admin_email = getSetting($conn, 'admin_email', 'ideanest.ict@gmail.com');
    $site_name = getSetting($conn, 'site_name', 'IdeaNest');
    $support_email = getSetting($conn, 'from_email', 'ideanest.ict@gmail.com');
    $registration_date = date('F j, Y, g:i a', strtotime($user['created_at'] ?? date('Y-m-d H:i:s')));

    $subject = "New User Registration - " . $site_name;

    $html_body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>New User Registration</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .header { background-color: #4361ee; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #ffffff; }
            .footer { text-align: center; margin-top: 20px; padding: 20px; font-size: 12px; color: #777; background-color: #f9f9f9; }
            .user-details { background-color: #f0f4f8; padding: 15px; border-radius: 4px; margin: 15px 0; }
            .button { display: inline-block; background-color: #4361ee; color: white !important; padding: 12px 24px; 
                text-decoration: none; border-radius: 4px; margin-top: 15px; font-weight: bold; }
            .button:hover { background-color: #3651d4; }
            @media (max-width: 600px) {
                .container { width: 100% !important; }
                .content { padding: 15px !important; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1 style="margin: 0; padding: 0;">New User Registration</h1>
            </div>
            <div class="content">
                <p>Hello Admin,</p>
                <p>A new user has registered on ' . $site_name . '.</p>
                
                <div class="user-details">
                    <h3 style="margin-top: 0; color: #2d3748;">User Details</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; width: 40%;"><strong>Name:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . $user['name'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Email:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . $user['email'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Phone:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . (isset($user['phone']) && $user['phone'] ? $user['phone'] : 'Not provided') . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;"><strong>Registration Date:</strong></td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">' . $registration_date . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0;"><strong>User ID:</strong></td>
                            <td style="padding: 8px 0;">' . $user['id'] . '</td>
                        </tr>
                    </table>
                </div>
                
                <p>This user can now submit projects and participate in the ' . $site_name . ' community.</p>
                <p>Best regards,<br>The ' . $site_name . ' System</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' ' . $site_name . '. All rights reserved.</p>
                <p>This is an automated notification from the ' . $site_name . ' system.</p>
            </div>
        </div>
    </body>
    </html>';

    return sendEmail($admin_email, 'Admin', $subject, $html_body, $conn);
}

// Function to log notification attempts
function logNotification($type, $user_id, $conn, $status, $project_id = null, $email_to = null, $email_subject = null, $error_message = null)
{
    $query = "INSERT INTO notification_logs (type, user_id, project_id, status, email_to, email_subject, error_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siissss", $type, $user_id, $project_id, $status, $email_to, $email_subject, $error_message);
    return $stmt->execute();
}

// Create notification_logs table if not exists
$create_logs_table = "CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    user_id INT,
    project_id INT NULL,
    status VARCHAR(50) NOT NULL,
    email_to VARCHAR(255),
    email_subject VARCHAR(255),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_user_id (user_id),
    INDEX idx_project_id (project_id)
)";
$conn->query($create_logs_table);

// Add missing columns if they don't exist
$alter_queries = [
    "ALTER TABLE notification_logs ADD COLUMN IF NOT EXISTS email_to VARCHAR(255)",
    "ALTER TABLE notification_logs ADD COLUMN IF NOT EXISTS email_subject VARCHAR(255)",
    "ALTER TABLE notification_logs ADD COLUMN IF NOT EXISTS error_message TEXT"
];

foreach ($alter_queries as $query) {
    $conn->query($query);
}
