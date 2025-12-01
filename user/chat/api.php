<?php
require_once __DIR__ . '/../../includes/security_init.php';
session_start();
require_once '../../Login/Login/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Check if users can chat (mutual follow or accepted request)
function canChat($conn, $user1_id, $user2_id) {
    $stmt = $conn->prepare("SELECT 
        (SELECT COUNT(*) FROM user_follows WHERE follower_id = ? AND following_id = ?) as user1_follows,
        (SELECT COUNT(*) FROM user_follows WHERE follower_id = ? AND following_id = ?) as user2_follows");
    $stmt->bind_param("iiii", $user1_id, $user2_id, $user2_id, $user1_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result['user1_follows'] > 0 && $result['user2_follows'] > 0) return true;
    
    $stmt = $conn->prepare("SELECT id FROM message_requests WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'accepted'");
    $stmt->bind_param("iiii", $user1_id, $user2_id, $user2_id, $user1_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Get or create conversation
function getOrCreateConversation($conn, $user1_id, $user2_id) {
    $stmt = $conn->prepare("SELECT id, encryption_key FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->bind_param("iiii", $user1_id, $user2_id, $user2_id, $user1_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (empty($row['encryption_key'])) {
            $key = base64_encode(random_bytes(32));
            $stmt = $conn->prepare("UPDATE conversations SET encryption_key = ? WHERE id = ?");
            $stmt->bind_param("si", $key, $row['id']);
            $stmt->execute();
        }
        return $row['id'];
    }
    
    $key = base64_encode(random_bytes(32));
    $stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id, encryption_key) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user1_id, $user2_id, $key);
    $stmt->execute();
    return $conn->insert_id;
}

