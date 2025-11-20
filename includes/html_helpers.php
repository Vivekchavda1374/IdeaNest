<?php
/**
 * HTML Helper Functions
 * Safe output functions for PHP 8.x compatibility
 */

/**
 * Safe htmlspecialchars that handles null values
 */
function safe_html($string, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
    return htmlspecialchars($string ?? '', $flags, $encoding);
}

/**
 * Safe echo with htmlspecialchars
 */
function echo_html($string) {
    echo safe_html($string);
}

/**
 * Get value from array with default
 */
function array_get($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Safe $_GET with default
 */
function get_param($key, $default = null) {
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

/**
 * Safe $_POST with default
 */
function post_param($key, $default = null) {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}
?>