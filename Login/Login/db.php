<?php
require_once __DIR__ . '/../../includes/security_init.php';
/**
 * Database Connection Configuration
 * Supports both development and production environments
 */

// Load production configuration if in production
if (file_exists(__DIR__ . '/../../config/production.php')) {
    require_once __DIR__ . '/../../config/production.php';
}

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

// Validate required configuration
if (empty($host) || empty($dbname)) {
    error_log("Database configuration incomplete: Host or database name missing");
    die("Database configuration error. Please check your .env file.");
}

// Warn about empty password in development
if (empty($pass) && ($_ENV['APP_ENV'] ?? 'development') === 'development') {
    error_log("WARNING: Database password is empty. This is acceptable for development but NOT for production.");
}

// Create connection with comprehensive error handling
$conn = null;
$max_retries = 3;
$retry_count = 0;

while ($retry_count < $max_retries && $conn === null) {
    try {
        // Disable error reporting temporarily to handle errors gracefully
        mysqli_report(MYSQLI_REPORT_OFF);
        
        $conn = new mysqli($host, $user, $pass, $dbname);
        
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error, $conn->connect_errno);
        }
        
        // Set charset to prevent SQL injection
        if (!$conn->set_charset("utf8mb4")) {
            throw new Exception("Error setting charset: " . $conn->error);
        }
        
        // Verify connection is working
        if (!$conn->ping()) {
            throw new Exception("Database connection ping failed");
        }
        
        // Set connection options for better performance and security
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        
        // Log successful connection (only in development)
        if (($_ENV['APP_ENV'] ?? 'development') === 'development') {
            error_log("Database connected successfully to $dbname@$host");
        }
        
    } catch (Exception $e) {
        $retry_count++;
        
        // Log detailed error information
        $error_msg = sprintf(
            "Database connection attempt %d/%d failed: %s (Host: %s, User: %s, DB: %s)",
            $retry_count,
            $max_retries,
            $e->getMessage(),
            $host,
            $user,
            $dbname
        );
        error_log($error_msg);
        
        // If this was the last retry, handle the error
        if ($retry_count >= $max_retries) {
            // In production, don't expose database details
            if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                // Check if we're in a web request or CLI
                if (php_sapi_name() === 'cli') {
                    die("Database connection error after $max_retries attempts. Check logs for details.\n");
                }
                
                // For web requests, show user-friendly error
                http_response_code(503);
                die("Service temporarily unavailable. Please try again later.");
            } else {
                // In development, show detailed error
                die("Connection failed after $max_retries attempts: " . $e->getMessage() . 
                    "\n\nPlease check:\n" .
                    "1. MySQL/MariaDB is running\n" .
                    "2. Database credentials in .env are correct\n" .
                    "3. Database '$dbname' exists\n" .
                    "4. User '$user' has access to '$dbname'\n");
            }
        }
        
        // Wait before retry (exponential backoff)
        usleep(100000 * $retry_count); // 0.1s, 0.2s, 0.3s
        $conn = null;
    }
}

// Enable mysqli error reporting for subsequent queries
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
