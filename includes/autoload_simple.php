<?php

// Simple autoloader without Composer
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';
    
    // Convert class name to file path
    $file = $baseDir . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include essential files
require_once __DIR__ . '/smtp_mailer.php';
require_once __DIR__ . '/email_helper.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/error_handler.php';