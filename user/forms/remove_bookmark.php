<?php
include '../../Login/Login/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if project_id is provided in POST data
if (!isset($_POST['project_id']) || empty($_POST['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$project_id = $_POST['project_id'];

// Prepare statement to delete the bookmark
$sql = "DELETE FROM user_bookmarks WHERE user_id = ? AND project_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $project_id);

// Execute the query
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Bookmark removed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove bookmark: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 