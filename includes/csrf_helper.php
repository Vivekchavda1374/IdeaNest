<?php
/**
 * CSRF Helper Functions
 */

require_once __DIR__ . '/../config/security.php';

/**
 * Generate CSRF token field for forms
 */
function csrf_field() {
    $token = SecurityManager::generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Get CSRF token
 */
function csrf_token() {
    return SecurityManager::generateCSRFToken();
}

/**
 * Verify CSRF token from request
 */
function verify_csrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    
    if (!SecurityManager::verifyCSRFToken($token)) {
        http_response_code(403);
        die('CSRF token validation failed. Please refresh the page and try again.');
    }
    
    return true;
}
?>
