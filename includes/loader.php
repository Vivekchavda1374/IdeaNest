<?php
// Universal Loader Include
function includeLoader() {
    echo '
    <!-- Universal Loader CSS -->
    <link rel="stylesheet" href="' . getLoaderPath() . 'assets/css/loader.css">
    
    <!-- Universal Loader JS -->
    <script src="' . getLoaderPath() . 'assets/js/loader.js"></script>
    ';
}

function getLoaderPath() {
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    if ($currentDir === 'user' || $currentDir === 'IdeaNest') {
        return './';
    } elseif ($currentDir === 'Blog' || $currentDir === 'forms') {
        return '../';
    } elseif ($currentDir === 'Login') {
        return '../';
    } elseif ($currentDir === 'Admin') {
        return '../';
    } else {
        return '../../';
    }
}
?>