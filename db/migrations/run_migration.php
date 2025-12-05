<?php
/**
 * Migration Runner Script
 * Run this script to create the missing user_follows table
 */

require_once __DIR__ . '/../../Login/Login/db.php';

echo "Starting migration...\n";
echo "Creating user_follows table...\n";

$sql = file_get_contents(__DIR__ . '/create_user_follows_table.sql');

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    
    echo "✓ Migration completed successfully!\n";
    echo "✓ user_follows table created.\n";
} else {
    echo "✗ Migration failed: " . $conn->error . "\n";
    exit(1);
}

// Verify the table was created
$check_sql = "SHOW TABLES LIKE 'user_follows'";
$result = $conn->query($check_sql);

if ($result && $result->num_rows > 0) {
    echo "✓ Verified: user_follows table exists in database.\n";
} else {
    echo "✗ Warning: Could not verify table creation.\n";
}

$conn->close();
echo "\nMigration complete!\n";
