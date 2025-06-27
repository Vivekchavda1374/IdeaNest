<?php
$host = "127.0.0.1";
$user = "root";
$pass = "your_password";
$dbname = "ideanest";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
