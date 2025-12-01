<?php
require_once __DIR__ . '/../includes/security_init.php';

// Production-safe error reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

header('Content-Type: application/json');

include '../Login/Login/db.php';
require_once '../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Check if user is logged in
if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to perform this action'
    ]);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$project_id = isset($input['project_id']) ? intval($input['project_id']) : 0;
$csrf_token = $input['csrf_token'] ?? '';

// Verify CSRF token exists in session (for AJAX, we check existence but don't consume)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!$csrf_token || !isset($_SESSION['csrf_tokens'][$csrf_token])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token. Please refresh the page.'
    ]);
    exit();
}

// Check if token is not expired (1 hour)
if (time() - $_SESSION['csrf_tokens'][$csrf_token] > 3600) {
    unset($_SESSION['csrf_tokens'][$csrf_token]);
    echo json_encode([
        'success' => false,
        'message' => 'Security token expired. Please refresh the page.'
    ]);
    exit();
}

if ($project_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid project ID'
    ]);
    exit();
}

// Handle like toggle
if ($action === 'toggle_like') {
    // Check if like already exists
    $check_like_sql = "SELECT * FROM project_likes WHERE project_id = ? AND user_id = ?";
    $check_like_stmt = $conn->prepare($check_like_sql);
    $check_like_stmt->bind_param("ii", $project_id, $user_id);
    $check_like_stmt->execute();
    $like_result = $check_like_stmt->get_result();

    if ($like_result->num_rows > 0) {
        // Remove like
        $delete_like_sql = "DELETE FROM project_likes WHERE project_id = ? AND user_id = ?";
        $delete_like_stmt = $conn->prepare($delete_like_sql);
        $delete_like_stmt->bind_param("ii", $project_id, $user_id);
        
        if ($delete_like_stmt->execute()) {
            $delete_like_stmt->close();
            
            // Get updated like count
            $count_sql = "SELECT COUNT(*) as total_likes FROM project_likes WHERE project_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param("i", $project_id);
            $count_stmt->execute();
            $total_likes = $count_stmt->get_result()->fetch_assoc()['total_likes'];
            $count_stmt->close();
            
            echo json_encode([
                'success' => true,
                'liked' => false,
                'total_likes' => $total_likes,
                'message' => 'Like removed successfully',
                'new_token' => generateCSRFToken()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to remove like'
            ]);
        }
    } else {
        // Add like
        $insert_like_sql = "INSERT INTO project_likes (project_id, user_id) VALUES (?, ?)";
        $insert_like_stmt = $conn->prepare($insert_like_sql);
        $insert_like_stmt->bind_param("ii", $project_id, $user_id);
        
        if ($insert_like_stmt->execute()) {
            $insert_like_stmt->close();
            
            // Get updated like count
            $count_sql = "SELECT COUNT(*) as total_likes FROM project_likes WHERE project_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param("i", $project_id);
            $count_stmt->execute();
            $total_likes = $count_stmt->get_result()->fetch_assoc()['total_likes'];
            $count_stmt->close();
            
            echo json_encode([
                'success' => true,
                'liked' => true,
                'total_likes' => $total_likes,
                'message' => 'Project liked successfully',
                'new_token' => generateCSRFToken()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add like'
            ]);
        }
    }
    $check_like_stmt->close();
}

// Handle bookmark toggle
elseif ($action === 'toggle_bookmark') {
    // Check if bookmark already exists
    $check_sql = "SELECT * FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $project_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Remove bookmark
        $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $project_id, $user_id);
        
        if ($delete_stmt->execute()) {
            $delete_stmt->close();
            
            echo json_encode([
                'success' => true,
                'bookmarked' => false,
                'message' => 'Bookmark removed successfully',
                'new_token' => generateCSRFToken()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to remove bookmark'
            ]);
        }
    } else {
        // Add bookmark
        $idea_id = 0;
        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $project_id, $user_id, $idea_id);
        
        if ($insert_stmt->execute()) {
            $insert_stmt->close();
            
            echo json_encode([
                'success' => true,
                'bookmarked' => true,
                'message' => 'Project bookmarked successfully',
                'new_token' => generateCSRFToken()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add bookmark'
            ]);
        }
    }
    $check_stmt->close();
}

else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action'
    ]);
}

$conn->close();
