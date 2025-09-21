<?php

session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$mentor_id = $_SESSION['mentor_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (!$current_password || !$new_password) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM register WHERE id = ?");
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!password_verify($current_password, $result['password'])) {
        echo json_encode(['error' => 'Current password is incorrect']);
        exit;
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE register SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $mentor_id);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to change password']);
}
