<?php
/**
 * Verification script to check which files have anti-injection protection
 */

$directories = [
    'Admin' => __DIR__ . '/../Admin',
    'Admin/subadmin' => __DIR__ . '/../Admin/subadmin',
    'mentor' => __DIR__ . '/../mentor',
    'user' => __DIR__ . '/../user',
    'user/Blog' => __DIR__ . '/../user/Blog',
    'user/chat' => __DIR__ . '/../user/chat',
    'user/forms' => __DIR__ . '/../user/forms',
    'Login/Login' => __DIR__ . '/../Login/Login',
    'Report' => __DIR__ . '/../Report',
    'Root' => __DIR__ . '/..',
];

$protected = 0;
$unprotected = 0;
$no_html = 0;

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     Anti-Injection Protection Verification Report        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

foreach ($directories as $name => $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    
    $files = glob($dir . '/*.php');
    if (empty($files)) {
        continue;
    }
    
    echo "📁 $name/\n";
    echo str_repeat("─", 60) . "\n";
    
    foreach ($files as $file) {
        $filename = basename($file);
        $content = file_get_contents($file);
        
        // Skip if no <head> tag (not an HTML file)
        if (strpos($content, '<head>') === false) {
            $no_html++;
            continue;
        }
        
        // Check if has anti_injection.js
        if (strpos($content, 'anti_injection.js') !== false) {
            echo "  ✅ $filename\n";
            $protected++;
        } else {
            echo "  ❌ $filename - MISSING PROTECTION!\n";
            $unprotected++;
        }
    }
    
    echo "\n";
}

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                        Summary                            ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║ ✅ Protected files:        " . str_pad($protected, 3, ' ', STR_PAD_LEFT) . "                              ║\n";
echo "║ ❌ Unprotected files:      " . str_pad($unprotected, 3, ' ', STR_PAD_LEFT) . "                              ║\n";
echo "║ ⊘  Non-HTML files:         " . str_pad($no_html, 3, ' ', STR_PAD_LEFT) . "                              ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

if ($unprotected > 0) {
    echo "⚠️  WARNING: $unprotected file(s) still need protection!\n";
    exit(1);
} else {
    echo "✨ SUCCESS: All HTML files are protected!\n";
    exit(0);
}
