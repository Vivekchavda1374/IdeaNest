<?php
require_once __DIR__ . '/../../includes/security_init.php';

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

// Detect environment
$is_localhost = (
    $_SERVER['SERVER_NAME'] === 'localhost' || 
    $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false
);

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');

// Dynamic redirect URI based on environment
if ($is_localhost) {
    define('GOOGLE_REDIRECT_URI', 'http://localhost/IdeaNest/Login/Login/google_callback.php');
} else {
    define('GOOGLE_REDIRECT_URI', 'https://ictmu.in/hcd/IdeaNest/Login/Login/google_callback.php');
}

define('GOOGLE_SCOPE', 'email profile');

// IMPORTANT: Add these to Google Cloud Console:
// 
// Authorized JavaScript origins:
// - http://localhost (for local development)
// - https://ictmu.in
// 
// Authorized redirect URIs:
// - http://localhost/IdeaNest/Login/Login/google_callback.php (for local)
// - https://ictmu.in/hcd/IdeaNest/Login/Login/google_callback.php (for production)


