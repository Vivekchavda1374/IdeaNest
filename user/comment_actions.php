<?php

session_start();
include '../Login/Login/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$session_id = session_id();

switch ($action) {
    case 'like_comment':
        $comment_id = intval($_POST['comment_id']);

        // Check if like exists
        $check_sql = "SELECT * FROM comment_likes WHERE comment_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $comment_id, $session_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // Remove like
            $delete_sql = "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("is", $comment_id, $session_id);
            $delete_stmt->execute();
            $liked = false;
        } else {
            // Add like
            $insert_sql = "INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("is", $comment_id, $session_id);
            $insert_stmt->execute();
            $liked = true;
        }

        // Get updated count
        $count_sql = "SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $comment_id);
        $count_stmt->execute();
        $count = $count_stmt->get_result()->fetch_assoc()['count'];

        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
