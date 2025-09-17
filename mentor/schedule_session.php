<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Handle both JSON and form POST data
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($content_type, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}

$pair_id = $input['pair_id'] ?? null;
$session_date = $input['session_date'] ?? null;
$duration = $input['duration'] ?? 60;
$notes = $input['notes'] ?? '';
$meeting_link = $input['meeting_link'] ?? null;

if (!$pair_id || !$session_date) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO mentoring_sessions (pair_id, session_date, duration_minutes, notes, meeting_link, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
    $stmt->bind_param("isiss", $pair_id, $session_date, $duration, $notes, $meeting_link);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to schedule session']);
}
?>