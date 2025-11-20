<?php
/**
 * Centralized Error Handler
 * Unified error logging and handling
 */

class ErrorHandler {
    
    private static $logFile = __DIR__ . '/../logs/error.log';
    private static $isProduction = false;
    
    /**
     * Initialize error handler
     */
    public static function init() {
        self::$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
        
        // Set error reporting
        if (self::$isProduction) {
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
        
        // Set custom error handler
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
        
        // Ensure log directory exists
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Handle errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $errorTypes = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSE',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE_ERROR',
            E_CORE_WARNING => 'CORE_WARNING',
            E_COMPILE_ERROR => 'COMPILE_ERROR',
            E_COMPILE_WARNING => 'COMPILE_WARNING',
            E_USER_ERROR => 'USER_ERROR',
            E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE',
            E_STRICT => 'STRICT',
            E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
            E_DEPRECATED => 'DEPRECATED',
            E_USER_DEPRECATED => 'USER_DEPRECATED'
        ];
        
        $type = $errorTypes[$errno] ?? 'UNKNOWN';
        
        self::log($type, $errstr, $errfile, $errline);
        
        // Don't execute PHP internal error handler
        return true;
    }
    
    /**
     * Handle exceptions
     */
    public static function handleException($exception) {
        self::log(
            'EXCEPTION',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        if (self::$isProduction) {
            http_response_code(500);
            include __DIR__ . '/../error_pages/500.html';
        } else {
            echo '<pre>';
            echo 'Exception: ' . $exception->getMessage() . "\n";
            echo 'File: ' . $exception->getFile() . ':' . $exception->getLine() . "\n";
            echo 'Trace: ' . $exception->getTraceAsString();
            echo '</pre>';
        }
        
        exit(1);
    }
    
    /**
     * Handle fatal errors
     */
    public static function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::log(
                'FATAL',
                $error['message'],
                $error['file'],
                $error['line']
            );
            
            if (self::$isProduction) {
                http_response_code(500);
                include __DIR__ . '/../error_pages/500.html';
            }
        }
    }
    
    /**
     * Log error
     */
    private static function log($type, $message, $file, $line, $trace = null) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message in $file:$line\n";
        
        if ($trace) {
            $logMessage .= "Stack trace:\n$trace\n";
        }
        
        $logMessage .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
        $logMessage .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n";
        $logMessage .= "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
        $logMessage .= str_repeat('-', 80) . "\n";
        
        // Write to log file
        error_log($logMessage, 3, self::$logFile);
        
        // Also log to system log in production
        if (self::$isProduction) {
            error_log("[$type] $message in $file:$line");
        }
    }
    
    /**
     * Log custom message
     */
    public static function logMessage($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        error_log($logMessage, 3, self::$logFile);
    }
    
    /**
     * Log database error
     */
    public static function logDatabaseError($query, $error) {
        self::log('DATABASE_ERROR', "Query: $query | Error: $error", __FILE__, __LINE__);
    }
}

// Initialize error handler
ErrorHandler::init();
?>
