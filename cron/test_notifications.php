<?php
// Test script to send notifications immediately (for testing purposes)
require_once '../Login/Login/db.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get a specific user for testing (replace with actual user ID)
$test_user_id = 1; // Change this to test with specific user
$query = "SELECT * FROM register WHERE id = ? AND email_notifications = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $test_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Get recent projects (last 30 days for testing)
    $projects_query = "SELECT p.*, r.name as author_name 
                      FROM projects p 
                      JOIN register r ON p.user_id = r.id 
                      WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                      AND p.user_id != ? 
                      ORDER BY p.created_at DESC 
                      LIMIT 5";
    $projects_stmt = $conn->prepare($projects_query);
    $projects_stmt->bind_param("i", $user['id']);
    $projects_stmt->execute();
    $projects = $projects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get recent ideas (last 30 days for testing)
    $ideas_query = "SELECT b.*, r.name as author_name 
                   FROM blog b 
                   JOIN register r ON b.er_number = r.enrollment_number 
                   WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                   AND r.id != ? 
                   ORDER BY b.created_at DESC 
                   LIMIT 5";
    $ideas_stmt = $conn->prepare($ideas_query);
    $ideas_stmt->bind_param("i", $user['id']);
    $ideas_stmt->execute();
    $ideas = $ideas_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo "Testing notification for: " . $user['name'] . " (" . $user['email'] . ")\n";
    echo "Projects found: " . count($projects) . "\n";
    echo "Ideas found: " . count($ideas) . "\n\n";

    if (count($projects) > 0 || count($ideas) > 0) {
        $mail = new PHPMailer(true);
        
        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ideanest.ict@gmail.com'; // Replace with your email
            $mail->Password = 'luou xlhs ojuw auvx';    // Replace with your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('your-email@gmail.com', 'IdeaNest');
            $mail->addAddress($user['email'], $user['name']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = '[TEST] Weekly Update - New Projects & Ideas on IdeaNest';
            $mail->Body = generateTestEmailTemplate($user, $projects, $ideas);

            $mail->send();
            echo "✅ Test notification sent successfully!\n";
            
        } catch (Exception $e) {
            echo "❌ Failed to send test notification: " . $mail->ErrorInfo . "\n";
        }
    } else {
        echo "No new content found for notification.\n";
    }
} else {
    echo "User not found or notifications disabled.\n";
}

function generateTestEmailTemplate($user, $projects, $ideas) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>[TEST] Weekly Update - IdeaNest</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #ff6b6b, #feca57); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .test-badge { background: #e74c3c; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
            .content { background: #f8f9fa; padding: 30px; }
            .section { margin-bottom: 30px; }
            .section h2 { color: #6366f1; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
            .item { background: white; padding: 15px; margin-bottom: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .item h3 { margin: 0 0 10px 0; color: #1f2937; }
            .item p { margin: 5px 0; color: #6b7280; }
            .author { font-weight: bold; color: #6366f1; }
            .footer { background: #1f2937; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="test-badge">TEST EMAIL</div>
                <h1>🧪 Test Weekly Update from IdeaNest</h1>
                <p>Hello ' . htmlspecialchars($user['name']) . '! This is a test notification.</p>
            </div>
            
            <div class="content">
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>⚠️ This is a test email</strong><br>
                    You are receiving this because the notification system is being tested.
                </div>';
    
    if (count($projects) > 0) {
        $html .= '
                <div class="section">
                    <h2>📁 Recent Projects (' . count($projects) . ')</h2>';
        
        foreach ($projects as $project) {
            $html .= '
                    <div class="item">
                        <h3>' . htmlspecialchars($project['project_title']) . '</h3>
                        <p><strong>By:</strong> <span class="author">' . htmlspecialchars($project['author_name']) . '</span></p>
                        <p>' . htmlspecialchars(substr($project['project_description'], 0, 100)) . '...</p>
                    </div>';
        }
        
        $html .= '</div>';
    }
    
    if (count($ideas) > 0) {
        $html .= '
                <div class="section">
                    <h2>💡 Recent Ideas (' . count($ideas) . ')</h2>';
        
        foreach ($ideas as $idea) {
            $html .= '
                    <div class="item">
                        <h3>' . htmlspecialchars($idea['title']) . '</h3>
                        <p><strong>By:</strong> <span class="author">' . htmlspecialchars($idea['author_name']) . '</span></p>
                        <p>' . htmlspecialchars(substr($idea['content'], 0, 100)) . '...</p>
                    </div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '
            </div>
            
            <div class="footer">
                <p>🧪 This was a test of the IdeaNest notification system</p>
                <p>If you received this, the system is working correctly!</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>