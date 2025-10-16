<?php
/**
 * AJAX Router - Central handler for all AJAX requests
 * Routes requests to appropriate handlers without full page reload
 */

session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../../Login/Login/db.php';

// Get request data
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

// Get user ID
$user_id = $_SESSION['user_id'] ?? null;

try {
    switch ($action) {
        case 'load_projects':
            $response = loadProjects($conn, $_GET);
            break;
            
        case 'load_ideas':
            $response = loadIdeas($conn, $_GET);
            break;
            
        case 'toggle_like':
            $response = toggleLike($conn, $user_id, $_POST);
            break;
            
        case 'toggle_bookmark':
            $response = toggleBookmark($conn, $user_id, $_POST);
            break;
            
        case 'add_comment':
            $response = addComment($conn, $user_id, $_POST);
            break;
            
        case 'delete_comment':
            $response = deleteComment($conn, $user_id, $_POST);
            break;
            
        case 'load_comments':
            $response = loadComments($conn, $_GET);
            break;
            
        case 'search':
            $response = search($conn, $_GET);
            break;
            
        case 'filter_projects':
            $response = filterProjects($conn, $_GET);
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;

// Handler functions

function loadProjects($conn, $params) {
    $page = intval($params['page'] ?? 1);
    $per_page = intval($params['per_page'] ?? 6);
    $offset = ($page - 1) * $per_page;
    
    $sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM project_likes WHERE project_id = p.id) as like_count,
            (SELECT COUNT(*) FROM project_comments WHERE project_id = p.id) as comment_count
            FROM admin_approved_projects p 
            WHERE p.status = 'approved'
            ORDER BY p.submission_date DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $projects = [];
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    
    return [
        'success' => true,
        'projects' => $projects,
        'page' => $page,
        'has_more' => count($projects) === $per_page
    ];
}

function loadIdeas($conn, $params) {
    $page = intval($params['page'] ?? 1);
    $per_page = intval($params['per_page'] ?? 6);
    $offset = ($page - 1) * $per_page;
    
    $sql = "SELECT b.*, 
            (SELECT COUNT(*) FROM idea_likes WHERE idea_id = b.id) as like_count,
            (SELECT COUNT(*) FROM idea_comments WHERE idea_id = b.id) as comment_count
            FROM blog b 
            ORDER BY b.submission_datetime DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ideas = [];
    while ($row = $result->fetch_assoc()) {
        $ideas[] = $row;
    }
    
    return [
        'success' => true,
        'ideas' => $ideas,
        'page' => $page,
        'has_more' => count($ideas) === $per_page
    ];
}

function toggleLike($conn, $user_id, $data) {
    if (!$user_id) {
        return ['success' => false, 'message' => 'Please login to like'];
    }
    
    $project_id = intval($data['project_id'] ?? 0);
    $type = $data['type'] ?? 'project'; // project or idea
    
    if ($type === 'idea') {
        $table = 'idea_likes';
        $id_field = 'idea_id';
    } else {
        $table = 'project_likes';
        $id_field = 'project_id';
    }
    
    // Check if already liked
    $check_sql = "SELECT id FROM $table WHERE $id_field = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $project_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Unlike
        $delete_sql = "DELETE FROM $table WHERE $id_field = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $project_id, $user_id);
        $delete_stmt->execute();
        $liked = false;
    } else {
        // Like
        $insert_sql = "INSERT INTO $table ($id_field, user_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $project_id, $user_id);
        $insert_stmt->execute();
        $liked = true;
    }
    
    // Get updated count
    $count_sql = "SELECT COUNT(*) as count FROM $table WHERE $id_field = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $project_id);
    $count_stmt->execute();
    $count = $count_stmt->get_result()->fetch_assoc()['count'];
    
    return [
        'success' => true,
        'liked' => $liked,
        'count' => $count,
        'message' => $liked ? 'Liked!' : 'Unliked'
    ];
}

function toggleBookmark($conn, $user_id, $data) {
    if (!$user_id) {
        return ['success' => false, 'message' => 'Please login to bookmark'];
    }
    
    $project_id = intval($data['project_id'] ?? 0);
    
    // Check if already bookmarked
    $check_sql = "SELECT id FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $project_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Remove bookmark
        $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $project_id, $user_id);
        $delete_stmt->execute();
        $bookmarked = false;
    } else {
        // Add bookmark
        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, 0)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $project_id, $user_id);
        $insert_stmt->execute();
        $bookmarked = true;
    }
    
    return [
        'success' => true,
        'bookmarked' => $bookmarked,
        'message' => $bookmarked ? 'Bookmarked!' : 'Bookmark removed'
    ];
}

