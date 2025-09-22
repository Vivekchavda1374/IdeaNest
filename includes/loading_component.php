<?php
/**
 * Loading Component for IdeaNest
 * Include this in pages that need loading functionality
 */

function includeLoadingAssets() {
    echo '<link rel="stylesheet" href="' . getAssetPath('css/loading.css') . '">';
    echo '<script src="' . getAssetPath('js/loading.js') . '"></script>';
}

function getAssetPath($path) {
    // Determine the correct path based on current directory depth
    $currentPath = $_SERVER['REQUEST_URI'];
    $depth = substr_count(parse_url($currentPath, PHP_URL_PATH), '/') - 2; // Adjust based on your structure
    
    $prefix = str_repeat('../', max(0, $depth));
    return $prefix . 'assets/' . $path;
}

function renderLoadingOverlay($id = 'globalLoading', $message = 'Loading...') {
    echo "
    <div id='{$id}' class='loading-overlay'>
        <div class='loading-content'>
            <div class='loading-spinner'></div>
            <p class='loading-text'>{$message}</p>
            <div class='progress-loading'>
                <div class='progress-loading-bar'></div>
            </div>
        </div>
    </div>";
}

function addLoadingScript() {
    echo "
    <script>
        // Show page loading on navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading to all navigation links
            const navLinks = document.querySelectorAll('a[href]:not([href^=\"#\"]):not([href^=\"mailto:\"]):not([href^=\"tel:\"]):not([target=\"_blank\"])');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!this.hasAttribute('data-no-loading')) {
                        showPageLoading();
                    }
                });
            });
            
            // Add loading to forms without data-no-loading attribute
            const forms = document.querySelectorAll('form:not([data-no-loading])');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type=\"submit\"], input[type=\"submit\"]');
                    if (submitBtn) {
                        let message = 'Processing...';
                        
                        // Determine message based on form context
                        if (this.action.includes('email') || this.id.includes('email')) {
                            message = 'Sending email...';
                        } else if (this.enctype === 'multipart/form-data') {
                            message = 'Uploading files...';
                        } else if (this.action.includes('login') || this.action.includes('auth')) {
                            message = 'Authenticating...';
                        }
                        
                        window.loadingManager.show(message);
                        setButtonLoading(submitBtn, true);
                    }
                });
            });
        });
    </script>";
}
?>