<?php
require_once 'Login/Login/db.php';

$sql = "ALTER TABLE mentoring_sessions ADD COLUMN meeting_link VARCHAR(500) DEFAULT NULL AFTER notes";

if ($conn->query($sql)) {
    echo "Meeting link column added successfully";
} else {
    echo "Error: " . $conn->error;
}
?>