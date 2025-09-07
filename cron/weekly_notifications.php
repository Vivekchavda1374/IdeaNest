<?php
require_once '../Login/Login/db.php';
require_once '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get users who have notifications enabled and haven't received notification in last 7 days
$query = "SELECT * FROM register 
          WHERE email_notifications = 1 
          AND (last_notification_sent IS NULL OR last_notification_sent < DATE_SUB(NOW(), INTERVAL 7 DAY))";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($user = $result->fetch_assoc()) {
        sendWeeklyNotification($user, $conn);
    }
}

function sendWeeklyNotification($user, $conn) {
    // Get new projects from last 7 days
    $projects_query = "SELECT p.*, r.name as author_name 
                      FROM projects p 
                      JOIN register r ON p.user_id = r.id 
                      WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                      AND p.user_id != ? 
                      ORDER BY p.created_at DESC 
                      LIMIT 10";
    $projects_stmt = $conn->prepare($projects_query);
    $projects_stmt->bind_param("i", $user['id']);
    $projects_stmt->execute();
    $projects = $projects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get new ideas from last 7 days
    $ideas_query = "SELECT b.*, r.name as author_name 
                   FROM blog b 
                   JOIN register r ON b.er_number = r.enrollment_number 
                   WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                   AND r.id != ? 
                   ORDER BY b.created_at DESC 
                   LIMIT 10";
    $ideas_stmt = $conn->prepare($ideas_query);
    $ideas_stmt->bind_param("i", $user['id']);
    $ideas_stmt->execute();
    $ideas = $ideas_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Only send if there are new projects or ideas
    if (count($projects) > 0 || count($ideas) > 0) {
        $mail = new PHPMailer(true);
        
        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email@gmail.com'; // Replace with your email
            $mail->Password = 'your-app-password';    // Replace with your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('your-email@gmail.com', 'IdeaNest');
            $mail->addAddress($user['email'], $user['name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Weekly Update - New Projects & Ideas on IdeaNest';
            $mail->Body = generateEmailTemplate($user, $projects, $ideas);

            $mail->send();
            
            // Update last notification sent
            $update_query = "UPDATE register SET last_notification_sent = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();

            // Log notification
            $log_query = "INSERT INTO notification_logs (user_id, notification_type, projects_count, ideas_count, status) VALUES (?, 'weekly', ?, ?, 'sent')";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("iii", $user['id'], count($projects), count($ideas));
            $log_stmt->execute();

            echo "Notification sent to: " . $user['email'] . "\n";
            
        } catch (Exception $e) {
            // Log failed notification
            $log_query = "INSERT INTO notification_logs (user_id, notification_type, projects_count, ideas_count, status) VALUES (?, 'weekly', ?, ?, 'failed')";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("iii", $user['id'], count($projects), count($ideas));
            $log_stmt->execute();
            
            echo "Failed to send notification to: " . $user['email'] . " - " . $mail->ErrorInfo . "\n";
        }
    }
}

function generateEmailTemplate($user, $projects, $ideas) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            .unsubscribe { font-size: 12px; color: #9ca3af; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚀 Weekly Update from IdeaNest</h1>
                <p>Hello ' . htmlspecialchars($user['name']) . '! Here\'s what\'s new this week.</p>
            </div>
            
            <div class="content">';
    
    if (count($projects) > 0) {
        $html .= '
                <div class="section">
                    <h2>📁 New Projects (' . count($projects) . ')</h2>';
        
        foreach ($projects as $project) {
            $html .= '
                    <div class="item">
                        <h3>' . htmlspecialchars($project['project_title']) . '</h3>
                        <p><strong>By:</strong> <span class="author">' . htmlspecialchars($project['author_name']) . '</span></p>
                        <p>' . htmlspecialchars(substr($project['project_description'], 0, 150)) . '...</p>
                        <p><strong>Category:</strong> ' . htmlspecialchars($project['project_category']) . '</p>
                    </div>';
        }
        
        $html .= '</div>';
    }
    
    if (count($ideas) > 0) {
        $html .= '
                <div class="section">
                    <h2>💡 New Ideas (' . count($ideas) . ')</h2>';
        
        foreach ($ideas as $idea) {
            $html .= '
                    <div class="item">
                        <h3>' . htmlspecialchars($idea['title']) . '</h3>
                        <p><strong>By:</strong> <span class="author">' . htmlspecialchars($idea['author_name']) . '</span></p>
                        <p>' . htmlspecialchars(substr($idea['content'], 0, 150)) . '...</p>
                    </div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '
                <div style="text-align: center; margin-top: 30px;">
                    <a href="http://localhost/IdeaNest/user/all_projects.php" class="btn">View All Projects</a>
                    <a href="http://localhost/IdeaNest/user/Blog/list-project.php" class="btn">View All Ideas</a>
                </div>
            </div>
            
            <div class="footer">
                <p>Thank you for being part of the IdeaNest community!</p>
                <p>Keep innovating and sharing your amazing projects.</p>
                <div class="unsubscribe">
                    <p>Don\'t want these emails? <a href="http://localhost/IdeaNest/user/user_profile_setting.php" style="color: #6366f1;">Update your notification preferences</a></p>
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>