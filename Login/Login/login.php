<?php
require_once "../../includes/csrf.php";
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCSRF();
    if (!isset($_POST['email'], $_POST['password'])) {
        $error_message = "Invalid form submission";
    } else {
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Check for admin credentials first
        if ($email === "ideanest.ict@gmail.com" && $password === "ideanest133") {
            // Set admin session variables
            $_SESSION['admin_id'] = 1;
            $_SESSION['user_id'] = 'admin';
            $_SESSION['er_number'] = $email;
            $_SESSION['user_name'] = 'Administrator';
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_logged_in'] = true;

            header("Location: ../../Admin/admin.php");
            exit();
        }

        // Check for mentor login using email
        $mentor_stmt = $conn->prepare("SELECT id, password, name FROM register WHERE email = ? AND role = 'mentor'");
        $mentor_stmt->bind_param("s", $email);
        $mentor_stmt->execute();
        $mentor_stmt->store_result();

        if ($mentor_stmt->num_rows > 0) {
            $mentor_stmt->bind_result($mentor_id, $mentor_hashed_password, $mentor_name);
            $mentor_stmt->fetch();

            if (password_verify($password, $mentor_hashed_password)) {
                $_SESSION['mentor_id'] = $mentor_id;
                $_SESSION['mentor_name'] = $mentor_name;
                $_SESSION['user_id'] = $mentor_id;
                $_SESSION['er_number'] = $email;
                header("Location: ../../mentor/dashboard.php");
                exit();
            } else {
                $error_message = "Incorrect Password!";
            }
        }
        $mentor_stmt->close();

        // If not mentor, proceed with regular user login
        $stmt = $conn->prepare("SELECT id, password, name FROM register WHERE email = ? OR enrollment_number = ? ");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $hashed_password, $user_name);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['er_number'] = $email;
                $_SESSION['user_name'] = $user_name;
                $_SESSION['is_admin'] = false;

                header("Location: ../../user/index.php");
                exit();
            } else {
                $error_message = "Incorrect Password!";
            }
        } else {
            // If not found in register, check subadmins table using email
            $stmt2 = $conn->prepare("SELECT id, password, name FROM subadmins WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $stmt2->store_result();
            if ($stmt2->num_rows > 0) {
                $stmt2->bind_result($subadmin_id, $subadmin_hashed_password, $subadmin_name);
                $stmt2->fetch();
                if (password_verify($password, $subadmin_hashed_password)) {
                    $_SESSION['subadmin_id'] = $subadmin_id;
                    $_SESSION['subadmin_email'] = $email;
                    $_SESSION['subadmin_name'] = $subadmin_name;
                    $_SESSION['subadmin_logged_in'] = true;
                    header("Location: ../../Admin/subadmin/dashboard.php");
                    exit();
                } else {
                    $error_message = "Incorrect Password!";
                }
            } else {
                $error_message = "User not found! Please register.";
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IdeaNest</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/login.css">
    <link rel="stylesheet" href="../../assets/css/loading.css">
    <style>
    .google-login {
        margin: 1rem 0;
        display: flex;
        justify-content: center;
    }
    .divider {
        text-align: center;
        margin: 1.5rem 0;
        position: relative;
    }
    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #e0e0e0;
    }
    .divider span {
        background: white;
        padding: 0 1rem;
        color: #666;
        font-size: 0.9rem;
    }
    </style>
</head>
<body>
<div class="login-container">
    <div class="logo-section">
        <div class="logo">
            <i class="fas fa-lightbulb"></i>
        </div>
        <div class="welcome-text">
            <h1>Welcome Back</h1>
            <p>Sign in to continue to IdeaNest</p>
        </div>
    </div>

    <form class="form-section" method="post" autocomplete="off">
        <!-- Error/Success Messages -->
        <?php if (isset($error_message)) : ?>
        <div class="alert alert-error" id="error-alert">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success') : ?>
        <div class="alert alert-success" id="success-alert">
            <i class="fas fa-check-circle"></i>
            <span>You have been successfully logged out!</span>
        </div>
        <?php else : ?>
        <div class="alert alert-success" style="display: none;" id="success-alert">
            <i class="fas fa-check-circle"></i>
            <span>You have been successfully logged out!</span>
        </div>
        <?php endif; ?>

        <div class="input-group">
            <input type="text" id="email" name="email" placeholder="Enter your ER number or Email" required autofocus>
            <i class="fas fa-user input-icon"></i>
        </div>

        <div class="input-group">
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <i class="fas fa-lock input-icon"></i>
        </div>

        <div class="forgot-password">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>

        <?php echo getCSRFField(); ?>
        <button type="submit" class="login-btn">
            <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
            Sign In
        </button>
    </form>

    <div class="divider">
        <span>or</span>
    </div>

    <div class="google-login">
        <?php 
        require_once 'google_config.php';
        ?>
        <div id="g_id_onload"
             data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
             data-callback="handleCredentialResponse"
             data-auto_prompt="false"
             data-cancel_on_tap_outside="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-size="large"
             data-theme="outline"
             data-text="signin_with"
             data-shape="rectangular"
             data-logo_alignment="left"
             data-width="300">
        </div>
    </div>

    <div class="divider">
        <span>New to IdeaNest?</span>
    </div>

    <div class="register-link">
        <p>Don't have an account?
            <a href="register.php">Create Account</a>
        </p>
    </div>
</div>

<script src="../../assets/js/loading.js"></script>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
function handleCredentialResponse(response) {
    if (!response.credential) {
        alert('Google authentication failed');
        return;
    }
    
    // Show loading for Google authentication
    window.loadingManager.show('Authenticating with Google...');
    
    fetch('google_auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            credential: response.credential
        }),
        noLoading: true // Prevent double loading
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    })
    .then(data => {
        window.loadingManager.hide();
        if (data.success) {
            window.loadingManager.show('Redirecting...');
            window.location.href = data.redirect;
        } else {
            alert('Login failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        window.loadingManager.hide();
        console.error('Error:', error);
        alert('Login failed. Please try again.');
    });
}

// Add loading to regular login form
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('.form-section');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.login-btn');
            if (submitBtn) {
                window.loadingManager.show('Signing in...');
                setButtonLoading(submitBtn, true, 'Signing in...');
            }
        });
    }
});
</script>
<script src="../../assets/js/login.js"></script>



</body>
</html>