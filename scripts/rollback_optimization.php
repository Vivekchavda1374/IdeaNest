<?php
/**
 * Rollback Optimization Script
 * Use this if optimization causes any issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Rollback Optimization ===\n\n";

try {
    require_once __DIR__ . '/../Login/Login/db.php';
    
    if (!isset($conn) || !$conn || !$conn->ping()) {
        die("✗ Database connection failed\n");
    }
    
    echo "✓ Database connected\n\n";
} catch (Exception $e) {
    die("✗ Error: " . $e->getMessage() . "\n");
}

echo "WARNING: This will remove optimization indexes and views.\n";
echo "Your data will NOT be deleted, only optimization structures.\n\n";

// Ask for confirmation
if (php_sapi_name() === 'cli') {
    echo "Do you want to continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim(strtolower($line)) !== 'yes') {
        die("Rollback cancelled.\n");
    }
    fclose($handle);
}

echo "\nStarting rollback...\n\n";

// Step 1: Drop views
echo "Step 1: Removing views...\n";
$views = ['v_project_stats', 'v_user_stats', 'v_idea_stats'];
$dropped_views = 0;

foreach ($views as $view) {
    try {
        $conn->query("DROP VIEW IF EXISTS $view");
        echo "  ✓ Dropped view: $view\n";
        $dropped_views++;
    } catch (Exception $e) {
        echo "  ⚠ Could not drop $view: " . $e->getMessage() . "\n";
    }
}
echo "Dropped $dropped_views views\n\n";

// Step 2: Remove indexes (optional - usually safe to keep)
echo "Step 2: Removing optimization indexes...\n";
echo "Note: Keeping PRIMARY and UNIQUE indexes (they are required)\n\n";

$indexes_to_remove = [
    ["table" => "admin_approved_projects", "name" => "idx_user_id"],
    ["table" => "admin_approved_projects", "name" => "idx_status"],
    ["table" => "admin_approved_projects", "name" => "idx_submission_date"],
    ["table" => "admin_approved_projects", "name" => "idx_classification"],
    ["table" => "admin_approved_projects", "name" => "idx_project_type"],
    ["table" => "admin_approved_projects", "name" => "idx_language"],
    ["table" => "blog", "name" => "idx_user_id"],
    ["table" => "blog", "name" => "idx_status"],
    ["table" => "blog", "name" => "idx_submission_datetime"],
    ["table" => "bookmark", "name" => "idx_user_project"],
    ["table" => "project_likes", "name" => "idx_project_user"],
    ["table" => "project_likes", "name" => "idx_project_id"],
];

$removed_indexes = 0;
foreach ($indexes_to_remove as $index) {
    try {
        $sql = "ALTER TABLE {$index['table']} DROP INDEX {$index['name']}";
        $conn->query($sql);
        echo "  ✓ Removed index: {$index['table']}.{$index['name']}\n";
        $removed_indexes++;
    } catch (Exception $e) {
        // Index might not exist, which is fine
        if (strpos($e->getMessage(), "check that column/key exists") === false) {
            echo "  ⚠ Could not remove {$index['table']}.{$index['name']}: " . $e->getMessage() . "\n";
        }
    }
}
echo "Removed $removed_indexes indexes\n\n";

// Step 3: Clear cache
echo "Step 3: Clearing cache...\n";
$cache_dir = __DIR__ . '/../cache/queries';
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '/*.cache');
    $cleared = 0;
    foreach ($files as $file) {
        if (unlink($file)) {
            $cleared++;
        }
    }
    echo "  ✓ Cleared $cleared cache files\n";
} else {
    echo "  - No cache directory found\n";
}
echo "\n";

// Step 4: Test basic queries
echo "Step 4: Testing basic queries...\n";
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM admin_approved_projects");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "  ✓ Basic query works - Found {$row['count']} projects\n";
    }
} catch (Exception $e) {
    echo "  ✗ Query test failed: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Rollback Complete ===\n\n";
echo "Summary:\n";
echo "- Views dropped: $dropped_views\n";
echo "- Indexes removed: $removed_indexes\n";
echo "- Cache cleared\n\n";

echo "Your application should now work as it did before optimization.\n";
echo "Note: Performance will be slower without indexes.\n\n";

$conn->close();
?>
