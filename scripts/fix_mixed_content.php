<?php
/**
 * Script to identify and fix mixed content issues
 * Run this to scan for HTTP resources that should be HTTPS
 */

echo "=== IdeaNest Mixed Content Fixer ===\n\n";

$root_dir = dirname(__DIR__);
$issues_found = [];
$files_to_check = [];

// Recursively find PHP and HTML files
function findFiles($dir, &$files, $extensions = ['php', 'html']) {
    if (!is_dir($dir)) return;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . '/' . $item;
        
        // Skip vendor, node_modules, .git directories
        if (is_dir($path)) {
            $skip_dirs = ['vendor', 'node_modules', '.git', '.idea', 'backups'];
            if (!in_array($item, $skip_dirs)) {
                findFiles($path, $files, $extensions);
            }
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($ext, $extensions)) {
                $files[] = $path;
            }
        }
    }
}

echo "Scanning for mixed content issues...\n";
findFiles($root_dir, $files_to_check);

echo "Found " . count($files_to_check) . " files to check\n\n";

// Patterns to look for
$patterns = [
    'http_script' => '/src=["\']http:\/\/[^"\']+["\']/',
    'http_link' => '/href=["\']http:\/\/[^"\']+["\']/',
    'http_img' => '/<img[^>]+src=["\']http:\/\/[^"\']+["\']/',
    'http_cdn' => '/http:\/\/(cdn\.|ajax\.|code\.)[^"\'<>\s]+/',
];

foreach ($files_to_check as $file) {
    $content = file_get_contents($file);
    $relative_path = str_replace($root_dir . '/', '', $file);
    
    foreach ($patterns as $type => $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $match) {
                $issues_found[] = [
                    'file' => $relative_path,
                    'type' => $type,
                    'match' => $match
                ];
            }
        }
    }
}

if (empty($issues_found)) {
    echo "✓ No mixed content issues found in codebase!\n";
} else {
    echo "✗ Found " . count($issues_found) . " mixed content issues:\n\n";
    
    $grouped = [];
    foreach ($issues_found as $issue) {
        $grouped[$issue['file']][] = $issue;
    }
    
    foreach ($grouped as $file => $issues) {
        echo "File: $file\n";
        foreach ($issues as $issue) {
            echo "  - [{$issue['type']}] {$issue['match']}\n";
        }
        echo "\n";
    }
    
    echo "\nRecommendations:\n";
    echo "1. Replace all http:// URLs with https://\n";
    echo "2. Use protocol-relative URLs (//example.com) for external resources\n";
    echo "3. Add 'upgrade-insecure-requests' to Content-Security-Policy\n";
    echo "4. Check .htaccess for HTTPS redirect rules\n";
}

echo "\n=== Checking .htaccess configuration ===\n";

$htaccess_files = [
    $root_dir . '/.htaccess',
    $root_dir . '/Login/Login/.htaccess'
];

foreach ($htaccess_files as $htaccess) {
    if (file_exists($htaccess)) {
        $content = file_get_contents($htaccess);
        $relative = str_replace($root_dir . '/', '', $htaccess);
        
        echo "\nFile: $relative\n";
        
        if (strpos($content, 'upgrade-insecure-requests') !== false) {
            echo "  ✓ Has upgrade-insecure-requests directive\n";
        } else {
            echo "  ✗ Missing upgrade-insecure-requests directive\n";
        }
        
        if (strpos($content, 'RewriteCond %{HTTPS} off') !== false) {
            echo "  ✓ Has HTTPS redirect rule\n";
        } else {
            echo "  ✗ Missing HTTPS redirect rule\n";
        }
    }
}

echo "\n=== Checking for external script injections ===\n";

// Check for common injection points
$injection_patterns = [
    'directfwd.com',
    'jsinit',
    'jspark',
    'eval(',
    'document.write(',
];

$suspicious_files = [];

foreach ($files_to_check as $file) {
    $content = file_get_contents($file);
    $relative_path = str_replace($root_dir . '/', '', $file);
    
    foreach ($injection_patterns as $pattern) {
        if (stripos($content, $pattern) !== false) {
            $suspicious_files[$relative_path][] = $pattern;
        }
    }
}

if (empty($suspicious_files)) {
    echo "✓ No suspicious script injections found\n";
} else {
    echo "✗ Found suspicious patterns:\n\n";
    foreach ($suspicious_files as $file => $patterns) {
        echo "File: $file\n";
        foreach ($patterns as $pattern) {
            echo "  - Contains: $pattern\n";
        }
        echo "\n";
    }
}

echo "\n=== Done ===\n";
?>
