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

$sender_id = $_SESSION['user_id'];
$sender_name = $_SESSION['user_name'];
$receiver_id = $_POST['receiver_id'] ?? null;
$message_text = $_POST['message_text'] ?? '';

// Validate input
if (!$receiver_id || empty(trim($message_text))) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid input'
    ]);
    exit();
}

try {
    // Prepare SQL to insert message
    $sql = "INSERT INTO user_messages (sender_id, receiver_id, message_text, is_read) 
            VALUES (?, ?, ?, 0)";  // Explicitly set is_read to 0
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message_text);
    
    if ($stmt->execute()) {
        // Get the ID of the last inserted message
        $message_id = $conn->insert_id;

        // Fetch receiver's name
        $receiver_query = "SELECT name FROM register WHERE id = ?";
        $receiver_stmt = $conn->prepare($receiver_query);
        $receiver_stmt->bind_param("i", $receiver_id);
        $receiver_stmt->execute();
        $receiver_result = $receiver_stmt->get_result();
        $receiver_name = $receiver_result->fetch_assoc()['name'];

        echo json_encode([
            'status' => 'success', 
            'message' => 'Message sent successfully',
            'message_details' => [
                'id' => $message_id,
                'sender_id' => $sender_id,
                'sender_name' => $sender_name,
                'receiver_id' => $receiver_id,
                'receiver_name' => $receiver_name,
                'message_text' => $message_text,
                'timestamp' => date('Y-m-d H:i:s'),
                'formatted_time' => 'Just now',
                'is_read' => false
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to send message'
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'An unexpected error occurred',
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 