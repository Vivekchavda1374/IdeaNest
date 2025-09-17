<?php
require_once 'Login/Login/db.php';

echo "Setting up Mentor Request System...\n";

// Read and execute the SQL file
$sql_content = file_get_contents('setup_mentor_request_system.sql');
$queries = explode(';', $sql_content);

$success_count = 0;
$error_count = 0;

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if ($conn->query($query)) {
        $success_count++;
        echo "✓ Query executed successfully\n";
    } else {
        $error_count++;
        echo "✗ Error: " . $conn->error . "\n";
        echo "Query: " . substr($query, 0, 100) . "...\n";
    }
}

echo "\n=== Setup Complete ===\n";
echo "Successful queries: $success_count\n";
echo "Failed queries: $error_count\n";

if ($error_count == 0) {
    echo "\n🎉 Mentor Request System setup completed successfully!\n";
    echo "\nFeatures added:\n";
    echo "- Students can browse and request mentors\n";
    echo "- Mentors can accept/reject student requests\n";
    echo "- Project access control based on mentor approval\n";
    echo "- Request status tracking for students\n";
} else {
    echo "\n⚠️  Some errors occurred during setup. Please check the database manually.\n";
}

$conn->close();
?>