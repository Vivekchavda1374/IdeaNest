<?php

require_once __DIR__ . '/../Login/Login/db.php';

// Try different autoload paths
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    die("Composer autoload not found. Run: composer install\n");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection not established'));
}

// Get users who want weekly notifications and haven't received one in the last 6 days
$query = "SELECT * FROM register 
          WHERE email_notifications = 1 
          AND (last_notification_sent IS NULL OR last_notification_sent < DATE_SUB(NOW(), INTERVAL 6 DAY))";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " users for weekly notifications\n";
    while ($user = $result->fetch_assoc()) {
        sendWeeklyNotification($user, $conn);
    }
    echo "Weekly notifications completed\n";
} else {
    echo "No users found for weekly notifications\n";
}

$conn->close();

function sendWeeklyNotification($user, $conn)
{
    // Get projects from the last 7 days
    $projects_query = "SELECT p.*, r.name as author_name 
                      FROM admin_approved_projects p 
                      JOIN register r ON p.user_id = r.id 
                      WHERE p.submission_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                      AND p.user_id != ? 
                      ORDER BY p.submission_date DESC 
                      LIMIT 10";
    $projects_stmt = $conn->prepare($projects_query);
    if (!$projects_stmt) {
        echo "Failed to prepare projects query: " . $conn->error . "\n";
        return;
    }
    $projects_stmt->bind_param("i", $user['id']);
    $projects_stmt->execute();
    $projects = $projects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $projects_stmt->close();

    // Get ideas from the last 7 days
    $ideas_query = "SELECT b.*, r.name as author_name 
                   FROM blog b 
                   JOIN register r ON b.er_number = r.enrollment_number 
                   WHERE b.submission_datetime >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                   AND r.id != ? 
                   ORDER BY b.submission_datetime DESC 
                   LIMIT 10";
    $ideas_stmt = $conn->prepare($ideas_query);
    if (!$ideas_stmt) {
        echo "Failed to prepare ideas query: " . $conn->error . "\n";
        return;
    }
    $ideas_stmt->bind_param("i", $user['id']);
    $ideas_stmt->execute();
    $ideas = $ideas_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ideas_stmt->close();

    // Send email even if no new content to maintain weekly contact
    $mail = new PHPMailer(true);

    try {
        // Get SMTP settings from database
        $smtp_query = "SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure', 'from_email')";
        $smtp_result = $conn->query($smtp_query);
        $smtp_settings = [];
        if ($smtp_result) {
            while ($row = $smtp_result->fetch_assoc()) {
                $smtp_settings[$row['setting_key']] = $row['setting_value'];
            }
        }

        $mail->isSMTP();
        $mail->Host = $smtp_settings['smtp_host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_settings['smtp_username'] ?? 'ideanest.ict@gmail.com';
        $mail->Password = $smtp_settings['smtp_password'] ?? 'luou xlhs ojuw auvx';
        $mail->SMTPSecure = ($smtp_settings['smtp_secure'] ?? 'tls') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtp_settings['smtp_port'] ?? 587;
        
        // Production SSL settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            )
        );

        $mail->setFrom($smtp_settings['from_email'] ?? 'ideanest.ict@gmail.com', 'IdeaNest');
        $mail->addAddress($user['email'], $user['name']);

        $mail->isHTML(true);
        $mail->Subject = 'Weekly Digest - New Projects & Ideas on IdeaNest';
        $mail->Body = generateEmailTemplate($user, $projects, $ideas);

        $mail->send();

        // Update last notification sent
        $update_query = "UPDATE register SET last_notification_sent = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        if ($update_stmt) {
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            $update_stmt->close();
        }

        // Log successful send
        try {
            $log_query = "INSERT INTO notification_logs (type, user_id, status, email_to, email_subject, created_at) VALUES ('weekly_notification', ?, 'sent', ?, ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            if ($log_stmt) {
                $log_stmt->bind_param("iss", $user['id'], $user['email'], $mail->Subject);
                $log_stmt->execute();
                $log_stmt->close();
            }
        } catch (Exception $log_error) {
            error_log('Notification logging error: ' . $log_error->getMessage());
        }

        echo "Notification sent to: " . $user['email'] . "\n";
    } catch (Exception $e) {
        // Log failed send
        try {
            $log_query = "INSERT INTO notification_logs (type, user_id, status, email_to, error_message, created_at) VALUES ('weekly_notification', ?, 'failed', ?, ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            if ($log_stmt) {
                $log_stmt->bind_param("iss", $user['id'], $user['email'], $e->getMessage());
                $log_stmt->execute();
                $log_stmt->close();
            }
        } catch (Exception $log_error) {
            error_log('Error logging failed notification: ' . $log_error->getMessage());
        }

        echo "Failed to send notification to: " . $user['email'] . " - Error: " . $e->getMessage() . "\n";
    }

}

