<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $activity_type = $input['activity_type'] ?? '';
    $description = $input['description'] ?? '';
    $student_id = $input['student_id'] ?? null;
    
    if (empty($activity_type) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    try {
        $query = "INSERT INTO mentor_activity_logs (mentor_id, activity_type, description, student_id, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issi", $mentor_id, $activity_type, $description, $student_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'activity_id' => $conn->insert_id]);
        } else {
            throw new Exception('Failed to log activity');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>