<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$pair_id = $data['pair_id'];
$rating = $data['rating'];
$feedback = $data['feedback'];
$mentor_id = $_SESSION['mentor_id'];

// Update pairing
$query = "UPDATE mentor_student_pairs SET status = 'completed', completed_at = NOW(), rating = ?, feedback = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("isi", $rating, $feedback, $pair_id);
$stmt->execute();

// Update mentor student count
$update_query = "UPDATE mentors SET current_students = current_students - 1 WHERE user_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>