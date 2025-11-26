<?php
/**
 * Enhanced Error Handler for Production
 * Prevents information disclosure while logging errors
 */

// Set error reporting based on environment
if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
    // Production: Log errors but don't display them
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    // Development: Show all errors
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = date('[Y-m-d H:i:s] ') . "Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($error_message, 3, __DIR__ . '/../logs/php_errors.log');
    
    // In production, show generic error page
    if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
        if (!headers_sent()) {
            http_response_code(500);
            include __DIR__ . '/../error_pages/500.html';
            exit;
        }
    }
    
    return false; // Let PHP handle the error normally in development
}

// Custom exception handler
function customExceptionHandler($exception) {
    $error_message = date('[Y-m-d H:i:s] ') . "Uncaught Exception: " . $exception->getMessage() . 
                     " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n" .
                     "Stack trace:\n" . $exception->getTraceAsString() . "\n";
    error_log($error_message, 3, __DIR__ . '/../logs/php_errors.log');
    
    // In production, show generic error page
    if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
        if (!headers_sent()) {
            http_response_code(500);
            include __DIR__ . '/../error_pages/500.html';
            exit;
        }
    } else {
        echo "<pre>$error_message</pre>";
    }
}

// Set custom handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');

// Ensure logs directory exists
$logs_dir = __DIR__ . '/../logs';
if (!file_exists($logs_dir)) {
    @mkdir($logs_dir, 0755, true);
}

// Ensure log file is writable
$log_file = $logs_dir . '/php_errors.log';
if (!file_exists($log_file)) {
    @touch($log_file);
    @chmod($log_file, 0644);
}
?>
