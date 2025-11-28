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

// Google OAuth Configuration for Development
// Development domain: http://localhost/IdeaNest/

define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '373663984974-msaj22ll4i9085r7120barr1g1akjs5d.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');

// Development OAuth Settings
define('GOOGLE_REDIRECT_URI', 'http://localhost/IdeaNest/Login/Login/google_callback.php');
define('GOOGLE_SCOPE', 'email profile');

// Development authorized origins that need to be added to Google Console:
// - http://localhost
// - http://localhost/IdeaNest
// 
// Authorized redirect URIs:
// - http://localhost/IdeaNest/Login/Login/google_callback.php


