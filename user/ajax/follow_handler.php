<?php
session_start();
require_once '../../Login/Login/db.php';

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$action = $_POST['action'] ?? '';
$target_user_id = intval($_POST['user_id'] ?? 0);
$current_user_id = $_SESSION['user_id'];

if ($target_user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Follow User Function
function followUser($conn, $follower_id, $following_id) {
    if ($follower_id <= 0 || $following_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }
    
    if ($follower_id == $following_id) {
        return ['success' => false, 'message' => 'You cannot follow yourself'];
    }
    
    $conn->begin_transaction();
    
    try {
        // Check if already following
        $stmt = $conn->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $follower_id, $following_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $conn->rollback();
            return ['success' => false, 'message' => 'You are already following this user'];
        }
        
        // Insert follow relationship
        $stmt = $conn->prepare("INSERT INTO user_follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $follower_id, $following_id);
        $stmt->execute();
        
        // Update stats for the followed user
        $stmt = $conn->prepare("
            INSERT INTO user_follow_stats (user_id, followers_count, following_count)
            VALUES (?, 1, 0)
            ON DUPLICATE KEY UPDATE followers_count = followers_count + 1
        ");
        $stmt->bind_param("i", $following_id);
        $stmt->execute();
        
        // Update stats for the follower
        $stmt = $conn->prepare("
            INSERT INTO user_follow_stats (user_id, followers_count, following_count)
            VALUES (?, 0, 1)
            ON DUPLICATE KEY UPDATE following_count = following_count + 1
        ");
        $stmt->bind_param("i", $follower_id);
        $stmt->execute();
        
        // Get follower name for notification
        $stmt = $conn->prepare("SELECT name FROM register WHERE id = ?");
        $stmt->bind_param("i", $follower_id);
        $stmt->execute();
        $follower_data = $stmt->get_result()->fetch_assoc();
        $follower_name = $follower_data['name'] ?? 'Someone';
        
        // Create notification
        $stmt = $conn->prepare("
            INSERT INTO user_notifications 
            (user_id, notification_type, title, message, related_id, related_type, icon, color)
            VALUES (?, 'new_follower', 'New Follower', ?, ?, 'user', 'bi-person-plus', 'info')
        ");
        $message = $follower_name . ' started following you';
        $stmt->bind_param("isi", $following_id, $message, $follower_id);
        $stmt->execute();
        
        $conn->commit();
        
        return ['success' => true, 'message' => 'Successfully followed user'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Unfollow User Function
function unfollowUser($conn, $follower_id, $following_id) {
    if ($follower_id <= 0 || $following_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID'];
    }
    
    $conn->begin_transaction();
    
    try {
        // Check if following exists
        $stmt = $conn->prepare("SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $follower_id, $following_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $conn->rollback();
            return ['success' => false, 'message' => 'You are not following this user'];
        }
        
        // Delete follow relationship
        $stmt = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $follower_id, $following_id);
        $stmt->execute();
        
        // Update stats
        $stmt = $conn->prepare("
            UPDATE user_follow_stats 
            SET followers_count = GREATEST(0, followers_count - 1)
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $following_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("
            UPDATE user_follow_stats 
            SET following_count = GREATEST(0, following_count - 1)
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $follower_id);
        $stmt->execute();
        
        $conn->commit();
        
        return ['success' => true, 'message' => 'Successfully unfollowed user'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Check if following
function isFollowing($conn, $follower_id, $following_id) {
    $stmt = $conn->prepare("
        SELECT EXISTS(
            SELECT 1 FROM user_follows 
            WHERE follower_id = ? AND following_id = ?
        ) as is_following
    ");
    $stmt->bind_param("ii", $follower_id, $following_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (bool)$row['is_following'];
}

// Get follow counts
function getFollowCounts($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT 
            COALESCE(followers_count, 0) as followers,
            COALESCE(following_count, 0) as following
        FROM user_follow_stats
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        return ['followers' => 0, 'following' => 0];
    }
    
    return $row;
}

// Handle actions
switch ($action) {
    case 'follow':
        $result = followUser($conn, $current_user_id, $target_user_id);
        echo json_encode($result);
        break;
        
    case 'unfollow':
        $result = unfollowUser($conn, $current_user_id, $target_user_id);
        echo json_encode($result);
        break;
        
    case 'check':
        $is_following = isFollowing($conn, $current_user_id, $target_user_id);
        echo json_encode(['success' => true, 'is_following' => $is_following]);
        break;
        
    case 'get_counts':
        $counts = getFollowCounts($conn, $target_user_id);
        echo json_encode(['success' => true, 'data' => $counts]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
