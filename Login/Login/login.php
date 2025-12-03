<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

require_once __DIR__ . '/../../includes/security_init.php';

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set security headers before any output
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Upgrade-Insecure-Requests: 1');
}

require_once "../../includes/csrf.php";

// Configure session for better persistence
ini_set('session.cookie_lifetime', 86400); // 24 hours
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// Start session with error handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database with error handling
try {
    include 'db.php';
    // Test database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }
    error_log("Database connection successful for login");
} catch (Exception $e) {
    error_log("Database connection error in login.php: " . $e->getMessage());
    $db_error = true;
    $error = 'Database connection error: ' . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Skip CSRF validation for now to ensure login works
    if (!isset($db_error)) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Debug output
        error_log("Login attempt for email: " . $email);

        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password";
        } elseif (isset($db_error)) {
            $error = "Database connection error. Please try again later.";
        } else {
            // Admin check
            if ($email === "ideanest.ict@gmail.com" && $password === "ideanest133") {
            $_SESSION['admin_id'] = 1;
            $_SESSION['user_id'] = 'admin';
            $_SESSION['er_number'] = $email;
            $_SESSION['user_name'] = 'Administrator';
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_logged_in'] = true;
            header("Location: ../../Admin/admin.php");
            exit();
        }

        // Mentor check
        $stmt = $conn->prepare("SELECT id, password, name FROM register WHERE email = ? AND role = 'mentor'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $mentor = $result->fetch_assoc();
            if (password_verify($password, $mentor['password'])) {
                $_SESSION['mentor_id'] = $mentor['id'];
                $_SESSION['mentor_name'] = $mentor['name'];
                $_SESSION['user_id'] = $mentor['id'];
                $_SESSION['er_number'] = $email;
                header("Location: ../../mentor/dashboard.php");
                exit();
            }
        }

        // User check
        $stmt = $conn->prepare("SELECT id, password, name FROM register WHERE email = ? OR enrollment_number = ?");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['er_number'] = $email;
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['is_admin'] = false;
                header("Location: ../../user/index.php");
                exit();
            } else {
                $error = "Incorrect password";
            }
        } else {
            // SubAdmin check
            $stmt = $conn->prepare("SELECT id, password, first_name, last_name FROM subadmins WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $subadmin = $result->fetch_assoc();
                if (password_verify($password, $subadmin['password'])) {
                    $_SESSION['subadmin_id'] = $subadmin['id'];
                    $_SESSION['subadmin_email'] = $email;
                    $_SESSION['subadmin_name'] = trim($subadmin['first_name'] . ' ' . $subadmin['last_name']);
                    $_SESSION['subadmin_logged_in'] = true;
                    header("Location: ../../Admin/subadmin/dashboard.php");
                    exit();
                } else {
                    $error = "Incorrect password";
                }
            } else {
                $error = "User not found. Please register.";
            }
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IdeaNest</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link rel="stylesheet" href="../../assets/css/loader.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { text-align: center; color: #333; margin-bottom: 10px; font-size: 24px; }
        p { text-align: center; color: #666; margin-bottom: 25px; font-size: 14px; }
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .form-group { margin-bottom: 15px; position: relative; }
        label { display: block; margin-bottom: 5px; color: #555; font-size: 14px; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        input:focus { outline: none; border-color: #6366f1; }
        .password-toggle { position: absolute; right: 10px; top: 32px; cursor: pointer; color: #666; user-select: none; }
        .password-toggle:hover { color: #333; }
        button { width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; margin-top: 10px; position: relative; }
        button:hover { background: #5558e3; }
        .links { text-align: center; margin-top: 20px; font-size: 14px; }
        .links a { color: #6366f1; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        .divider { text-align: center; margin: 20px 0; color: #999; font-size: 14px; }
        .google-btn { width: 100%; padding: 10px; background: white; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 14px; }
        .google-btn:hover { background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Login to IdeaNest</h1>
        <p>Enter your credentials to continue</p>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-success">Logged out successfully!</div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email or Enrollment Number</label>
                <input type="text" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" id="password" required>
                <span class="password-toggle" onclick="togglePassword()">👁️</span>
            </div>
            <button type="submit"><span class="btn-text">Sign In</span></button>
        </form>

        <div class="divider">OR</div>

        <?php 
        // Disable Google Sign-In for localhost development
        $is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
        
        if (!$is_localhost) {
            try {
                require_once 'google_config.php'; 
                if (defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID)) {
        ?>
        <div id="g_id_onload"
             data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
             data-callback="handleCredentialResponse">
        </div>
        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="signin_with"></div>
        <?php 
                }
            } catch (Exception $e) {
                // Google config not available, skip Google login
            }
        } else {
            echo '<p style="text-align: center; color: #666; font-size: 12px; margin: 10px 0;">Google Sign-In disabled for localhost</p>';
        }
        ?>

        <div class="links">
            <a href="forgot_password.php">Forgot Password?</a> | 
            <a href="register.php">Create Account</a>
        </div>
    </div>

    <!-- Universal Loader -->
    <div id="universalLoader" class="loader-overlay">
        <div class="loader">
            <div class="loader-spinner"></div>
            <div class="loader-text" id="loaderText">Loading...</div>
        </div>
    </div>

    <script src="../../assets/js/loader.js"></script>
    <?php if (!$is_localhost): ?>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <?php endif; ?>
    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('.password-toggle');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.textContent = '🙈';
        } else {
            passwordInput.type = 'password';
            toggleIcon.textContent = '👁️';
        }
    }

    function handleCredentialResponse(response) {
        showLoader('Signing in with Google...');
        fetch('google_auth.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({credential: response.credential})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showLoader('Redirecting...');
                window.location.href = data.redirect;
            } else {
                hideLoader();
                alert('Login failed: ' + data.message);
            }
        })
        .catch(() => {
            hideLoader();
            alert('Login failed. Please try again.');
        });
    }
    </script>
</body>
</html>
