<?php
require_once __DIR__ . '/../../includes/security_init.php';
session_start();
require_once '../../Login/Login/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

try {
    $activities = [];

    // Get mentor sessions
    $sessions_query = "
        SELECT 
            'session_scheduled' as activity_type,
            ms.session_date as activity_date,
            CONCAT('Session scheduled with ', r.name) as activity_description,
            r.name as mentor_name,
            ms.notes as details,
            ms.status,
            ms.id as activity_id
        FROM mentoring_sessions ms
        JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
        JOIN register r ON msp.mentor_id = r.id
        WHERE msp.student_id = ?
        ORDER BY ms.session_date DESC
        LIMIT 10
    ";

    $stmt = $conn->prepare($sessions_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $activities = array_merge($activities, $sessions);

    // Get mentor requests
    $requests_query = "
        SELECT 
            'request_response' as activity_type,
            mr.updated_at as activity_date,
            CONCAT('Mentor request ', mr.status, ' by ', r.name) as activity_description,
            r.name as mentor_name,
            mr.message as details,
            mr.status,
            mr.id as activity_id
        FROM mentor_requests mr
        JOIN register r ON mr.mentor_id = r.id
        WHERE mr.student_id = ?
        ORDER BY mr.updated_at DESC
        LIMIT 10
    ";

    $stmt = $conn->prepare($requests_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $activities = array_merge($activities, $requests);

    // Sort all activities by date
    usort($activities, function ($a, $b) {
        return strtotime($b['activity_date']) - strtotime($a['activity_date']);
    });

    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'count' => count($activities)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
