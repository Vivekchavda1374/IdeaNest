<?php
// Base URL Configuration
define('BASE_URL', 'https://ictmu.in/hcd/IdeaNest');

// Helper function to get full URL
function getBaseUrl($path = '') {
    $url = rtrim(BASE_URL, '/');
    if ($path) {
        $path = ltrim($path, '/');
        return $url . '/' . $path;
    }
    return $url;
}

// Get current protocol
function getProtocol() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
}

// Get current domain
function getCurrentDomain() {
    return getProtocol() . $_SERVER['HTTP_HOST'];
}
?>
