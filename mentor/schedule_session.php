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
    // Get student info for logging
    $student_query = "SELECT msp.student_id, r.name as student_name FROM mentor_student_pairs msp JOIN register r ON msp.student_id = r.id WHERE msp.id = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("i", $pair_id);
    $student_stmt->execute();
    $student_info = $student_stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("INSERT INTO mentoring_sessions (pair_id, session_date, duration_minutes, notes, meeting_link, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
    $stmt->bind_param("isiss", $pair_id, $session_date, $duration, $notes, $meeting_link);
    $stmt->execute();

    // Log activity
    if ($student_info) {
        $activity_desc = "Scheduled session with " . $student_info['student_name'] . " for " . date('M j, Y g:i A', strtotime($session_date));
        $log_stmt = $conn->prepare("INSERT INTO mentor_activity_logs (mentor_id, activity_type, description, student_id, created_at) VALUES (?, 'session_scheduled', ?, ?, NOW())");
        $log_stmt->bind_param("isi", $_SESSION['mentor_id'], $activity_desc, $student_info['student_id']);
        $log_stmt->execute();
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to schedule session']);
}
