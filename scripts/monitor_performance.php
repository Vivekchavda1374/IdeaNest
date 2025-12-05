<?php
/**
 * Performance Monitoring Script
 * Monitors database query performance and provides recommendations
 */

require_once __DIR__ . '/../Login/Login/db.php';
require_once __DIR__ . '/../includes/query_cache.php';

echo "=== IdeaNest Performance Monitor ===\n\n";

/**
 * Check database connection performance
 */
function checkConnectionPerformance($conn) {
    echo "1. Database Connection Performance\n";
    echo str_repeat("-", 50) . "\n";
    
    $start = microtime(true);
    $result = $conn->query("SELECT 1");
    $end = microtime(true);
    $ping_time = round(($end - $start) * 1000, 2);
    
    echo "  Ping time: {$ping_time}ms\n";
    
    if ($ping_time < 1) {
        echo "  Status: ✓ Excellent\n";
    } elseif ($ping_time < 5) {
        echo "  Status: ✓ Good\n";
    } elseif ($ping_time < 10) {
        echo "  Status: ⚠ Fair (consider optimizing connection)\n";
    } else {
        echo "  Status: ✗ Poor (check network/database server)\n";
    }
    
    echo "\n";
}

/**
 * Check table sizes
 */
function checkTableSizes($conn) {
    echo "2. Table Sizes\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql = "SELECT 
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                table_rows
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
            LIMIT 10";
    
    $result = $conn->query($sql);
    
    if ($result) {
        printf("  %-30s %15s %15s\n", "Table", "Size (MB)", "Rows");
        printf("  %s\n", str_repeat("-", 62));
        
        while ($row = $result->fetch_assoc()) {
            printf("  %-30s %15s %15s\n", 
                $row['table_name'], 
                $row['size_mb'], 
                number_format($row['table_rows'])
            );
        }
    }
    
    echo "\n";
}

/**
 * Check index usage
 */
function checkIndexUsage($conn) {
    echo "3. Index Usage\n";
    echo str_repeat("-", 50) . "\n";
    
    $tables = [
        'admin_approved_projects',
        'blog',
        'bookmark',
        'project_likes',
        'user_follows',
        'register'
    ];
    
    foreach ($tables as $table) {
        $sql = "SHOW INDEX FROM $table";
        $result = $conn->query($sql);
        
        if ($result) {
            $index_count = $result->num_rows;
            echo "  $table: $index_count indexes\n";
        }
    }
    
    echo "\n";
}

/**
 * Test common query performance
 */
