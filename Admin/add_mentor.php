<?php
session_start();
require_once '../Login/Login/db.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

if ($_POST) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $specialization = $_POST['specialization'];
    $experience = $_POST['experience'];
    $max_students = $_POST['max_students'];

    // Generate random password
    $password = bin2hex(random_bytes(4)); // 8 character password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate enrollment number for mentor
    $enrollment = 'MEN' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

    try {
        // Insert into register table
        $stmt = $conn->prepare("INSERT INTO register (name, email, enrollment_number, gr_number, password, about, department, passout_year, role, expertise) VALUES (?, ?, ?, ?, ?, ?, 'Mentor', 2024, 'mentor', ?)");
        $stmt->bind_param("sssssss", $name, $email, $enrollment, $enrollment, $hashed_password, $specialization, $specialization);
        $stmt->execute();
        $user_id = $conn->insert_id;

        // Insert into mentors table
        $stmt = $conn->prepare("INSERT INTO mentors (user_id, specialization, experience_years, max_students, bio) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $user_id, $specialization, $experience, $max_students, $specialization);
        $stmt->execute();

        // Send email with credentials
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ideanest.ict@gmail.com';
        $mail->Password = 'luou xlhs ojuw auvx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ideanest.ict@gmail.com', 'IdeaNest');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'Welcome to IdeaNest - Mentor Account Created';
        $mail->Body = "
        <h2>Welcome to IdeaNest Mentor Program</h2>
        <p>Dear $name,</p>
        <p>Your mentor account has been created successfully.</p>
        <p><strong>Login Credentials:</strong></p>
        <p>Email: $email</p>
        <p>Password: $password</p>
        <p>Login URL: <a href='https://ictmu.in/hcd/IdeaNest/mentor/login.php'>Mentor Dashboard</a></p>
        <p>Please change your password after first login.</p>
        ";

        $mail->send();
        $success = "Mentor added successfully! Credentials sent to email.";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Mentor - IdeaNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
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
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)) : ?>
        <div class="alert alert-danger"><?= $error ?></div>
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
</body>
</html>