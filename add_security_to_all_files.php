<?php
/**
 * Add Security Init to All PHP Files
 * Run this once to add security initialization to all PHP files
 */

$security_include = "<?php\nrequire_once __DIR__ . '/includes/security_init.php';\n?>\n";

// Directories to process
$directories = [
    'Admin',
    'Login',
    'user',
    'mentor',
    'Report'
];

// Files to skip
$skip_files = [
    'includes/security_init.php',
    'anti_injection.php',
    'add_security_to_all_files.php',
    'check_security.php',
    'vendor/',
    'tests/'
];

function shouldSkipFile($file, $skip_files) {
    foreach ($skip_files as $skip) {
        if (strpos($file, $skip) !== false) {
            return true;
        }
    }
    return false;
}

function addSecurityToFile($file) {
    global $security_include;
    
    $content = file_get_contents($file);
    
    // Check if security init is already included
    if (strpos($content, 'security_init.php') !== false) {
        echo "✓ Already secured: $file\n";
        return;
    }
    
    // Check if file starts with <?php
    if (strpos($content, '<?php') === 0) {
        // Calculate relative path to includes/security_init.php
        $depth = substr_count(dirname($file), '/');
        $relative_path = str_repeat('../', $depth) . 'includes/security_init.php';
        
        $security_line = "<?php\nrequire_once __DIR__ . '/$relative_path';\n";
        
        // Insert after opening <?php tag
        $content = preg_replace('/^<\?php\s*\n/', $security_line, $content, 1);
        
        file_put_contents($file, $content);
        echo "✓ Secured: $file\n";
    } else {
        echo "⚠ Skipped (no PHP tag): $file\n";
    }
}

function processDirectory($dir, $skip_files) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filepath = $file->getPathname();
            
            if (!shouldSkipFile($filepath, $skip_files)) {
                addSecurityToFile($filepath);
            }
        }
    }
}

echo "=== Adding Security Init to All PHP Files ===\n\n";

// Process each directory
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "\nProcessing directory: $dir\n";
        processDirectory($dir, $skip_files);
    }
}

// Process root PHP files
$root_files = glob('*.php');
foreach ($root_files as $file) {
    if (!shouldSkipFile($file, $skip_files)) {
        addSecurityToFile($file);
    }
}

echo "\n=== Complete ===\n";
echo "\nNOTE: You may need to adjust relative paths in some files.\n";
echo "Test your application thoroughly after running this script.\n";
