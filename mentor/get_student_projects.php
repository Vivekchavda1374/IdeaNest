<?php

session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$student_id = $_GET['student_id'] ?? 0;

if (!$student_id) {
    echo json_encode([]);
    exit;
}

try {
    $query = "SELECT id, project_name, classification, description 
              FROM projects 
              WHERE user_id = ? 
              ORDER BY submission_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($projects);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
