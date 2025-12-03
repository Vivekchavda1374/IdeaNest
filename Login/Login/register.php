<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/security_init.php';

// Initialize variables
$error = '';
$success = '';

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database with error handling
$conn = null;
try {
    include 'db.php';
    // Test database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }
    
    // Test if we can query the register table
    $test_query = $conn->query("SELECT 1 FROM register LIMIT 1");
    if (!$test_query) {
        throw new Exception("Cannot access register table: " . $conn->error);
    }
    
    error_log("Database connection successful for registration");
} catch (Exception $e) {
    error_log("Database connection error in register.php: " . $e->getMessage());
    $error = 'Database connection error. Please contact administrator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && isset($conn)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $enrollment_number = trim($_POST['enrollment_number'] ?? '');
    $gr_number = trim($_POST['gr_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $passout_year = trim($_POST['passout_year'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Debug output
    error_log("Registration attempt for: " . $name . " (" . $email . ")");

    // Validation
    if (!$name || !$email || !$enrollment_number || !$gr_number || !$department || !$passout_year || !$password || !$confirm) {
        $error = 'All required fields must be filled';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } elseif (!is_numeric($passout_year) || $passout_year < date('Y') || $passout_year > (date('Y') + 6)) {
        $error = 'Invalid passout year';
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM register WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email already registered';
            } else {
                // Check if enrollment number already exists
                $stmt = $conn->prepare("SELECT id FROM register WHERE enrollment_number = ?");
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("s", $enrollment_number);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = 'Enrollment number already registered';
                } else {
                    // Insert new user with default values for required fields
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $about = 'New student at IdeaNest'; // Default about text (required field)
                    $user_image = ''; // Default empty image
                    
                    $stmt = $conn->prepare("INSERT INTO register (name, email, enrollment_number, gr_number, password, about, department, passout_year, user_image, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')");
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("sssssssss", $name, $email, $enrollment_number, $gr_number, $hashed_password, $about, $department, $passout_year, $user_image);
                    
                    if ($stmt->execute()) {
                        $success = 'Registration successful! You can now login.';
                        error_log("User registered successfully: " . $email);
                        
                        // Clear form data on success
                        $_POST = array();
                    } else {
                        $error = 'Registration failed: ' . $stmt->error;
                        error_log("Registration failed for " . $email . ": " . $stmt->error);
                    }
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - IdeaNest</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link rel="stylesheet" href="../../assets/css/loader.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h1 { text-align: center; color: #333; margin-bottom: 10px; font-size: 24px; }
        p { text-align: center; color: #666; margin-bottom: 25px; font-size: 14px; }
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .form-group { margin-bottom: 15px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-size: 14px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #6366f1; }
        button { width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #5558e3; }
        .links { text-align: center; margin-top: 20px; font-size: 14px; }
        .links a { color: #6366f1; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 Create Account</h1>
        <p>Join IdeaNest to share your innovations</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" id="registrationForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Enrollment Number *</label>
                    <input type="text" name="enrollment_number" value="<?= htmlspecialchars($_POST['enrollment_number'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>GR Number *</label>
                    <input type="text" name="gr_number" value="<?= htmlspecialchars($_POST['gr_number'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Department *</label>
                    <select name="department" required>
                        <option value="">Select Department</option>
                        <option value="ict" <?= ($_POST['department'] ?? '') === 'ict' ? 'selected' : '' ?>>ICT</option>
                        <option value="cse" <?= ($_POST['department'] ?? '') === 'cse' ? 'selected' : '' ?>>CSE</option>
                        <option value="ece" <?= ($_POST['department'] ?? '') === 'ece' ? 'selected' : '' ?>>ECE</option>
                        <option value="mechanical" <?= ($_POST['department'] ?? '') === 'mechanical' ? 'selected' : '' ?>>Mechanical</option>
                        <option value="civil" <?= ($_POST['department'] ?? '') === 'civil' ? 'selected' : '' ?>>Civil</option>
                        <option value="electrical" <?= ($_POST['department'] ?? '') === 'electrical' ? 'selected' : '' ?>>Electrical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Passout Year *</label>
                    <input type="number" name="passout_year" value="<?= htmlspecialchars($_POST['passout_year'] ?? '') ?>" min="<?= date('Y') ?>" max="<?= date('Y') + 6 ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm" required minlength="6">
                </div>
            </div>

            <button type="submit" id="submitBtn">Create Account</button>
        </form>

        <div class="links">
            Already have an account? <a href="login.php">Login Here</a>
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
    
    <script>
    // Form validation
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const password = document.querySelector('input[name="password"]').value;
        const confirm = document.querySelector('input[name="confirm"]').value;
        const submitBtn = document.getElementById('submitBtn');
        
        // Reset any previous error styling
        document.querySelectorAll('input').forEach(input => {
            input.style.borderColor = '#ddd';
        });
        
        let hasError = false;
        
        // Check required fields
        const requiredFields = ['name', 'email', 'enrollment_number', 'gr_number', 'department', 'passout_year', 'password', 'confirm'];
        requiredFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (!field.value.trim()) {
                field.style.borderColor = '#dc3545';
                hasError = true;
            }
        });
        
        // Validate email
        const email = document.querySelector('input[name="email"]').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            document.querySelector('input[name="email"]').style.borderColor = '#dc3545';
            alert('Please enter a valid email address');
            hasError = true;
        }
        
        // Validate password length
        if (password.length < 6) {
            document.querySelector('input[name="password"]').style.borderColor = '#dc3545';
            alert('Password must be at least 6 characters long!');
            hasError = true;
        }
        
        // Check password confirmation
        if (password !== confirm) {
            document.querySelector('input[name="confirm"]').style.borderColor = '#dc3545';
            alert('Passwords do not match!');
            hasError = true;
        }
        
        if (hasError) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        submitBtn.innerHTML = '<span style="margin-right: 8px;">⏳</span>Creating Account...';
        submitBtn.disabled = true;
        
        if (typeof showLoader === 'function') {
            showLoader('Creating account...');
        }
    });
    
    // Real-time password confirmation check
    document.querySelector('input[name="confirm"]').addEventListener('input', function() {
        const password = document.querySelector('input[name="password"]').value;
        const confirm = this.value;
        
        if (confirm && password !== confirm) {
            this.style.borderColor = '#dc3545';
            this.title = 'Passwords do not match';
        } else if (confirm) {
            this.style.borderColor = '#28a745';
            this.title = 'Passwords match';
        } else {
            this.style.borderColor = '#ddd';
            this.title = '';
        }
    });
    
    // Real-time password strength indicator
    document.querySelector('input[name="password"]').addEventListener('input', function() {
        const password = this.value;
        if (password.length < 6) {
            this.style.borderColor = '#dc3545';
            this.title = 'Password must be at least 6 characters';
        } else {
            this.style.borderColor = '#28a745';
            this.title = 'Password strength: Good';
        }
    });
    
    // Email validation
    document.querySelector('input[name="email"]').addEventListener('blur', function() {
        const email = this.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            this.style.borderColor = '#dc3545';
            this.title = 'Please enter a valid email address';
        } else if (email) {
            this.style.borderColor = '#28a745';
            this.title = 'Valid email address';
        }
    });
    </script>
</body>
</html>
