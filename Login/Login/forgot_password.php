<?php
session_start();
include 'db.php';

// Include simple email functions
require_once '../../includes/simple_smtp.php';

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
            $stmt = $conn->prepare("UPDATE $table SET password = ? WHERE id = ?");
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
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | IdeaNest</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
<div class="login-container">
    <div class="logo-section">
        <div class="logo">
            <i class="fas fa-<?php echo $step == 'email' ? 'key' : ($step == 'otp' ? 'shield-alt' : ($step == 'reset' ? 'lock' : 'check-circle')); ?>"></i>
        </div>
        <div class="welcome-text">
            <h1><?php
                echo $step == 'email' ? 'Forgot Password' :
                    ($step == 'otp' ? 'Verify OTP' :
                    ($step == 'reset' ? 'Reset Password' : 'Success'));
                ?></h1>
            <p><?php
                echo $step == 'email' ? 'Enter your registered email address to receive OTP' :
                    ($step == 'otp' ? 'Enter the 6-digit code sent to your email' :
                    ($step == 'reset' ? 'Enter your new password' : 'Password has been reset successfully'));
                ?></p>
        </div>
    </div>

    <?php if ($error_message) : ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success_message) : ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($step == 'email') : ?>
        <form class="form-section" method="post">
            <div class="input-group">
                <input type="email" name="email" placeholder="Enter your email address" required>
                <i class="fas fa-envelope input-icon"></i>
            </div>
            <button type="submit" name="send_otp" class="login-btn">
                <i class="fas fa-paper-plane"></i>
                Send OTP
            </button>
        </form>
    <?php elseif ($step == 'otp') : ?>
        <form class="form-section" method="post">
            <div class="input-group">
                <input type="text" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" required>
                <i class="fas fa-key input-icon"></i>
            </div>
            <button type="submit" name="verify_otp" class="login-btn">
                <i class="fas fa-check"></i>
                Verify OTP
            </button>
        </form>
    <?php elseif ($step == 'reset') : ?>
        <form class="form-section" method="post">
            <div class="input-group">
                <input type="password" name="new_password" placeholder="New Password" required>
                <i class="fas fa-lock input-icon"></i>
            </div>
            <div class="input-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <i class="fas fa-lock input-icon"></i>
            </div>
            <button type="submit" name="reset_password" class="login-btn">
                <i class="fas fa-save"></i>
                Update Password
            </button>
        </form>
    <?php else : ?>
        <div class="form-section">
            <script>
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 3000);
            </script>
            <p style="text-align: center; color: #10B981; margin: 2rem 0;">
                Redirecting to login page...
            </p>
        </div>
    <?php endif; ?>

    <div class="register-link">
        <p><a href="login.php">Back to Login</a></p>
    </div>
</div>
</body>
</html>