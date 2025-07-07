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
        $error = 'All fields are required.';
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
                logNotification('new_user_notification', $user_id, null, 
                              $notification_result['success'] ? 'sent' : 'failed', $admin_email, $email_subject, $error_message, $conn);
                
        $success = 'Registration successful! You can now <a href="login.php">login</a>.';
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
    <title>Register | IdeaNest</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #ec4899;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-700: #374151;
            --white: #fff;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .register-card {
            position: relative;
            z-index: 1;
            margin: 2rem 0;
            max-width: 600px;
            width: 100%;
            padding: 3.5rem 2.5rem 2.5rem 2.5rem;
            background: var(--white);
            border-radius: 1.5rem;
            box-shadow: 0 12px 40px rgba(99,102,241,0.13), 0 2px 8px rgba(0,0,0,0.06);
            animation: fadeInUp 0.7s;
        }
        .register-card .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        .register-card h2 {
            font-weight: 700;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .register-card p {
            color: var(--gray-200);
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1.5px solid var(--gray-200);
            background: var(--gray-50);
            font-size: 1rem;
            color: var(--gray-700);
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            background: var(--white);
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.75rem;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            font-weight: 700;
            border: none;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(99,102,241,0.08);
            transition: background 0.2s, transform 0.2s;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-2px) scale(1.01);
        }
        .error-message {
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }
        .success-message {
            background: #d1fae5;
            color: #047857;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }
        .register-card .login-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }
        .register-card .login-link:hover {
            color: var(--accent);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        @media (max-width: 500px) {
            .register-card { padding: 1.5rem 0.5rem; }
        }
        .register-bg {
            min-height: 100vh;
            width: 100vw;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .register-bg::before {
            content: "";
            position: absolute;
            top: -100px; left: -100px;
            width: 400px; height: 400px;
            background: radial-gradient(circle, #fff3 0%, transparent 70%);
            z-index: 0;
        }
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-row .form-group {
            flex: 1 1 0;
            margin-bottom: 0;
        }
        .floating {
            position: relative;
        }
        .floating .form-control {
            padding-top: 1.25rem;
            padding-bottom: 0.5rem;
        }
        .floating .form-label {
            position: absolute;
            top: 0.9rem;
            left: 1rem;
            color: #888;
            font-size: 1rem;
            pointer-events: none;
            transition: 0.2s;
            background: transparent;
        }
        .floating .form-control:focus + .form-label,
        .floating .form-control:not(:placeholder-shown):not([value=""]) + .form-label {
            top: 0.2rem;
            left: 0.9rem;
            font-size: 0.85rem;
            color: var(--primary);
            background: var(--white);
            padding: 0 0.25rem;
        }
        @media (max-width: 700px) {
            .form-row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>
    <div class="register-bg">
    <form class="register-card" method="post" autocomplete="off">
        <div class="logo">
            <i class="fas fa-lightbulb"></i>
        </div>
        <h2>Create Account</h2>
        <p>Sign up for your IdeaNest account</p>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
            <div class="form-row">
                <div class="form-group floating">
                    <input class="form-control" type="text" id="username" name="username" required autofocus placeholder=" ">
                    <label class="form-label" for="username">Full Name</label>
        </div>
                <div class="form-group floating">
                    <input class="form-control" type="email" id="email" name="email" required placeholder=" ">
            <label class="form-label" for="email">Email</label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group floating">
                    <input class="form-control" type="text" id="enrollment_number" name="enrollment_number" required placeholder=" ">
                    <label class="form-label" for="enrollment_number">Enrollment Number</label>
                </div>
                <div class="form-group floating">
                    <input class="form-control" type="text" id="gr_number" name="gr_number" required placeholder=" ">
                    <label class="form-label" for="gr_number">GR Number</label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group floating">
                    <input class="form-control" type="text" id="about" name="about" maxlength="500" placeholder=" ">
                    <label class="form-label" for="about">About</label>
                </div>
                <div class="form-group floating">
                    <input class="form-control" type="text" id="phone_no" name="phone_no" maxlength="20" placeholder=" ">
                    <label class="form-label" for="phone_no">Phone Number</label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group floating">
                    <input class="form-control" type="text" id="department" name="department" maxlength="100" placeholder=" ">
                    <label class="form-label" for="department">Department</label>
                </div>
                <div class="form-group floating">
                    <input class="form-control" type="number" id="passout_year" name="passout_year" min="1900" max="2099" required placeholder=" ">
                    <label class="form-label" for="passout_year">Passout Year</label>
                </div>
        </div>
            <div class="form-row">
                <div class="form-group floating">
                    <input class="form-control" type="password" id="password" name="password" required placeholder=" ">
            <label class="form-label" for="password">Password</label>
        </div>
                <div class="form-group floating">
                    <input class="form-control" type="password" id="confirm" name="confirm" required placeholder=" ">
            <label class="form-label" for="confirm">Confirm Password</label>
                </div>
        </div>
        <button class="btn-primary" type="submit">
            <i class="fas fa-user-plus me-2"></i> Register
        </button>
        <a class="login-link" href="login.php">Already have an account? Login</a>
    </form>
    </div>
</body>
</html>