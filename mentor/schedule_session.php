<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pair_id = $input['pair_id'] ?? null;
$session_date = $input['session_date'] ?? null;
$duration = $input['duration'] ?? 60;
$notes = $input['notes'] ?? '';

if (!$pair_id || !$session_date) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO mentoring_sessions (pair_id, session_date, duration_minutes, notes, status) VALUES (?, ?, ?, ?, 'scheduled')");
    $stmt->bind_param("isis", $pair_id, $session_date, $duration, $notes);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to schedule session']);
}
?>