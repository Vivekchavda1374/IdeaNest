<?php
require_once __DIR__ . '/includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['mentor_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$mentor_id = $_SESSION['mentor_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    $update_query = "UPDATE mentoring_sessions ms 
                     JOIN mentor_student_pairs msp ON ms.pair_id = msp.id 
                     SET ms.status = ? 
                     WHERE ms.id = ? AND msp.mentor_id = ?";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sii", $input['status'], $input['id'], $mentor_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
