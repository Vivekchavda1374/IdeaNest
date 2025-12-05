<?php
/**
 * Comprehensive Query Optimization Script
 * This script analyzes and optimizes database queries across the entire project
 */

require_once __DIR__ . '/../Login/Login/db.php';

echo "=== IdeaNest Query Optimization Tool ===\n\n";

// Track optimization results
$optimizations = [];
$errors = [];

/**
 * Add missing indexes for better query performance
 */
function addIndexes($conn, &$optimizations, &$errors) {
    echo "Step 1: Adding Missing Indexes...\n";
    
    $indexes = [
        // admin_approved_projects table
        "ALTER TABLE admin_approved_projects ADD INDEX idx_user_id (user_id)",
        "ALTER TABLE admin_approved_projects ADD INDEX idx_status (status)",
        "ALTER TABLE admin_approved_projects ADD INDEX idx_submission_date (submission_date)",
        "ALTER TABLE admin_approved_projects ADD INDEX idx_classification (classification)",
        "ALTER TABLE admin_approved_projects ADD INDEX idx_project_type (project_type)",
        "ALTER TABLE admin_approved_projects ADD INDEX idx_language (language)",
        "ALTER TABLE admin_approved_projects ADD INDEX idx_difficulty_level (difficulty_level)",
        "ALTER TABLE admin_approved_projects ADD INDEX idx_user_status (user_id, status)",
        "ALTER TABLE admin_approved_projects ADD INDEX idx_status_date (status, submission_date)",
        
        // blog table
        "ALTER TABLE blog ADD INDEX idx_user_id (user_id)",
        "ALTER TABLE blog ADD INDEX idx_status (status)",
        "ALTER TABLE blog ADD INDEX idx_submission_datetime (submission_datetime)",
        "ALTER TABLE blog ADD INDEX idx_classification (classification)",
        
        // bookmark table
        "ALTER TABLE bookmark ADD INDEX idx_user_project (user_id, project_id)",
        "ALTER TABLE bookmark ADD INDEX idx_user_idea (user_id, idea_id)",
        
        // project_likes table
        "ALTER TABLE project_likes ADD INDEX idx_project_user (project_id, user_id)",
        "ALTER TABLE project_likes ADD INDEX idx_project_id (project_id)",
        
        // idea_likes table  
        "ALTER TABLE idea_likes ADD INDEX idx_idea_user (idea_id, user_id)",
        "ALTER TABLE idea_likes ADD INDEX idx_idea_id (idea_id)",
        
        // idea_comments table
        "ALTER TABLE idea_comments ADD INDEX idx_idea_id (idea_id)",
        "ALTER TABLE idea_comments ADD INDEX idx_user_id (user_id)",
        
        // idea_views table
        "ALTER TABLE idea_views ADD INDEX idx_idea_id (idea_id)",
        
        // user_follows table
        "ALTER TABLE user_follows ADD INDEX idx_follower (follower_id)",
        "ALTER TABLE user_follows ADD INDEX idx_following (following_id)",
        "ALTER TABLE user_follows ADD INDEX idx_created_at (created_at)",
        
        // register table
        "ALTER TABLE register ADD INDEX idx_email (email)",
        "ALTER TABLE register ADD INDEX idx_github_username (github_username)",
        "ALTER TABLE register ADD INDEX idx_department (department)",
        
        // temp_project_ownership table
        "ALTER TABLE temp_project_ownership ADD INDEX idx_session (user_session)",
        "ALTER TABLE temp_project_ownership ADD INDEX idx_project (project_id)",
        
        // message_requests table
        "ALTER TABLE message_requests ADD INDEX idx_sender_receiver (sender_id, receiver_id)",
        "ALTER TABLE message_requests ADD INDEX idx_status (status)",
        
        // conversations table
        "ALTER TABLE conversations ADD INDEX idx_user1_user2 (user1_id, user2_id)",
        "ALTER TABLE conversations ADD INDEX idx_last_message (last_message_at)",
        
        // messages table
        "ALTER TABLE messages ADD INDEX idx_conversation (conversation_id)",
        "ALTER TABLE messages ADD INDEX idx_sender (sender_id)",
        "ALTER TABLE messages ADD INDEX idx_created_at (created_at)",
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $conn->query($index_sql);
            $optimizations[] = "✓ " . substr($index_sql, 0, 80) . "...";
            echo "  ✓ Added index\n";
        } catch (Exception $e) {
            // Index might already exist, which is fine
            if (strpos($e->getMessage(), 'Duplicate key name') === false && 
                strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = "✗ " . $e->getMessage();
                echo "  ✗ Error: " . $e->getMessage() . "\n";
            } else {
                echo "  - Index already exists (skipped)\n";
            }
        }
    }
    
    echo "\n";
}

