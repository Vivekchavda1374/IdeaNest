<?php
include "../Login/Login/db.php";
error_reporting(E_ERROR);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to bookmark projects',
        'login_required' => true
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Check if project_id and action are set
if (!isset($_POST['project_id']) || !isset($_POST['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$project_id = intval($_POST['project_id']);
$action = $_POST['action'];

// Validate project exists
$project_check = $conn->prepare("SELECT id FROM admin_approved_projects WHERE id = ?");
$project_check->bind_param("i", $project_id);
$project_check->execute();
$project_result = $project_check->get_result();

if ($project_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Project not found'
    ]);
    exit;
}

// Process the bookmark action
if ($action === 'add') {
    // Check if bookmark already exists
    $check_stmt = $conn->prepare("SELECT id FROM bookmark WHERE user_id = ? AND project_id = ?");
    $check_stmt->bind_param("ii", $user_id, $project_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Project already bookmarked'
        ]);
        exit;
    }

    // Add bookmark
    $stmt = $conn->prepare("INSERT INTO bookmark (user_id, project_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $project_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Project added to bookmarks'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error adding bookmark: ' . $conn->error
        ]);
    }
    $stmt->close();
} elseif ($action === 'remove') {
    // Remove bookmark
    $stmt = $conn->prepare("DELETE FROM bookmark WHERE user_id = ? AND project_id = ?");
    $stmt->bind_param("ii", $user_id, $project_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Project removed from bookmarks'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error removing bookmark: ' . $conn->error
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}

$conn->close();
?>