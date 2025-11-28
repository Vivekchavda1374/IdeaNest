<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

try {
    $stmt = $conn->prepare("SELECT COUNT(*) as new_count FROM realtime_notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode(['new_count' => $result['new_count'] ?? 0]);
} catch (Exception $e) {
    echo json_encode(['new_count' => 0]);
}
