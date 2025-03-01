<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the email already exists
    $stmt = $conn->prepare("SELECT id FROM login WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Account already exists! <a href='../back-end/Login.php'>Login here</a>";
    } else {
        // Hash the password before storing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO login (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $hashed_password);

        if ($stmt->execute()) {
            echo "Registration successful! <a href='../front-end/index.php'>Login here</a>";
        } else {
            echo "Error: " . $stmt->error;
        }
    }
    $stmt->close();
}
?>
