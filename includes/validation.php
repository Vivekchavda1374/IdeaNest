<?php
/**
 * Input Validation and Sanitization System
 * Comprehensive input security for all user data
 */

class InputValidator {
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input, $allowHtml = false) {
        if ($input === null) return '';
        
        $input = trim($input);
        if (!$allowHtml) {
            $input = strip_tags($input);
        }
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate and sanitize email
     */
    public static function validateEmail($email) {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }
    
    /**
     * Validate integer
     */
    public static function validateInt($input, $min = null, $max = null) {
        $int = filter_var($input, FILTER_VALIDATE_INT);
        if ($int === false) return false;
        
        if ($min !== null && $int < $min) return false;
        if ($max !== null && $int > $max) return false;
        
        return $int;
    }
    
    /**
     * Validate URL
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename) {
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return substr($filename, 0, 255);
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 10485760) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize SQL input (for prepared statements)
     */
    public static function sanitizeForDB($input) {
        return trim(strip_tags($input));
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        return true;
    }
    
    /**
     * Sanitize array recursively
     */
    public static function sanitizeArray($array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::sanitizeArray($value);
            } else {
                $array[$key] = self::sanitizeString($value);
            }
        }
        return $array;
    }
}