<?php

// Database configuration - Update for your Fedora setup
$host = "localhost";
$user = "root";  // Change to 'ideanest_user' for production
$pass = "";      // Add your database password
$dbname = "ideanest";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
