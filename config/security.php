<?php
// Security configuration for IdeaNest Production
// Domain: https://ictmu.in/hcd/IdeaNest/

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Production session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', ($_ENV['APP_ENV'] === 'production') ? 1 : 0);
ini_set('session.cookie_samesite', $_ENV['SESSION_SAMESITE'] ?? 'Strict');
ini_set('session.name', 'IDEANEST_SESSID');

// Set session timeout (30 minutes)
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_lifetime', 1800);

// Prevent session fixation
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// Production security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Enhanced Content Security Policy for production
$csp = "default-src 'self'; ";
$csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://apis.google.com https://accounts.google.com https://ictmu.in; ";
$csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; ";
$csp .= "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; ";
$csp .= "img-src 'self' data: https: https://ictmu.in; ";
$csp .= "connect-src 'self' https://api.github.com https://ictmu.in; ";
$csp .= "frame-src https://accounts.google.com;";
header("Content-Security-Policy: $csp");

// Production error reporting settings
if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
    
    // Create logs directory if it doesn't exist
    if (!is_dir(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// File upload security
ini_set('file_uploads', 1);
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', 60);
ini_set('memory_limit', '256M');

// Database security
ini_set('mysql.default_socket', '');

// Disable dangerous functions for production
if (function_exists('ini_set')) {
    ini_set('allow_url_fopen', 0);
    ini_set('allow_url_include', 0);
}

// Enhanced CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Enhanced input sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Enhanced file upload validation
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip', 'mp4', 'avi', 'mov']) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > 50 * 1024 * 1024) { // 50MB limit
        return false;
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip', 'video/mp4', 'video/avi', 'video/quicktime'
    ];
    
    return in_array($mimeType, $allowedMimes);
}

// Enhanced rate limiting
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $key = 'rate_limit_' . hash('sha256', $identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    
    $data = $_SESSION[$key];
    
    if (time() - $data['time'] > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    if ($data['count'] >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

// Production URL helper
function getBaseUrl() {
    return $_ENV['APP_URL'] ?? 'https://ictmu.in/hcd/IdeaNest';
}

// Secure redirect function
function secureRedirect($url) {
    $baseUrl = getBaseUrl();
    if (strpos($url, 'http') === 0) {
        if (strpos($url, $baseUrl) !== 0) {
            $url = $baseUrl;
        }
    } else {
        $url = $baseUrl . '/' . ltrim($url, '/');
    }
    header('Location: ' . $url);
    exit;
}

?>