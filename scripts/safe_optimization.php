<?php
/**
 * Safe Query Optimization Script
 * This script carefully optimizes queries without breaking existing functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Safe Query Optimization ===\n\n";

// Step 1: Test database connection
echo "Step 1: Testing database connection...\n";
try {
    require_once __DIR__ . '/../Login/Login/db.php';
    
    if (!isset($conn) || !$conn) {
        die("✗ Database connection not available. Please start MySQL/MariaDB.\n");
    }
    
    if (!$conn->ping()) {
        die("✗ Database connection failed. Please check your database server.\n");
    }
    
    echo "✓ Database connection successful\n\n";
} catch (Exception $e) {
    die("✗ Error: " . $e->getMessage() . "\n");
}

// Step 2: Backup check
echo "Step 2: Checking for recent backup...\n";
$backup_dir = __DIR__ . '/../backups';
if (is_dir($backup_dir)) {
    echo "✓ Backup directory exists\n";
    echo "  Recommendation: Create a backup before optimization\n";
    echo "  Command: mysqldump -u root -p ictmu6ya_ideanest > backup_before_optimization.sql\n\n";
} else {
    echo "⚠ No backup directory found\n";
    echo "  Recommendation: Create a backup before proceeding\n\n";
}

// Step 3: Check existing tables
echo "Step 3: Checking existing tables...\n";
$required_tables = [
    'admin_approved_projects',
    'blog',
    'bookmark',
    'project_likes',
    'register',
    'user_follows'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "  ✓ $table exists\n";
    } else {
        echo "  ✗ $table missing\n";
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    die("\n✗ Missing required tables. Cannot proceed.\n");
}
echo "\n";

// Step 4: Check current indexes
echo "Step 4: Checking current indexes...\n";
$tables_to_check = ['admin_approved_projects', 'blog', 'bookmark', 'project_likes'];
$total_indexes = 0;

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW INDEX FROM $table");
    if ($result) {
        $count = $result->num_rows;
        $total_indexes += $count;
        echo "  $table: $count indexes\n";
    }
}
echo "  Total indexes: $total_indexes\n\n";

// Step 5: Add indexes safely (only if they don't exist)
echo "Step 5: Adding missing indexes...\n";
$indexes_to_add = [
    // admin_approved_projects
    ["table" => "admin_approved_projects", "name" => "idx_user_id", "sql" => "ALTER TABLE admin_approved_projects ADD INDEX idx_user_id (user_id)"],
    ["table" => "admin_approved_projects", "name" => "idx_status", "sql" => "ALTER TABLE admin_approved_projects ADD INDEX idx_status (status)"],
    ["table" => "admin_approved_projects", "name" => "idx_submission_date", "sql" => "ALTER TABLE admin_approved_projects ADD INDEX idx_submission_date (submission_date)"],
    ["table" => "admin_approved_projects", "name" => "idx_classification", "sql" => "ALTER TABLE admin_approved_projects ADD INDEX idx_classification (classification)"],
    
    // blog
    ["table" => "blog", "name" => "idx_user_id", "sql" => "ALTER TABLE blog ADD INDEX idx_user_id (user_id)"],
    ["table" => "blog", "name" => "idx_status", "sql" => "ALTER TABLE blog ADD INDEX idx_status (status)"],
    ["table" => "blog", "name" => "idx_submission_datetime", "sql" => "ALTER TABLE blog ADD INDEX idx_submission_datetime (submission_datetime)"],
    
    // bookmark
    ["table" => "bookmark", "name" => "idx_user_project", "sql" => "ALTER TABLE bookmark ADD INDEX idx_user_project (user_id, project_id)"],
    
    // project_likes
    ["table" => "project_likes", "name" => "idx_project_user", "sql" => "ALTER TABLE project_likes ADD INDEX idx_project_user (project_id, user_id)"],
    ["table" => "project_likes", "name" => "idx_project_id", "sql" => "ALTER TABLE project_likes ADD INDEX idx_project_id (project_id)"],
];

$added_count = 0;
$skipped_count = 0;
$error_count = 0;

foreach ($indexes_to_add as $index) {
    // Check if index already exists
    $check_sql = "SHOW INDEX FROM {$index['table']} WHERE Key_name = '{$index['name']}'";
    $result = $conn->query($check_sql);
    
    if ($result && $result->num_rows > 0) {
        echo "  - {$index['table']}.{$index['name']} already exists (skipped)\n";
        $skipped_count++;
        continue;
    }
    
    // Try to add index
    try {
        $conn->query($index['sql']);
        echo "  ✓ Added {$index['table']}.{$index['name']}\n";
        $added_count++;
    } catch (Exception $e) {
        echo "  ✗ Failed to add {$index['table']}.{$index['name']}: " . $e->getMessage() . "\n";
        $error_count++;
    }
}

echo "\nSummary: $added_count added, $skipped_count skipped, $error_count errors\n\n";

// Step 6: Create views safely
echo "Step 6: Creating optimized views...\n";

// Check if views already exist
$views_to_create = [
    'v_project_stats' => "CREATE OR REPLACE VIEW v_project_stats AS
        SELECT 
            ap.id,
            ap.project_name,
            ap.user_id,
            ap.status,
            ap.submission_date,
            COALESCE(pl.likes_count, 0) AS likes_count,
            COALESCE(b.bookmark_count, 0) AS bookmark_count
        FROM admin_approved_projects ap
        LEFT JOIN (
            SELECT project_id, COUNT(*) as likes_count 
            FROM project_likes 
            GROUP BY project_id
        ) pl ON ap.id = pl.project_id
        LEFT JOIN (
            SELECT project_id, COUNT(*) as bookmark_count 
            FROM bookmark 
            WHERE project_id > 0
            GROUP BY project_id
        ) b ON ap.id = b.project_id",
    
    'v_user_stats' => "CREATE OR REPLACE VIEW v_user_stats AS
        SELECT 
            r.id,
            r.name,
            r.email,
            COALESCE(p.total_projects, 0) AS total_projects,
            COALESCE(p.approved_projects, 0) AS approved_projects,
            COALESCE(i.total_ideas, 0) AS total_ideas
        FROM register r
        LEFT JOIN (
            SELECT user_id, 
                   COUNT(*) as total_projects,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_projects
            FROM admin_approved_projects
            GROUP BY user_id
        ) p ON r.id = p.user_id
        LEFT JOIN (
            SELECT user_id, COUNT(*) as total_ideas
            FROM blog
            GROUP BY user_id
        ) i ON r.id = i.user_id"
];

$view_count = 0;
foreach ($views_to_create as $view_name => $view_sql) {
    try {
        $conn->query($view_sql);
        echo "  ✓ Created/Updated view: $view_name\n";
        $view_count++;
    } catch (Exception $e) {
        echo "  ✗ Failed to create view $view_name: " . $e->getMessage() . "\n";
    }
}
echo "\nCreated/Updated $view_count views\n\n";

// Step 7: Optimize tables
echo "Step 7: Optimizing tables...\n";
$tables_to_optimize = ['admin_approved_projects', 'blog', 'bookmark', 'project_likes', 'register'];
$optimized_count = 0;

foreach ($tables_to_optimize as $table) {
    try {
        $conn->query("OPTIMIZE TABLE $table");
        echo "  ✓ Optimized: $table\n";
        $optimized_count++;
    } catch (Exception $e) {
        echo "  ⚠ Could not optimize $table: " . $e->getMessage() . "\n";
    }
}
echo "\nOptimized $optimized_count tables\n\n";

// Step 8: Test queries
echo "Step 8: Testing query performance...\n";
$test_queries = [
    "Simple SELECT" => "SELECT * FROM admin_approved_projects WHERE status = 'approved' LIMIT 10",
    "With JOIN" => "SELECT ap.*, r.name FROM admin_approved_projects ap LEFT JOIN register r ON ap.user_id = r.id LIMIT 10",
    "COUNT query" => "SELECT COUNT(*) FROM admin_approved_projects WHERE status = 'approved'",
    "View query" => "SELECT * FROM v_project_stats LIMIT 10",
];

foreach ($test_queries as $name => $query) {
    try {
        $start = microtime(true);
        $result = $conn->query($query);
        $time = round((microtime(true) - $start) * 1000, 2);
        
        $status = $time < 10 ? "✓" : ($time < 50 ? "⚠" : "✗");
        echo "  $status $name: {$time}ms\n";
    } catch (Exception $e) {
        echo "  ✗ $name: ERROR - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Step 9: Final report
echo "=== Optimization Complete ===\n\n";
echo "Summary:\n";
echo "- Indexes added: $added_count\n";
echo "- Views created: $view_count\n";
echo "- Tables optimized: $optimized_count\n\n";

echo "Next Steps:\n";
echo "1. Test your application to ensure everything works\n";
echo "2. Use the optimized query helpers in includes/optimized_queries.php\n";
echo "3. Enable caching with includes/query_cache.php\n";
echo "4. Monitor performance with: php scripts/monitor_performance.php\n\n";

echo "Documentation:\n";
echo "- Quick Reference: QUICK_OPTIMIZATION_REFERENCE.md\n";
echo "- Full Guide: QUERY_OPTIMIZATION_GUIDE.md\n";
echo "- Migration Guide: MIGRATION_GUIDE.md\n\n";

$conn->close();
?>
