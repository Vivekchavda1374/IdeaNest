<?php
session_start();
$_SESSION['user_id'] = 1; // Set test user ID

include 'Login/Login/db.php';

// Test database connection
echo "<h2>Database Connection Test</h2>";
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
} else {
    echo "Connected successfully<br>";
}

// Check if GitHub columns exist
echo "<h2>GitHub Columns Test</h2>";
$result = $conn->query("DESCRIBE register");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

$github_columns = ['github_username', 'github_profile_url', 'github_repos_count'];
foreach ($github_columns as $col) {
    if (in_array($col, $columns)) {
        echo "✓ Column $col exists<br>";
    } else {
        echo "✗ Column $col missing<br>";
    }
}

// Check if GitHub repos table exists
echo "<h2>GitHub Repos Table Test</h2>";
$result = $conn->query("SHOW TABLES LIKE 'user_github_repos'");
if ($result->num_rows > 0) {
    echo "✓ user_github_repos table exists<br>";
} else {
    echo "✗ user_github_repos table missing<br>";
}

echo "<h2>GitHub Profile Page Test</h2>";
echo "<a href='user/github_profile.php'>Test GitHub Profile Page</a>";
?>