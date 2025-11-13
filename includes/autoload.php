<?php

// Simple autoloader without Composer
require_once __DIR__ . '/autoload_simple.php';

// Security headers function
if (!function_exists('setSecurityHeaders')) {
    function setSecurityHeaders()
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
}
