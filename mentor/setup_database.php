<?php
require_once '../Login/Login/db.php';

// Execute mentor setup SQL
$sql = file_get_contents('../mentor_setup.sql');
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if ($conn->query($query)) {
            echo "✓ Executed: " . substr($query, 0, 50) . "...\n<br>";
        } else {
            echo "✗ Error: " . $conn->error . "\n<br>";
        }
    }
}

echo "<br>Database setup complete!";
?>