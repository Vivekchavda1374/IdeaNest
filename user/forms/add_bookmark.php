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

// Check if the bookmark already exists
$checkSql = "SELECT id FROM user_bookmarks WHERE user_id = ? AND project_id = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ii", $user_id, $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Bookmark already exists
    echo json_encode(['success' => false, 'message' => 'Project is already bookmarked']);
    $stmt->close();
    $conn->close();
    exit;
}

// Add the bookmark
$insertSql = "INSERT INTO user_bookmarks (user_id, project_id, bookmarked_at) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($insertSql);
$stmt->bind_param("ii", $user_id, $project_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Project bookmarked successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to bookmark project: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 