<?php
require_once __DIR__ . '/../../includes/security_init.php';
// Production-safe error reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
ini_set('error_log', '../../logs/forgot_password_errors.log');

session_start();
include 'db.php';

// Include simple email functions
try {
    require_once '../../includes/simple_smtp.php';
} catch (Exception $e) {
    error_log("Failed to load email functions: " . $e->getMessage());
    die("System error. Please contact administrator.");
}

$step = $_GET['step'] ?? 'email';
$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['send_otp'])) {
        $email = $_POST['email'];

        // Check in register table (students and mentors)
        $stmt = $conn->prepare("SELECT id, name, 'register' as table_type FROM register WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = null;
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        } else {
            // Check in subadmins table
            $stmt2 = $conn->prepare("SELECT id, name, 'subadmins' as table_type FROM subadmins WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($result2->num_rows > 0) {
                $user = $result2->fetch_assoc();
            }
            $stmt2->close();
        }
        $stmt->close();

        if ($user) {
            $otp = rand(100000, 999999);

            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_table'] = $user['table_type'];
            $_SESSION['otp_time'] = time();

            $subject = 'Password Reset OTP - IdeaNest';
            $message = "Your OTP for password reset is: $otp\n\nThis OTP is valid for 10 minutes.";
            
            if (sendEmail($email, $subject, $message)) {
                $step = 'otp';
                $success_message = "OTP sent to your email address.";
            } else {
                $error_message = "Failed to send OTP. Email service temporarily unavailable.";
            }
        } else {
            $error_message = "Email not found.";
        }
    }

    if (isset($_POST['verify_otp'])) {
        if (!isset($_SESSION['reset_otp']) || time() - $_SESSION['otp_time'] > 600) {
            $error_message = "OTP expired. Please request a new one.";
            $step = 'email';
        } else {
            $entered_otp = $_POST['otp'];
            if ($entered_otp == $_SESSION['reset_otp']) {
                $step = 'reset';
                $success_message = "OTP verified successfully.";
            } else {
                $error_message = "Invalid OTP. Please try again.";
                $step = 'otp';
            }
        }
    }

    if (isset($_POST['reset_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error_message = "Passwords do not match.";
            $step = 'reset';
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters.";
            $step = 'reset';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $table = $_SESSION['reset_table'];
            
            // Validate table name to prevent SQL injection
            if ($table !== 'register' && $table !== 'subadmins') {
                $error_message = "Invalid session data. Please try again.";
                $step = 'email';
            } else {
                $query = "UPDATE " . $table . " SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                
                if ($stmt) {
                    $stmt->bind_param("si", $hashed_password, $_SESSION['reset_user_id']);
                    
                    if ($stmt->execute()) {
                        unset($_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['reset_user_id'], $_SESSION['reset_table'], $_SESSION['otp_time']);
                        $success_message = "Password updated successfully!";
                        $step = 'complete';
                    } else {
                        $error_message = "Failed to update password. Please try again.";
                        $step = 'reset';
                    }
                    $stmt->close();
                } else {
                    $error_message = "Database error. Please try again.";
                    $step = 'reset';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../../assets/js/anti_injection.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $step == 'email' ? 'Forgot Password' : ($step == 'otp' ? 'Verify OTP' : ($step == 'reset' ? 'Reset Password' : 'Success')); ?> - IdeaNest</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { text-align: center; color: #333; margin-bottom: 10px; font-size: 24px; }
        p { text-align: center; color: #666; margin-bottom: 25px; font-size: 14px; }
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-size: 14px; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        input:focus { outline: none; border-color: #6366f1; }
        button { width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #5558e3; }
        .links { text-align: center; margin-top: 20px; font-size: 14px; }
        .links a { color: #6366f1; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        .success-icon { text-align: center; font-size: 48px; color: #10b981; margin-bottom: 20px; }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
    <link rel="stylesheet" href="../assets/css/loading.css">
</head>
<body>
    <div class="container">
        <h1><?php
            echo $step == 'email' ? '🔑 Forgot Password' :
                ($step == 'otp' ? '🔐 Verify OTP' :
                ($step == 'reset' ? '🔒 Reset Password' : '✅ Success'));
            ?></h1>
        <p><?php
            echo $step == 'email' ? 'Enter your email to receive OTP' :
                ($step == 'otp' ? 'Enter the 6-digit code sent to your email' :
                ($step == 'reset' ? 'Enter your new password' : 'Password reset successfully'));
            ?></p>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if ($step == 'email'): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required autofocus>
                </div>
                <button type="submit" name="send_otp">Send OTP</button>
            </form>
        <?php elseif ($step == 'otp'): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Enter OTP</label>
                    <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" required autofocus placeholder="000000">
                </div>
                <button type="submit" name="verify_otp">Verify OTP</button>
            </form>
        <?php elseif ($step == 'reset'): ?>
            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required autofocus>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" name="reset_password">Update Password</button>
            </form>
        <?php else: ?>
            <div class="success-icon">✓</div>
            <script>setTimeout(() => window.location.href = 'login.php', 3000);</script>
            <p style="color: #10b981;">Redirecting to login...</p>
        <?php endif; ?>

        <div class="links">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
<script src="../assets/js/loading.js"></script>
</body>
</html>