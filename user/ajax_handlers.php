<?php
session_start();
include '../Login/Login/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get actual user ID from database
$actual_user_id = null;
if (isset($_SESSION['user_name'])) {
    $stmt = $conn->prepare("SELECT id FROM register WHERE name = ?");
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['user_name']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $actual_user_id = $row['id'];
        }
        $stmt->close();
    }
}

if (!$actual_user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'toggle_bookmark':
        $project_id = intval($_POST['project_id']);
        
        // Check if bookmark exists
        $check_sql = "SELECT id FROM bookmark WHERE project_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $project_id, $actual_user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Remove bookmark
            $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("is", $project_id, $actual_user_id);
            
            if ($delete_stmt->execute()) {
                echo json_encode(['success' => true, 'bookmarked' => false, 'message' => 'Bookmark removed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove bookmark']);
            }
            $delete_stmt->close();
        } else {
            // Add bookmark
            $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, 0)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("is", $project_id, $actual_user_id);
            
            if ($insert_stmt->execute()) {
                echo json_encode(['success' => true, 'bookmarked' => true, 'message' => 'Project bookmarked']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add bookmark']);
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
        break;
        
    case 'toggle_like':
        $project_id = intval($_POST['project_id']);
        
        // Check if like exists
        $check_sql = "SELECT id FROM project_likes WHERE project_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $project_id, $actual_user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Remove like
            $delete_sql = "DELETE FROM project_likes WHERE project_id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("is", $project_id, $actual_user_id);
            $delete_stmt->execute();
            $liked = false;
            $delete_stmt->close();
        } else {
            // Add like
            $insert_sql = "INSERT INTO project_likes (project_id, user_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("is", $project_id, $actual_user_id);
            $insert_stmt->execute();
            $liked = true;
            $insert_stmt->close();
        }
        
        // Get updated like count
        $count_sql = "SELECT COUNT(*) as count FROM project_likes WHERE project_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $project_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count = $count_result->fetch_assoc()['count'];
        $count_stmt->close();
        
        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
        $check_stmt->close();
        break;
        
    case 'toggle_comment_like':
        $comment_id = intval($_POST['comment_id']);
        
        // Check if like exists
        $check_sql = "SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $comment_id, $actual_user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Remove like
            $delete_sql = "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("is", $comment_id, $actual_user_id);
            $delete_stmt->execute();
            $liked = false;
            $delete_stmt->close();
        } else {
            // Add like
            $insert_sql = "INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("is", $comment_id, $actual_user_id);
            $insert_stmt->execute();
            $liked = true;
            $insert_stmt->close();
        }
        
        // Get updated like count
        $count_sql = "SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $comment_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count = $count_result->fetch_assoc()['count'];
        $count_stmt->close();
        
        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
        $check_stmt->close();
        break;
        
    case 'add_comment':
        $project_id = intval($_POST['project_id']);
        $comment_text = trim($_POST['comment_text']);
        $parent_comment_id = isset($_POST['parent_comment_id']) && !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;
        $user_name = $_SESSION['user_name'] ?? 'Anonymous';
        
        if (empty($comment_text)) {
            echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
            break;
        }
        
        $insert_sql = "INSERT INTO project_comments (project_id, user_id, user_name, comment_text, parent_comment_id) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssi", $project_id, $actual_user_id, $user_name, $comment_text, $parent_comment_id);
        
        if ($insert_stmt->execute()) {
            $comment_id = $conn->insert_id;
            
            // Get updated comment count
            $count_sql = "SELECT COUNT(*) as count FROM project_comments WHERE project_id = ? AND is_deleted = 0";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param("i", $project_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count = $count_result->fetch_assoc()['count'];
            $count_stmt->close();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Comment added successfully',
                'comment_id' => $comment_id,
                'count' => $count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
        $insert_stmt->close();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>