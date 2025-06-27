<?php
// Set the project root as include path
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);

// router.php
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is.
} else {
    // Remove query string
    $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    
    // Default to index.php if accessing root
    if ($uri == '/') {
        $uri = '/user/index.php';
    }
    
    // Convert URI to file path
    $file = __DIR__ . $uri;
    
    if (file_exists($file) && is_file($file)) {
        // Set up relative paths to work from any directory
        chdir(dirname($file));
        require $file;
    } else {
        http_response_code(404);
        echo "Not Found: " . $file;
    }
}
?>