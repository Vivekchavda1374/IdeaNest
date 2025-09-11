<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$mentor_id = $_SESSION['mentor_id'];
$student_id = $data['student_id'];

// Check mentor capacity
$capacity_query = "SELECT current_students, max_students FROM mentors WHERE user_id = ?";
$stmt = $conn->prepare($capacity_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$capacity = $stmt->get_result()->fetch_assoc();

if ($capacity['current_students'] >= $capacity['max_students']) {
    http_response_code(400);
    echo json_encode(['error' => 'Mentor at capacity']);
    exit;
}

// Create pairing
$pair_query = "INSERT INTO mentor_student_pairs (mentor_id, student_id) VALUES (?, ?)";
$stmt = $conn->prepare($pair_query);
$stmt->bind_param("ii", $mentor_id, $student_id);
$stmt->execute();

// Update mentor student count
$update_query = "UPDATE mentors SET current_students = current_students + 1 WHERE user_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>