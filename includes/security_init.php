<?php
/**
 * Security Initialization
 * Include this at the very top of EVERY PHP file
 * Prevents hosting provider script injection
 */

// Load anti-injection first
require_once __DIR__ . '/anti_injection.php';

// Prevent direct access
if (!defined('SECURITY_INIT')) {
    define('SECURITY_INIT', true);
}

// Start multiple levels of output buffering to catch all injections
ob_start(function($buffer) {
    // List of patterns to remove - more aggressive
    $remove_patterns = [
        // Remove ALL script tags from directfwd.com (any protocol)
        '/<script[^>]*src=["\'][^"\']*directfwd[^"\']*["\'][^>]*>.*?<\/script>/is',
        '/<script[^>]*src=["\'][^"\']*jsinit[^"\']*["\'][^>]*>.*?<\/script>/is',
        '/<script[^>]*src=["\'][^"\']*jspark[^"\']*["\'][^>]*>.*?<\/script>/is',
        // Remove any HTTP (non-HTTPS) script tags
        '/<script[^>]*src=["\']http:\/\/(?!localhost)[^"\']*["\'][^>]*>.*?<\/script>/is',
        // Remove inline scripts with injection keywords
        '/<script[^>]*>.*?(?:directfwd|jsinit|jspark).*?<\/script>/is',
        // Remove script tags without closing tags
        '/<script[^>]*src=["\']http:\/\/cdn\.jsinit[^"\']*["\'][^>]*>/i',
    ];
    
    // Remove all matching patterns
    foreach ($remove_patterns as $pattern) {
        $buffer = preg_replace($pattern, '', $buffer);
    }
    
    // Additional aggressive cleanup - remove any remaining references
    $buffer = str_replace([
        'http://cdn.jsinit.directfwd.com',
        'https://cdn.jsinit.directfwd.com',
        'cdn.jsinit.directfwd.com',
        'sk-jspark_init.php',
        'sk-jspark_init',
        'directfwd.com',
        'jsinit.directfwd',
    ], '', $buffer);
    
    // Remove any orphaned script tags
    $buffer = preg_replace('/<script[^>]*><\/script>/i', '', $buffer);
    
    return $buffer;
});

// Add a second layer of buffering for extra protection
ob_start();

// Set comprehensive security headers
$security_headers = [
    'X-Content-Type-Options: nosniff',
    'X-Frame-Options: DENY',
    'X-XSS-Protection: 1; mode=block',
    'Referrer-Policy: strict-origin-when-cross-origin',
    'Permissions-Policy: geolocation=(), microphone=(), camera=()',
    'Cross-Origin-Opener-Policy: same-origin-allow-popups',
    'Content-Security-Policy: upgrade-insecure-requests; default-src \'self\' https:; script-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com https://accounts.google.com https://apis.google.com https://cdn.jsdelivr.net https://code.jquery.com; style-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com https://accounts.google.com https://fonts.googleapis.com https://cdn.jsdelivr.net; img-src \'self\' data: https:; font-src \'self\' https://cdnjs.cloudflare.com https://fonts.gstatic.com https://cdn.jsdelivr.net; connect-src \'self\' https: https://accounts.google.com; frame-ancestors \'none\'; block-all-mixed-content;',
];

// Apply headers if not already sent
if (!headers_sent()) {
    foreach ($security_headers as $header) {
        header($header);
    }
}

// Disable any auto-prepended or auto-appended files at runtime
ini_set('auto_prepend_file', '');
ini_set('auto_append_file', '');

// Register shutdown function to ensure buffers are properly flushed
register_shutdown_function(function() {
    // Get all buffered content and clean it
    $output = '';
    while (ob_get_level() > 0) {
        $output = ob_get_clean() . $output;
    }
    
    // Final cleanup pass
    $output = preg_replace([
        '/<script[^>]*src=["\'][^"\']*directfwd[^"\']*["\'][^>]*>.*?<\/script>/is',
        '/<script[^>]*src=["\'][^"\']*jsinit[^"\']*["\'][^>]*>.*?<\/script>/is',
        '/<script[^>]*src=["\']http:\/\/[^"\']*["\'][^>]*>.*?<\/script>/is',
    ], '', $output);
    
    echo $output;
});
