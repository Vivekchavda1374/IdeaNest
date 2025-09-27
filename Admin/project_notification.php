<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once dirname(__DIR__) . "/Login/Login/db.php";

// Use Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function sendProjectStatusEmail($project_id, $status, $rejection_reason = '', $subadmin_details = null, $email_options = [])
{
    global $conn;

    $project_query = "SELECT p.*, r.email, r.name 
                 FROM projects p 
                 JOIN register r ON p.user_id = r.id 
                 WHERE p.id = ?";

    $stmt = $conn->prepare($project_query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        return ['success' => false, 'message' => 'Project not found'];
    }

    $project = $result->fetch_assoc();
    $user_email = $project['email'];
    $user_name = $project['name'];

    $username = $project['name'];

    $project_name = $project['project_name'];
    $project_type = $project['project_type'];
    $project_classification = $project['classification'];

    // Default email options
    $default_options = [
        'company_name' => 'IdeaNest',
        'logo_url' => 'logo-no-background.png',
        'support_email' => 'ideanest.ict@gmail.com',
        'company_address' => 'Marwadi University',
        'website_url' => 'https://ictmu.in/hcd/IdeaNest/user/index.php',
        'dashboard_url' => 'https://ictmu.in/hcd/IdeaNest/user/index.php',
        'submission_url' => 'https://ictmu.in/hcd/IdeaNest/user/forms/new_project_add.php',
        'include_project_details' => true,
        'custom_text' => '',

        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => 'ideanest.ict@gmail.com',
        'smtp_password' => 'luou xlhs ojuw auvx',
        'smtp_secure' => 'tls'
    ];

    // Merge provided options with defaults
    $options = array_merge($default_options, $email_options);

    // Set email details based on status
    if ($status == 'approved') {
        // Dynamic subject line options
        $subject_options = [
            "Congratulations! Your Project \"{$project_name}\" Has Been Approved",
            "{$options['company_name']} - Your Project Submission Has Been Approved",
            "Good News! Your {$project_type} Project Is Now Live on {$options['company_name']}",
            "{$options['company_name']} - Your Project Has Been Approved!"
        ];

        // Use custom subject if provided, otherwise randomly select one
        $subject = isset($email_options['subject']) ? $email_options['subject'] : $subject_options[array_rand($subject_options)];
        $message = createApprovedEmailContent($user_name, $project, $options, $subadmin_details);
    } elseif ($status == 'rejected') {
        // Dynamic subject line options
        $subject_options = [
            "Important Update About Your Project \"{$project_name}\"",
            "{$options['company_name']} - Update on Your Project Submission",
            "Feedback on Your Recent {$project_type} Project Submission",
            "Your Project Submission: Review Feedback"
        ];

        // Use custom subject if provided, otherwise randomly select one
        $subject = isset($email_options['subject']) ? $email_options['subject'] : $subject_options[array_rand($subject_options)];
        $message = createRejectedEmailContent($user_name, $project, $rejection_reason, $options, $subadmin_details);
    } else {
        return ['success' => false, 'message' => 'Invalid status provided'];
    }

    // Send email using PHPMailer
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->SMTPDebug = 0; // Set to 0 for production, 2 for debugging
        $mail->isSMTP();
        $mail->Host = $options['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $options['smtp_username'];
        $mail->Password = $options['smtp_password'];
        $mail->SMTPSecure = $options['smtp_secure'];
        $mail->Port = $options['smtp_port'];

        // Additional settings for better compatibility
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Set timeout values for XAMPP compatibility
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;

        // Recipients
        $mail->setFrom($options['support_email'], $options['company_name']);
        $mail->addAddress($user_email, $user_name);
        if (isset($options['reply_to'])) {
            $mail->addReplyTo($options['reply_to']);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));

        $mail->send();
        return ['success' => true, 'message' => 'Email has been sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}

function createApprovedEmailContent($user_name, $project, $options, $subadmin_details = null)
{
    // Extract project details
    $project_name = $project['project_name'];
    $project_type = $project['project_type'];
    $submission_date = date('F j, Y', strtotime($project['submission_date']));
    $approval_date = date('F j, Y');

    // Project details block (optional)
    $project_details = '';
    if ($options['include_project_details']) {
        $project_details = '
        <div style="background-color: #f0f4f8; padding: 15px; border-radius: 4px; margin: 15px 0;">
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
        </div>';
    }

    // Custom text (if provided)
    $custom_text = '';
    if (!empty($options['custom_text'])) {
        $custom_text = '<p>' . $options['custom_text'] . '</p>';
    }

    $html = '
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
            .highlight { color: #43ee68; font-weight: bold; }
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
                <p>Your project is now published on ' . $options['company_name'] . ' and visible to the community. We\'re excited to see how others engage with your work!</p>
                
                ' . $project_details . '
                
                ' . $custom_text . '
                
                <p>Thank you for contributing to our growing community of innovators and creators.</p>
                <p style="text-align: center;">
                    <a href="' . $options['dashboard_url'] . '" class="button">View Your Projects</a>
                </p>
                <p>Keep creating amazing things!</p>
                ' . ($subadmin_details ? '<p>Reviewed by: <strong>' . $subadmin_details['name'] . '</strong><br>Contact: ' . $subadmin_details['email'] . '</p>' : '') . '
                <p>Best regards,<br>The ' . $options['company_name'] . ' Team</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' ' . $options['company_name'] . '. All rights reserved.</p>
                <p>' . $options['company_address'] . '</p>
                <p>If you have any questions, please contact our support team at ' . $options['support_email'] . '</p>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}

function createRejectedEmailContent($user_name, $project, $rejection_reason, $options, $subadmin_details = null)
{
    // Extract project details
    $project_name = $project['project_name'];
    $project_type = $project['project_type'];
    $submission_date = date('F j, Y', strtotime($project['submission_date']));
    $rejection_date = date('F j, Y');

    // Project details block (optional)
    $project_details = '';
    if ($options['include_project_details']) {
        $project_details = '
        <div style="background-color: #f0f4f8; padding: 15px; border-radius: 4px; margin: 15px 0;">
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
        </div>';
    }

    // Custom text (if provided)
    $custom_text = '';
    if (!empty($options['custom_text'])) {
        $custom_text = '<p>' . $options['custom_text'] . '</p>';
    }

    $html = '
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
            .highlight { color: #5f5f5f; font-weight: bold; }
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
                <p>Thank you for submitting your project <strong>"' . $project_name . '"</strong> to ' . $options['company_name'] . '.</p>
                <p>After careful review, our team has decided not to approve this project at this time. Here\'s the feedback from our review team:</p>
                
                <div class="reason">
                    <p><strong>Reason:</strong> ' . $rejection_reason . '</p>
                </div>
                
                ' . $project_details . '
                
                ' . $custom_text . '
                
                <p>We encourage you to revise your project based on this feedback and resubmit. Many successful projects on our platform went through multiple iterations!</p>
                <p style="text-align: center;">
                    <a href="' . $options['submission_url'] . '" class="button">Submit Another Project</a>
                </p>
                <p>If you have any questions about the review process or need clarification on the feedback, please don\'t hesitate to contact us.</p>
                ' . ($subadmin_details ? '<p>Reviewed by: <strong>' . $subadmin_details['name'] . '</strong><br>Contact: ' . $subadmin_details['email'] . '</p>' : '') . '
                <p>Best regards,<br>The ' . $options['company_name'] . ' Team</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' ' . $options['company_name'] . '. All rights reserved.</p>
                <p>' . $options['company_address'] . '</p>
                <p>If you have any questions, please contact our support team at ' . $options['support_email'] . '</p>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}
