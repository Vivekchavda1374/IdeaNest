<?php
/**
 * Security Initialization
 * Include this at the very top of EVERY PHP file
 * Prevents hosting provider script injection
 */

// Prevent direct access
if (!defined('SECURITY_INIT')) {
    define('SECURITY_INIT', true);
}

// Start output buffering with callback to clean injected content
ob_start(function($buffer) {
    // List of patterns to remove
    $remove_patterns = [
        // Remove HTTP script tags from directfwd.com
        '/<script[^>]*src=["\']http:\/\/cdn\.jsinit\.directfwd\.com[^"\']*["\'][^>]*><\/script>/i',
        '/<script[^>]*src=["\']https?:\/\/[^"\']*directfwd\.com[^"\']*["\'][^>]*><\/script>/i',
        '/<script[^>]*src=["\']https?:\/\/[^"\']*jsinit[^"\']*["\'][^>]*><\/script>/i',
        '/<script[^>]*src=["\']https?:\/\/[^"\']*jspark[^"\']*["\'][^>]*><\/script>/i',
        // Remove inline scripts with injection keywords
        '/<script[^>]*>.*?(?:directfwd|jsinit|jspark).*?<\/script>/is',
        // Remove any script tag with HTTP (not HTTPS)
        '/<script([^>]*)src=["\']http:\/\/(?!localhost)([^"\']*)["\']([^>]*)>/i',
    ];
    
    // Remove all matching patterns
    foreach ($remove_patterns as $pattern) {
        $buffer = preg_replace($pattern, '', $buffer);
    }
    
    // Additional cleanup - remove any remaining references
    $buffer = str_replace([
        'http://cdn.jsinit.directfwd.com',
        'cdn.jsinit.directfwd.com',
        'sk-jspark_init.php'
    ], '', $buffer);
    
    return $buffer;
});

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

// Register shutdown function to ensure buffer is flushed
register_shutdown_function(function() {
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
});
