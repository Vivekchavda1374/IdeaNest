<?php
session_start();
require_once '../../Login/Login/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'send_request') {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    
    $stmt = $conn->prepare("INSERT INTO message_requests (sender_id, receiver_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE status = 'pending', updated_at = CURRENT_TIMESTAMP");
    $stmt->bind_param("ii", $user_id, $receiver_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Message request sent']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send request']);
    }
} elseif ($action === 'accept_request') {
    $request_id = intval($_POST['request_id'] ?? 0);
    
    $stmt = $conn->prepare("SELECT sender_id FROM message_requests WHERE id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }
    
    $request = $result->fetch_assoc();
    $sender_id = $request['sender_id'];
    
    $stmt = $conn->prepare("UPDATE message_requests SET status = 'accepted' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    
    if ($stmt->execute()) {
        $stmt = $conn->prepare("SELECT name FROM register WHERE id = ?");
        $stmt->bind_param("i", $sender_id);
        $stmt->execute();
        $sender_result = $stmt->get_result();
        $sender_name = $sender_result->fetch_assoc()['name'] ?? 'User';
        
        echo json_encode(['success' => true, 'message' => 'Request accepted', 'sender_id' => $sender_id, 'sender_name' => $sender_name]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to accept']);
    }
} elseif ($action === 'reject_request') {
    $request_id = intval($_POST['request_id'] ?? 0);
    
    $stmt = $conn->prepare("UPDATE message_requests SET status = 'rejected' WHERE id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $request_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request rejected']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject']);
    }
}
?>
