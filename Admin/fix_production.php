<?php
/**
 * Production Fix Script
 * This script attempts to fix common production issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>IdeaNest - Production Fix Script</h1>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}h2{color:#333;border-bottom:2px solid #6366f1;padding-bottom:10px;}pre{background:#fff;padding:15px;border-radius:8px;}.success{color:#10b981;}.error{color:#ef4444;}</style>";

// 1. Create missing directories
echo "<h2>1. Creating Missing Directories</h2>";
echo "<pre>";
$dirs = [
    __DIR__ . '/../logs',
    __DIR__ . '/../cache',
    __DIR__ . '/../user/uploads',
    __DIR__ . '/../user/profile_pictures',
    __DIR__ . '/../backups'
];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        if (@mkdir($dir, 0777, true)) {
            echo "<span class='success'>✓</span> Created: $dir\n";
        } else {
            echo "<span class='error'>✗</span> Failed to create: $dir (Permission denied - run as sudo or create manually)\n";
        }
    } else {
        echo "<span class='success'>✓</span> Already exists: $dir\n";
    }
}
echo "</pre>";

// 2. Remove .htaccess if exists (as requested)
echo "<h2>2. Checking for .htaccess File</h2>";
echo "<pre>";
$htaccess_file = __DIR__ . '/.htaccess';
if (file_exists($htaccess_file)) {
    if (unlink($htaccess_file)) {
        echo "<span class='success'>✓</span> Removed .htaccess file (as requested)\n";
    } else {
        echo "<span class='error'>✗</span> Could not remove .htaccess (check permissions)\n";
    }
} else {
    echo "No .htaccess file found (good)\n";
}
echo "</pre>";

// 3. Check file permissions (read-only check)
echo "<h2>3. Checking File Permissions</h2>";
echo "<pre>";
$files_to_check = [
    __DIR__ . '/admin.php',
    __DIR__ . '/../Login/Login/db.php',
    __DIR__ . '/../includes/security_init.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        $readable = is_readable($file);
        echo "File: " . basename($file) . "\n";
        echo "Permissions: $perms ";
        if ($readable) {
            echo "<span class='success'>✓ Readable</span>\n";
        } else {
            echo "<span class='error'>✗ Not Readable</span>\n";
        }
        echo "\n";
    } else {
        echo "<span class='error'>✗ File not found: " . basename($file) . "</span>\n\n";
    }
}
echo "</pre>";

// 4. Test database connection
echo "<h2>4. Testing Database Connection</h2>";
echo "<pre>";
try {
    include __DIR__ . "/../Login/Login/db.php";
    if (isset($conn) && $conn->ping()) {
        echo "<span class='success'>✓ Database connection successful</span>\n";
    } else {
        echo "<span class='error'>✗ Database connection failed</span>\n";
        echo "Check your database credentials in Login/Login/db.php\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Database error: " . $e->getMessage() . "</span>\n";
}
echo "</pre>";

// 5. Clear cache
echo "<h2>5. Clearing Cache</h2>";
echo "<pre>";
$cache_dir = __DIR__ . '/../cache';
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    echo "<span class='success'>✓</span> Cleared $count cache files\n";
} else {
    echo "Cache directory not found\n";
}
echo "</pre>";

// 6. Create error log file
echo "<h2>6. Creating Error Log File</h2>";
echo "<pre>";
$log_file = __DIR__ . '/../logs/error.log';
if (!file_exists($log_file)) {
    if (touch($log_file) && chmod($log_file, 0666)) {
        echo "<span class='success'>✓</span> Created error log file\n";
    } else {
        echo "<span class='error'>✗</span> Failed to create error log\n";
    }
} else {
    echo "Error log already exists\n";
}
echo "</pre>";

echo "<hr>";
echo "<h2>Fix Complete!</h2>";
echo "<p><a href='check_errors.php' style='color:#6366f1;'>Run Diagnostic Check</a> | ";
echo "<a href='admin.php' style='color:#6366f1;'>Try Admin Panel</a></p>";
?>
