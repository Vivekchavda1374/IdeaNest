<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'debug' => 'No mentor_id in session']);
    exit;
}

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'debug' => $conn->connect_error ?? 'Connection not established']);
    exit;
}

// Handle both JSON and form POST data
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($content_type, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON', 'debug' => json_last_error_msg()]);
        exit;
    }
} else {
    $input = $_POST;
}

$pair_id = $input['pair_id'] ?? null;
$session_date = $input['session_date'] ?? null;
$duration = $input['duration'] ?? 60;
$notes = $input['notes'] ?? '';
$meeting_link = $input['meeting_link'] ?? null;

// Validate required fields
if (!$pair_id || !$session_date) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required fields',
        'debug' => [
            'pair_id' => $pair_id ? 'present' : 'missing',
            'session_date' => $session_date ? 'present' : 'missing'
        ]
    ]);
    exit;
}

// Validate pair_id belongs to this mentor
$verify_query = "SELECT msp.id, msp.student_id, r.name as student_name 
                 FROM mentor_student_pairs msp 
                 JOIN register r ON msp.student_id = r.id 
                 WHERE msp.id = ? AND msp.mentor_id = ? AND msp.status = 'active'";
$verify_stmt = $conn->prepare($verify_query);

if (!$verify_stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'debug' => 'Failed to prepare verify statement: ' . $conn->error]);
    error_log("Schedule session - prepare verify failed: " . $conn->error);
    exit;
}

$verify_stmt->bind_param("ii", $pair_id, $_SESSION['mentor_id']);

if (!$verify_stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'debug' => 'Failed to execute verify: ' . $verify_stmt->error]);
    error_log("Schedule session - execute verify failed: " . $verify_stmt->error);
    exit;
}

$student_info = $verify_stmt->get_result()->fetch_assoc();
$verify_stmt->close();

if (!$student_info) {
    http_response_code(403);
    echo json_encode([
        'error' => 'Invalid pair_id or not your student',
        'debug' => [
            'pair_id' => $pair_id,
            'mentor_id' => $_SESSION['mentor_id']
        ]
    ]);
    error_log("Schedule session - invalid pair_id: $pair_id for mentor: " . $_SESSION['mentor_id']);
    exit;
}

// Validate session_date format and future date
$session_timestamp = strtotime($session_date);
if ($session_timestamp === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format', 'debug' => 'session_date: ' . $session_date]);
    exit;
}

if ($session_timestamp < time()) {
    http_response_code(400);
    echo json_encode(['error' => 'Session date must be in the future', 'debug' => 'session_date: ' . $session_date]);
    exit;
}

// Begin transaction for data integrity
$conn->begin_transaction();

try {
    // Insert session
    $insert_query = "INSERT INTO mentoring_sessions (pair_id, session_date, duration_minutes, notes, meeting_link, status) 
                     VALUES (?, ?, ?, ?, ?, 'scheduled')";
    $stmt = $conn->prepare($insert_query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare insert statement: " . $conn->error);
    }
    
    $stmt->bind_param("isiss", $pair_id, $session_date, $duration, $notes, $meeting_link);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute insert: " . $stmt->error);
    }
    
    $session_id = $stmt->insert_id;
    $stmt->close();
    
    if ($session_id == 0) {
        throw new Exception("Session insert succeeded but no ID returned");
    }

    // Log activity
    $activity_desc = "Scheduled session with " . $student_info['student_name'] . " for " . date('M j, Y g:i A', $session_timestamp);
    $log_stmt = $conn->prepare("INSERT INTO mentor_activity_logs (mentor_id, activity_type, description, student_id, created_at) 
                                VALUES (?, 'session_scheduled', ?, ?, NOW())");
    
    if ($log_stmt) {
        $log_stmt->bind_param("isi", $_SESSION['mentor_id'], $activity_desc, $student_info['student_id']);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    // Create notification for student
    $notif_query = "INSERT INTO user_notifications (user_id, notification_type, title, message, related_id, related_type, action_url, icon, color) 
                   VALUES (?, 'session_scheduled', 'New Session Scheduled', ?, ?, 'session', '/user/sessions.php', 'bi-calendar-check', 'success')";
    $notif_stmt = $conn->prepare($notif_query);
    
    if ($notif_stmt) {
        $notif_message = "Your mentor has scheduled a session for " . date('M j, Y g:i A', $session_timestamp);
        $notif_stmt->bind_param("isi", $student_info['student_id'], $notif_message, $session_id);
        $notif_stmt->execute();
        $notif_stmt->close();
    }

    // Commit transaction
    $conn->commit();
    
    // Log success
    error_log("Schedule session SUCCESS - Session ID: $session_id, Pair ID: $pair_id, Mentor ID: " . $_SESSION['mentor_id']);
    
    echo json_encode([
        'success' => true,
        'session_id' => $session_id,
        'message' => 'Session scheduled successfully',
        'session_date' => date('M j, Y g:i A', $session_timestamp)
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    $error_message = $e->getMessage();
    
    error_log("Schedule session ERROR: " . $error_message);
    
    echo json_encode([
        'error' => 'Failed to schedule session',
        'debug' => $error_message,
        'details' => [
            'pair_id' => $pair_id,
            'session_date' => $session_date,
            'mentor_id' => $_SESSION['mentor_id']
        ]
    ]);
}
