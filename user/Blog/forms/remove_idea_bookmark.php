<?php
include '../../../Login/Login/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if idea_id is provided in POST data
if (!isset($_POST['idea_id']) || empty($_POST['idea_id'])) {
    echo json_encode(['success' => false, 'message' => 'Idea ID is required']);
    exit;
}

// Ensure the idea_bookmarks table exists
$create_table_sql = "CREATE TABLE IF NOT EXISTS idea_bookmarks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    idea_id INT NOT NULL,
    bookmarked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES register(id),
    FOREIGN KEY (idea_id) REFERENCES blog(id),
    UNIQUE KEY unique_bookmark (user_id, idea_id)
)";
$conn->query($create_table_sql);

$user_id = $_SESSION['user_id'];
$idea_id = $_POST['idea_id'];

// Prepare statement to delete the bookmark
$sql = "DELETE FROM idea_bookmarks WHERE user_id = ? AND idea_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $idea_id);

// Execute the query
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Bookmark removed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove bookmark: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 