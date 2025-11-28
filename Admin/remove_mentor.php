<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mentorId = $input['mentor_id'] ?? null;

if (!$mentorId) {
    echo json_encode(['success' => false, 'error' => 'Mentor ID is required']);
    exit;
}

try {
    $conn->begin_transaction();

    // Get mentor details before deletion
    $stmt = $conn->prepare("SELECT r.name, r.email FROM register r WHERE r.id = ? AND r.role = 'mentor'");
    $stmt->bind_param("i", $mentorId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Mentor not found');
    }

    $mentor = $result->fetch_assoc();

    // Delete from mentors table first (due to foreign key constraints)
    $stmt = $conn->prepare("DELETE FROM mentors WHERE user_id = ?");
    $stmt->bind_param("i", $mentorId);
    $stmt->execute();

    // Update user role back to student
    $stmt = $conn->prepare("UPDATE register SET role = 'student' WHERE id = ?");
    $stmt->bind_param("i", $mentorId);
    $stmt->execute();

    // Log the removal
    $stmt = $conn->prepare("INSERT INTO admin_logs (action, details, admin_id) VALUES (?, ?, ?)");
    $action = 'mentor_removed';
    $details = "Removed mentor: {$mentor['name']} ({$mentor['email']})";
    $adminId = $_SESSION['admin_id'] ?? null;
    $stmt->bind_param("ssi", $action, $details, $adminId);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Mentor removed successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
