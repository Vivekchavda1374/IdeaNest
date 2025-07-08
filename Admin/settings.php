<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include "../Login/Login/db.php";

// Include PHPMailer for email functionality
require_once dirname(__FILE__) . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once dirname(__FILE__) . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once dirname(__FILE__) . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Site name
$site_name = "IdeaNest Admin";

// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to admin login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_name = "Admin";

// Create settings table if not exists
$create_settings_table = "CREATE TABLE IF NOT EXISTS admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_settings_table);

// Function to get setting value
function getSetting($conn, $key, $default = '') {
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

// Function to save setting value
function saveSetting($conn, $key, $value) {
    $query = "INSERT INTO admin_settings (setting_key, setting_value) VALUES (?, ?) 
              ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $key, $value, $value);
    return $stmt->execute();
}

// Function to log notification attempts with enhanced details
function logNotification($type, $user_id, $project_id = null, $status, $conn, $email_to = null, $email_subject = null, $error_message = null) {
    $query = "INSERT INTO notification_logs (type, user_id, project_id, status, email_to, email_subject, error_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siissss", $type, $user_id, $project_id, $status, $email_to, $email_subject, $error_message);
    return $stmt->execute();
}

// Function to get notification template
function getNotificationTemplate($conn, $type) {
    $query = "SELECT * FROM notification_templates WHERE type = ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Function to update notification template
function updateNotificationTemplate($conn, $type, $subject, $body, $variables) {
    $query = "INSERT INTO notification_templates (type, subject, body, variables) VALUES (?, ?, ?, ?) 
              ON DUPLICATE KEY UPDATE subject = ?, body = ?, variables = ?, updated_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssss", $type, $subject, $body, $variables, $subject, $body, $variables);
    return $stmt->execute();
}

// Create notification tables if they don't exist
$create_logs_table = "CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    user_id INT,
    project_id INT NULL,
    status VARCHAR(50) NOT NULL,
    error_message TEXT DEFAULT NULL,
    email_to VARCHAR(255) DEFAULT NULL,
    email_subject VARCHAR(255) DEFAULT NULL,
    email_body TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_user_id (user_id),
    INDEX idx_project_id (project_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
)";
$conn->query($create_logs_table);

// Create notification_templates table if not exists
$create_templates_table = "CREATE TABLE IF NOT EXISTS notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_type (type)
)";
$conn->query($create_templates_table);

// Create notification_counters table if not exists
$create_counters_table = "CREATE TABLE IF NOT EXISTS notification_counters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    count INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_type_status (type, status)
)";
$conn->query($create_counters_table);

