<?php

$host = "localhost";
$user = "ictmu6ya_ideanest";
$pass = "ictmu6ya_ideanest";
$dbname = "ictmu6ya_ideanest";


$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
