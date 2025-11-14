<?php
/**
 * Production Error Fixes Applied
 * 
 * This file documents all the production errors that have been fixed
 * and provides a comprehensive overview of the changes made.
 */

// 1. COMPOSER AUTOLOAD ERRORS FIXED
// Files Fixed: includes/autoload.php, user/select_mentor.php, Admin/settings.php, 
//             Admin/notification_backend.php, Admin/add_mentor.php, Login/Login/forgot_password.php,
//             mentor/student_requests.php, mentor/email_system.php, cron/weekly_notifications.php
// 
// Fix: Added try-catch blocks and graceful fallbacks for missing Composer dependencies

// 2. DISPLAY ERRORS IN PRODUCTION FIXED
// Files Fixed: Admin/settings.php, Admin/notification_backend.php, user/bookmark.php, user/all_projects.php
//
// Issue: error_reporting(E_ALL) and ini_set('display_errors', 1) enabled in production
// Fix: Added environment-based error reporting that only shows errors in development

// 3. DATABASE CONNECTION ISSUES FIXED
// Files Fixed: user/all_projects.php (line 332), user/bookmark.php (line 91)
//
// Issue: bind_param() called on boolean false when prepared statements failed
// Fix: Added proper error checking before calling bind_param()                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     

// 4. USER ID MISMATCH ISSUES FIXED
// Files Fixed: user/index.php, user/bookmark.php, user/all_projects.php
//
// Issue: Using session_id() instead of actual user_id from database
// Fix: Changed to use $_SESSION['user_id'] consistently across all files

// 5. SQL PARAMETER TYPE ISSUES FIXED
// Files Fixed: user/all_projects.php, user/bookmark.php
//
// Issue: Using "s" (string) parameter type for integer user_id fields
// Fix: Changed to "i" (integer) parameter type for user_id fields

// 6. UNDEFINED VARIABLE PROTECTION ADDED
// Files Fixed: Multiple files
//
// Issue: Potential undefined variable warnings
// Fix: Added isset() checks and null coalescing operators where needed

// 7. AUTHENTICATION CHECKS ENHANCED
// Files Fixed: user/all_projects.php, user/bookmark.php
//
// Issue: Actions allowed without proper user authentication
// Fix: Added $user_id checks before allowing user actions

// PRODUCTION READY CONFIGURATION
if (!defined('PRODUCTION_MODE')) {
    define('PRODUCTION_MODE', ($_ENV['APP_ENV'] ?? 'development') === 'production');
}

// Error reporting based on environment
if (PRODUCTION_MODE) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Security headers for production
if (PRODUCTION_MODE) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Session security
if (PRODUCTION_MODE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
}

// Database connection error handling
function handleDatabaseError($error, $is_production = PRODUCTION_MODE) {
    error_log("Database error: " . $error);
    if ($is_production) {
        die("Service temporarily unavailable. Please try again later.");
    } else {
        die("Database error: " . $error);
    }
}

// Safe prepared statement execution
function executePreparedStatement($stmt, $error_message = "Database query failed") {
    if (!$stmt) {
        handleDatabaseError($error_message);
    }
    return $stmt;
}

// Safe user ID retrieval
function getCurrentUserId() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return (int)$_SESSION['user_id'];
}

// Safe user name retrieval
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Guest User';
}

// Safe email retrieval
function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? 'user@example.com';
}

// Production-safe email sending
function sendEmailSafely($to, $subject, $message, $from_email = 'noreply@ideanest.com', $from_name = 'IdeaNest') {
    try {
        require_once __DIR__ . '/smtp_mailer.php';
        $mailer = new SMTPMailer();
        return $mailer->send($to, $subject, $message);
    } catch (Exception $e) {
        $headers = "From: $from_name <$from_email>\r\n";
        $headers .= "Reply-To: $from_email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        return mail($to, $subject, $message, $headers);
    }
}

// Input sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// CSRF token validation
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting
function checkRateLimit($action, $limit = 10, $window = 60) {
    $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $current_time = time();

    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }

    // Clean old entries
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key],
        function ($timestamp) use ($current_time, $window) {
            return ($current_time - $timestamp) < $window;
        }
    );

    // Check limit
    if (count($_SESSION['rate_limit'][$key]) >= $limit) {
        return false;
    }

    // Add current request
    $_SESSION['rate_limit'][$key][] = $current_time;
    return true;
}

// Log errors safely
function logError($message, $context = []) {
    $log_message = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $log_message .= " - Context: " . json_encode($context);
    }
    error_log($log_message);
}

// Production status check
function isProductionReady() {
    $checks = [
        'database' => function() {
            global $conn;
            return isset($conn) && !$conn->connect_error;
        },
        'session' => function() {
            return session_status() === PHP_SESSION_ACTIVE;
        },
        'error_logging' => function() {
            return ini_get('log_errors') && ini_get('error_log');
        },
        'security_headers' => function() {
            return PRODUCTION_MODE && ini_get('session.cookie_httponly');
        }
    ];

    $results = [];
    foreach ($checks as $name => $check) {
        $results[$name] = $check();
    }

    return $results;
}

// Initialize production environment
if (PRODUCTION_MODE) {
    // Set error handlers
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        logError("PHP Error [$errno]: $errstr in $errfile on line $errline");
        if (!ini_get('display_errors')) {
            http_response_code(500);
            echo "Service temporarily unavailable";
            exit;
        }
    });

    set_exception_handler(function($exception) {
        logError("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        http_response_code(500);
        echo "Service temporarily unavailable";
        exit;
    });
}

?>