function testQueryPerformance($conn) {
    echo "4. Common Query Performance\n";
    echo str_repeat("-", 50) . "\n";
    
    $queries = [
        "Get approved projects" => "SELECT * FROM admin_approved_projects WHERE status = 'approved' ORDER BY submission_date DESC LIMIT 10",
        "Get user projects" => "SELECT * FROM admin_approved_projects WHERE user_id = 1 LIMIT 10",
        "Get project with stats" => "SELECT * FROM v_project_stats WHERE id = 1",
        "Get user stats" => "SELECT * FROM v_user_stats WHERE id = 1",
        "Count total projects" => "SELECT COUNT(*) FROM admin_approved_projects WHERE status = 'approved'",
        "Get recent ideas" => "SELECT * FROM blog ORDER BY submission_datetime DESC LIMIT 10",
    ];
    
    foreach ($queries as $name => $query) {
        try {
            $start = microtime(true);
            $result = $conn->query($query);
            $end = microtime(true);
            $time = round(($end - $start) * 1000, 2);
            
            $status = $time < 10 ? "✓" : ($time < 50 ? "⚠" : "✗");
            echo "  $status $name: {$time}ms\n";
            
        } catch (Exception $e) {
            echo "  ✗ $name: ERROR - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

/**
 * Check cache performance
 */
function checkCachePerformance() {
    global $query_cache;
    
    echo "5. Cache Performance\n";
    echo str_repeat("-", 50) . "\n";
    
    $stats = $query_cache->getStats();
    
    echo "  Total entries: " . $stats['total_entries'] . "\n";
    echo "  Active entries: " . $stats['active_entries'] . "\n";
    echo "  Expired entries: " . $stats['expired_entries'] . "\n";
    echo "  Total size: " . $stats['total_size_mb'] . " MB\n";
    
    if ($stats['expired_entries'] > 0) {
        echo "  ⚠ Recommendation: Clear expired cache\n";
        echo "    Run: php -r \"require 'includes/query_cache.php'; \\\$query_cache->clearExpired();\"\n";
    }
    
    if ($stats['total_size_mb'] > 100) {
        echo "  ⚠ Warning: Cache size is large (> 100MB)\n";
        echo "    Consider reducing cache TTL or clearing old entries\n";
    }
    
    echo "\n";
}

/**
 * Check for missing indexes
 */
function checkMissingIndexes($conn) {
    echo "6. Missing Index Analysis\n";
    echo str_repeat("-", 50) . "\n";
    
    // Check for tables without indexes on foreign keys
    $tables_to_check = [
        'admin_approved_projects' => ['user_id', 'status'],
        'blog' => ['user_id', 'status'],
        'bookmark' => ['user_id', 'project_id'],
        'project_likes' => ['project_id', 'user_id'],
        'user_follows' => ['follower_id', 'following_id'],
    ];
    
    $missing_indexes = [];
    
    foreach ($tables_to_check as $table => $columns) {
        $sql = "SHOW INDEX FROM $table";
        $result = $conn->query($sql);
        
        if ($result) {
            $existing_indexes = [];
            while ($row = $result->fetch_assoc()) {
                $existing_indexes[] = $row['Column_name'];
            }
            
            foreach ($columns as $column) {
                if (!in_array($column, $existing_indexes)) {
                    $missing_indexes[] = "$table.$column";
                }
            }
        }
    }
    
    if (empty($missing_indexes)) {
        echo "  ✓ All recommended indexes are present\n";
    } else {
        echo "  ⚠ Missing indexes detected:\n";
        foreach ($missing_indexes as $index) {
            echo "    - $index\n";
        }
        echo "\n  Run optimization script to add missing indexes:\n";
        echo "    php scripts/query_optimization.php\n";
    }
    
    echo "\n";
}

/**
 * Generate recommendations
 */
function generateRecommendations($conn) {
    echo "7. Optimization Recommendations\n";
    echo str_repeat("-", 50) . "\n";
    
    $recommendations = [];
    
    // Check table sizes
    $sql = "SELECT 
                table_name,
                table_rows
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            ORDER BY table_rows DESC
            LIMIT 5";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['table_rows'] > 10000) {
                $recommendations[] = "Consider partitioning large table: {$row['table_name']} ({$row['table_rows']} rows)";
            }
        }
    }
    
    // Check for views
    $sql = "SELECT COUNT(*) as count FROM information_schema.VIEWS WHERE table_schema = DATABASE()";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['count'] < 3) {
            $recommendations[] = "Create more views for frequently used complex queries";
        }
    }
    
    // Check cache
    global $query_cache;
    $stats = $query_cache->getStats();
    if ($stats['total_entries'] == 0) {
        $recommendations[] = "Enable query caching for better performance";
    }
    
    if (empty($recommendations)) {
        echo "  ✓ No immediate recommendations\n";
    } else {
        foreach ($recommendations as $i => $rec) {
            echo "  " . ($i + 1) . ". $rec\n";
        }
    }
    
    echo "\n";
}

/**
 * Performance score
 */
function calculatePerformanceScore($conn) {
    echo "8. Overall Performance Score\n";
    echo str_repeat("-", 50) . "\n";
    
    $score = 100;
    $issues = [];
    
    // Test connection
    $start = microtime(true);
    $conn->query("SELECT 1");
    $ping_time = (microtime(true) - $start) * 1000;
    
    if ($ping_time > 10) {
        $score -= 20;
        $issues[] = "Slow database connection";
    } elseif ($ping_time > 5) {
        $score -= 10;
        $issues[] = "Fair database connection speed";
    }
    
    // Check cache
    global $query_cache;
    $stats = $query_cache->getStats();
    if ($stats['expired_entries'] > 10) {
        $score -= 10;
        $issues[] = "Many expired cache entries";
    }
    
    // Check views
    $sql = "SELECT COUNT(*) as count FROM information_schema.VIEWS WHERE table_schema = DATABASE()";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['count'] < 3) {
            $score -= 15;
            $issues[] = "Not using optimized views";
        }
    }
    
    echo "  Score: $score/100\n\n";
    
    if ($score >= 90) {
        echo "  Status: ✓ Excellent - Your database is well optimized!\n";
    } elseif ($score >= 70) {
        echo "  Status: ✓ Good - Minor optimizations recommended\n";
    } elseif ($score >= 50) {
        echo "  Status: ⚠ Fair - Several optimizations needed\n";
    } else {
        echo "  Status: ✗ Poor - Immediate optimization required\n";
    }
    
    if (!empty($issues)) {
        echo "\n  Issues found:\n";
        foreach ($issues as $issue) {
            echo "    - $issue\n";
        }
    }
    
    echo "\n";
}

// Run all checks
try {
    checkConnectionPerformance($conn);
    checkTableSizes($conn);
    checkIndexUsage($conn);
    testQueryPerformance($conn);
    checkCachePerformance();
    checkMissingIndexes($conn);
    generateRecommendations($conn);
    calculatePerformanceScore($conn);
    
    echo "=== Monitoring Complete ===\n";
    echo "\nFor detailed optimization, run:\n";
    echo "  php scripts/query_optimization.php\n";
    
} catch (Exception $e) {
    echo "\n✗ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>