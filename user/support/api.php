<?php
require_once __DIR__ . '/../../includes/security_init.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../Login/Login/db.php';

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'submit_query':
            submitQuery($conn, $user_id);
            break;
        
        case 'get_queries':
            getQueries($conn, $user_id);
            break;
        
        case 'update_query':
            updateQuery($conn, $user_id);
            break;
        
        case 'delete_query':
            deleteQuery($conn, $user_id);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Support API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

function submitQuery($conn, $user_id) {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($subject) || empty($category) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate category
    $valid_categories = ['technical', 'account', 'feature', 'bug', 'other'];
    if (!in_array($category, $valid_categories)) {
        echo json_encode(['success' => false, 'message' => 'Invalid category']);
        return;
    }
    
    // Create table if not exists
    $create_table = "CREATE TABLE IF NOT EXISTS user_queries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        category VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        admin_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    
    if (!$conn->query($create_table)) {
        error_log("Failed to create user_queries table: " . $conn->error);
    }
    
    // Insert query
    $stmt = $conn->prepare("INSERT INTO user_queries (user_id, subject, category, description, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isss", $user_id, $subject, $category, $description);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Query submitted successfully',
            'query_id' => $stmt->insert_id
        ]);
    } else {
        error_log("Failed to insert query: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to submit query']);
    }
    
    $stmt->close();
}

function getQueries($conn, $user_id) {
    // Create table if not exists
    $create_table = "CREATE TABLE IF NOT EXISTS user_queries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        category VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        admin_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    
    if (!$conn->query($create_table)) {
        error_log("Failed to create user_queries table: " . $conn->error);
    }
    
    $stmt = $conn->prepare("SELECT id, subject, category, description, status, admin_response, created_at, updated_at FROM user_queries WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $queries = [];
    while ($row = $result->fetch_assoc()) {
        $queries[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'queries' => $queries
    ]);
    
    $stmt->close();
}

function updateQuery($conn, $user_id) {
    $query_id = intval($_POST['query_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($query_id) || empty($subject) || empty($category) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate category
    $valid_categories = ['technical', 'account', 'feature', 'bug', 'other'];
    if (!in_array($category, $valid_categories)) {
        echo json_encode(['success' => false, 'message' => 'Invalid category']);
        return;
    }
    
    // Check if query belongs to user and can be edited
    $check_stmt = $conn->prepare("SELECT status FROM user_queries WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $query_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Query not found or access denied']);
        $check_stmt->close();
        return;
    }
    
    $row = $result->fetch_assoc();
    $status = $row['status'];
    $check_stmt->close();
    
    // Only allow editing if status is pending or in-progress
    if ($status !== 'pending' && $status !== 'in-progress') {
        echo json_encode(['success' => false, 'message' => 'Cannot edit queries that are resolved or closed']);
        return;
    }
    
    // Update query
    $stmt = $conn->prepare("UPDATE user_queries SET subject = ?, category = ?, description = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssii", $subject, $category, $description, $query_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Query updated successfully'
        ]);
    } else {
        error_log("Failed to update query: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update query']);
    }
    
    $stmt->close();
}

function deleteQuery($conn, $user_id) {
    $query_id = intval($_POST['query_id'] ?? 0);
    
    if (empty($query_id)) {
        echo json_encode(['success' => false, 'message' => 'Query ID is required']);
        return;
    }
    
    // Check if query belongs to user
    $check_stmt = $conn->prepare("SELECT id FROM user_queries WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $query_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Query not found or access denied']);
        $check_stmt->close();
        return;
    }
    $check_stmt->close();
    
    // Delete query
    $stmt = $conn->prepare("DELETE FROM user_queries WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $query_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Query deleted successfully'
        ]);
    } else {
        error_log("Failed to delete query: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to delete query']);
    }
    
    $stmt->close();
}
?>
