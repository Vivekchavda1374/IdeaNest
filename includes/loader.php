<?php
/**
 * Universal Loader Component
 * Include this file in your pages to add loading functionality
 */

function render_loader() {
    ?>
    <!-- Universal Loader Overlay -->
    <div id="universalLoader" class="loader-overlay">
        <div class="loader">
            <div class="loader-spinner"></div>
            <div class="loader-text" id="loaderText">Loading...</div>
        </div>
    </div>
    <?php
}

function include_loader_assets() {
    ?>
    <!-- Loader CSS -->
    <link rel="stylesheet" href="<?php echo get_base_url(); ?>/assets/css/loader.css">
    
    <!-- Loader JavaScript -->
    <script src="<?php echo get_base_url(); ?>/assets/js/loader.js"></script>
    <?php
}

function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    
    // Remove trailing slash
    $path = rtrim($path, '/');
    
    // Calculate base URL relative to project root
    $base = $protocol . '://' . $host;
    
    // If we're in a subdirectory, adjust the path
    if (strpos($path, '/Admin') !== false || strpos($path, '/user') !== false || strpos($path, '/mentor') !== false) {
        $base .= substr($path, 0, strrpos($path, '/'));
    } else {
        $base .= $path;
    }
    
    return $base;
}
?>
