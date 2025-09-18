<?php
/**
 * Enhanced Input Sanitization
 */

function sanitizeInput($input, $type = "string") {
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $input);
    }
    
    $input = trim($input);
    
    switch ($type) {
        case "email":
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case "url":
            return filter_var($input, FILTER_SANITIZE_URL);
        case "int":
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case "float":
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case "html":
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, "UTF-8");
        default:
            return htmlspecialchars(strip_tags($input), ENT_QUOTES | ENT_HTML5, "UTF-8");
    }
}

function validateInput($input, $type, $required = false) {
    if ($required && empty($input)) {
        return ["valid" => false, "message" => "Field is required"];
    }
    
    if (empty($input) && !$required) {
        return ["valid" => true, "value" => ""];
    }
    
    switch ($type) {
        case "email":
            $valid = filter_var($input, FILTER_VALIDATE_EMAIL);
            return ["valid" => $valid !== false, "value" => $valid, "message" => $valid ? "" : "Invalid email format"];
        case "url":
            $valid = filter_var($input, FILTER_VALIDATE_URL);
            return ["valid" => $valid !== false, "value" => $valid, "message" => $valid ? "" : "Invalid URL format"];
        case "int":
            $valid = filter_var($input, FILTER_VALIDATE_INT);
            return ["valid" => $valid !== false, "value" => $valid, "message" => $valid ? "" : "Invalid number"];
        default:
            return ["valid" => true, "value" => sanitizeInput($input, $type)];
    }
}

function logSecurityEvent($event, $details = "", $severity = "INFO") {
    $logDir = __DIR__ . "/../logs";
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = date("Y-m-d H:i:s") . " [$severity] $event";
    if ($details) {
        $logEntry .= " - $details";
    }
    $logEntry .= "\n";
    
    file_put_contents("$logDir/security.log", $logEntry, FILE_APPEND | LOCK_EX);
}
?>