<?php

/**
 * Simple Autoloader without Composer
 * Provides fallback email functionality if Composer is not available
 */

// Simple autoloader for classes
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';
    
    // Convert class name to file path
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include essential files with error handling
$essential_files = [
    'smtp_mailer.php',
    'email_helper.php',
    'validation.php',
    'csrf.php',
    'error_handler.php'
];

foreach ($essential_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        try {
            require_once $filepath;
        } catch (Throwable $e) {
            error_log("Warning: Failed to load $file: " . $e->getMessage());
        }
    } else {
        error_log("Warning: Essential file not found: $filepath");
    }
}

// Check if PHPMailer is available from Composer
$phpmailer_available = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        $phpmailer_available = class_exists('PHPMailer\PHPMailer\PHPMailer');
    } catch (Throwable $e) {
        error_log("Composer autoload failed: " . $e->getMessage());
    }
}

// Store status for later use
if (!defined('PHPMAILER_AVAILABLE')) {
    define('PHPMAILER_AVAILABLE', $phpmailer_available);
}
