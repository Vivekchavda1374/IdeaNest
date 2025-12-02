<?php
/**
 * Get My Projects API - Retrieve user's projects for sharing
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
            project_category,
            difficulty_level,
            image_path,
            submission_date,
            status
        FROM projects
        WHERE user_id = ?
        ORDER BY submission_date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $projects = [];
    while ($row = $result->fetch_assoc()) {
        $projects[] = [
            'id' => $row['id'],
            'project_name' => $row['project_name'],
            'description' => $row['description'],
            'classification' => $row['classification'],
            'project_type' => $row['project_type'],
            'project_category' => $row['project_category'],
            'difficulty_level' => $row['difficulty_level'],
            'image_path' => $row['image_path'],
            'submission_date' => $row['submission_date'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'projects' => $projects
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
