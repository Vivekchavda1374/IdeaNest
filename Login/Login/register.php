<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $conn = new mysqli("localhost", "root", "", "ideanest");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $name = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $enrollment_number = trim($_POST['enrollment_number'] ?? '');
    $gr_number = trim($_POST['gr_number'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $phone_no = trim($_POST['phone_no'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $passout_year = trim($_POST['passout_year'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Validation
    if (!$name || !$email || !$enrollment_number || !$gr_number || !$password || !$confirm || !$passout_year) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check for duplicate email
        $stmt = $conn->prepare("SELECT id FROM register WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $user_image = ""; // or a default image path if you want
            $stmt = $conn->prepare("INSERT INTO register (name, email, enrollment_number, gr_number, password, about, phone_no, department, passout_year, user_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $name, $email, $enrollment_number, $gr_number, $hashed_password, $about, $phone_no, $department, $passout_year, $user_image);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;

                // Send new user notification to admin
                include "../../Admin/notification_backend.php";
                $notification_result = sendNewUserNotificationToAdmin($user_id, $conn);

                // Log the notification
                $user_query = "SELECT * FROM register WHERE id = ?";
                $user_stmt = $conn->prepare($user_query);
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user = $user_result->fetch_assoc();

                $admin_email = getSetting($conn, 'admin_email', 'ideanest.ict@gmail.com');
                $email_subject = "New User Registration - " . getSetting($conn, 'site_name', 'IdeaNest');
                $error_message = $notification_result['success'] ? null : $notification_result['message'];
                logNotification('new_user_notification', $user_id, $conn,
                        $notification_result['success'] ? 'sent' : 'failed', null, $admin_email, $email_subject, $error_message);

                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | IdeaNest</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/register.css">

</head>
<body>
<div class="register-container">
    <div class="logo-section">
        <div class="logo">
            <i class="fas fa-lightbulb"></i>
        </div>
        <div class="welcome-text">
            <h1>Create Account</h1>
            <p>Join IdeaNest to share your innovations</p>
        </div>
    </div>

    <form class="form-section" method="post" autocomplete="off">
        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <!-- Personal Information -->
        <div class="form-row">
            <div class="input-group">
                <input type="text" id="username" name="username" placeholder="Enter your full name" required autofocus>
                <i class="fas fa-user input-icon"></i>
            </div>
            <div class="input-group">
                <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                <i class="fas fa-envelope input-icon"></i>
            </div>
        </div>

        <!-- Academic Information -->
        <div class="form-row">
            <div class="input-group">
                <input type="text" id="enrollment_number" name="enrollment_number" placeholder="Enter enrollment number" required>
                <i class="fas fa-id-card input-icon"></i>
            </div>
            <div class="input-group">
                <input type="text" id="gr_number" name="gr_number" placeholder="Enter GR number" required>
                <i class="fas fa-hashtag input-icon"></i>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="form-row">
            <div class="input-group">
                <input type="text" id="department" name="department" placeholder="Enter your department" maxlength="100">
                <i class="fas fa-building input-icon"></i>
            </div>
            <div class="input-group">
                <input type="number" id="passout_year" name="passout_year" placeholder="Enter passout year" min="1900" max="2099" required>
                <i class="fas fa-calendar input-icon"></i>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="form-row">
            <div class="input-group">
                <input type="text" id="phone_no" name="phone_no" placeholder="Enter phone number" maxlength="20">
                <i class="fas fa-phone input-icon"></i>
            </div>
            <div class="input-group">
                <textarea id="about" name="about" placeholder="Tell us about yourself" maxlength="500"></textarea>
                <i class="fas fa-info-circle input-icon"></i>
            </div>
        </div>

        <!-- Password -->
        <div class="form-row">
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Create a password" required>
                <i class="fas fa-lock input-icon"></i>
            </div>
            <div class="input-group">
                <input type="password" id="confirm" name="confirm" placeholder="Confirm your password" required>
                <i class="fas fa-lock input-icon"></i>
            </div>
        </div>

        <button type="submit" class="register-btn">
            <i class="fas fa-user-plus" style="margin-right: 8px;"></i>
            Create Account
        </button>
    </form>

    <div class="divider">
        <span>Already have an account?</span>
    </div>

    <div class="login-link">
        <p>Ready to sign in?
            <a href="login.php">Login Here</a>
        </p>
    </div>
</div>

<script src="../../assets/js/register.js"></script>

</body>
</html>