switch ($action) {
    case 'get_available_users':
        $stmt = $conn->prepare("
            SELECT DISTINCT r.id, r.name, r.department, r.user_image
            FROM register r
            WHERE r.id != ? AND r.role = 'student'
            AND (
                EXISTS(
                    SELECT 1 FROM user_follows uf1 
                    WHERE uf1.follower_id = ? AND uf1.following_id = r.id
                    AND EXISTS(
                        SELECT 1 FROM user_follows uf2 
                        WHERE uf2.follower_id = r.id AND uf2.following_id = ?
                    )
                )
                OR EXISTS(
                    SELECT 1 FROM message_requests mr 
                    WHERE ((mr.sender_id = ? AND mr.receiver_id = r.id) OR (mr.sender_id = r.id AND mr.receiver_id = ?)) 
                    AND mr.status = 'accepted'
                )
            )
            ORDER BY r.name ASC
        ");
        $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        echo json_encode(['success' => true, 'users' => $users]);
        break;
        
    case 'start_chat':
        $other_user_id = intval($_POST['other_user_id'] ?? 0);
        
        if ($other_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user']);
            exit;
        }
        
        if (!canChat($conn, $user_id, $other_user_id)) {
            echo json_encode(['success' => false, 'message' => 'Cannot chat with this user']);
            exit;
        }
        
        $conversation_id = getOrCreateConversation($conn, $user_id, $other_user_id);
        
        $stmt = $conn->prepare("SELECT encryption_key FROM conversations WHERE id = ?");
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $enc_key = $result->fetch_assoc()['encryption_key'] ?? null;
        
        echo json_encode(['success' => true, 'conversation_id' => $conversation_id, 'encryption_key' => $enc_key]);
        break;
        
    case 'send_request':
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        
        if ($receiver_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid receiver']);
            exit;
        }
        
        if (canChat($conn, $user_id, $receiver_id)) {
            echo json_encode(['success' => false, 'message' => 'Already connected']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO chat_requests (sender_id, receiver_id, message) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE message = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("iiss", $user_id, $receiver_id, $message, $message);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Request sent']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send request']);
        }
        break;
        
    case 'accept_request':
        $request_id = intval($_POST['request_id'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE chat_requests SET status = 'accepted' WHERE id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $request_id, $user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Request accepted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to accept']);
        }
        break;
        
    case 'reject_request':
        $request_id = intval($_POST['request_id'] ?? 0);
        
        $stmt = $conn->prepare("UPDATE chat_requests SET status = 'rejected' WHERE id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $request_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Request rejected']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject']);
        }
        break;
        
    case 'get_requests':
        $stmt = $conn->prepare("SELECT mr.*, r.name, r.user_image FROM message_requests mr JOIN register r ON mr.sender_id = r.id WHERE mr.receiver_id = ? AND mr.status = 'pending' ORDER BY mr.created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        echo json_encode(['success' => true, 'requests' => $requests]);
        break;
        
    case 'send_message':
        $receiver_id = intval($_POST['receiver_id'] ?? 0);
        $encrypted_content = $_POST['encrypted_content'] ?? '';
        $iv = $_POST['iv'] ?? '';
        
        if (!canChat($conn, $user_id, $receiver_id)) {
            echo json_encode(['success' => false, 'message' => 'Cannot send message']);
            exit;
        }
        
        $conversation_id = getOrCreateConversation($conn, $user_id, $receiver_id);
        
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, encrypted_content, iv) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $conversation_id, $user_id, $receiver_id, $encrypted_content, $iv);
        
        if ($stmt->execute()) {
            $message_id = $conn->insert_id;
            
            $stmt = $conn->prepare("UPDATE conversations SET last_message_id = ?, last_message_at = CURRENT_TIMESTAMP, user1_unread = IF(user1_id = ?, user1_unread, user1_unread + 1), user2_unread = IF(user2_id = ?, user2_unread, user2_unread + 1) WHERE id = ?");
            $stmt->bind_param("iiii", $message_id, $user_id, $user_id, $conversation_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message_id' => $message_id, 'conversation_id' => $conversation_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send']);
        }
        break;
        
    case 'get_conversations':
        $stmt = $conn->prepare("
            SELECT 
                c.id,
                c.user1_id,
                c.user2_id,
                c.last_message_at,
                c.encryption_key,
                IF(c.user1_id = ?, c.user1_unread, c.user2_unread) as unread_count,
                IF(c.user1_id = ?, c.user2_id, c.user1_id) as other_user_id,
                r.name as other_user_name,
                r.user_image as other_user_image,
                m.encrypted_content,
                m.iv,
                m.created_at as last_message_time
            FROM conversations c
            LEFT JOIN register r ON r.id = IF(c.user1_id = ?, c.user2_id, c.user1_id)
            LEFT JOIN messages m ON c.last_message_id = m.id
            WHERE c.user1_id = ? OR c.user2_id = ?
            ORDER BY c.last_message_at DESC
        ");
        $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $conversations = [];
        while ($row = $result->fetch_assoc()) {
            $conversations[] = $row;
        }
        
        echo json_encode(['success' => true, 'conversations' => $conversations]);
        break;
        
    case 'get_messages':
        $conversation_id = intval($_GET['conversation_id'] ?? 0);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        
        $stmt = $conn->prepare("SELECT m.*, r.name as sender_name FROM messages m JOIN register r ON m.sender_id = r.id WHERE m.conversation_id = ? AND ((m.sender_id = ? AND m.deleted_by_sender = 0) OR (m.receiver_id = ? AND m.deleted_by_receiver = 0)) ORDER BY m.created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iiiii", $conversation_id, $user_id, $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->bind_param("ii", $conversation_id, $user_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE conversations SET user1_unread = IF(user1_id = ?, 0, user1_unread), user2_unread = IF(user2_id = ?, 0, user2_unread) WHERE id = ?");
        $stmt->bind_param("iii", $user_id, $user_id, $conversation_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'messages' => array_reverse($messages)]);
        break;
        
    case 'typing':
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $is_typing = intval($_POST['is_typing'] ?? 1);
        
        $stmt = $conn->prepare("INSERT INTO typing_indicators (conversation_id, user_id, is_typing) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_typing = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("iiii", $conversation_id, $user_id, $is_typing, $is_typing);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        break;
        
    case 'check_typing':
        $conversation_id = intval($_GET['conversation_id'] ?? 0);
        
        $stmt = $conn->prepare("SELECT user_id FROM typing_indicators WHERE conversation_id = ? AND user_id != ? AND is_typing = 1 AND updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)");
        $stmt->bind_param("ii", $conversation_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo json_encode(['success' => true, 'is_typing' => $result->num_rows > 0]);
        break;
        
    case 'edit_message':
        $message_id = intval($_POST['message_id'] ?? 0);
        $encrypted_content = $_POST['encrypted_content'] ?? '';
        $iv = $_POST['iv'] ?? '';
        
        if ($message_id <= 0 || empty($encrypted_content)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE messages SET encrypted_content = ?, iv = ? WHERE id = ? AND sender_id = ?");
        $stmt->bind_param("ssii", $encrypted_content, $iv, $message_id, $user_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Message updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update message']);
        }
        break;
        
    case 'delete_message':
        $message_id = intval($_POST['message_id'] ?? 0);
        
        if ($message_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Message not found']);
            exit;
        }
        
        $msg = $result->fetch_assoc();
        
        if ($msg['sender_id'] == $user_id) {
            $stmt = $conn->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?");
        } elseif ($msg['receiver_id'] == $user_id) {
            $stmt = $conn->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?");
        } else {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $stmt->bind_param("i", $message_id);
        
        if ($stmt->execute()) {
            $stmt = $conn->prepare("SELECT id FROM messages WHERE id = ? AND deleted_by_sender = 1 AND deleted_by_receiver = 1");
            $stmt->bind_param("i", $message_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->bind_param("i", $message_id);
                $stmt->execute();
            }
            
            echo json_encode(['success' => true, 'message' => 'Message deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
        }
        break;
        
    case 'delete_conversation':
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        
        if ($conversation_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid conversation ID']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT user1_id, user2_id FROM conversations WHERE id = ?");
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Conversation not found']);
            exit;
        }
        
        $conv = $result->fetch_assoc();
        
        if ($conv['user1_id'] != $user_id && $conv['user2_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("DELETE FROM messages WHERE conversation_id = ?");
            $stmt->bind_param("i", $conversation_id);
            $stmt->execute();
            
            $stmt = $conn->prepare("DELETE FROM typing_indicators WHERE conversation_id = ?");
            $stmt->bind_param("i", $conversation_id);
            $stmt->execute();
            
            $stmt = $conn->prepare("DELETE FROM conversations WHERE id = ?");
            $stmt->bind_param("i", $conversation_id);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Conversation deleted']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to delete conversation']);
        }
        break;
        
    case 'get_conversation_key':
        $conversation_id = intval($_GET['conversation_id'] ?? 0);
        
        $stmt = $conn->prepare("SELECT encryption_key FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
        $stmt->bind_param("iii", $conversation_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['success' => true, 'encryption_key' => $row['encryption_key']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
