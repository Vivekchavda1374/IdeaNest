<?php
session_start();
require_once '../../Login/Login/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'toggle_like':
            $idea_id = (int)$_POST['idea_id'];
            
            $stmt = $conn->prepare("SELECT id FROM idea_likes WHERE idea_id=? AND user_id=?");
            $stmt->bind_param("ii", $idea_id, $user_id);
            $stmt->execute();
            $check = $stmt->get_result();
            
            if ($check && $check->num_rows > 0) {
                $stmt = $conn->prepare("DELETE FROM idea_likes WHERE idea_id=? AND user_id=?");
                $stmt->bind_param("ii", $idea_id, $user_id);
                $stmt->execute();
                $liked = false;
            } else {
                $stmt = $conn->prepare("INSERT INTO idea_likes (idea_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $idea_id, $user_id);
                $stmt->execute();
                $liked = true;
            }
            
            // Get updated count
            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM idea_likes WHERE idea_id=?");
            $count_stmt->bind_param("i", $idea_id);
            $count_stmt->execute();
            $count = $count_stmt->get_result()->fetch_assoc()['count'];
            
            echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
            break;
            
        case 'toggle_bookmark':
            $idea_id = (int)$_POST['idea_id'];
            
            $check = $conn->query("SELECT id FROM idea_bookmarks WHERE idea_id=$idea_id AND user_id=$user_id");
            
            if ($check && $check->num_rows > 0) {
                $conn->query("DELETE FROM idea_bookmarks WHERE idea_id=$idea_id AND user_id=$user_id");
                $bookmarked = false;
            } else {
                $conn->query("INSERT INTO idea_bookmarks (idea_id, user_id) VALUES ($idea_id, $user_id)");
                $bookmarked = true;
            }
            
            echo json_encode(['success' => true, 'bookmarked' => $bookmarked]);
            break;
            
        case 'toggle_follow':
            $idea_id = (int)$_POST['idea_id'];
            
            $check = $conn->query("SELECT id FROM idea_followers WHERE idea_id=$idea_id AND user_id=$user_id");
            
            if ($check && $check->num_rows > 0) {
                $conn->query("DELETE FROM idea_followers WHERE idea_id=$idea_id AND user_id=$user_id");
                $following = false;
            } else {
                $conn->query("INSERT INTO idea_followers (idea_id, user_id) VALUES ($idea_id, $user_id)");
                $following = true;
            }
            
            // Get updated count
            $count = $conn->query("SELECT COUNT(*) as count FROM idea_followers WHERE idea_id=$idea_id")->fetch_assoc()['count'];
            
            echo json_encode(['success' => true, 'following' => $following, 'count' => $count]);
            break;
            
        case 'submit_rating':
            $idea_id = (int)$_POST['idea_id'];
            $rating = (int)$_POST['rating'];
            
            if ($rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Invalid rating']);
                break;
            }
            
            $stmt = $conn->prepare("INSERT INTO idea_ratings (idea_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating=?");
            $stmt->bind_param("iiii", $idea_id, $user_id, $rating, $rating);
            $stmt->execute();
            
            // Get updated average
            $avg_result = $conn->query("SELECT AVG(rating) as avg, COUNT(*) as count FROM idea_ratings WHERE idea_id=$idea_id");
            $avg_data = $avg_result->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'avg_rating' => round($avg_data['avg'], 1),
                'total_ratings' => $avg_data['count']
            ]);
            break;
            
        case 'add_comment':
            $idea_id = (int)$_POST['idea_id'];
            $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
            $comment = trim($_POST['comment'] ?? '');
            
            if (empty($comment)) {
                echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
                break;
            }
            
            $comment_escaped = $conn->real_escape_string($comment);
            $parent_sql = $parent_id ? $parent_id : 'NULL';
            
            $result = $conn->query("INSERT INTO idea_comments (idea_id, user_id, parent_id, comment) VALUES ($idea_id, $user_id, $parent_sql, '$comment_escaped')");
            
            if ($result) {
                $comment_id = $conn->insert_id;
                
                // Get user name
                $user_result = $conn->query("SELECT name FROM register WHERE id=$user_id");
                $user_name = $user_result ? $user_result->fetch_assoc()['name'] : 'Unknown';
                
                // Get updated comment count
                $count = $conn->query("SELECT COUNT(*) as count FROM idea_comments WHERE idea_id=$idea_id")->fetch_assoc()['count'];
                
                echo json_encode([
                    'success' => true,
                    'comment_id' => $comment_id,
                    'user_name' => $user_name,
                    'comment' => $comment,
                    'created_at' => date('Y-m-d H:i:s'),
                    'count' => $count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
            }
            break;
            
        case 'track_view':
            $idea_id = (int)$_POST['idea_id'];
            
            // Check if already viewed recently (within last hour)
            $check = $conn->query("SELECT id FROM idea_views WHERE idea_id=$idea_id AND user_id=$user_id AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            
            if (!$check || $check->num_rows === 0) {
                $conn->query("INSERT INTO idea_views (idea_id, user_id) VALUES ($idea_id, $user_id)");
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'track_share':
            $idea_id = (int)$_POST['idea_id'];
            $platform = $conn->real_escape_string($_POST['platform'] ?? 'other');
            
            $conn->query("INSERT INTO idea_shares (idea_id, user_id, platform) VALUES ($idea_id, $user_id, '$platform')");
            
            // Get updated count
            $count = $conn->query("SELECT COUNT(*) as count FROM idea_shares WHERE idea_id=$idea_id")->fetch_assoc()['count'];
            
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'submit_report':
            $idea_id = (int)$_POST['idea_id'];
            $reason = $conn->real_escape_string($_POST['reason'] ?? '');
            $description = $conn->real_escape_string($_POST['description'] ?? '');
            
            if (empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Reason is required']);
                break;
            }
            
            $result = $conn->query("INSERT INTO idea_reports (idea_id, user_id, reason, description) VALUES ($idea_id, $user_id, '$reason', '$description')");
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit report']);
            }
            break;
            
        case 'delete_comment':
            $comment_id = (int)$_POST['comment_id'];
            
            // Check if user owns the comment
            $check = $conn->query("SELECT user_id FROM idea_comments WHERE id=$comment_id");
            if ($check && $check->num_rows > 0) {
                $comment_user_id = $check->fetch_assoc()['user_id'];
                
                if ($comment_user_id == $user_id) {
                    $conn->query("DELETE FROM idea_comments WHERE id=$comment_id");
                    echo json_encode(['success' => true, 'message' => 'Comment deleted']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Comment not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>
