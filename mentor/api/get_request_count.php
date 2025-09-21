<?php

session_start();
require_once '../../Login/Login/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['mentor_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$mentor_id = $_SESSION['mentor_id'];

// Get count of pending requests
$count_query = "SELECT COUNT(*) as count FROM mentor_requests WHERE mentor_id = ? AND status = 'pending'";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $mentor_id);
$count_stmt->execute();
$result = $count_stmt->get_result()->fetch_assoc();

echo json_encode(['count' => $result['count']]);