function generateEmailTemplate($user, $projects, $ideas)
{
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Weekly Update - IdeaNest</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; }
            .section { margin-bottom: 30px; }
            .section h2 { color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
            .item { background: white; padding: 15px; margin-bottom: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .item h3 { margin: 0 0 10px 0; color: #1f2937; }
            .item p { margin: 5px 0; color: #6b7280; }
            .author { font-weight: bold; color: #6366f1; }
            .footer { background: #1f2937; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
            .btn { display: inline-block; background: #6366f1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📬 Weekly Digest from IdeaNest</h1>
                <p>Hello ' . htmlspecialchars($user['name']) . '! Here\'s what happened this week.</p>
            </div>
            <div class="content">';

    if (count($projects) > 0) {
        $html .= '<div class="section">
                    <h2>📁 New Projects This Week (' . count($projects) . ')</h2>';

        foreach ($projects as $project) {
            $html .= '<div class="item">
                        <h3>' . htmlspecialchars($project['project_name']) . '</h3>
                        <p><strong>By:</strong> <span class="author">' . htmlspecialchars($project['author_name']) . '</span></p>
                        <p>' . htmlspecialchars(substr($project['description'], 0, 150)) . '...</p>
                        <p><strong>Category:</strong> ' . htmlspecialchars($project['project_category']) . '</p>
                    </div>';
        }
        $html .= '</div>';
    }

    if (count($ideas) > 0) {
        $html .= '<div class="section">
                    <h2>💡 New Ideas This Week (' . count($ideas) . ')</h2>';

        foreach ($ideas as $idea) {
            $html .= '<div class="item">
                        <h3>' . htmlspecialchars($idea['project_name']) . '</h3>
                        <p><strong>By:</strong> <span class="author">' . htmlspecialchars($idea['author_name']) . '</span></p>
                        <p>' . htmlspecialchars(substr($idea['description'], 0, 150)) . '...</p>
                    </div>';
        }
        $html .= '</div>';
    }

    // Show message if no new content
    if (count($projects) == 0 && count($ideas) == 0) {
        $html .= '<div class="section">
                    <h2>📭 No New Content This Week</h2>
                    <div class="item">
                        <p>There were no new projects or ideas shared this week, but stay tuned for exciting updates!</p>
                        <p>Why not be the first to share something new?</p>
                    </div>
                </div>';
    }

    $html .= '<div style="text-align: center; margin-top: 30px;">
                    <a href="https://ictmu.in/hcd/IdeaNest/user/all_projects.php" class="btn">View All Projects</a>
                    <a href="https://ictmu.in/hcd/IdeaNest/user/Blog/list-project.php" class="btn">View All Ideas</a>
                </div>
            </div>
            <div class="footer">
                <p>Thank you for being part of the IdeaNest community!</p>
                <p>Don\'t want these emails? <a href="https://ictmu.in/hcd/IdeaNest/user/user_profile_setting.php" style="color: #6366f1;">Update your preferences</a></p>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}
