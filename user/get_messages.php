<?php
session_start();
include '../Login/Login/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'User not authenticated'
    ]);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$selected_user_id = $_GET['user_id'] ?? null;

// Validate input
if (!$selected_user_id) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid user ID'
    ]);
    exit();
}

try {
    // Fetch messages between current user and selected user, sorted by most recent first
    $sql = "SELECT um.*, 
                   r_sender.name AS sender_name, 
                   r_receiver.name AS receiver_name,
                   TIMESTAMPDIFF(SECOND, um.timestamp, NOW()) AS time_diff
            FROM user_messages um
            JOIN register r_sender ON um.sender_id = r_sender.id
            JOIN register r_receiver ON um.receiver_id = r_receiver.id
            WHERE (um.sender_id = ? AND um.receiver_id = ?) 
               OR (um.sender_id = ? AND um.receiver_id = ?) 
            ORDER BY um.timestamp DESC 
            LIMIT 50"; // Limit to prevent overwhelming load
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $current_user_id, $selected_user_id, $selected_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        // Format timestamp
        $time_diff = intval($row['time_diff']);
        if ($time_diff < 60) {
            $formatted_time = $time_diff . ' seconds ago';
        } elseif ($time_diff < 3600) {
            $minutes = floor($time_diff / 60);
            $formatted_time = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($time_diff < 86400) {
            $hours = floor($time_diff / 3600);
            $formatted_time = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = floor($time_diff / 86400);
            $formatted_time = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }

        $messages[] = [
            'id' => $row['id'],
            'sender_id' => $row['sender_id'],
            'receiver_id' => $row['receiver_id'],
            'sender_name' => $row['sender_name'],
            'receiver_name' => $row['receiver_name'],
            'message_text' => $row['message_text'],
            'timestamp' => $row['timestamp'],
            'formatted_time' => $formatted_time,
            'is_read' => $row['is_read'] == 1,
            'time_diff' => $time_diff
        ];
    }

    // Reverse the messages to maintain chronological order when displayed
    $messages = array_reverse($messages);

    // Mark messages as read for the current user
    $read_sql = "UPDATE user_messages 
                 SET is_read = 1 
                 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0";
    $read_stmt = $conn->prepare($read_sql);
    $read_stmt->bind_param("ii", $current_user_id, $selected_user_id);
    $read_stmt->execute();

    echo json_encode($messages);
    
    $stmt->close();
    $read_stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'An unexpected error occurred',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 