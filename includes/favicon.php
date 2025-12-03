<?php
// Auto-detect the correct path to favicon based on current file location
if (!isset($favicon_path)) {
    $current_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $root_dir = dirname(__DIR__);
    
    // Calculate relative path from current file to assets
    $rel_path = str_replace($root_dir, '', $current_dir);
    $depth = substr_count($rel_path, '/');
    
    if ($depth == 0) {
        // Root level
        $favicon_path = 'assets/image/fevicon.png';
    } else {
        // Subdirectory - go up appropriate levels
        $favicon_path = str_repeat('../', $depth) . 'assets/image/fevicon.png';
    }
}
?>
<link rel="icon" type="image/png" href="<?php echo $favicon_path; ?>">
