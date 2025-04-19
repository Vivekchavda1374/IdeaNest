<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['login_id'], $_POST['password'])) {
        $error_message = "Invalid form submission";
    } else {
        $login_id = $_POST['login_id'];
        $password = $_POST['password'];
        
        // Check if login attempt is with email (admin/sub-admin) or enrollment number (student)
        $is_email = strpos($login_id, '@') !== false;
        
        if ($is_email) {
            // Admin login with primary admin credentials
            if($login_id === "admin@ict.com" && $password === "admin@ICT123"){
                // Set admin session variables
                $_SESSION['user_id'] = 'admin';
                $_SESSION['login_id'] = $login_id;
                $_SESSION['user_name'] = 'Administrator';
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_type'] = 'primary';

                header("Location: ../../Admin/admin.php");
                exit(); // Stop execution after redirect
            }
            
            // Check for sub-admin with university email
            if (strpos($login_id, '@marwadiuniversity.edu.in') !== false) {
                $stmt = $conn->prepare("SELECT id, name, password FROM sub_admin WHERE email = ?");
                $stmt->bind_param("s", $login_id);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($admin_id, $admin_name, $admin_password);
                    $stmt->fetch();
                    
                    // Assuming password is stored securely (hashed)
                    if (password_verify($password, $admin_password)) {
                        $_SESSION['user_id'] = $admin_id;
                        $_SESSION['login_id'] = $login_id;
                        $_SESSION['user_name'] = $admin_name;
                        $_SESSION['is_admin'] = true;
                        $_SESSION['admin_type'] = 'sub';
                        
                        header("Location: ../Admin/admin.php");
                        exit();
                    } else {
                        $error_message = "Incorrect password for admin account!";
                    }
                } else {
                    $error_message = "Admin account not found with this email!";
                }
                
                $stmt->close();
            } else {
                $error_message = "Invalid university email format!";
            }
        } else {
            // Regular user login with enrollment number
            $stmt = $conn->prepare("SELECT id, password, name FROM register WHERE enrollment_number = ?");
            $stmt->bind_param("s", $login_id);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($user_id, $hashed_password, $user_name);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['login_id'] = $login_id;
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
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    body {
        background: url('./image/register_image.jpg') no-repeat center center/cover;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: 'Poppins', sans-serif;
    }

    .login-container {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        width: 80%;
        max-width: 900px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
    }

    .login-box {
        flex: 1;
        padding: 20px;
    }

    .login-box h2 {
        color: #00838f;
        font-weight: bold;
        margin-bottom: 20px;
        font-size: 2.2em;
        position: relative;
    }

    .login-box h2:after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -5px;
        width: 50px;
        height: 3px;
        background: #f57c00;
    }

    .form-control {
        border-radius: 8px;
        margin-bottom: 20px;
        padding: 12px;
        border: 1px solid #ddd;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #00838f;
        box-shadow: 0 0 0 0.25rem rgba(0, 131, 143, 0.25);
    }

    .input-group {
        position: relative;
        margin-bottom: 20px;
    }

    .input-group i {
        position: absolute;
        top: 15px;
        left: 15px;
        color: #666;
    }

    .input-field {
        padding-left: 40px;
    }

    .btn-container {
        display: flex;
        gap: 15px;
        margin-top: 10px;
    }

    .btn-login,
    .btn-register {
        flex: 1;
        padding: 12px;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .btn-login {
        background: #00838f;
        color: white;
    }

    .btn-login:hover {
        background: #005f6b;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-register {
        background: #f57c00;
        color: white;
    }

    .btn-register:hover {
        background: #d65a00;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .forgot-password {
        display: block;
        margin-top: 15px;
        color: #555;
        text-decoration: none;
        text-align: center;
        font-size: 0.9em;
        transition: color 0.3s;
    }

    .forgot-password:hover {
        color: #00838f;
    }

    .login-info {
        margin-top: 30px;
        padding: 15px;
        background-color: #e3f2fd;
        border-radius: 8px;
        font-size: 0.9em;
        position: relative;
        border-left: 4px solid #1976d2;
    }

    .login-info h5 {
        color: #1976d2;
        margin-bottom: 10px;
    }

    .toggle-user-type {
        margin-bottom: 20px;
        text-align: center;
    }

    .toggle-user-type .btn {
        border-radius: 20px;
        padding: 8px 16px;
        margin: 0 5px;
        font-size: 0.9em;
        transition: all 0.3s ease;
    }

    .toggle-user-type .active {
        background-color: #00838f;
        color: white;
    }

    .login-image {
        flex: 1;
        padding: 20px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-image img {
        max-width: 100%;
        border-radius: 10px;
    }

    @media (max-width: 768px) {
        .login-container {
            flex-direction: column;
            width: 95%;
        }

        .login-image {
            display: none;
        }
    }

    .modal-content {
        border-radius: 15px;
    }

    .modal-header {
        background-color: #f44336;
        color: white;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Welcome to IdeaNest</h2>
            <p>Please login with your credentials to continue</p>

            <div class="toggle-user-type">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary active" id="studentBtn">Student</button>
                    <button type="button" class="btn btn-outline-secondary" id="adminBtn">Admin</button>
                </div>
            </div>

            <form action="login.php" method="post">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="login_id" id="login_id" class="form-control input-field"
                        placeholder="Enrollment Number" required>
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control input-field" placeholder="Password"
                        required>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>LOGIN
                    </button>
                    <a href="./register.php" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i>REGISTER
                    </a>
                </div>

                <a href="#" class="forgot-password">Forgot Password?</a>

                <div class="login-info" id="loginInfo">
                    <h5><i class="fas fa-info-circle me-2"></i>Student Login</h5>
                    <p>Use your enrollment number and password to access student features.</p>
                </div>
            </form>
        </div>

        <div class="login-image">
            <img src="./image/login_illustration.svg" alt="Login" onerror="this.src='./image/register_image.jpg'">
        </div>
    </div>

    <?php if (isset($error_message)) : ?>
    <div class="modal fade show" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true"
        style="display:block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Login Failed
                    </h5>
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
    <?php endif; ?>

    <script>
    function closeModal() {
        document.getElementById('errorModal').style.display = 'none';
    }

    // Toggle between student and admin login
    document.getElementById('studentBtn').addEventListener('click', function() {
        this.classList.add('active');
        document.getElementById('adminBtn').classList.remove('active');
        document.getElementById('login_id').placeholder = 'Enrollment Number';
        document.getElementById('loginInfo').innerHTML = `
            <h5><i class="fas fa-info-circle me-2"></i>Student Login</h5>
            <p>Use your enrollment number and password to access student features.</p>
        `;
    });

    document.getElementById('adminBtn').addEventListener('click', function() {
        this.classList.add('active');
        document.getElementById('studentBtn').classList.remove('active');
        document.getElementById('login_id').placeholder = 'University Email (@marwadiuniversity.edu.in)';
        document.getElementById('loginInfo').innerHTML = `
            <h5><i class="fas fa-info-circle me-2"></i>Admin Login</h5>
            <p>Use your university email (@marwadiuniversity.edu.in) and password to access admin features.</p>
        `;
    });
    </script>
</body>

</html>