/**
 * Create optimized views for frequently used queries
 */
function createOptimizedViews($conn, &$optimizations, &$errors) {
    echo "Step 2: Creating Optimized Views...\n";
    
    // Drop existing views first
    $drop_views = [
        "DROP VIEW IF EXISTS v_project_stats",
        "DROP VIEW IF EXISTS v_user_stats",
        "DROP VIEW IF EXISTS v_idea_stats",
    ];
    
    foreach ($drop_views as $drop_sql) {
        try {
            $conn->query($drop_sql);
        } catch (Exception $e) {
            // Ignore errors when dropping non-existent views
        }
    }
    
    $views = [
        // Project statistics view
        "CREATE VIEW v_project_stats AS
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
            GROUP BY project_id
        ) b ON ap.id = b.project_id",
        
        // User statistics view
        "CREATE VIEW v_user_stats AS
        SELECT 
            r.id,
            r.name,
            r.email,
            COALESCE(p.total_projects, 0) AS total_projects,
            COALESCE(p.approved_projects, 0) AS approved_projects,
            COALESCE(i.total_ideas, 0) AS total_ideas,
            COALESCE(f.followers_count, 0) AS followers_count,
            COALESCE(f.following_count, 0) AS following_count
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
        ) i ON r.id = i.user_id
        LEFT JOIN user_follow_stats f ON r.id = f.user_id",
        
        // Idea statistics view
        "CREATE VIEW v_idea_stats AS
        SELECT 
            b.id,
            b.project_name,
            b.user_id,
            b.status,
            b.submission_datetime,
            COALESCE(il.likes_count, 0) AS likes_count,
            COALESCE(ic.comments_count, 0) AS comments_count,
            COALESCE(iv.views_count, 0) AS views_count
        FROM blog b
        LEFT JOIN (
            SELECT idea_id, COUNT(*) as likes_count 
            FROM idea_likes 
            GROUP BY idea_id
        ) il ON b.id = il.idea_id
        LEFT JOIN (
            SELECT idea_id, COUNT(*) as comments_count 
            FROM idea_comments 
            GROUP BY idea_id
        ) ic ON b.id = ic.idea_id
        LEFT JOIN (
            SELECT idea_id, COUNT(*) as views_count 
            FROM idea_views 
            GROUP BY idea_id
        ) iv ON b.id = iv.idea_id",
    ];
    
    foreach ($views as $view_sql) {
        try {
            $conn->query($view_sql);
            $optimizations[] = "✓ Created view";
            echo "  ✓ Created view\n";
        } catch (Exception $e) {
            $errors[] = "✗ View creation error: " . $e->getMessage();
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

/**
 * Optimize table structure
 */
function optimizeTables($conn, &$optimizations, &$errors) {
    echo "Step 3: Optimizing Table Structure...\n";
    
    $tables = [
        'admin_approved_projects',
        'blog',
        'bookmark',
        'project_likes',
        'idea_likes',
        'idea_comments',
        'idea_views',
        'user_follows',
        'register',
        'temp_project_ownership',
        'message_requests',
        'conversations',
        'messages',
    ];
    
    foreach ($tables as $table) {
        try {
            $conn->query("OPTIMIZE TABLE $table");
            $optimizations[] = "✓ Optimized table: $table";
            echo "  ✓ Optimized: $table\n";
        } catch (Exception $e) {
            $errors[] = "✗ Table optimization error for $table: " . $e->getMessage();
            echo "  ✗ Error optimizing $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

/**
 * Analyze query performance
 */
function analyzeQueryPerformance($conn) {
    echo "Step 4: Analyzing Query Performance...\n";
    
    // Enable query profiling
    $conn->query("SET profiling = 1");
    
    // Test common queries
    $test_queries = [
        "SELECT * FROM admin_approved_projects WHERE status = 'approved' ORDER BY submission_date DESC LIMIT 10",
        "SELECT * FROM blog WHERE status = 'pending' ORDER BY submission_datetime DESC LIMIT 10",
        "SELECT COUNT(*) FROM project_likes WHERE project_id = 1",
        "SELECT * FROM register WHERE email = 'test@example.com'",
    ];
    
    foreach ($test_queries as $query) {
        try {
            $start = microtime(true);
            $conn->query($query);
            $end = microtime(true);
            $time = round(($end - $start) * 1000, 2);
            echo "  Query time: {$time}ms - " . substr($query, 0, 60) . "...\n";
        } catch (Exception $e) {
            echo "  ✗ Query error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

/**
 * Add query caching configuration
 */
function configureQueryCache($conn, &$optimizations, &$errors) {
    echo "Step 5: Configuring Query Cache...\n";
    
    try {
        // Check if query cache is available
        $result = $conn->query("SHOW VARIABLES LIKE 'have_query_cache'");
        if ($result && $row = $result->fetch_assoc()) {
            if ($row['Value'] === 'YES') {
                $optimizations[] = "✓ Query cache is available";
                echo "  ✓ Query cache is available\n";
            } else {
                echo "  - Query cache not available on this MySQL version\n";
            }
        }
    } catch (Exception $e) {
        echo "  - Query cache check skipped (MySQL 8.0+ doesn't support query cache)\n";
    }
    
    echo "\n";
}

/**
 * Generate optimization report
 */
function generateReport($optimizations, $errors) {
    echo "=== Optimization Report ===\n\n";
    
    echo "Successful Optimizations: " . count($optimizations) . "\n";
    if (!empty($optimizations)) {
        foreach (array_slice($optimizations, 0, 10) as $opt) {
            echo "  $opt\n";
        }
        if (count($optimizations) > 10) {
            echo "  ... and " . (count($optimizations) - 10) . " more\n";
        }
    }
    
    echo "\nErrors: " . count($errors) . "\n";
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "  $error\n";
        }
    }
    
    echo "\n=== Recommendations ===\n";
    echo "1. Use prepared statements for all queries (prevents SQL injection)\n";
    echo "2. Add LIMIT clauses to queries that don't need all results\n";
    echo "3. Use indexes on columns used in WHERE, JOIN, and ORDER BY clauses\n";
    echo "4. Avoid SELECT * - specify only needed columns\n";
    echo "5. Use views for complex, frequently-used queries\n";
    echo "6. Consider caching results in application layer for expensive queries\n";
    echo "7. Use EXPLAIN to analyze slow queries\n";
    echo "8. Regularly run OPTIMIZE TABLE on frequently updated tables\n";
    
    echo "\n=== Next Steps ===\n";
    echo "1. Update application code to use the new views\n";
    echo "2. Monitor query performance using slow query log\n";
    echo "3. Run this script periodically to maintain optimization\n";
    echo "4. Consider implementing Redis/Memcached for caching\n";
}

// Run all optimization steps
try {
    addIndexes($conn, $optimizations, $errors);
    createOptimizedViews($conn, $optimizations, $errors);
    optimizeTables($conn, $optimizations, $errors);
    analyzeQueryPerformance($conn);
    configureQueryCache($conn, $optimizations, $errors);
    generateReport($optimizations, $errors);
    
    echo "\n✓ Optimization complete!\n";
    
} catch (Exception $e) {
    echo "\n✗ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
