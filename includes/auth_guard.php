<?php
/**
 * Authentication Guard System
 * Prevents authentication bypass vulnerabilities
 */

class AuthGuard {
    
    /**
     * Verify user session
     */
    public static function verifySession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            return false;
        }
        
        // Verify session integrity
        if (!self::verifySessionIntegrity()) {
            session_destroy();
            return false;
        }
        
        return true;
    }
    
    /**
     * Require authentication
     */
    public static function requireAuth($redirectUrl = '/IdeaNest/Login/Login/login.php') {
        if (!self::verifySession()) {
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($requiredRole, $redirectUrl = '/IdeaNest/') {
        if (!self::verifySession()) {
            header('Location: /IdeaNest/Login/Login/login.php');
            exit();
        }
        
        if ($_SESSION['user_role'] !== $requiredRole) {
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
    
    /**
     * Verify session integrity
     */
    private static function verifySessionIntegrity() {
        // Check session timeout (2 hours)
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 7200)) {
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Verify user agent consistency
        if (isset($_SESSION['user_agent']) && 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize secure session
     */
    public static function initSecureSession($userId, $userRole, $userEmail) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['user_email'] = $userEmail;
        $_SESSION['last_activity'] = time();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Secure logout
     */
    public static function logout($redirectUrl = '/IdeaNest/Login/Login/login.php') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_unset();
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        header('Location: ' . $redirectUrl);
        exit();
    }
    
    /**
     * Check if user has permission
     */
    public static function hasPermission($permission) {
        if (!self::verifySession()) {
            return false;
        }
        
        $role = $_SESSION['user_role'];
        $permissions = [
            'admin' => ['manage_users', 'manage_projects', 'manage_system', 'view_analytics'],
            'subadmin' => ['review_projects', 'manage_assignments'],
            'mentor' => ['view_students', 'manage_sessions'],
            'user' => ['submit_projects', 'view_projects']
        ];
        
        return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
    }
}