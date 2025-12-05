<?php
require_once __DIR__ . '/../includes/security_init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../Login/Login/db.php';

// Load required classes
require_once dirname(__DIR__) . "/includes/smtp_mailer.php";
require_once dirname(__DIR__) . "/includes/credential_manager.php";
require_once dirname(__DIR__) . "/includes/email_logger.php";

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $specialization = $_POST['specialization'];
    $experience = intval($_POST['experience']);
    $max_students = intval($_POST['max_students']);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM register WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "A user with this email already exists.";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Generate random password (8 characters)
            $password = bin2hex(random_bytes(4));
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Generate unique enrollment number for mentor
            $enrollment = 'MEN' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            
            // Check if enrollment number already exists
            $check_enroll = $conn->prepare("SELECT id FROM register WHERE enrollment_number = ?");
            $check_enroll->bind_param("s", $enrollment);
            $check_enroll->execute();
            $enroll_result = $check_enroll->get_result();
            
            // Generate new enrollment if exists
            while ($enroll_result->num_rows > 0) {
                $enrollment = 'MEN' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                $check_enroll->bind_param("s", $enrollment);
                $check_enroll->execute();
                $enroll_result = $check_enroll->get_result();
            }
            $check_enroll->close();

            try {
                // Start transaction
                $conn->begin_transaction();
                
                // Insert into register table
                $stmt = $conn->prepare("INSERT INTO register (name, email, enrollment_number, gr_number, password, about, department, passout_year, role, expertise) VALUES (?, ?, ?, ?, ?, ?, 'Mentor', 2024, 'mentor', ?)");
                $stmt->bind_param("sssssss", $name, $email, $enrollment, $enrollment, $hashed_password, $specialization, $specialization);
                $stmt->execute();
                $user_id = $conn->insert_id;
                $stmt->close();

                // Insert into mentors table
                $stmt = $conn->prepare("INSERT INTO mentors (user_id, specialization, experience_years, max_students, bio) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isiss", $user_id, $specialization, $experience, $max_students, $specialization);
                $stmt->execute();
                $stmt->close();
                
                // Initialize credential manager and email logger
                $credManager = new CredentialManager($conn);
                $emailLogger = new EmailLogger($conn);
                
                // Store credentials FIRST (before sending email)
                $credManager->storeCredentials('mentor', $user_id, $email, $password, false);
                
                // Commit transaction
                $conn->commit();
                
                // Now try to send email
                $mailer = new SMTPMailer();
                $subject = 'Welcome to IdeaNest - Mentor Account Created';
                $body = "
                <html>
                <head>
    <!-- Anti-injection script - MUST be first -->
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                        .credentials { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; }
                        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                    </style>
</head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Welcome to IdeaNest!</h1>
                            <p>Your Mentor Account Has Been Created</p>
                        </div>
                        <div class='content'>
                            <p>Dear {$name},</p>
                            <p>Your mentor account has been successfully created on IdeaNest platform.</p>
                            
                            <div class='credentials'>
                                <h3>Your Login Credentials:</h3>
                                <p><strong>Email:</strong> {$email}</p>
                                <p><strong>Password:</strong> <code style='background: #f0f0f0; padding: 5px 10px; border-radius: 3px; font-size: 16px;'>{$password}</code></p>
                                <p><strong>Enrollment Number:</strong> {$enrollment}</p>
                            </div>
                            
                            <p><strong>Important:</strong> Please change your password immediately after your first login for security purposes.</p>
                            
                            <p>Best regards,<br>The IdeaNest Team</p>
                        </div>
                    </div>
                </body>
                </html>
                ";

                $email_sent = $mailer->send($email, $subject, $body);
                
                // Update credential status
                $credManager->updateEmailStatus('mentor', $user_id, $email_sent, $email_sent ? null : 'SMTP send failed');
                
                // Log email attempt
                $emailLogger->logEmail($email, $subject, 'mentor_welcome', $email_sent ? 'sent' : 'failed', $email_sent ? null : 'SMTP send failed');
                
                if ($email_sent) {
                    $success = "Mentor added successfully! Credentials have been sent to {$email}.";
                } else {
                    $warning = "Mentor added successfully, but email could not be sent. Please use the 'View Credentials' option to retrieve the password.";
                }
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error = "Error: " . $e->getMessage();
                error_log("Mentor creation error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../assets/js/anti_injection.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Mentor - IdeaNest Admin</title>
    
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
<?php include 'sidebar_admin.php'; ?>

<div class="main-content">
    <div class="topbar">
        <button class="btn d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title">Add New Mentor</h1>
    </div>

    <div class="dashboard-content">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-person-plus me-2"></i>Add New Mentor</h5>
            </div>
            <div class="card-body">
    
    <?php if (isset($success)) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($warning)) : ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= $warning ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-circle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Specialization</label>
            <select class="form-control" name="specialization" required>
                <option value="Web Development">Web Development</option>
                <option value="Mobile Development">Mobile Development</option>
                <option value="AI/ML">AI/ML</option>
                <option value="IoT">IoT</option>
                <option value="Cybersecurity">Cybersecurity</option>
                <option value="Data Science">Data Science</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Experience (Years)</label>
            <input type="number" class="form-control" name="experience" min="1" max="50" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Max Students</label>
            <input type="number" class="form-control" name="max_students" min="1" max="20" value="5" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Add Mentor</button>
            <a href="admin.php" class="btn btn-secondary">Back</a>
        </div>
            </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
</body>
</html>