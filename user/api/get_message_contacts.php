<?php


session_start();
require_once '../../Login/Login/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get users with accepted message requests or existing conversations
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            u.id,
            u.name,
            u.email,
            u.user_image as profile_image
        FROM register u
        WHERE u.id != ? AND (
            -- Users with accepted message requests
            u.id IN (
                SELECT receiver_id FROM message_requests 
                WHERE sender_id = ? AND status = 'accepted'
            )
            OR u.id IN (
                SELECT sender_id FROM message_requests 
                WHERE receiver_id = ? AND status = 'accepted'
            )
            OR 
            -- Users with existing conversations
            u.id IN (
                SELECT user1_id FROM conversations WHERE user2_id = ?
                UNION
                SELECT user2_id FROM conversations WHERE user1_id = ?
            )
        )
        ORDER BY u.name ASC
    ");
    $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'profile_image' => $row['profile_image'] ?? 'default-avatar.png'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
