<?php
require_once __DIR__ . '/includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pair_id = $input['pair_id'] ?? null;
$rating = $input['rating'] ?? null;
$feedback = $input['feedback'] ?? '';

if (!$pair_id || !isset($rating) || $rating < 1 || $rating > 5) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE mentor_student_pairs SET status = 'completed', rating = ?, feedback = ?, completed_at = NOW() WHERE id = ?");
    $stmt->bind_param("isi", $rating, $feedback, $pair_id);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to complete pairing']);
}
