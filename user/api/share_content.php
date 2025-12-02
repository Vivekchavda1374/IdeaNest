<?php
/**
 * Share Content API - Share ideas or projects via messages
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../../Login/Login/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['receiver_id']) || !isset($data['content_type']) || !isset($data['content_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$receiver_id = intval($data['receiver_id']);
$content_type = $data['content_type']; // 'idea' or 'project'
$content_id = intval($data['content_id']);
$message_text = isset($data['message']) ? trim($data['message']) : 'Check out this ' . $content_type . '!';

// Validate content type
if (!in_array($content_type, ['idea', 'project'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid content type']);
    exit;
}

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    error_log('Database connection failed in share_content.php');
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

try {
    // Verify content exists first
    if ($content_type === 'idea') {
        $verify = $conn->prepare("SELECT id, project_name FROM blog WHERE id = ?");
    } else {
        $verify = $conn->prepare("SELECT id, project_name FROM admin_approved_projects WHERE id = ?");
    }
    
    if (!$verify) {
        throw new Exception('Failed to prepare content verification query: ' . $conn->error);
    }
    
    $verify->bind_param("i", $content_id);
    $verify->execute();
    $verify_result = $verify->get_result();
    
    if ($verify_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Content not found']);
        exit;
    }
    
    $content_data = $verify_result->fetch_assoc();
    $verify->close();
    
    // Check if conversation exists
    $stmt = $conn->prepare("
        SELECT id FROM conversations 
        WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare conversation query: ' . $conn->error);
    }
    
    $stmt->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create new conversation
        $encryption_key = base64_encode(random_bytes(32));
        $create_conv = $conn->prepare("
            INSERT INTO conversations (user1_id, user2_id, encryption_key, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        if (!$create_conv) {
            throw new Exception('Failed to prepare conversation insert: ' . $conn->error);
        }
        
        $create_conv->bind_param("iis", $user_id, $receiver_id, $encryption_key);
        
        if (!$create_conv->execute()) {
            throw new Exception('Failed to create conversation: ' . $create_conv->error);
        }
        
        $conversation_id = $conn->insert_id;
        $create_conv->close();
    } else {
        $conversation_id = $result->fetch_assoc()['id'];
    }
    $stmt->close();
    
    // For shared content messages, we store a placeholder
    // The chat system will detect message_type and display shared content accordingly
    // We use empty encrypted_content since the actual content is in shared_idea_id/shared_project_id
    $iv = '';
    $encrypted_content = '';
    
    // Insert message
    $message_type = $content_type === 'idea' ? 'idea_share' : 'project_share';
    $shared_idea_id = $content_type === 'idea' ? $content_id : null;
    $shared_project_id = $content_type === 'project' ? $content_id : null;
    
    $insert_msg = $conn->prepare("
        INSERT INTO messages 
        (conversation_id, sender_id, receiver_id, encrypted_content, iv, 
         shared_idea_id, shared_project_id, message_type, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$insert_msg) {
        throw new Exception('Failed to prepare message insert: ' . $conn->error);
    }
    
    $insert_msg->bind_param(
        "iiiisiis", 
        $conversation_id, $user_id, $receiver_id, $encrypted_content, 
        $iv, $shared_idea_id, $shared_project_id, $message_type
    );
    
    if (!$insert_msg->execute()) {
        throw new Exception('Failed to insert message: ' . $insert_msg->error);
    }
    
    $message_id = $conn->insert_id;
    $insert_msg->close();
    
    // Update conversation
    $update_conv = $conn->prepare("
        UPDATE conversations 
        SET last_message_id = ?, last_message_at = NOW(),
            user2_unread = user2_unread + 1
        WHERE id = ?
    ");
    
    if ($update_conv) {
        $update_conv->bind_param("ii", $message_id, $conversation_id);
        $update_conv->execute();
        $update_conv->close();
    }
    
    // Try to track share (optional - won't fail if table doesn't exist or has issues)
    try {
        $track_share = $conn->prepare("
            INSERT INTO message_shares 
            (message_id, sender_id, receiver_id, content_type, content_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($track_share) {
            $track_share->bind_param("iiisi", $message_id, $user_id, $receiver_id, $content_type, $content_id);
            $track_share->execute();
            $track_share->close();
        }
    } catch (Exception $track_error) {
        // Log but don't fail - tracking is optional
        error_log('Message share tracking failed (non-critical): ' . $track_error->getMessage());
    }
    
    // Try to create notification (optional)
    try {
        $notif_title = $content_type === 'idea' ? 'New Idea Shared' : 'New Project Shared';
        $notif_message = "Someone shared a {$content_type} with you: " . $content_data['project_name'];
        $notif_link = "messages.php?conversation={$conversation_id}";
        
        $notif_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, title, message, link, created_at) 
            VALUES (?, 'message_share', ?, ?, ?, NOW())
        ");
        
        if ($notif_stmt) {
            $notif_stmt->bind_param("isss", $receiver_id, $notif_title, $notif_message, $notif_link);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    } catch (Exception $notif_error) {
        // Log but don't fail - notification is optional
        error_log('Notification creation failed (non-critical): ' . $notif_error->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'message' => ucfirst($content_type) . ' shared successfully!',
        'conversation_id' => $conversation_id,
        'message_id' => $message_id
    ]);
    
} catch (Exception $e) {
    error_log('Share content error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
