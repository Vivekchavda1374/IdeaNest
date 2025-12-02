<?php
/**
 * Get My Ideas API - Retrieve user's ideas for sharing
 */

session_start();
require_once '../../Login/Login/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            id,
            project_name,
            description,
            classification,
            project_type,
            submission_datetime,
            status
        FROM blog
        WHERE user_id = ?
        ORDER BY submission_datetime DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ideas = [];
    while ($row = $result->fetch_assoc()) {
        $ideas[] = [
            'id' => $row['id'],
            'project_name' => $row['project_name'],
            'description' => $row['description'],
            'classification' => $row['classification'],
            'project_type' => $row['project_type'],
            'submission_datetime' => $row['submission_datetime'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'ideas' => $ideas
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
