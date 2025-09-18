<?php
/**
 * Enhanced CSRF Protection
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function generateCSRFToken() {
    if (!isset($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function validateCSRFToken($token) {
    return isset($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);
}

function getCSRFField() {
    $token = generateCSRFToken();
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"" . htmlspecialchars($token) . "\">";
}

function requireCSRF() {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (!isset($_POST["csrf_token"]) || !validateCSRFToken($_POST["csrf_token"])) {
            http_response_code(403);
            die("CSRF token validation failed");
        }
    }
}
?>