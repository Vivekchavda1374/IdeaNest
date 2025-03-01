<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $er_number = trim($_POST['er_number']);
    $gr_number = trim($_POST['gr_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords
    if ($password !== $confirm_password) {
        die("Passwords do not match! <a href='register.php'>Try again</a>");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO users (name, email, er_number, gr_number, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $er_number, $gr_number, $hashed_password);

    if ($stmt->execute()) {
        echo "Registration successful! <a href='login.php'>Login here</a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
<h2>Register</h2>
<form action="register.php" method="post">
    <input type="text" name="name" placeholder="Full Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="text" name="er_number" placeholder="ER Number" required><br>
    <input type="text" name="gr_number" placeholder="GR Number" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
    <button type="submit">Register</button>
</form>
</body>
</html>
