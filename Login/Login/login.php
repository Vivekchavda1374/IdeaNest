<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IdeaNest</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/login.css">
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
        <div class="alert alert-error" style="display: none;" id="error-alert">
            <i class="fas fa-exclamation-circle"></i>
            <span>Invalid credentials. Please try again.</span>
        </div>

        <div class="alert alert-success" style="display: none;" id="success-alert">
            <i class="fas fa-check-circle"></i>
            <span>You have been successfully logged out!</span>
        </div>

        <div class="input-group">
            <input type="text" id="er_number" name="er_number" placeholder="Enter your ER number" required autofocus>
            <i class="fas fa-user input-icon"></i>
        </div>

        <div class="input-group">
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <i class="fas fa-lock input-icon"></i>
        </div>

        <div class="forgot-password">
            <a href="#" onclick="alert('Contact admin to reset password')">Forgot Password?</a>
        </div>

        <button type="submit" class="login-btn">
            <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>
            Sign In
        </button>
    </form>

    <div class="divider">
        <span>New to IdeaNest?</span>
    </div>

    <div class="register-link">
        <p>Don't have an account?
            <a href="register.php">Create Account</a>
        </p>
    </div>
</div>

<script src="../../assets/js/login.js"></script>

</body>
</html>