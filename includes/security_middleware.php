<?php
/**
 * Security Middleware
 * Centralized security layer for all requests
 */

require_once 'csrf.php';
require_once 'validation.php';
require_once 'auth_guard.php';

class SecurityMiddleware {
    
    /**
     * Apply security measures to all requests
     */
    public static function apply() {
        self::setSecurityHeaders();
        self::validateRequest();
        self::sanitizeGlobals();
    }
    
    /**
     * Set security headers
     */
    private static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://apis.google.com; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com;');
    }
    
    /**
     * Validate incoming request
     */
    private static function validateRequest() {
        // Check for POST requests and validate CSRF token
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !self::isExemptFromCSRF()) {
            CSRFProtection::verifyRequest();
        }
        
        // Rate limiting for login attempts
        if (self::isLoginRequest()) {
            self::checkRateLimit();
        }
    }
    
    /**
     * Sanitize global variables
     */
    private static function sanitizeGlobals() {
        $_GET = InputValidator::sanitizeArray($_GET);
        $_POST = InputValidator::sanitizeArray($_POST);
        $_COOKIE = InputValidator::sanitizeArray($_COOKIE);
    }
    
    /**
     * Check if request is exempt from CSRF protection
     */
    private static function isExemptFromCSRF() {
        $exemptPaths = [
            '/api/webhook',
            '/cron/',
            '/Login/Login/google_callback.php'
        ];
        
        $currentPath = $_SERVER['REQUEST_URI'];
        foreach ($exemptPaths as $path) {
            if (strpos($currentPath, $path) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if this is a login request
     */
    private static function isLoginRequest() {
        return strpos($_SERVER['REQUEST_URI'], 'login.php') !== false ||
               strpos($_SERVER['REQUEST_URI'], 'register.php') !== false;
    }
    
    /**
     * Rate limiting for login attempts
     */
    private static function checkRateLimit() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = 'login_attempts_' . $ip;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'last_attempt' => time()];
        }
        
        $attempts = $_SESSION[$key];
        
        // Reset counter if more than 15 minutes passed
        if (time() - $attempts['last_attempt'] > 900) {
            $_SESSION[$key] = ['count' => 1, 'last_attempt' => time()];
            return;
        }
        
        // Block if more than 5 attempts in 15 minutes
        if ($attempts['count'] >= 5) {
            http_response_code(429);
            die('Too many login attempts. Please try again later.');
        }
        
        $_SESSION[$key]['count']++;
        $_SESSION[$key]['last_attempt'] = time();
    }
}