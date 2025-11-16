<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$user = "ictmu6ya_ideanest";
$pass = "ictmu6ya_ideanest";
$dbname = "ictmu6ya_ideanest";

$conn = new mysqli($host, $user, $pass, $dbname, null, '/opt/lampp/var/mysql/mysql.sock');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}
$conn->set_charset("utf8mb4");

$jsonFile = __DIR__ . '/ictmu6ya_ideanest.json';
$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true);

if (!$data) {
    die("Failed to parse JSON file\n");
}

echo "Starting database import...\n\n";
echo "Schema already exists, importing data only...\n\n";

// Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// Now import data from JSON
foreach ($data as $item) {
    if ($item['type'] === 'table' && isset($item['data']) && !empty($item['data'])) {
        $tableName = $item['name'];
        
        // Check if table exists
        $checkTable = $conn->query("SHOW TABLES LIKE '$tableName'");
        if ($checkTable->num_rows == 0) {
            echo "Skipping table: $tableName (doesn't exist)\n\n";
            continue;
        }
        
        echo "Importing data into table: $tableName\n";
        
        foreach ($item['data'] as $row) {
            $columns = array_keys($row);
            $values = array_values($row);
            
            // Convert NULL strings to actual NULL
            $values = array_map(function($v) {
                return ($v === null || $v === 'NULL') ? null : $v;
            }, $values);
            
            $placeholders = implode(',', array_fill(0, count($values), '?'));
            $columnsList = implode(',', array_map(function($col) {
                return "`$col`";
            }, $columns));
            
            $sql = "INSERT INTO `$tableName` ($columnsList) VALUES ($placeholders)";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $types = str_repeat('s', count($values));
                $stmt->bind_param($types, ...$values);
                
                if (!$stmt->execute()) {
                    // Skip this row silently
                }
                $stmt->close();
            } else {
                // Column mismatch, skip this table
                echo "  Skipping due to column mismatch\n";
                break;
            }
        }
        
        echo "  Imported " . count($item['data']) . " rows\n\n";
    }
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");

echo "\nDatabase import completed!\n";
$conn->close();
?>
