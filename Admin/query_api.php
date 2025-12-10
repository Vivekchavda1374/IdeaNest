<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../Login/Login/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'get_all_queries':
            getAllQueries($conn);
            break;
        
        case 'get_stats':
            getStats($conn);
            break;
        
        case 'update_query':
            updateQuery($conn);
            break;
        
        case 'save_response':
            saveResponse($conn);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Query API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

function getAllQueries($conn) {
    $status = $_GET['status'] ?? '';
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $query = "SELECT q.*, r.name as user_name, r.email as user_email 
              FROM user_queries q 
              LEFT JOIN register r ON q.user_id = r.id 
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($status)) {
        $query .= " AND q.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($category)) {
        $query .= " AND q.category = ?";
        $params[] = $category;
        $types .= 's';
    }
    
    if (!empty($search)) {
        $query .= " AND (q.subject LIKE ? OR q.description LIKE ? OR r.name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }
    
    $query .= " ORDER BY q.created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
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

function getStats($conn) {
    $stats = [
        'pending' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'total' => 0
    ];
    
    $result = $conn->query("SELECT status, COUNT(*) as count FROM user_queries GROUP BY status");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = str_replace('-', '_', $row['status']);
            $stats[$status] = $row['count'];
            $stats['total'] += $row['count'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

function updateQuery($conn) {
    $query_id = $_POST['query_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if (empty($query_id) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE user_queries SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $query_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Query updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update query']);
    }
    
    $stmt->close();
}

function saveResponse($conn) {
    $query_id = $_POST['query_id'] ?? 0;
    $response = trim($_POST['response'] ?? '');
    
    if (empty($query_id) || empty($response)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE user_queries SET admin_response = ?, status = 'in-progress' WHERE id = ?");
    $stmt->bind_param("si", $response, $query_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Response sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send response']);
    }
    
    $stmt->close();
}
?>
