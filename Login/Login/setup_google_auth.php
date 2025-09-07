<?php
include 'db.php';

// Check if google_id column exists
$check_google_id = "SHOW COLUMNS FROM register LIKE 'google_id'";
$result = $conn->query($check_google_id);
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE register ADD COLUMN google_id VARCHAR(255) NULL";
    if ($conn->query($sql) === TRUE) {
        echo "Google ID column added successfully<br>";
    } else {
        echo "Error adding google_id column: " . $conn->error . "<br>";
    }
} else {
    echo "Google ID column already exists<br>";
}

// Check if profile_complete column exists
$check_profile = "SHOW COLUMNS FROM register LIKE 'profile_complete'";
$result2 = $conn->query($check_profile);
if ($result2->num_rows == 0) {
    $sql2 = "ALTER TABLE register ADD COLUMN profile_complete TINYINT(1) DEFAULT 1";
    if ($conn->query($sql2) === TRUE) {
        echo "Profile complete column added successfully<br>";
    } else {
        echo "Error adding profile_complete column: " . $conn->error . "<br>";
    }
} else {
    echo "Profile complete column already exists<br>";
}

echo "<br>Google authentication setup complete!<br>";
echo "<a href='login.php'>Go to Login Page</a>";

$conn->close();
?>