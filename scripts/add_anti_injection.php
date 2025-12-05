<?php
/**
 * Script to add anti-injection JavaScript to all admin/mentor/subadmin/user pages
 * Run this once to update all files
 */

// Define directories to scan
$directories = [
    __DIR__ . '/../Admin',
    __DIR__ . '/../mentor',
    __DIR__ . '/../user',
    __DIR__ . '/../Admin/subadmin',
    __DIR__ . '/../Login',
    __DIR__ . '/../Report',
];

$files_updated = 0;
$files_skipped = 0;
$files_already_have = 0;

echo "Starting anti-injection script addition...\n\n";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "⚠ Directory not found: $dir\n";
        continue;
    }
    
    echo "📁 Scanning: " . basename($dir) . "/\n";
    
    $files = glob($dir . '/*.php');
    
    foreach ($files as $file) {
        $filename = basename($file);
        $content = file_get_contents($file);
        
        // Skip if already has anti_injection.js
        if (strpos($content, 'anti_injection.js') !== false) {
            $files_already_have++;
            echo "  ✓ Already protected: $filename\n";
            continue;
        }
        
        // Skip if no <head> tag
        if (strpos($content, '<head>') === false) {
            $files_skipped++;
            echo "  ⊘ No <head> tag: $filename\n";
            continue;
        }
        
        // Determine the correct path to anti_injection.js based on directory depth
        $relative_path = '../assets/js/anti_injection.js';
        if (strpos($file, 'Admin/subadmin') !== false) {
            $relative_path = '../../assets/js/anti_injection.js';
        } elseif (strpos($file, 'Login') !== false || strpos($file, 'Report') !== false) {
            $relative_path = '../assets/js/anti_injection.js';
        }
        
        $anti_injection_script = '    <!-- Anti-injection script - MUST be first -->' . "\n" . 
                                 '    <script src="' . $relative_path . '"></script>';
        
        // Try multiple patterns to find where to insert
        $patterns = [
            // Pattern 1: After <head> with newline
            '/(<head[^>]*>\s*\n)/',
            // Pattern 2: After <head> without newline
            '/(<head[^>]*>)/',
        ];
        
        $replaced = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $new_content = preg_replace(
                    $pattern,
                    '$1' . $anti_injection_script . "\n",
                    $content,
                    1
                );
                
                if ($new_content !== $content && $new_content !== null) {
                    file_put_contents($file, $new_content);
                    echo "  ✅ Updated: $filename\n";
                    $files_updated++;
                    $replaced = true;
                    break;
                }
            }
        }
        
        if (!$replaced) {
            $files_skipped++;
            echo "  ⚠ Could not update: $filename\n";
        }
    }
    
    echo "\n";
}

echo "═══════════════════════════════════════\n";
echo "📊 Summary:\n";
echo "═══════════════════════════════════════\n";
echo "✅ Files updated: $files_updated\n";
echo "✓  Already protected: $files_already_have\n";
echo "⊘  Skipped (no <head>): $files_skipped\n";
echo "═══════════════════════════════════════\n";
echo "\n✨ Done! All files have been processed.\n";
