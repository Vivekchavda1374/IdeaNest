<?php
/**
 * CSRF Protection System
 * Comprehensive Cross-Site Request Forgery protection
 */

class CSRFProtection {
    private static $tokenName = 'csrf_token';
    private static $sessionKey = 'csrf_tokens';
    
    /**
     * Generate CSRF token
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$sessionKey][$token] = time();
        
        // Clean old tokens (older than 1 hour)
        self::cleanOldTokens();
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$sessionKey][$token])) {
            return false;
        }
        
        // Check if token is not expired (1 hour)
        if (time() - $_SESSION[self::$sessionKey][$token] > 3600) {
            unset($_SESSION[self::$sessionKey][$token]);
            return false;
        }
        
        // Remove token after use (one-time use)
        unset($_SESSION[self::$sessionKey][$token]);
        return true;
    }
    
    /**
     * Get token from request
     */
    public static function getTokenFromRequest() {
        return $_POST[self::$tokenName] ?? $_GET[self::$tokenName] ?? null;
    }
    
    /**
     * Generate hidden input field
     */
    public static function getHiddenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Verify request has valid CSRF token
     */
    public static function verifyRequest() {
        $token = self::getTokenFromRequest();
        if (!$token || !self::validateToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }
    
    /**
     * Clean old tokens
     */
    private static function cleanOldTokens() {
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
            return;
        }
        
        $currentTime = time();
        foreach ($_SESSION[self::$sessionKey] as $token => $timestamp) {
            if ($currentTime - $timestamp > 3600) {
                unset($_SESSION[self::$sessionKey][$token]);
            }
        }
    }
}

/**
 * Helper function to get CSRF hidden field
 */
function getCSRFField() {
    return CSRFProtection::getHiddenField();
}

/**
 * Helper function to generate CSRF token
 */
function generateCSRFToken() {
    return CSRFProtection::generateToken();
}

/**
 * Helper function to validate CSRF token
 */
function validateCSRFToken($token) {
    return CSRFProtection::validateToken($token);
}
