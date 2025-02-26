<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user from database
    $stmt = $conn->prepare("SELECT password FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user'] = $email;
            header("Location: dashboard.php"); // Redirect after login
            exit();
        } else {
            echo "Invalid email or password! <a href='../front-end/index.php'>Try again</a>";
        }
    } else {
        echo "User not found! <a href='register.php'>Register here</a>";
    }
    $stmt->close();
}
?>
