<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$student_id = $input['student_id'] ?? null;
$mentor_id = $_SESSION['mentor_id'];

if (!$student_id) {
    echo json_encode(['error' => 'Student ID required']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO mentor_student_pairs (mentor_id, student_id, status) VALUES (?, ?, 'active')");
    $stmt->bind_param("ii", $mentor_id, $student_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to pair student']);
}
?>