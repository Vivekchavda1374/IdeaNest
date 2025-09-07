<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include "../Login/Login/db.php";

// Include PHPMailer
require_once dirname(__FILE__) . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once dirname(__FILE__) . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once dirname(__FILE__) . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$message = '';
$error = '';

if(isset($_POST['send_test'])) {
    try {
        $mail = new PHPMailer(true);

        // Get test email address
        $test_email = $_POST['test_email'] ?? 'ideanest.ict@gmail.com';
        
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ideanest.ict@gmail.com';
        $mail->Password = 'luou xlhs ojuw auvx';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
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
        $mail->setFrom('ideanest.ict@gmail.com', 'IdeaNest Admin');
        $mail->addAddress($test_email, 'Test Recipient');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from IdeaNest Admin - ' . date('Y-m-d H:i:s');
        $mail->Body = '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">Test Email Configuration</h2>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                    <h3 style="color: #2d3748; margin-top: 0;">Email Configuration Test</h3>
                    <p>This is a test email to verify your email configuration is working properly.</p>
                    
                    <h4 style="color: #4a5568;">Default Email Configuration:</h4>
                    <ul style="color: #4a5568;">
                        <li><strong>SMTP Host:</strong> smtp.gmail.com</li>
                        <li><strong>SMTP Port:</strong> 587</li>
                        <li><strong>SMTP Security:</strong> TLS</li>
                        <li><strong>From Email:</strong> ideanest.ict@gmail.com</li>
                        <li><strong>Site Name:</strong> IdeaNest</li>
                    </ul>
                </div>
                
                <div style="background: #e6fffa; padding: 15px; border-radius: 8px; border-left: 4px solid #48bb78;">
                    <p style="margin: 0; color: #22543d;">
                        <strong>✅ Success!</strong> If you received this email, your email configuration is working correctly.
                    </p>
                </div>
                
                <div style="text-align: center; margin-top: 20px; color: #718096; font-size: 14px;">
                    <p>Test sent at: ' . date('F j, Y, g:i a') . '</p>
                    <p>This email was sent from the admin test panel.</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->send();
        $message = "Test email sent successfully! Check your inbox at: " . $test_email;
        
    } catch (Exception $e) {
        $error = "Test email failed: " . $mail->ErrorInfo;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Configuration - IdeaNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .info-box {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <h2 class="mb-0">
                    <i class="bi bi-envelope me-2"></i>
                    Test Email Configuration
                </h2>
            </div>
            <div class="card-body p-4">
                
                <!-- Info Box -->
                <div class="info-box">
                    <h5><i class="bi bi-info-circle me-2"></i>Where Emails Are Sent By Default</h5>
                    <p class="mb-0">
                        <strong>Project Approval/Rejection Emails:</strong> Sent to the user's email address (stored in the database)<br>
                        <strong>Test Emails:</strong> Sent to the admin email address (ideanest.ict@gmail.com)<br>
                        <strong>From Address:</strong> ideanest.ict@gmail.com (IdeaNest Admin)
                    </p>
                </div>

                <!-- Alert Messages -->
                <?php if($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Test Email Form -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="test_email" class="form-label">
                            <i class="bi bi-envelope me-2"></i>
                            Test Email Address
                        </label>
                        <input type="email" class="form-control" id="test_email" name="test_email" 
                               value="ideanest.ict@gmail.com" required>
                        <div class="form-text">
                            Enter the email address where you want to receive the test email.
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="send_test" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>
                            Send Test Email
                        </button>
                    </div>
                </form>

                <!-- Back to Settings -->
                <div class="text-center mt-4">
                    <a href="settings.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 