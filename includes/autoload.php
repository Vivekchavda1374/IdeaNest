<?php

// Simple autoloader for PHPMailer - Production Safe
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
    } catch (Exception $e) {
        // Composer autoloader failed, continue without it
        error_log("Composer autoloader failed: " . $e->getMessage());
    }
} else {
    // No vendor directory, continue without PHPMailer
    // This is normal in production environments without Composer
}

// Security headers function
if (!function_exists('setSecurityHeaders')) {
    function setSecurityHeaders()
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
}
