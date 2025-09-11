<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$pair_id = $data['pair_id'];
$session_date = $data['session_date'];

$query = "INSERT INTO mentoring_sessions (pair_id, session_date) VALUES (?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $pair_id, $session_date);
$stmt->execute();

echo json_encode(['success' => true]);
?>