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


/**
 * Global helper functions for backward compatibility
 */

/**
 * Sanitize input - can handle strings or arrays
 * @param mixed $input The input to sanitize
 * @param string $type Optional type for specific sanitization (email, url, etc.)
 * @return mixed Sanitized input
 */
function sanitizeInput($input, $type = 'string') {
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $input);
    }
    
    if ($input === null) {
        return '';
    }
    
    // Type-specific sanitization
    if ($type === 'email') {
        // For email, just remove tags but keep the email structure
        return trim(strip_tags($input));
    }
    
    // Default: Remove HTML tags and trim
    $input = trim(strip_tags($input));
    
    // Convert special characters to HTML entities
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate input based on type
 * Returns array with 'valid', 'value', and 'message' keys for detailed validation
 * Or boolean for simple validation (backward compatibility)
 */
function validateInput($value, $type, $required = false) {
    $returnArray = false;
    
    // Check if required and empty
    if ($required && empty(trim($value))) {
        if ($returnArray) {
            return ['valid' => false, 'value' => $value, 'message' => 'Field is required'];
        }
        return ['valid' => false, 'value' => $value, 'message' => 'Field is required'];
    }
    
    // If not required and empty, return true
    if (!$required && empty($value)) {
        if ($returnArray) {
            return ['valid' => true, 'value' => $value, 'message' => ''];
        }
        return true;
    }
    
    // Validate based on type
    $valid = false;
    $message = '';
    $sanitizedValue = $value;
    
    switch ($type) {
        case 'email':
            $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            $message = $valid ? '' : 'Invalid email format';
            $sanitizedValue = $valid ? $value : '';
            break;
            
        case 'string':
            $valid = is_string($value) && strlen(trim($value)) > 0;
            $message = $valid ? '' : 'Invalid string';
            break;
            
        case 'int':
        case 'integer':
            $valid = filter_var($value, FILTER_VALIDATE_INT) !== false;
            $message = $valid ? '' : 'Invalid integer';
            break;
            
        case 'url':
            $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
            $message = $valid ? '' : 'Invalid URL';
            break;
            
        case 'phone':
            $valid = preg_match('/^[0-9]{10,15}$/', $value);
            $message = $valid ? '' : 'Invalid phone number';
            break;
            
        case 'alphanumeric':
            $valid = preg_match('/^[a-zA-Z0-9]+$/', $value);
            $message = $valid ? '' : 'Must be alphanumeric';
            break;
            
        case 'alpha':
            $valid = preg_match('/^[a-zA-Z]+$/', $value);
            $message = $valid ? '' : 'Must contain only letters';
            break;
            
        case 'numeric':
            $valid = is_numeric($value);
            $message = $valid ? '' : 'Must be numeric';
            break;
            
        default:
            $valid = true;
            break;
    }
    
    // Return array format for tests
    return ['valid' => $valid, 'value' => $sanitizedValue, 'message' => $message];
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize email address
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate required field
 */
function validateRequired($value) {
    return !empty(trim($value));
}

/**
 * Validate string length
 */
function validateLength($value, $min = 0, $max = PHP_INT_MAX) {
    $length = strlen($value);
    return $length >= $min && $length <= $max;
}

/**
 * Validate integer range
 */
function validateRange($value, $min, $max) {
    $int = filter_var($value, FILTER_VALIDATE_INT);
    if ($int === false) {
        return false;
    }
    return $int >= $min && $int <= $max;
}
