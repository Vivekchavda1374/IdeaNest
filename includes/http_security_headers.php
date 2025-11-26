<?php
/**
 * HTTP Security Headers Configuration
 * This file should be included at the very beginning of every page
 * to ensure proper security headers and HTTPS enforcement
 */

// Only set headers if not already sent
if (!headers_sent()) {
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Prevent clickjacking attacks
    header('X-Frame-Options: DENY');
    
    // Enable XSS protection in older browsers
    header('X-XSS-Protection: 1; mode=block');
    
    // Control referrer information
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Restrict permissions
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    // Force HTTPS for all mixed content
    header('Upgrade-Insecure-Requests: 1');
    
    // HSTS - Force HTTPS on all subsequent requests
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    
    // Content Security Policy - Allow HTTPS resources only
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://apis.google.com https://accounts.google.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com https://cdn.datatables.net; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://accounts.google.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.datatables.net; " .
           "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
           "img-src 'self' data: https: blob:; " .
           "connect-src 'self' https://accounts.google.com https:; " .
           "frame-src https://accounts.google.com; " .
           "object-src 'none'; " .
           "base-uri 'self'; " .
           "form-action 'self'; " .
           "frame-ancestors 'none'; " .
           "upgrade-insecure-requests; " .
           "block-all-mixed-content;";
    
    header("Content-Security-Policy: $csp");
}
?>
