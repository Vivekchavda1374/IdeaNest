<?php
/**
 * Anti-Injection Script
 * Prevents hosting provider script injection
 * Include this at the top of every PHP file
 */

// Start output buffering
ob_start(function($buffer) {
    // Remove any injected scripts from directfwd.com or similar
    $patterns = [
        // Remove script tags with directfwd.com
        '/<script[^>]*src=["\']http:\/\/cdn\.jsinit\.directfwd\.com[^"\']*["\'][^>]*><\/script>/i',
        '/<script[^>]*src=["\']https?:\/\/[^"\']*directfwd\.com[^"\']*["\'][^>]*><\/script>/i',
        '/<script[^>]*src=["\']https?:\/\/[^"\']*jsinit[^"\']*["\'][^>]*><\/script>/i',
        '/<script[^>]*src=["\']https?:\/\/[^"\']*jspark[^"\']*["\'][^>]*><\/script>/i',
        // Remove inline scripts containing directfwd
        '/<script[^>]*>.*?directfwd.*?<\/script>/is',
        '/<script[^>]*>.*?jsinit.*?<\/script>/is',
        '/<script[^>]*>.*?jspark.*?<\/script>/is',
        // Remove any HTTP script tags (force HTTPS)
        '/<script([^>]*)src=["\']http:\/\/([^"\']*)["\']([^>]*)>/i',
    ];
    
    $replacements = [
        '', // Remove directfwd scripts
        '', // Remove directfwd scripts
        '', // Remove jsinit scripts
        '', // Remove jspark scripts
        '', // Remove inline directfwd scripts
        '', // Remove inline jsinit scripts
        '', // Remove inline jspark scripts
        '<script$1src="https://$2"$3>', // Convert HTTP to HTTPS
    ];
    
    $buffer = preg_replace($patterns, $replacements, $buffer);
    
    // Remove any remaining HTTP references in script tags
    $buffer = str_replace('http://cdn.jsinit.directfwd.com', '', $buffer);
    
    return $buffer;
});

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy - Block all mixed content
header("Content-Security-Policy: upgrade-insecure-requests; default-src 'self' https:; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self' https:; frame-ancestors 'none'; block-all-mixed-content;");

// Register shutdown function to flush buffer
register_shutdown_function(function() {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
});
