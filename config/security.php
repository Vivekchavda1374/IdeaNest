<?php
/**
 * Security Configuration and Helper Functions
 * Centralized security management for IdeaNest
 */

class SecurityManager {
    
    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF Token
     */
    public static function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize Input
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        return $data;
    }
    
    /**
     * Validate File Upload
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 10485760) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Invalid file upload';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check for upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File size exceeds limit';
                return ['valid' => false, 'errors' => $errors];
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No file uploaded';
                return ['valid' => false, 'errors' => $errors];
            default:
                $errors[] = 'Unknown upload error';
                return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds ' . ($maxSize / 1048576) . 'MB limit';
        }
        
        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes);
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = [
            'jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 
            'zip', 'rar', 'mp4', 'avi', 'mov', 'txt', 'ppt', 'pptx'
        ];
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Invalid file extension';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }
    
    /**
     * Encrypt Data
     */
    public static function encrypt($data, $key = null) {
        if ($key === null) {
            $key = $_ENV['ENCRYPTION_KEY'] ?? 'default_encryption_key_change_this';
        }
        
        $cipher = "AES-256-CBC";
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Decrypt Data
     */
    public static function decrypt($data, $key = null) {
        if ($key === null) {
            $key = $_ENV['ENCRYPTION_KEY'] ?? 'default_encryption_key_change_this';
        }
        
        $cipher = "AES-256-CBC";
        
        list($encrypted, $iv) = explode('::', base64_decode($data), 2);
        
        return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    }
    
    /**
     * Prevent Session Fixation
     */
    public static function regenerateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        session_regenerate_id(true);
    }
    
    /**
     * Set Security Headers
     */
    public static function setSecurityHeaders() {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
    
    /**
     * Rate Limiting
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . $identifier;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        $data = $_SESSION[$key];
        $timePassed = time() - $data['first_attempt'];
        
        // Reset if time window passed
        if ($timePassed > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    /**
     * Validate File Path (Prevent Directory Traversal)
     */
    public static function validateFilePath($path, $baseDir) {
        $realBase = realpath($baseDir);
        $realPath = realpath($path);
        
        if ($realPath === false || strpos($realPath, $realBase) !== 0) {
            return false;
        }
        
        return true;
    }
}

// Set security headers on every request
SecurityManager::setSecurityHeaders();
?>
