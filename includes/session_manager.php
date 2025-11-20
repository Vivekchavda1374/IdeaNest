<?php
/**
 * Unified Session Management
 * Centralized session handling for all user types
 */

class SessionManager {
    
    /**
     * Start secure session
     */
    public static function startSecure() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            
            // Set session name
            session_name('IDEANEST_SESSION');
            
            // Start session
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                // Regenerate session ID every 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        self::startSecure();
        return isset($_SESSION['user_id']) || isset($_SESSION['admin_logged_in']) || isset($_SESSION['mentor_id']);
    }
    
    /**
     * Get user type
     */
    public static function getUserType() {
        self::startSecure();
        
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            return 'admin';
        } elseif (isset($_SESSION['mentor_id'])) {
            return 'mentor';
        } elseif (isset($_SESSION['user_id'])) {
            return 'user';
        }
        
        return null;
    }
    
    /**
     * Get user ID
     */
    public static function getUserId() {
        self::startSecure();
        
        $type = self::getUserType();
        
        switch ($type) {
            case 'admin':
                return $_SESSION['admin_id'] ?? 1;
            case 'mentor':
                return $_SESSION['mentor_id'];
            case 'user':
                return $_SESSION['user_id'];
            default:
                return null;
        }
    }
    
    /**
     * Set user session
     */
    public static function setUser($userId, $userName, $userType = 'user', $additionalData = []) {
        self::startSecure();
        
        // Regenerate session ID on login
        session_regenerate_id(true);
        
        switch ($userType) {
            case 'admin':
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $userId;
                $_SESSION['admin_name'] = $userName;
                break;
            case 'mentor':
                $_SESSION['mentor_id'] = $userId;
                $_SESSION['mentor_name'] = $userName;
                break;
            case 'user':
            default:
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $userName;
                break;
        }
        
        // Set additional data
        foreach ($additionalData as $key => $value) {
            $_SESSION[$key] = $value;
        }
        
        // Set login timestamp
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Update last activity
     */
    public static function updateActivity() {
        self::startSecure();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check session timeout
     */
    public static function checkTimeout($timeout = 3600) {
        self::startSecure();
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::destroy();
            return false;
        }
        
        self::updateActivity();
        return true;
    }
    
    /**
     * Destroy session
     */
    public static function destroy() {
        self::startSecure();
        
        // Unset all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Require authentication
     */
    public static function requireAuth($allowedTypes = ['user', 'admin', 'mentor']) {
        self::startSecure();
        
        if (!self::isLoggedIn()) {
            header('Location: /IdeaNest/Login/Login/login.php');
            exit();
        }
        
        $userType = self::getUserType();
        
        if (!in_array($userType, $allowedTypes)) {
            http_response_code(403);
            die('Access denied. Insufficient permissions.');
        }
        
        // Check timeout
        if (!self::checkTimeout()) {
            header('Location: /IdeaNest/Login/Login/login.php?timeout=1');
            exit();
        }
    }
    
    /**
     * Get session data
     */
    public static function get($key, $default = null) {
        self::startSecure();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set session data
     */
    public static function set($key, $value) {
        self::startSecure();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Check if session has key
     */
    public static function has($key) {
        self::startSecure();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public static function remove($key) {
        self::startSecure();
        unset($_SESSION[$key]);
    }
    
    /**
     * Flash message (one-time message)
     */
    public static function flash($key, $value = null) {
        self::startSecure();
        
        if ($value === null) {
            // Get and remove flash message
            $message = $_SESSION['flash_' . $key] ?? null;
            unset($_SESSION['flash_' . $key]);
            return $message;
        } else {
            // Set flash message
            $_SESSION['flash_' . $key] = $value;
        }
    }
}
?>
