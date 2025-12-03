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

// Google OAuth Configuration for Production
// Production domain: https://ictmu.in/hcd/IdeaNest/

define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');

// Production OAuth Settings
define('GOOGLE_REDIRECT_URI', 'https://ictmu.in/hcd/IdeaNest/Login/Login/google_callback.php');
define('GOOGLE_SCOPE', 'email profile');

// Production authorized origins that need to be added to Google Console:
// - https://ictmu.in
// - https://ictmu.in/hcd/IdeaNest
// 
// Authorized redirect URIs:
// - https://ictmu.in/hcd/IdeaNest/Login/Login/google_callback.php


