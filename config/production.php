<?php
/**
 * IdeaNest Production Configuration
 * This file contains production-specific settings
 * 
 * IMPORTANT: This file is loaded automatically in production environment
 */

// Ensure we're in production
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    return;
}

// ============================================
// ERROR HANDLING - PRODUCTION
// ============================================

// Disable error display (errors should only be logged)
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Report all errors except deprecated and strict
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Enable error logging
ini_set('log_errors', '1');

// Set error log file
$error_log_path = __DIR__ . '/../logs/error.log';
if (!file_exists(dirname($error_log_path))) {
    mkdir(dirname($error_log_path), 0775, true);
}
ini_set('error_log', $error_log_path);

// ============================================
// SECURITY SETTINGS
// ============================================

// Hide PHP version
header_remove('X-Powered-By');

// Disable expose_php
ini_set('expose_php', 'Off');

// Session security
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');  // Requires HTTPS
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', '1');

// Session lifetime from environment or default 2 hours
$session_lifetime = $_ENV['SESSION_LIFETIME'] ?? 7200;
ini_set('session.gc_maxlifetime', $session_lifetime);
ini_set('session.cookie_lifetime', $session_lifetime);

// ============================================
// PERFORMANCE SETTINGS
// ============================================

// Increase limits for production
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '256M');
ini_set('post_max_size', '50M');
ini_set('upload_max_filesize', '50M');

// Enable output buffering
ini_set('output_buffering', '4096');

// Enable compression
ini_set('zlib.output_compression', 'On');
ini_set('zlib.output_compression_level', '6');

// ============================================
// SECURITY RESTRICTIONS
// ============================================

// Disable dangerous functions
ini_set('allow_url_fopen', '0');
ini_set('allow_url_include', '0');

// ============================================
// CUSTOM ERROR HANDLER
// ============================================

// Custom error handler for production
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Don't log suppressed errors (@)
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Log error
    $error_message = sprintf(
        "[%s] Error %d: %s in %s on line %d",
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    
    error_log($error_message);
    
    // For fatal errors, show generic message
    if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // Check if we're in CLI or web
        if (php_sapi_name() === 'cli') {
            echo "A system error occurred. Please check the error logs.\n";
        } else {
            // Redirect to error page
            if (!headers_sent()) {
                header('Location: /error_pages/500.html');
                exit;
            }
        }
    }
    
    return true;
});

// Custom exception handler
set_exception_handler(function($exception) {
    // Log exception
    $error_message = sprintf(
        "[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    error_log($error_message);
    
    // Show generic error page
    if (php_sapi_name() !== 'cli' && !headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Location: /error_pages/500.html');
        exit;
    } else {
        echo "A system error occurred. Please contact the administrator.\n";
    }
});

// ============================================
// TIMEZONE CONFIGURATION
// ============================================

// Set default timezone (adjust as needed)
date_default_timezone_set('Asia/Kolkata');

// ============================================
// DATABASE CONNECTION OPTIMIZATION
// ============================================

// Set MySQL connection timeout
ini_set('mysql.connect_timeout', '10');
ini_set('mysqli.reconnect', '1');

// ============================================
// FILE UPLOAD SECURITY
// ============================================

// Allowed file extensions for uploads
define('ALLOWED_FILE_EXTENSIONS', [
    'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx',
    'zip', 'rar', 'mp4', 'avi', 'mov', 'txt', 'ppt', 'pptx',
    'xls', 'xlsx', 'csv'
]);

// Allowed MIME types
define('ALLOWED_MIME_TYPES', [
    'image/jpeg', 'image/png', 'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/zip', 'application/x-rar-compressed',
    'video/mp4', 'video/x-msvideo', 'video/quicktime',
    'text/plain',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/csv'
]);

// Maximum file size (50MB)
define('MAX_FILE_SIZE', 52428800);

// ============================================
// RATE LIMITING CONFIGURATION
// ============================================

define('RATE_LIMIT_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_LOGIN_WINDOW', 900); // 15 minutes
define('RATE_LIMIT_API_REQUESTS', 100);
define('RATE_LIMIT_API_WINDOW', 3600); // 1 hour

// ============================================
// LOGGING CONFIGURATION
// ============================================

// Ensure log directory exists
$log_dir = __DIR__ . '/../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0775, true);
}

// Create .htaccess in logs directory to prevent access
$log_htaccess = $log_dir . '/.htaccess';
if (!file_exists($log_htaccess)) {
    file_put_contents($log_htaccess, "Require all denied\nDeny from all");
}

// ============================================
// MAINTENANCE MODE CHECK
// ============================================

$maintenance_file = __DIR__ . '/../.maintenance';
if (file_exists($maintenance_file)) {
    // Allow admin IPs during maintenance
    $admin_ips = ['127.0.0.1', '::1']; // Add your admin IPs here
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!in_array($client_ip, $admin_ips)) {
        if (php_sapi_name() !== 'cli') {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Retry-After: 3600');
            
            if (file_exists(__DIR__ . '/../error_pages/maintenance.html')) {
                readfile(__DIR__ . '/../error_pages/maintenance.html');
            } else {
                echo '<!DOCTYPE html><html><head><title>Maintenance</title>    <link rel="stylesheet" href="../assets/css/loader.css">
</head><body>';
                echo '<h1>Site Under Maintenance</h1>';
                echo '<p>We are currently performing scheduled maintenance. Please check back soon.</p>';
                echo '<script src="../assets/js/loader.js"></script>
</body></html>';
            }
            exit;
        }
    }
}

// ============================================
// PRODUCTION CONSTANTS
// ============================================

define('IS_PRODUCTION', true);
define('IS_DEVELOPMENT', false);
define('DEBUG_MODE', false);

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Log security events
 */
function log_security_event($event_type, $details, $user_id = null) {
    $log_file = __DIR__ . '/../logs/security.log';
    $log_entry = sprintf(
        "[%s] %s | User: %s | IP: %s | Details: %s\n",
        date('Y-m-d H:i:s'),
        $event_type,
        $user_id ?? 'N/A',
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $details
    );
    error_log($log_entry, 3, $log_file);
}

/**
 * Check if request is from allowed IP
 */
function is_allowed_ip($allowed_ips = []) {
    if (empty($allowed_ips)) {
        return true;
    }
    
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($client_ip, $allowed_ips);
}

/**
 * Sanitize filename for uploads
 */
function sanitize_filename($filename) {
    // Remove any path information
    $filename = basename($filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    
    // Limit length
    if (strlen($filename) > 255) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250);
        $filename = $name . '.' . $ext;
    }
    
    return $filename;
}

// ============================================
// LOAD SECURITY MANAGER
// ============================================

if (file_exists(__DIR__ . '/security.php')) {
    require_once __DIR__ . '/security.php';
}

// ============================================
// PRODUCTION INITIALIZATION COMPLETE
// ============================================

// Log production mode initialization
error_log(sprintf(
    "[%s] Production mode initialized | PHP: %s | Memory: %s",
    date('Y-m-d H:i:s'),
    PHP_VERSION,
    ini_get('memory_limit')
));
