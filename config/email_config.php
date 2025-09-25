<?php
// Email Configuration for Production
// This file should be included in .gitignore for security

// Production email settings
$email_config = [
    'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
    'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
    'smtp_username' => $_ENV['SMTP_USERNAME'] ?? 'ideanest.ict@gmail.com',
    'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? 'luou xlhs ojuw auvx',
    'smtp_secure' => $_ENV['SMTP_SECURE'] ?? 'tls',
    'from_email' => $_ENV['FROM_EMAIL'] ?? 'ideanest.ict@gmail.com',
    'from_name' => $_ENV['FROM_NAME'] ?? 'IdeaNest',
    
    // SSL options for production
    'ssl_verify_peer' => $_ENV['SSL_VERIFY_PEER'] ?? true,
    'ssl_verify_peer_name' => $_ENV['SSL_VERIFY_PEER_NAME'] ?? true,
    'ssl_allow_self_signed' => $_ENV['SSL_ALLOW_SELF_SIGNED'] ?? false
];

// Function to get email configuration
function getEmailConfig($conn = null) {
    global $email_config;
    
    // Try to get settings from database first
    if ($conn) {
        try {
            $smtp_query = "SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure', 'from_email')";
            $smtp_result = $conn->query($smtp_query);
            if ($smtp_result) {
                while ($row = $smtp_result->fetch_assoc()) {
                    $email_config[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Exception $e) {
            error_log('Failed to load email config from database: ' . $e->getMessage());
        }
    }
    
    return $email_config;
}

// Function to setup PHPMailer with proper error handling
function setupPHPMailer($conn = null) {
    $config = getEmailConfig($conn);
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'] === 'tls' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $config['smtp_port'];
        $mail->setFrom($config['from_email'], $config['from_name']);
        
        // SSL options
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => $config['ssl_verify_peer'],
                'verify_peer_name' => $config['ssl_verify_peer_name'],
                'allow_self_signed' => $config['ssl_allow_self_signed']
            )
        );
        
        return $mail;
    } catch (Exception $e) {
        error_log('PHPMailer setup error: ' . $e->getMessage());
        throw new Exception('Email system configuration error');
    }
}
?>