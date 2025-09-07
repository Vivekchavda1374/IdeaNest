<?php
// Production error handler
function productionErrorHandler($errno, $errstr, $errfile, $errline) {
    // Log error securely
    $error_message = date('Y-m-d H:i:s') . " - Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($error_message, 3, dirname(__DIR__) . '/logs/error.log');
    
    // Don't expose errors to users in production
    if (ini_get('display_errors')) {
        return false; // Let PHP handle it in development
    }
    
    // Show generic error page in production
    http_response_code(500);
    echo "Service temporarily unavailable";
    exit();
}

function productionExceptionHandler($exception) {
    $error_message = date('Y-m-d H:i:s') . " - Uncaught exception: " . $exception->getMessage() . 
                    " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($error_message, 3, dirname(__DIR__) . '/logs/error.log');
    
    http_response_code(500);
    echo "Service temporarily unavailable";
    exit();
}

// Set error handlers
set_error_handler('productionErrorHandler');
set_exception_handler('productionExceptionHandler');

// Security function to sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// CSRF token validation
function validateCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting function
function checkRateLimit($action, $limit = 10, $window = 60) {
    $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
    $current_time = time();
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }
    
    // Clean old entries
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key], 
        function($timestamp) use ($current_time, $window) {
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
?>