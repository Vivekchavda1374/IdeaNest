<?php
/**
 * Database Import Script
 * Dynamically imports SQL file and creates all tables, views, and data
 */

// Database configuration
$host = "localhost";
$user = "ictmu6ya_ideanest";
$pass = "ictmu6ya_ideanest";
$dbname = "ictmu6ya_ideanest";


// SQL file path
$sqlFile = __DIR__ . '/ictmu6ya_ideanest.sql';

// Check if SQL file exists
if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at: $sqlFile\n");
}

try {
    // Create database connection
    $conn = new mysqli($host, $user, $pass);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    
    echo "Connected to MySQL server successfully.\n";
    
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if ($conn->query($sql) === TRUE) {
        echo "Database '$dbname' created or already exists.\n";
    } else {
        die("Error creating database: " . $conn->error . "\n");
    }
    
    // Select database
    $conn->select_db($dbname);
    echo "Database '$dbname' selected.\n\n";
    
    // Read SQL file
    $sqlContent = file_get_contents($sqlFile);
    
    if ($sqlContent === false) {
        die("Error: Unable to read SQL file.\n");
    }
    
    echo "SQL file loaded successfully.\n";
    echo "File size: " . strlen($sqlContent) . " bytes\n\n";
    
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Split SQL file into individual statements
    $statements = [];
    $delimiter = ';';
    $tempStatement = '';
    $lines = explode("\n", $sqlContent);
    
    foreach ($lines as $line) {
        // Skip comments and empty lines
        $line = trim($line);
        if (empty($line) || substr($line, 0, 2) === '--' || substr($line, 0, 2) === '/*' && substr($line, -2) === '*/') {
            continue;
        }
        
        // Check for DELIMITER command
        if (stripos($line, 'DELIMITER') === 0) {
            $delimiter = trim(substr($line, 9));
            continue;
        }
        
        $tempStatement .= $line . "\n";
        
        // Check if statement is complete
        if (substr(rtrim($line), -strlen($delimiter)) === $delimiter) {
            $statement = trim(substr($tempStatement, 0, -strlen($delimiter)));
            if (!empty($statement)) {
                $statements[] = $statement;
            }
            $tempStatement = '';
        }
    }
    
    // Add last statement if exists
    if (!empty(trim($tempStatement))) {
        $statements[] = trim($tempStatement);
    }
    
    echo "Total SQL statements to execute: " . count($statements) . "\n\n";
    
    // Execute statements
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($statements as $index => $statement) {
        // Skip empty statements
        if (empty(trim($statement))) {
            continue;
        }
        
        // Execute statement
        if ($conn->multi_query($statement)) {
            do {
                // Store first result set
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            $successCount++;
            
            // Show progress for every 10 statements
            if (($index + 1) % 10 === 0) {
                echo "Processed " . ($index + 1) . " statements...\n";
            }
        } else {
            $errorCount++;
            $errorMsg = "Error in statement " . ($index + 1) . ": " . $conn->error;
            $errors[] = $errorMsg;
            
            // Show first 100 characters of failed statement
            $preview = substr($statement, 0, 100);
            echo "FAILED: $preview...\n";
            echo "Error: " . $conn->error . "\n\n";
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Display summary
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "IMPORT SUMMARY\n";
    echo str_repeat("=", 60) . "\n";
    echo "Total statements: " . count($statements) . "\n";
    echo "Successful: $successCount\n";
    echo "Failed: $errorCount\n";
    
    if ($errorCount > 0) {
        echo "\nErrors encountered:\n";
        foreach ($errors as $error) {
            echo "- $error\n";
        }
    }
    
    // Get table count
    $result = $conn->query("SHOW TABLES");
    $tableCount = $result->num_rows;
    echo "\nTotal tables created: $tableCount\n";
    
    // List all tables
    echo "\nTables in database:\n";
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Database import completed!\n";
    echo str_repeat("=", 60) . "\n";
    
    // Close connection
    $conn->close();
    
} catch (Exception $e) {
    die("Exception: " . $e->getMessage() . "\n");
}
?>
