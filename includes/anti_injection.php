<?php
/**
 * Anti-Injection Bootstrap
 * MUST be the FIRST line included in every PHP file
 * Aggressively prevents hosting provider script injection
 */

// Prevent multiple inclusions
if (defined('ANTI_INJECTION_LOADED')) {
    return;
}
define('ANTI_INJECTION_LOADED', true);

// Immediately disable any auto prepend/append
@ini_set('auto_prepend_file', '');
@ini_set('auto_append_file', '');

// Start output buffering at the highest level
if (!ob_get_level()) {
    ob_start();
}

// Register the FIRST shutdown function (will run LAST)
register_shutdown_function(function() {
    // Collect ALL output
    $final_output = '';
    while (ob_get_level() > 0) {
        $final_output = ob_get_clean() . $final_output;
    }
    
    // Aggressive pattern matching to remove ALL injected scripts
    $patterns = [
        // Remove complete script tags with directfwd/jsinit/jspark
        '/<script[^>]*src=["\'][^"\']*(?:directfwd|jsinit|jspark)[^"\']*["\'][^>]*>(?:.*?<\/script>)?/is',
        
        // Remove any HTTP (non-HTTPS) external scripts
        '/<script[^>]*src=["\']http:\/\/(?!localhost|127\.0\.0\.1)[^"\']*["\'][^>]*>(?:.*?<\/script>)?/is',
        
        // Remove inline scripts containing injection keywords
        '/<script[^>]*>.*?(?:directfwd|jsinit|jspark).*?<\/script>/is',
        
        // Remove orphaned script tags
        '/<script[^>]*><\/script>/i',
        
        // Remove script tags without proper closing
        '/<script[^>]*src=["\']http:\/\/cdn\.jsinit[^>]*>/i',
    ];
    
    foreach ($patterns as $pattern) {
        $final_output = preg_replace($pattern, '', $final_output);
    }
    
    // String replacement for any remaining fragments
    $remove_strings = [
        'http://cdn.jsinit.directfwd.com',
        'https://cdn.jsinit.directfwd.com',
        'cdn.jsinit.directfwd.com',
        'sk-jspark_init.php',
        'sk-jspark_init',
        'directfwd.com/sk',
    ];
    
    $final_output = str_ireplace($remove_strings, '', $final_output);
    
    // Output the cleaned content
    echo $final_output;
}, 0); // Priority 0 = runs last

// Also start a nested buffer for additional protection
ob_start(function($buffer) {
    // Clean the buffer before passing to next level
    $buffer = preg_replace([
        '/<script[^>]*src=["\'][^"\']*(?:directfwd|jsinit|jspark)[^"\']*["\'][^>]*>.*?<\/script>/is',
        '/<script[^>]*src=["\']http:\/\/(?!localhost)[^"\']*["\'][^>]*>.*?<\/script>/is',
    ], '', $buffer);
    
    return $buffer;
});