// Handle settings form submission
if(isset($_POST['save_settings'])) {
    $success_count = 0;
    $error_count = 0;
    
    // General Settings
    if(isset($_POST['site_name'])) {
        if(saveSetting($conn, 'site_name', $_POST['site_name'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['site_url'])) {
        if(saveSetting($conn, 'site_url', $_POST['site_url'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['admin_email'])) {
        if(saveSetting($conn, 'admin_email', $_POST['admin_email'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['timezone'])) {
        if(saveSetting($conn, 'timezone', $_POST['timezone'])) $success_count++;
        else $error_count++;
    }
    
    // Email Settings
    if(isset($_POST['smtp_host'])) {
        if(saveSetting($conn, 'smtp_host', $_POST['smtp_host'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['smtp_port'])) {
        if(saveSetting($conn, 'smtp_port', $_POST['smtp_port'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['smtp_username'])) {
        if(saveSetting($conn, 'smtp_username', $_POST['smtp_username'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['smtp_password'])) {
        if(saveSetting($conn, 'smtp_password', $_POST['smtp_password'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['smtp_secure'])) {
        if(saveSetting($conn, 'smtp_secure', $_POST['smtp_secure'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['from_email'])) {
        if(saveSetting($conn, 'from_email', $_POST['from_email'])) $success_count++;
        else $error_count++;
    }
    
    // Notification Settings
    $email_notifications = isset($_POST['email_notifications']) ? '1' : '0';
    if(saveSetting($conn, 'email_notifications', $email_notifications)) $success_count++;
    else $error_count++;
    
    $project_approval_emails = isset($_POST['project_approval_emails']) ? '1' : '0';
    if(saveSetting($conn, 'project_approval_emails', $project_approval_emails)) $success_count++;
    else $error_count++;
    
    $project_rejection_emails = isset($_POST['project_rejection_emails']) ? '1' : '0';
    if(saveSetting($conn, 'project_rejection_emails', $project_rejection_emails)) $success_count++;
    else $error_count++;
    
    $new_user_notifications = isset($_POST['new_user_notifications']) ? '1' : '0';
    if(saveSetting($conn, 'new_user_notifications', $new_user_notifications)) $success_count++;
    else $error_count++;
    
    // System Settings
    if(isset($_POST['max_file_size'])) {
        if(saveSetting($conn, 'max_file_size', $_POST['max_file_size'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['allowed_file_types'])) {
        if(saveSetting($conn, 'allowed_file_types', $_POST['allowed_file_types'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['session_timeout'])) {
        if(saveSetting($conn, 'session_timeout', $_POST['session_timeout'])) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['maintenance_mode'])) {
        if(saveSetting($conn, 'maintenance_mode', $_POST['maintenance_mode'])) $success_count++;
        else $error_count++;
    }
    
    // Save notification templates
    if(isset($_POST['new_user_subject']) && isset($_POST['new_user_body'])) {
        $variables = '{USER_NAME}, {USER_EMAIL}, {REGISTRATION_DATE}, {SITE_NAME}';
        if(updateNotificationTemplate($conn, 'new_user_notification', $_POST['new_user_subject'], $_POST['new_user_body'], $variables)) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['project_approval_subject']) && isset($_POST['project_approval_body'])) {
        $variables = '{USER_NAME}, {PROJECT_TITLE}, {SUBMISSION_DATE}, {APPROVAL_DATE}, {SITE_NAME}';
        if(updateNotificationTemplate($conn, 'project_approval', $_POST['project_approval_subject'], $_POST['project_approval_body'], $variables)) $success_count++;
        else $error_count++;
    }
    
    if(isset($_POST['project_rejection_subject']) && isset($_POST['project_rejection_body'])) {
        $variables = '{USER_NAME}, {PROJECT_TITLE}, {SUBMISSION_DATE}, {REVIEW_DATE}, {REJECTION_REASON}, {SITE_NAME}';
        if(updateNotificationTemplate($conn, 'project_rejection', $_POST['project_rejection_subject'], $_POST['project_rejection_body'], $variables)) $success_count++;
        else $error_count++;
    }
    
    if($error_count == 0) {
        $message = "Settings updated successfully! ($success_count settings saved)";
    } else {
        $error = "Some settings could not be saved. $error_count errors occurred.";
    }
}

// Handle test email functionality
if(isset($_POST['test_email'])) {
    try {
        $mail = new PHPMailer(true);

        // Get settings from database
        $smtp_host = getSetting($conn, 'smtp_host', 'smtp.gmail.com');
        $smtp_port = getSetting($conn, 'smtp_port', '587');
        $smtp_username = getSetting($conn, 'smtp_username', 'ideanest.ict@gmail.com');
        $smtp_password = getSetting($conn, 'smtp_password', 'luou xlhs ojuw auvx');
        $smtp_secure = getSetting($conn, 'smtp_secure', 'tls');
        $from_email = getSetting($conn, 'from_email', 'ideanest.ict@gmail.com');
        $site_name = getSetting($conn, 'site_name', 'IdeaNest');

        // Server settings
        $mail->SMTPDebug = 0; // Set to 0 for production
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
        $mail->addAddress($smtp_username, 'Test Recipient'); // Send to admin email for testing

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from ' . $site_name . ' Settings - ' . date('Y-m-d H:i:s');
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
                    
                    <h4 style="color: #4a5568;">Configuration Details:</h4>
                    <ul style="color: #4a5568;">
                        <li><strong>SMTP Host:</strong> ' . $smtp_host . '</li>
                        <li><strong>SMTP Port:</strong> ' . $smtp_port . '</li>
                        <li><strong>SMTP Security:</strong> ' . strtoupper($smtp_secure) . '</li>
                        <li><strong>From Email:</strong> ' . $from_email . '</li>
                        <li><strong>Site Name:</strong> ' . $site_name . '</li>
                    </ul>
                </div>
                
                <div style="background: #e6fffa; padding: 15px; border-radius: 8px; border-left: 4px solid #48bb78;">
                    <p style="margin: 0; color: #22543d;">
                        <strong>✅ Success!</strong> If you received this email, your email configuration is working correctly.
                    </p>
                </div>
                
                <div style="text-align: center; margin-top: 20px; color: #718096; font-size: 14px;">
                    <p>Test sent at: ' . date('F j, Y, g:i a') . '</p>
                    <p>This email was sent from the admin settings panel.</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->send();
        $message = "Test email sent successfully! Check your inbox at: " . $smtp_username;
        
    } catch (Exception $e) {
        $error = "Test email failed: " . $mail->ErrorInfo;
    }
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo $site_name; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        /* Custom Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
            padding: 1rem;
        }

        .sidebar-header {
            padding: 1rem 0;
            text-align: center;
            border-bottom: 1px solid #f1f1f1;
            margin-bottom: 1rem;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4361ee;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .sidebar-brand i {
            margin-right: 0.5rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-item {
            margin-bottom: 0.5rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #6c757d;
            text-decoration: none;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }

        .sidebar-link i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        .sidebar-link.active {
            background-color: #4361ee;
            color: #fff;
        }

        .sidebar-link:hover:not(.active) {
            background-color: #f8f9fa;
            color: #4361ee;
        }

        .sidebar-divider {
            margin: 1rem 0;
            border-top: 1px solid #f1f1f1;
        }

        .sidebar-footer {
            padding: 1rem 0;
            border-top: 1px solid #f1f1f1;
            margin-top: 1rem;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 1rem;
            transition: all 0.3s;
        }

        /* Topbar Styles */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4361ee;
            margin-left: 1rem;
        }

        /* Settings Card Styles */
        .settings-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .settings-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f1f1f1;
            background-color: #f8f9fa;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .settings-card-body {
            padding: 1.5rem;
        }

        .settings-section {
            margin-bottom: 2rem;
        }

        .settings-section:last-child {
            margin-bottom: 0;
        }

        .settings-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #2d3748;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }

        .btn-primary {
            background-color: #4361ee;
            border-color: #4361ee;
        }

        .btn-primary:hover {
            background-color: #3651d4;
            border-color: #3651d4;
        }

        /* Alert banner */
        .alert-banner {
            margin-bottom: 20px;
        }

        /* Media Query for Responsive Sidebar */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.pushed {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="bi bi-lightbulb"></i>
                <span><?php echo $site_name; ?></span>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="admin.php" class="sidebar-link">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="admin_view_project.php" class="sidebar-link">
                    <i class="bi bi-kanban"></i>
                    <span>Projects</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="user_manage_by_admin.php" class="sidebar-link">
                    <i class="bi bi-people"></i>
                    <span>Users Management</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="subadmin/add_subadmin.php" class="sidebar-link">
                    <i class="bi bi-person-plus"></i>
                    <span>Add Subadmin</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="notifications.php" class="sidebar-link">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
         
            <li class="sidebar-item">
                <a href="settings.php" class="sidebar-link active">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <button class="btn d-lg-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="page-title">Settings</h1>
            <div class="topbar-actions">
                <div class="dropdown">
                    <a href="#" class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if($message): ?>
            <div class="alert alert-success alert-banner alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger alert-banner alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Settings Content -->
        <div class="settings-content">
            <form method="POST" action="settings.php">
                <!-- General Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gear me-2"></i>
                            General Settings
                        </h5>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="site_name" class="form-label">Site Name</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars(getSetting($conn, 'site_name', 'IdeaNest')); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="site_url" class="form-label">Site URL</label>
                                        <input type="url" class="form-control" id="site_url" name="site_url" value="<?php echo htmlspecialchars(getSetting($conn, 'site_url', 'http://localhost/IdeaNest')); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="admin_email" class="form-label">Admin Email</label>
                                        <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars(getSetting($conn, 'admin_email', 'ideanest.ict@gmail.com')); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="timezone" class="form-label">Timezone</label>
                                        <select class="form-select" id="timezone" name="timezone">
                                            <option value="Asia/Kolkata" <?php echo (getSetting($conn, 'timezone', 'Asia/Kolkata') == 'Asia/Kolkata') ? 'selected' : ''; ?>>Asia/Kolkata (IST)</option>
                                            <option value="UTC" <?php echo (getSetting($conn, 'timezone', 'Asia/Kolkata') == 'UTC') ? 'selected' : ''; ?>>UTC</option>
                                            <option value="America/New_York" <?php echo (getSetting($conn, 'timezone', 'Asia/Kolkata') == 'America/New_York') ? 'selected' : ''; ?>>America/New_York (EST)</option>
                                            <option value="Europe/London" <?php echo (getSetting($conn, 'timezone', 'Asia/Kolkata') == 'Europe/London') ? 'selected' : ''; ?>>Europe/London (GMT)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-envelope me-2"></i>
                            Email Settings
                        </h5>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="smtp_host" class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars(getSetting($conn, 'smtp_host', 'smtp.gmail.com')); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars(getSetting($conn, 'smtp_port', '587')); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="smtp_username" class="form-label">SMTP Username</label>
                                        <input type="email" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars(getSetting($conn, 'smtp_username', 'ideanest.ict@gmail.com')); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="smtp_password" class="form-label">SMTP Password</label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars(getSetting($conn, 'smtp_password', 'luou xlhs ojuw auvx')); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="smtp_secure" class="form-label">SMTP Security</label>
                                        <select class="form-select" id="smtp_secure" name="smtp_secure">
                                            <option value="tls" <?php echo (getSetting($conn, 'smtp_secure', 'tls') == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo (getSetting($conn, 'smtp_secure', 'tls') == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                            <option value="none" <?php echo (getSetting($conn, 'smtp_secure', 'tls') == 'none') ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="from_email" class="form-label">From Email</label>
                                        <input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo htmlspecialchars(getSetting($conn, 'from_email', 'ideanest.ict@gmail.com')); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="test_email" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-envelope me-2"></i>
                                    Test Email Configuration
                                </button>
                                <a href="test_email_admin.php" class="btn btn-outline-info">
                                    <i class="bi bi-gear me-2"></i>
                                    Advanced Test Email
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-bell me-2"></i>
                            Notification Settings
                        </h5>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-section">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" <?php echo (getSetting($conn, 'email_notifications', '1') == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_notifications">
                                        Enable Email Notifications
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="project_approval_emails" name="project_approval_emails" <?php echo (getSetting($conn, 'project_approval_emails', '1') == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="project_approval_emails">
                                        Send emails when projects are approved
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="project_rejection_emails" name="project_rejection_emails" <?php echo (getSetting($conn, 'project_rejection_emails', '1') == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="project_rejection_emails">
                                        Send emails when projects are rejected
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="new_user_notifications" name="new_user_notifications" <?php echo (getSetting($conn, 'new_user_notifications', '0') == '1') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="new_user_notifications">
                                        Notify admin when new users register
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Templates -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-file-text me-2"></i>
                            Email Templates
                        </h5>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-section">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Email Templates:</strong> Customize the email templates for different notification types. Use variables like {USER_NAME}, {PROJECT_TITLE}, {SITE_NAME} in your templates.
                            </div>
                            
                            <!-- New User Notification Template -->
                            <div class="form-group">
                                <label for="new_user_subject" class="form-label">New User Notification - Subject</label>
                                <input type="text" class="form-control" id="new_user_subject" name="new_user_subject" 
                                       value="<?php 
                                       $template = getNotificationTemplate($conn, 'new_user_notification');
                                       echo htmlspecialchars($template ? $template['subject'] : 'New User Registration - {SITE_NAME}');
                                       ?>">
                            </div>
                            <div class="form-group">
                                <label for="new_user_body" class="form-label">New User Notification - Body</label>
                                <textarea class="form-control" id="new_user_body" name="new_user_body" rows="6"><?php 
                                echo htmlspecialchars($template ? $template['body'] : '<h2>New User Registration</h2>
<p>A new user has registered on {SITE_NAME}:</p>
<ul>
<li><strong>Name:</strong> {USER_NAME}</li>
<li><strong>Email:</strong> {USER_EMAIL}</li>
<li><strong>Registration Date:</strong> {REGISTRATION_DATE}</li>
</ul>
<p>Please review the user account in the admin panel.</p>');
                                ?></textarea>
                            </div>

                            <!-- Project Approval Template -->
                            <div class="form-group">
                                <label for="project_approval_subject" class="form-label">Project Approval - Subject</label>
                                <input type="text" class="form-control" id="project_approval_subject" name="project_approval_subject" 
                                       value="<?php 
                                       $template = getNotificationTemplate($conn, 'project_approval');
                                       echo htmlspecialchars($template ? $template['subject'] : 'Congratulations! Your Project "{PROJECT_TITLE}" Has Been Approved');
                                       ?>">
                            </div>
                            <div class="form-group">
                                <label for="project_approval_body" class="form-label">Project Approval - Body</label>
                                <textarea class="form-control" id="project_approval_body" name="project_approval_body" rows="8"><?php 
                                echo htmlspecialchars($template ? $template['body'] : '<h2>Project Approved!</h2>
<p>Dear {USER_NAME},</p>
<p>We are pleased to inform you that your project "<strong>{PROJECT_TITLE}</strong>" has been approved!</p>
<p><strong>Project Details:</strong></p>
<ul>
<li><strong>Project Title:</strong> {PROJECT_TITLE}</li>
<li><strong>Submission Date:</strong> {SUBMISSION_DATE}</li>
<li><strong>Approval Date:</strong> {APPROVAL_DATE}</li>
</ul>
<p>You can now proceed with your project implementation.</p>
<p>Best regards,<br>The {SITE_NAME} Team</p>');
                                ?></textarea>
                            </div>

                            <!-- Project Rejection Template -->
                            <div class="form-group">
                                <label for="project_rejection_subject" class="form-label">Project Rejection - Subject</label>
                                <input type="text" class="form-control" id="project_rejection_subject" name="project_rejection_subject" 
                                       value="<?php 
                                       $template = getNotificationTemplate($conn, 'project_rejection');
                                       echo htmlspecialchars($template ? $template['subject'] : 'Important Update About Your Project "{PROJECT_TITLE}"');
                                       ?>">
                            </div>
                            <div class="form-group">
                                <label for="project_rejection_body" class="form-label">Project Rejection - Body</label>
                                <textarea class="form-control" id="project_rejection_body" name="project_rejection_body" rows="10"><?php 
                                echo htmlspecialchars($template ? $template['body'] : '<h2>Project Status Update</h2>
<p>Dear {USER_NAME},</p>
<p>Thank you for submitting your project "<strong>{PROJECT_TITLE}</strong>" to {SITE_NAME}.</p>
<p>After careful review, we regret to inform you that your project could not be approved at this time.</p>
<p><strong>Reason:</strong> {REJECTION_REASON}</p>
<p><strong>Project Details:</strong></p>
<ul>
<li><strong>Project Title:</strong> {PROJECT_TITLE}</li>
<li><strong>Submission Date:</strong> {SUBMISSION_DATE}</li>
<li><strong>Review Date:</strong> {REVIEW_DATE}</li>
</ul>
<p>We encourage you to review the feedback and consider resubmitting your project after addressing the mentioned concerns.</p>
<p>Best regards,<br>The {SITE_NAME} Team</p>');
                                ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-cpu me-2"></i>
                            System Settings
                        </h5>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_file_size" class="form-label">Maximum File Upload Size (MB)</label>
                                        <input type="number" class="form-control" id="max_file_size" name="max_file_size" value="<?php echo htmlspecialchars(getSetting($conn, 'max_file_size', '10')); ?>" min="1" max="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="allowed_file_types" class="form-label">Allowed File Types</label>
                                        <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" value="<?php echo htmlspecialchars(getSetting($conn, 'allowed_file_types', 'jpg,jpeg,png,gif,pdf,zip,rar')); ?>" placeholder="jpg,jpeg,png,gif,pdf,zip,rar">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                        <input type="number" class="form-control" id="session_timeout" name="session_timeout" value="<?php echo htmlspecialchars(getSetting($conn, 'session_timeout', '30')); ?>" min="5" max="1440">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="maintenance_mode" class="form-label">Maintenance Mode</label>
                                        <select class="form-select" id="maintenance_mode" name="maintenance_mode">
                                            <option value="0" <?php echo (getSetting($conn, 'maintenance_mode', '0') == '0') ? 'selected' : ''; ?>>Disabled</option>
                                            <option value="1" <?php echo (getSetting($conn, 'maintenance_mode', '0') == '1') ? 'selected' : ''; ?>>Enabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="d-flex justify-content-end">
                    <button type="submit" name="save_settings" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-content').classList.toggle('pushed');
        });

        // Auto-save form data to localStorage
        document.querySelectorAll('input, select, textarea').forEach(function(element) {
            element.addEventListener('change', function() {
                const formData = new FormData(document.querySelector('form'));
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                localStorage.setItem('settingsFormData', JSON.stringify(data));
            });
        });

        // Load saved form data on page load
        window.addEventListener('load', function() {
            const savedData = localStorage.getItem('settingsFormData');
            if (savedData) {
                const data = JSON.parse(savedData);
                for (let key in data) {
                    const element = document.querySelector(`[name="${key}"]`);
                    if (element) {
                        element.value = data[key];
                    }
                }
            }
        });
    </script>
</body>
</html> 