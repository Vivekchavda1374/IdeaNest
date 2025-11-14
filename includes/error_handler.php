<?php

// Production error handler
function productionErrorHandler($errno, $errstr, $errfile, $errline)
{
    // Log error securely
    $error_message = date('Y-m-d H:i:s') . " - Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($error_message, 3, dirname(__DIR__) . '/logs/error.log');

    // Don't expose errors to users in production
    if (ini_get('display_errors')) {
        return false; // Let PHP handle it in development
    }

    // Show generic error page in production
    http_response_code(500);
    echo "Service temporarily unavailable";
    exit();
}

function productionExceptionHandler($exception)
{
    $error_message = date('Y-m-d H:i:s') . " - Uncaught exception: " . $exception->getMessage() .
                    " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($error_message, 3, dirname(__DIR__) . '/logs/error.log');

    http_response_code(500);
    echo "Service temporarily unavailable";
    exit();
}

// Set error handlers
set_error_handler('productionErrorHandler');
set_exception_handler('productionExceptionHandler');
