<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include_once "../../Login/Login/db.php";
// PHPMailer
require_once '../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../../vendor/phpmailer/phpmailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        // Generate random password
        $plain_password = bin2hex(random_bytes(4)); // 8-char random password
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO subadmins (email, password) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $hashed_password);
            if ($stmt->execute()) {
                // Send email
                $mail = new PHPMailer(true);
                try {
                    // Gmail SMTP settings (hardcoded)
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'ideanest.ict@gmail.com';
                    $mail->Password = 'luou xlhs ojuw auvx'; // Use your Gmail App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('ideanest.ict@gmail.com', 'IdeaNest Admin');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Welcome to IdeaNest - Subadmin Access';
                    $mail->Body = "<h3>Welcome to IdeaNest!</h3><p>Your subadmin account has been created.</p><ul><li><b>Login ID:</b> $email</li><li><b>Password:</b> $plain_password</li></ul><p>Please log in and change your password after first login.</p>";
                    $mail->AltBody = "Your subadmin account has been created.\nLogin ID: $email\nPassword: $plain_password\nPlease log in and change your password after first login.";
                    $mail->send();
                    $message = "Subadmin added and credentials sent to $email.";
                } catch (Exception $e) {
                    $error = "Subadmin added, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $error = "Failed to add subadmin. Email may already exist.";
            }
            $stmt->close();
        } else {
            $error = "Database error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h3 class="mb-4">Add Subadmin</h3>
                        <?php if($message): ?>
                            <div class="alert alert-success"> <?php echo $message; ?> </div>
                        <?php endif; ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger"> <?php echo $error; ?> </div>
                        <?php endif; ?>
                        <form method="post" autocomplete="off">
                            <div class="mb-3">
                                <label for="email" class="form-label">Subadmin Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Add & Send Credentials</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 