<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>

<body>
    <h2>Register</h2>
    <form action="login.php" method="post">
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


<?php
// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $er_number = $_POST["er_number"];
    $gr_number = $_POST["gr_number"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Display received input
    echo "<h2>Received Data:</h2>";
    echo "Name: " . htmlspecialchars($name) . "<br>";
    echo "Email: " . htmlspecialchars($email) . "<br>";
    echo "ER Number: " . htmlspecialchars($er_number) . "<br>";
    echo "GR Number: " . htmlspecialchars($gr_number) . "<br>";

    // Check password match
    if ($password === $confirm_password) {
        echo "Password confirmed successfully!";
    } else {
        echo "Error: Passwords do not match!";
    }
}
?>