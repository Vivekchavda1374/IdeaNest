<?php
session_start();
include 'db.php';

function preventDirectAccess() {
    // Only allow access from form submission or legitimate referrers
    if ($_SERVER["REQUEST_METHOD"] != "POST" && !isset($_SESSION['is_admin'])) {
        // If someone is trying to directly access admin pages
        if (strpos($_SERVER['PHP_SELF'], 'admin') !== false) {
            header("Location: ../../../login.php");
            exit();
        }
    }
}

preventDirectAccess();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['er_number'], $_POST['password'])) {
        $error_message = "Invalid form submission";
    } else {
        $er_number = $_POST['er_number'];
        $password = $_POST['password'];

        // Check for admin credentials
        if ($er_number === "admin@ict.com" && $password === "admin@ICT123") {
            // Set admin session variables
            $_SESSION['user_id'] = 'admin';
            $_SESSION['er_number'] = $er_number;
            $_SESSION['user_name'] = 'Administrator';
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_token'] = bin2hex(random_bytes(32)); // Generate secure token
            $_SESSION['login_time'] = time();

            header("Location: ../../Admin/admin.php");
            exit(); // Stop execution after redirect
        } elseif ($er_number === "ideanest.ict@gmail.com" && $password === "ideanest@133") {
            // Set admin session variables for second admin account
            $_SESSION['user_id'] = 'admin';
            $_SESSION['er_number'] = $er_number;
            $_SESSION['user_name'] = 'Administrator';
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_token'] = bin2hex(random_bytes(32)); // Generate secure token
            $_SESSION['login_time'] = time();

            header("Location: ../../Admin/admin.php");
            exit(); // Stop execution after redirect
        } else {
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
                    $_SESSION['login_time'] = time();

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
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: url('./image/register_image.jpg') no-repeat center center/cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            width: 80%;
            max-width: 900px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.2);
        }

        .login-box {
            flex: 1;
            padding: 20px;
        }

        .login-box h2 {
            color: #00838f;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .btn-container {
            display: flex;
            gap: 10px;
        }

        .btn-login,
        .btn-register {
            flex: 1;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            border: none;
            cursor: pointer;
        }

        .btn-login {
            background: #00838f;
            color: white;
        }

        .btn-login:hover {
            background: #005f6b;
        }

        .btn-register {
            background: #f57c00;
            color: white;
        }

        .btn-register:hover {
            background: #d65a00;
        }

        .forgot-password {
            display: block;
            margin-top: 10px;
            color: #555;
            text-decoration: none;
        }

        .admin-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 5px;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
<div class="login-container">
    <div class="login-box">
        <h2>LOGIN</h2>
        <p>Please login with your ER number and Password</p>
        <form action="login.php" method="post">
            <input type="text" name="er_number" class="form-control" placeholder="Enrollment Number " required>
            <input type="password" name="password" class="form-control" placeholder="Password" required>

            <div class="btn-container">
                <button type="submit" class="btn btn-login">LOGIN</button>
                <a href="./register.php" class="btn btn-register">REGISTER</a>
            </div>

            <a href="#" class="forgot-password">Forgot Password?</a>
        </form>
    </div>
</div>

<?php if (isset($error_message)) : ?>
    <div class="modal fade show" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true"
         style="display:block;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Login Failed</h5>
                    <button type="button" class="btn-close" onclick="closeModal()"></button>
                </div>
                <div class="modal-body text-center">
                    <p><?php echo $error_message; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function closeModal() {
            document.getElementById('errorModal').style.display = 'none';
        }
    </script>
<?php endif; ?>

</body>
</html>