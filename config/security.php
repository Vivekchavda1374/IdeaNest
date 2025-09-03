<?php
// Production security configuration

// Environment detection
define('IS_PRODUCTION', !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'dev.local']));

// Security constants
define('CSRF_TOKEN_LENGTH', 32);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File upload security
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'image' => ['jpg', 'jpeg', 'png', 'gif'],
    'video' => ['mp4', 'avi', 'mov'],
    'document' => ['pdf', 'doc', 'docx'],
    'archive' => ['zip', 'rar']
]);

// Database security settings
if (IS_PRODUCTION) {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', IS_PRODUCTION ? 1 : 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Input validation patterns
define('VALIDATION_PATTERNS', [
    'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
    'username' => '/^[a-zA-Z0-9_]{3,20}$/',
    'project_name' => '/^[a-zA-Z0-9\s\-_]{3,100}$/',
    'safe_string' => '/^[a-zA-Z0-9\s\-_.,!?]{1,500}$/'
]);

// Security headers function
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (IS_PRODUCTION) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Input validation function
function validateInput($input, $type = 'safe_string') {
    if (!isset(VALIDATION_PATTERNS[$type])) {
        return false;
    }
    
    return preg_match(VALIDATION_PATTERNS[$type], $input);
}

// File validation function
function validateFile($file, $type) {
    if (!isset(ALLOWED_FILE_TYPES[$type])) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    return in_array($extension, ALLOWED_FILE_TYPES[$type]) && 
           $file['size'] <= MAX_FILE_SIZE &&
           $file['error'] === UPLOAD_ERR_OK;
}
?>