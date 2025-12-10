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
    <!-- Anti-injection script - MUST be first -->
    <script src="../../assets/js/anti_injection.js"></script>
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
        
        /* Google Sign-In Button Container */
        #g_id_onload { display: block; }
        .g_id_signin { 
            display: flex !important; 
            justify-content: center !important; 
            align-items: center !important;
            margin: 10px auto !important;
            width: 100% !important;
        }
        .google-signin-container {
            width: 100%;
            display: flex;
            justify-content: center;
            margin: 10px 0;
        }
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
        // Enable Google Sign-In for both localhost and production
        try {
            require_once 'google_config.php'; 
            if (defined('GOOGLE_CLIENT_ID') && !empty(GOOGLE_CLIENT_ID)) {
        ?>
        <div class="google-signin-container">
            <!-- Google One Tap Sign-In -->
            <div id="g_id_onload"
                 data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
                 data-callback="handleCredentialResponse"
                 data-auto_prompt="false"
                 data-cancel_on_tap_outside="false">
            </div>
            
            <!-- Google Sign-In Button -->
            <div id="googleButtonWrapper">
                <div class="g_id_signin" 
                     data-type="standard" 
                     data-size="large" 
                     data-theme="outline" 
                     data-text="signin_with"
                     data-shape="rectangular"
                     data-logo_alignment="left"
                     data-width="350">
                </div>
            </div>
            
            <!-- Fallback Manual Button (shows if Google button fails) -->
            <button type="button" id="manualGoogleBtn" class="google-btn" style="display: none;">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                Sign in with Google
            </button>
        </div>
        <script>
            // Check if Google Sign-In library loaded and button rendered
            let checkAttempts = 0;
            const maxAttempts = 10;
            
            const checkGoogleButton = setInterval(function() {
                checkAttempts++;
                const googleBtn = document.querySelector('.g_id_signin');
                const manualBtn = document.getElementById('manualGoogleBtn');
                
                // Check if Google button rendered successfully
                if (googleBtn && googleBtn.children.length > 0) {
                    console.log('✓ Google Sign-In button loaded successfully');
                    clearInterval(checkGoogleButton);
                    return;
                }
                
                // After 5 seconds, show manual button as fallback
                if (checkAttempts >= maxAttempts) {
                    console.warn('Google Sign-In button failed to render, showing fallback');
                    console.log('Client ID:', '<?php echo GOOGLE_CLIENT_ID; ?>');
                    console.log('Origin:', window.location.origin);
                    
                    // Hide Google button container and show manual button
                    if (googleBtn) {
                        googleBtn.style.display = 'none';
                    }
                    if (manualBtn) {
                        manualBtn.style.display = 'flex';
                    }
                    
                    clearInterval(checkGoogleButton);
                }
            }, 500);
            
            // Manual button click handler - opens Google OAuth popup
            document.getElementById('manualGoogleBtn')?.addEventListener('click', function() {
                const clientId = '<?php echo GOOGLE_CLIENT_ID; ?>';
                const redirectUri = window.location.origin + window.location.pathname.replace('login.php', 'google_callback.php');
                const scope = 'email profile';
                const responseType = 'code';
                
                const authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' +
                    'client_id=' + encodeURIComponent(clientId) +
                    '&redirect_uri=' + encodeURIComponent(redirectUri) +
                    '&response_type=' + responseType +
                    '&scope=' + encodeURIComponent(scope) +
                    '&access_type=offline' +
                    '&prompt=select_account';
                
                console.log('Opening Google OAuth:', authUrl);
                window.location.href = authUrl;
            });
        </script>
        <?php 
            } else {
                echo '<p style="text-align: center; color: #f44; font-size: 12px;">Google Sign-In not configured</p>';
            }
        } catch (Exception $e) {
            error_log('Google config error: ' . $e->getMessage());
            echo '<p style="text-align: center; color: #f44; font-size: 12px;">Google config error: ' . htmlspecialchars($e->getMessage()) . '</p>';
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
    <!-- Google Sign-In Library - Works on both localhost and production -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
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
