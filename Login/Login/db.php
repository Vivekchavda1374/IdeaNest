<?php

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration with environment variable support
$host = $_ENV['DB_HOST'] ?? "localhost";
$user = $_ENV['DB_USERNAME'] ?? "ictmu6ya_ideanest";
$pass = $_ENV['DB_PASSWORD'] ?? "ictmu6ya_ideanest";
$dbname = $_ENV['DB_NAME'] ?? "ictmu6ya_ideanest";

// Create connection with error handling
try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        
        // In production, don't expose database errors
        if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
            die("Database connection error. Please contact administrator.");
        } else {
            die("Connection failed: " . $conn->connect_error);
        }
    }
    
    // Set charset to prevent SQL injection
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    
    if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
        die("Database connection error. Please contact administrator.");
    } else {
        die("Connection exception: " . $e->getMessage());
    }
}
?>
