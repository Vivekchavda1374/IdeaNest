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
            $error_message = "User not found! Please register.";
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
        .login-card {
            background: var(--white);
            border-radius: 1.25rem;
            box-shadow: 0 8px 32px rgba(99,102,241,0.10), 0 1.5px 4px rgba(0,0,0,0.04);
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 400px;
            width: 100%;
            animation: fadeInUp 0.7s;
        }
        .login-card .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        .login-card h2 {
            font-weight: 700;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .login-card p {
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
        .login-card .register-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--primary);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }
        .login-card .register-link:hover {
            color: var(--accent);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        @media (max-width: 500px) {
            .login-card { padding: 1.5rem 0.5rem; }
        }
    </style>
</head>

<body>
    <form class="login-card" method="post" autocomplete="off">
        <div class="logo">
            <i class="fas fa-lightbulb"></i>
            </div>
        <h2>Welcome Back</h2>
        <p>Sign in to your IdeaNest account</p>
<?php if (isset($error_message)) : ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
<?php if (isset($_GET['logout']) && $_GET['logout'] === 'success') : ?>
            <div class="success-message" style="background: #d1fae5; color: #065f46; border-radius: 0.5rem; padding: 0.75rem 1rem; margin-bottom: 1rem; text-align: center; font-weight: 500;">You have been successfully logged out!</div>
        <?php endif; ?>
        <div class="form-group">
            <label class="form-label" for="er_number">ER Number</label>
            <input class="form-control" type="text" id="er_number" name="er_number" required autofocus>
                </div>
        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" type="password" id="password" name="password" required>
        </div>
        <button class="btn-primary" type="submit">
            <i class="fas fa-sign-in-alt me-2"></i> Login
        </button>
        <a class="register-link" href="register.php">Don't have an account? Register</a>
    </form>
</body>
</html>