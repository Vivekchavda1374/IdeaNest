<?php
/**
 * IdeaNest Database Import Tool
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

$config = [
    'host' => 'localhost',
    'username' => 'ictmu6ya_ideanest',
    'password' => 'ictmu6ya_ideanest',
    'database' => 'ictmu6ya_ideanest',
    'sql_file' => __DIR__ . '/ictmu6ya_ideanest.sql'
];

$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Database Import</title>';
    echo '<style>body{font-family:monospace;background:#f8fafc;padding:20px;}';
    echo '.header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:30px;border-radius:10px;margin-bottom:20px;}';
    echo '.content{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}';
    echo '.success{color:#10b981;}.error{color:#ef4444;}.info{color:#667eea;}.warning{color:#f59e0b;}';
    echo 'pre{background:#1e293b;color:#e2e8f0;padding:20px;border-radius:8px;overflow-x:auto;}</style></head><body>';
    echo '<div class="header"><h1>🗄️ Database Import</h1></div><div class="content"><pre>';
}

function out($msg, $type = 'info') {
    global $isCLI;
    if ($isCLI) {
        echo $msg . "\n";
    } else {
        echo "<span class='$type'>$msg</span>\n";
    }
    flush();
}

try {
    $start = microtime(true);
    
    out("Database Import Started", 'info');
    out("", 'info');
    
    if (!file_exists($config['sql_file'])) {
        throw new Exception("SQL file not found: {$config['sql_file']}");
    }
    out("✓ SQL file found", 'success');
    
    $conn = new mysqli($config['host'], $config['username'], $config['password']);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    out("✓ Connected to MySQL", 'success');
    
    $conn->query("CREATE DATABASE IF NOT EXISTS `{$config['database']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->select_db($config['database']);
    out("✓ Database ready", 'success');
    out("", 'info');
    
    // Drop all existing views and tables
    out("Dropping existing objects...", 'info');
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop views
    $result = $conn->query("SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = '{$config['database']}'");
    $droppedViews = 0;
    if ($result) {
        while ($row = $result->fetch_array()) {
            $conn->query("DROP VIEW IF EXISTS `{$row[0]}`");
            $droppedViews++;
        }
    }
    
    // Drop tables
    $result = $conn->query("SHOW TABLES");
    $droppedTables = 0;
    while ($row = $result->fetch_array()) {
        $conn->query("DROP TABLE IF EXISTS `{$row[0]}`");
        $droppedTables++;
    }
    
    out("✓ Dropped $droppedTables tables and $droppedViews views", 'success');
    out("", 'info');
    
    $sqlContent = file_get_contents($config['sql_file']);
    out("Reading SQL file...", 'info');
    
    $sqlContent = preg_replace('/DEFINER\s*=\s*`[^`]+`@`[^`]+`\s*/i', '', $sqlContent);
    $sqlContent = preg_replace('/ALGORITHM\s*=\s*UNDEFINED\s*/i', '', $sqlContent);
    
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    
    out("Importing entire SQL file...", 'info');
    
    // Use multi_query to import entire file at once
    if ($conn->multi_query($sqlContent)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }
    
    if ($conn->error) {
        out("Warning: " . $conn->error, 'warning');
    }
    
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    $result = $conn->query("SHOW TABLES");
    $tableCount = $result->num_rows;
    
    // Count views
    $result = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.VIEWS WHERE TABLE_SCHEMA = '{$config['database']}'");
    $viewCount = $result->fetch_assoc()['cnt'];
    
    $time = round(microtime(true) - $start, 2);
    
    out("", 'info');
    out("═══════════════════════════════════", 'success');
    out("IMPORT COMPLETED", 'success');
    out("═══════════════════════════════════", 'success');
    out("Tables Created: $tableCount", 'info');
    out("Views Created: $viewCount", 'info');
    out("Execution Time: {$time}s", 'info');
    out("", 'info');
    
    out("Database Objects:", 'info');
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        out("  • {$row[0]}", 'info');
    }
    
    $conn->close();
    
} catch (Exception $e) {
    out("", 'error');
    out("ERROR: " . $e->getMessage(), 'error');
    exit(1);
}

if (!$isCLI) {
    echo '</pre></div></body></html>';
}
?>
