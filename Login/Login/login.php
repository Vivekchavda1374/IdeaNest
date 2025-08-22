<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['er_number'], $_POST['password'])) {
        $error_message = "Invalid form submission";
    } else {
        $er_number = $_POST['er_number'];
        $password = $_POST['password'];

        // Check for admin credentials first
        if($er_number === "ideanest.ict@gmail.com" && $password === "ideanest133"){
            // Set admin session variables
            $_SESSION['user_id'] = 'admin';
            $_SESSION['er_number'] = $er_number;
            $_SESSION['user_name'] = 'Administrator';
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_logged_in'] = true; // Add this line for admin session validation

            header("Location: ../../Admin/admin.php");
            exit(); // Stop execution after redirect
        }

        // If not admin, proceed with regular user login
        $stmt = $conn->prepare("SELECT id, password, name FROM register WHERE enrollment_number = ? ");
        $stmt->bind_param("s", $er_number);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $hashed_password, $user_name);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['er_number'] = $er_number;
                $_SESSION['user_name'] = $user_name;
                $_SESSION['is_admin'] = false;

                header("Location: ../../user/index.php");
                exit();
            } else {
                $error_message = "Incorrect Password!";
            }
        } else {
            // If not found in register, check subadmins table using email
            $stmt2 = $conn->prepare("SELECT id, password FROM subadmins WHERE email = ?");
            $stmt2->bind_param("s", $er_number);
            $stmt2->execute();
            $stmt2->store_result();
            if ($stmt2->num_rows > 0) {
                $stmt2->bind_result($subadmin_id, $subadmin_hashed_password);
                $stmt2->fetch();
                if (password_verify($password, $subadmin_hashed_password)) {
                    $_SESSION['subadmin_id'] = $subadmin_id;
                    $_SESSION['subadmin_email'] = $er_number;
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