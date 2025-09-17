<?php
require_once 'Login/Login/db.php';

$sql = "CREATE TABLE IF NOT EXISTS mentoring_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pair_id INT NOT NULL,
    session_date DATETIME NOT NULL,
    duration_minutes INT DEFAULT 60,
    notes TEXT,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pair_id) REFERENCES mentor_student_pairs(id)
)";

if ($conn->query($sql)) {
    echo "Sessions table created successfully";
} else {
    echo "Error: " . $conn->error;
}

// Also create mentor_student_pairs if it doesn't exist
$sql2 = "CREATE TABLE IF NOT EXISTS mentor_student_pairs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    student_id INT NOT NULL,
    project_id INT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES register(id),
    FOREIGN KEY (student_id) REFERENCES register(id),
    FOREIGN KEY (project_id) REFERENCES projects(id)
)";

if ($conn->query($sql2)) {
    echo "<br>Pairs table created successfully";
} else {
    echo "<br>Error: " . $conn->error;
}
?>