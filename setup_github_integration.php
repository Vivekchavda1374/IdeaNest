<?php
// GitHub Integration Setup Script
include 'Login/Login/db.php';

echo "<h2>Setting up GitHub Integration...</h2>";

try {
    // Read and execute the SQL file
    $sql_content = file_get_contents('github_integration_update.sql');
    
    if ($sql_content === false) {
        throw new Exception("Could not read SQL file");
    }
    
    // Split SQL commands by semicolon and execute each one
    $sql_commands = array_filter(array_map('trim', explode(';', $sql_content)));
    
    foreach ($sql_commands as $command) {
        if (!empty($command)) {
            if ($conn->query($command) === TRUE) {
                echo "<p style='color: green;'>✓ Executed: " . substr($command, 0, 50) . "...</p>";
            } else {
                // Check if error is about column already existing
                if (strpos($conn->error, 'Duplicate column name') !== false) {
                    echo "<p style='color: orange;'>⚠ Column already exists: " . substr($command, 0, 50) . "...</p>";
                } else {
                    echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
                    echo "<p>Command: " . $command . "</p>";
                }
            }
        }
    }
    
    echo "<h3 style='color: green;'>GitHub Integration setup completed!</h3>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Go to <a href='user/user_profile_setting.php'>Profile Settings</a> to connect your GitHub account</li>";
    echo "<li>View your <a href='user/github_profile.php'>GitHub Profile</a> page</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>GitHub Integration Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2 { color: #333; }
        p { margin: 10px 0; }
        ul { margin: 20px 0; }
        a { color: #0366d6; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
</body>
</html>