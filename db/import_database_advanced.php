<?php
/**
 * Advanced Database Import Script
 * Dynamically imports SQL file with progress tracking and detailed logging
 */

// Configuration
$config = [
    'host' => 'localhost',
    'username' => 'ictmu6ya_ideanest',
    'password' => 'ictmu6ya_ideanest',
    'database' => 'ictmu6ya_ideanest',
    'sql_file' => __DIR__ . '/ictmu6ya_ideanest_fixed.sql',
    'drop_existing' => false,
    'log_file' => __DIR__ . '/import_log.txt',
    'show_progress' => true
];

// Start time tracking
$startTime = microtime(true);

// Initialize log
$logContent = "Database Import Log - " . date('Y-m-d H:i:s') . "\n";
$logContent .= str_repeat("=", 80) . "\n\n";

function logMessage($message, $toScreen = true)
{
    global $logContent;
    $logContent .= $message . "\n";
    if ($toScreen) {
        echo $message . "\n";
    }
}

function saveLog()
{
    global $logContent, $config;
    file_put_contents($config['log_file'], $logContent);
}

try {
    // Check if SQL file exists
    if (!file_exists($config['sql_file'])) {
        throw new Exception("SQL file not found: " . $config['sql_file']);
    }

    logMessage("SQL File: " . $config['sql_file']);
    logMessage("File Size: " . number_format(filesize($config['sql_file'])) . " bytes");
    logMessage("");

    // Create connection
    $conn = new mysqli(
        $config['host'],
        $config['username'],
        $config['password']
    );

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    logMessage("✓ Connected to MySQL server");

    // Drop database if requested
    if ($config['drop_existing']) {
        $conn->query("DROP DATABASE IF EXISTS `{$config['database']}`");
        logMessage("✓ Dropped existing database (if existed)");
    }

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS `{$config['database']}` 
            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";

    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }

    logMessage("✓ Database '{$config['database']}' ready");

    // Select database
    if (!$conn->select_db($config['database'])) {
        throw new Exception("Error selecting database: " . $conn->error);
    }

    logMessage("✓ Database selected");
    logMessage("");

    // Read and parse SQL file
    logMessage("Reading SQL file...");
    $sqlContent = file_get_contents($config['sql_file']);

    if ($sqlContent === false) {
        throw new Exception("Unable to read SQL file");
    }

    // Remove comments and split into statements
    $sqlContent = preg_replace('/^--.*$/m', '', $sqlContent);
    $sqlContent = preg_replace('/\/\*.*?\*\//s', '', $sqlContent);

    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $conn->query("SET time_zone = '+00:00'");

    logMessage("✓ SQL settings configured");
    logMessage("");

    // Split by semicolon but handle special cases
    $statements = [];
    $buffer = '';
    $inDelimiter = false;

    foreach (explode("\n", $sqlContent) as $line) {
        $line = trim($line);

        if (empty($line)) {
            continue;
        }

        // Handle DELIMITER changes
        if (stripos($line, 'DELIMITER') === 0) {
            $inDelimiter = !$inDelimiter;
            continue;
        }

        $buffer .= $line . ' ';

        // Check for statement end
        if (!$inDelimiter && substr(rtrim($line), -1) === ';') {
            $statement = trim($buffer);
            if (!empty($statement) && $statement !== ';') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    logMessage("Total statements to execute: " . count($statements));
    logMessage(str_repeat("-", 80));
    logMessage("");

    // Execute statements with progress tracking
    $stats = [
        'total' => count($statements),
        'success' => 0,
        'failed' => 0,
        'tables_created' => 0,
        'views_created' => 0,
        'inserts' => 0,
        'errors' => []
    ];

    foreach ($statements as $index => $statement) {
        $statementType = strtoupper(substr(trim($statement), 0, 6));

        // Execute statement
        if ($conn->multi_query($statement)) {
            // Clear all results
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());

            $stats['success']++;

            // Track statement types
            if (stripos($statement, 'CREATE TABLE') !== false) {
                $stats['tables_created']++;
            } elseif (stripos($statement, 'CREATE VIEW') !== false ||
                stripos($statement, 'CREATE ALGORITHM') !== false) {
                $stats['views_created']++;
            } elseif (stripos($statement, 'INSERT INTO') !== false) {
                $stats['inserts']++;
            }

            // Show progress
            $indexPlusOne = $index + 1;
            $remainder = $indexPlusOne % 50;
            if ($config['show_progress'] && $remainder === 0) {
                $progress = round(($indexPlusOne / $stats['total']) * 100, 1);
                logMessage("Progress: {$progress}% ({$indexPlusOne}/{$stats['total']})");
            }
        } else {
            $stats['failed']++;
            $errorMsg = "Statement " . ($index + 1) . ": " . $conn->error;
            $stats['errors'][] = $errorMsg;

            // Log error with statement preview
            $preview = substr($statement, 0, 150);
            logMessage("ERROR: $preview...", false);
            logMessage("  → " . $conn->error, false);
        }
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    // Get final database statistics
    $result = $conn->query("SHOW TABLES");
    $allTables = [];
    while ($row = $result->fetch_array()) {
        $allTables[] = $row[0];
    }

    // Calculate execution time
    $executionTime = microtime(true) - $startTime;

    // Display final summary
    logMessage("");
    logMessage(str_repeat("=", 80));
    logMessage("IMPORT COMPLETED SUCCESSFULLY");
    logMessage(str_repeat("=", 80));
    logMessage("");
    logMessage("Execution Statistics:");
    logMessage("  • Total Statements: " . $stats['total']);
    logMessage("  • Successful: " . $stats['success']);
    logMessage("  • Failed: " . $stats['failed']);
    logMessage("  • Tables Created: " . $stats['tables_created']);
    logMessage("  • Views Created: " . $stats['views_created']);
    logMessage("  • Insert Statements: " . $stats['inserts']);
    logMessage("");
    logMessage("Database Statistics:");
    logMessage("  • Total Objects: " . count($allTables));
    logMessage("  • Execution Time: " . round($executionTime, 2) . " seconds");
    logMessage("");

    if ($stats['failed'] > 0) {
        logMessage("Errors Encountered:");
        foreach ($stats['errors'] as $error) {
            logMessage("  • $error");
        }
        logMessage("");
    }

    logMessage("All Tables and Views:");
    foreach ($allTables as $table) {
        logMessage("  • $table");
    }

    logMessage("");
    logMessage(str_repeat("=", 80));
    logMessage("Log saved to: " . $config['log_file']);
    logMessage(str_repeat("=", 80));

    // Save log
    saveLog();

    // Close connection
    $conn->close();

    exit(0);
} catch (Exception $e) {
    logMessage("");
    logMessage("FATAL ERROR: " . $e->getMessage());
    logMessage("");
    saveLog();
    exit(1);
}