function addComment($conn, $user_id, $data) {
    if (!$user_id) {
        return ['success' => false, 'message' => 'Please login to comment'];
    }
    
    $project_id = intval($data['project_id'] ?? 0);
    $comment_text = trim($data['comment_text'] ?? '');
    $type = $data['type'] ?? 'project';
    
    if (empty($comment_text)) {
        return ['success' => false, 'message' => 'Comment cannot be empty'];
    }
    
    if ($type === 'idea') {
        $table = 'idea_comments';
        $id_field = 'idea_id';
    } else {
        $table = 'project_comments';
        $id_field = 'project_id';
    }
    
    $insert_sql = "INSERT INTO $table ($id_field, user_id, comment) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iis", $project_id, $user_id, $comment_text);
    
    if ($insert_stmt->execute()) {
        // Get updated count
        $count_sql = "SELECT COUNT(*) as count FROM $table WHERE $id_field = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $project_id);
        $count_stmt->execute();
        $count = $count_stmt->get_result()->fetch_assoc()['count'];
        
        return [
            'success' => true,
            'message' => 'Comment added',
            'comment_id' => $conn->insert_id,
            'count' => $count
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to add comment'];
}

function deleteComment($conn, $user_id, $data) {
    if (!$user_id) {
        return ['success' => false, 'message' => 'Please login'];
    }
    
    $comment_id = intval($data['comment_id'] ?? 0);
    $type = $data['type'] ?? 'project';
    
    $table = $type === 'idea' ? 'idea_comments' : 'project_comments';
    
    // Verify ownership
    $check_sql = "SELECT id FROM $table WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $comment_id, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        return ['success' => false, 'message' => 'Unauthorized'];
    }
    
    $delete_sql = "DELETE FROM $table WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $comment_id);
    
    if ($delete_stmt->execute()) {
        return ['success' => true, 'message' => 'Comment deleted'];
    }
    
    return ['success' => false, 'message' => 'Failed to delete comment'];
}

function loadComments($conn, $params) {
    $project_id = intval($params['project_id'] ?? 0);
    $type = $params['type'] ?? 'project';
    
    $table = $type === 'idea' ? 'idea_comments' : 'project_comments';
    $id_field = $type === 'idea' ? 'idea_id' : 'project_id';
    
    $sql = "SELECT c.*, r.name as user_name 
            FROM $table c 
            LEFT JOIN register r ON c.user_id = r.id 
            WHERE c.$id_field = ? 
            ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    
    return [
        'success' => true,
        'comments' => $comments
    ];
}

function search($conn, $params) {
    $query = trim($params['q'] ?? '');
    $type = $params['type'] ?? 'all';
    
    if (empty($query)) {
        return ['success' => false, 'message' => 'Search query required'];
    }
    
    $results = [];
    $search_pattern = "%{$query}%";
    
    if ($type === 'all' || $type === 'projects') {
        $sql = "SELECT 'project' as type, id, project_name as title, description 
                FROM admin_approved_projects 
                WHERE project_name LIKE ? OR description LIKE ? 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $search_pattern, $search_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }
    
    if ($type === 'all' || $type === 'ideas') {
        $sql = "SELECT 'idea' as type, id, project_name as title, description 
                FROM blog 
                WHERE project_name LIKE ? OR description LIKE ? 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $search_pattern, $search_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    }
    
    return [
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ];
}

function filterProjects($conn, $params) {
    $type = $params['type'] ?? '';
    $status = $params['status'] ?? '';
    $classification = $params['classification'] ?? '';
    
    $where = ['1=1'];
    $bind_params = [];
    $bind_types = '';
    
    if ($type) {
        $where[] = 'project_type = ?';
        $bind_params[] = $type;
        $bind_types .= 's';
    }
    
    if ($status) {
        $where[] = 'status = ?';
        $bind_params[] = $status;
        $bind_types .= 's';
    }
    
    if ($classification) {
        $where[] = 'classification = ?';
        $bind_params[] = $classification;
        $bind_types .= 's';
    }
    
    $sql = "SELECT * FROM admin_approved_projects WHERE " . implode(' AND ', $where) . " ORDER BY submission_date DESC LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    if (!empty($bind_params)) {
        $stmt->bind_param($bind_types, ...$bind_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $projects = [];
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    
    return [
        'success' => true,
        'projects' => $projects,
        'count' => count($projects)
    ];
}
