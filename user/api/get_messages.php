<?php
/**
 * Get Messages API - Retrieve messages with shared content
 */

session_start();
require_once '../../Login/Login/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

if ($conversation_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid conversation ID']);
    exit;
}

try {
    // Verify user is part of conversation
    $verify = $conn->prepare("
        SELECT id FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $verify->bind_param("iii", $conversation_id, $user_id, $user_id);
    $verify->execute();
    
    if ($verify->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get messages with shared content details
    $stmt = $conn->prepare("
        SELECT 
            m.id,
            m.sender_id,
            m.receiver_id,
            m.encrypted_content,
            m.message_type,
            m.shared_idea_id,
            m.shared_project_id,
            m.is_read,
            m.created_at,
            u.name as sender_name,
            u.profile_image as sender_image,
            -- Idea details
            b.project_name as idea_title,
            b.description as idea_description,
            b.classification as idea_classification,
            -- Project details
            p.project_name as project_title,
            p.description as project_description,
            p.classification as project_classification,
            p.image_path as project_image
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN blog b ON m.shared_idea_id = b.id
        LEFT JOIN projects p ON m.shared_project_id = p.id
        WHERE m.conversation_id = ?
        AND m.deleted_by_sender = 0 
        AND m.deleted_by_receiver = 0
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $conversation_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $message = [
            'id' => $row['id'],
            'sender_id' => $row['sender_id'],
            'receiver_id' => $row['receiver_id'],
            'content' => base64_decode($row['encrypted_content']),
            'type' => $row['message_type'],
            'is_read' => $row['is_read'],
            'created_at' => $row['created_at'],
            'sender' => [
                'name' => $row['sender_name'],
                'image' => $row['sender_image']
            ]
        ];
        
        // Add shared content details
        if ($row['message_type'] === 'idea_share' && $row['shared_idea_id']) {
            $message['shared_content'] = [
                'type' => 'idea',
                'id' => $row['shared_idea_id'],
                'title' => $row['idea_title'],
                'description' => substr($row['idea_description'], 0, 200) . '...',
                'classification' => $row['idea_classification'],
                'link' => 'view_idea.php?id=' . $row['shared_idea_id']
            ];
        } elseif ($row['message_type'] === 'project_share' && $row['shared_project_id']) {
            $message['shared_content'] = [
                'type' => 'project',
                'id' => $row['shared_project_id'],
                'title' => $row['project_title'],
                'description' => substr($row['project_description'], 0, 200) . '...',
                'classification' => $row['project_classification'],
                'image' => $row['project_image'],
                'link' => 'view_project.php?id=' . $row['shared_project_id']
            ];
        }
        
        $messages[] = $message;
    }
    
    // Mark messages as read
    $mark_read = $conn->prepare("
        UPDATE messages 
        SET is_read = 1, read_at = NOW() 
        WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $mark_read->bind_param("ii", $conversation_id, $user_id);
    $mark_read->execute();
    
    // Update unread count
    $update_unread = $conn->prepare("
        UPDATE conversations 
        SET user1_unread = CASE WHEN user1_id = ? THEN 0 ELSE user1_unread END,
            user2_unread = CASE WHEN user2_id = ? THEN 0 ELSE user2_unread END
        WHERE id = ?
    ");
    $update_unread->bind_param("iii", $user_id, $user_id, $conversation_id);
    $update_unread->execute();
    
    echo json_encode([
        'success' => true,
        'messages' => array_reverse($messages),
        'total' => count($messages)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
