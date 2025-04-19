<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['name'], $_POST['email'], $_POST['enrollment_number'], $_POST['gr_number'], $_POST['password'], $_POST['confirm_password'])) {
        echo "<script>alert('Please fill in all fields!'); window.location.href='register.php';</script>";
        exit();
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $enrollment_number = trim($_POST['enrollment_number']);
    $gr_number = trim($_POST['gr_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate ER number is exactly 11 digits
    if (!preg_match("/^\d{11}$/", $enrollment_number)) {
        echo "<script>alert('ER Number must be exactly 11 digits!'); window.location.href='register.php';</script>";
        exit();
    }

    // Validate GR number is exactly 6 digits
    if (!preg_match("/^\d{6}$/", $gr_number)) {
        echo "<script>alert('GR Number must be exactly 6 digits!'); window.location.href='register.php';</script>";
        exit();
    }

    $allowed_domains = ["marwadiuniversity.ac.in", "marwadiuniversity.edu.in"];
    $email_domain = substr(strrchr($email, "@"), 1);

    if (!in_array($email_domain, $allowed_domains)) {
        echo "<script>alert('Please use a Marwadi University email ID!'); window.location.href='register.php';</script>";
        exit();
    }
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.location.href='register.php';</script>";
        exit();
    }
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $stmt_email_check = $conn->prepare("SELECT id FROM register WHERE email = ?");
    $stmt_email_check->bind_param("s", $email);
    $stmt_email_check->execute();
    $stmt_email_check->store_result();

    if ($stmt_email_check->num_rows > 0) {
        echo "<script>alert('This email is already registered! Please log in.'); window.location.href='login.php';</script>";
        exit();
    }
    $stmt_email_check->close();

    $stmt_check = $conn->prepare("SELECT id FROM register WHERE enrollment_number = ?");
    $stmt_check->bind_param("s", $enrollment_number);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        echo "<script>alert('This ER Number is already registered!'); window.location.href='register.php';</script>";
        exit();
    }
    $stmt_check->close();

    $stmt = $conn->prepare("INSERT INTO register (name, email, enrollment_number, gr_number, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $enrollment_number, $gr_number, $hashed_password);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! You can now log in.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Error during registration. Please try again!');</script>";
    }

    // Close connections
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: url('image/register_image.jpg') no-repeat center center/cover;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .register-container {
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

    .register-box {
        flex: 1;
        padding: 20px;
    }

    .register-box h2 {
        color: #00838f;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .form-control {
        border-radius: 5px;
        margin-bottom: 15px;
    }

    .btn-register {
        background: #00838f;
        color: white;
        width: 100%;
        padding: 10px;
        border-radius: 5px;
        font-weight: bold;
        border: none;
        cursor: pointer;
    }

    .btn-register:hover {
        background: #005f6b;
    }

    .login-link {
        display: block;
        margin-top: 10px;
        color: #555;
        text-decoration: none;
    }

    .email-input-group {
        display: flex;
        margin-bottom: 15px;
    }

    .email-input-group input {
        border-radius: 5px 0 0 5px;
        border-right: none;
    }

    .email-input-group select {
        border-radius: 0 5px 5px 0;
        border-left: none;
        min-width: 220px;
        background-color: #f8f9fa;
    }

    .form-hint {
        font-size: 12px;
        color: #666;
        margin-top: -12px;
        margin-bottom: 10px;
    }
    </style>
</head>

<body>

    <div class="register-container">
        <div class="register-box">
            <h2>REGISTER</h2>
            <p>Please fill in the details to create an account</p>
            <form id="registerForm" action="register.php" method="post">
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>

                <div class="email-input-group">
                    <input type="text" name="email_username" id="email_username" class="form-control"
                        placeholder="Email Username" required>
                    <select name="email_domain" id="email_domain" class="form-control">
                        <option value="@marwadiuniversity.ac.in">@marwadiuniversity.ac.in</option>
                        <option value="@marwadiuniversity.edu.in">@marwadiuniversity.edu.in</option>
                    </select>
                </div>
                <input type="hidden" name="email" id="complete_email">

                <input type="text" name="enrollment_number" id="enrollment_number" class="form-control"
                    placeholder="ER Number" maxlength="11" pattern="\d{11}" title="ER Number must be exactly 11 digits"
                    required>
                <div class="form-hint">ER Number must be exactly 11 digits</div>

                <input type="text" name="gr_number" id="gr_number" class="form-control" placeholder="GR Number"
                    maxlength="6" pattern="\d{6}" title="GR Number must be exactly 6 digits" required>
                <div class="form-hint">GR Number must be exactly 6 digits</div>

                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password"
                    required>

                <button type="submit" class="btn btn-register">REGISTER</button>

                <a href="login.php" class="login-link">Already have an account? Login here</a>
            </form>
        </div>
    </div>

    <script>
    document.getElementById("registerForm").addEventListener("submit", function(event) {
        // Combine email username and domain
        const emailUsername = document.getElementById("email_username").value;
        const emailDomain = document.getElementById("email_domain").value;
        const completeEmail = emailUsername + emailDomain;

        // Set the complete email to the hidden field
        document.getElementById("complete_email").value = completeEmail;

        // Validate ER Number - must be exactly 11 digits
        const erNumber = document.getElementById("enrollment_number").value;
        if (!/^\d{11}$/.test(erNumber)) {
            alert("ER Number must be exactly 11 digits!");
            event.preventDefault();
            return;
        }

        // Validate GR Number - must be exactly 6 digits
        const grNumber = document.getElementById("gr_number").value;
        if (!/^\d{6}$/.test(grNumber)) {
            alert("GR Number must be exactly 6 digits!");
            event.preventDefault();
            return;
        }

        // Email domain validation (backup)
        let validDomains = ["@marwadiuniversity.ac.in", "@marwadiuniversity.edu.in"];
        let emailDomainPart = emailDomain;

        if (!validDomains.includes(emailDomainPart)) {
            alert("Please use a Marwadi University email ID!");
            event.preventDefault();
        }
    });

    // Input restrictions for ER and GR numbers - only allow digits
    document.getElementById("enrollment_number").addEventListener("input", function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 11) {
            this.value = this.value.slice(0, 11);
        }
    });

    document.getElementById("gr_number").addEventListener("input", function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 6) {
            this.value = this.value.slice(0, 6);
        }
    });
    </script>

</body>

</html>