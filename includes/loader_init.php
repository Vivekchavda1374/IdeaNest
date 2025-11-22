<?php
/**
 * Loader Initialization Script
 * This file provides helper functions to easily add loaders to any page
 */

// Function to render loader in head section
function loader_head() {
    $base_path = get_loader_base_path();
    echo '<link rel="stylesheet" href="' . $base_path . '/assets/css/loader.css">';
    echo '<link rel="stylesheet" href="' . $base_path . '/assets/css/loading.css">';
}

// Function to render loader HTML
function loader_body() {
    ?>
    <div id="universalLoader" class="loader-overlay">
        <div class="loader">
            <div class="loader-spinner"></div>
            <div class="loader-text" id="loaderText">Loading...</div>
        </div>
    </div>
    <?php
}

// Function to render loader scripts
function loader_scripts() {
    $base_path = get_loader_base_path();
    echo '<script src="' . $base_path . '/assets/js/loader.js"></script>';
    echo '<script src="' . $base_path . '/assets/js/loading.js"></script>';
}

// Helper function to get base path
function get_loader_base_path() {
    $current_path = $_SERVER['SCRIPT_NAME'];
    
    // Count directory depth
    $depth = substr_count(dirname($current_path), '/') - substr_count($_SERVER['DOCUMENT_ROOT'], '/');
    
    // Build relative path
    $base = '';
    for ($i = 0; $i < $depth; $i++) {
        $base .= '../';
    }
    
    return rtrim($base, '/') ?: '.';
}

// Auto-initialize loader on page load
function init_loader() {
    ?>
    <script>
    // Auto-show loader on page navigation
    window.addEventListener('beforeunload', function() {
        if (window.loader) {
            loader.show('Loading...');
        }
    });
    
    // Hide loader when page is fully loaded
    window.addEventListener('load', function() {
        if (window.loader) {
            setTimeout(() => loader.hide(), 300);
        }
    });
    
    // Show loader for AJAX requests
    if (typeof $ !== 'undefined') {
        $(document).ajaxStart(function() {
            if (window.loader) loader.show('Processing...');
        });
        $(document).ajaxStop(function() {
            if (window.loader) loader.hide();
        });
    }
    </script>
    <?php
}
?>
