<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Catch any output before JSON
ob_start();

try {
    require_once __DIR__ . '/../includes/security_init.php';
    require_once '../Login/Login/db.php';
    require_once '../includes/csrf.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clean any output buffer
    ob_end_clean();
    
    header('Content-Type: application/json');
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Initialization error: ' . $e->getMessage()
    ]);
    exit;
}

// Get user info
$user_id = $_SESSION['user_id'] ?? null;
$session_id = $user_id ?? session_id();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$project_id = intval($input['project_id'] ?? 0);

// Actions that require login
$login_required_actions = ['toggle_like', 'toggle_bookmark'];

// Check if user is logged in for actions that require it
if (in_array($action, $login_required_actions) && !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Please login to perform this action']);
    exit;
}

// Verify CSRF token for actions that require it
if (in_array($action, $login_required_actions)) {
    if (!isset($input['csrf_token']) || !verifyCSRF($input['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
}

// Handle different actions
try {
    switch ($action) {
        case 'toggle_like':
            handleToggleLike($conn, $user_id, $project_id);
            break;
            
        case 'toggle_bookmark':
            handleToggleBookmark($conn, $user_id, $session_id, $project_id);
            break;
            
        case 'load_projects':
            handleLoadProjects($conn, $user_id, $session_id, $input);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    error_log("AJAX Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

/**
 * Toggle Like
 */
function handleToggleLike($conn, $user_id, $project_id) {
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        return;
    }
    
    // Check if like exists
    $check_stmt = $conn->prepare("SELECT id FROM project_likes WHERE project_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $project_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $exists = $result->num_rows > 0;
    $check_stmt->close();
    
    if ($exists) {
        // Unlike
        $stmt = $conn->prepare("DELETE FROM project_likes WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $project_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $liked = false;
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO project_likes (project_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $project_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $liked = true;
    }
    
    // Get total likes
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM project_likes WHERE project_id = ?");
    $count_stmt->bind_param("i", $project_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_likes = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'total_likes' => $total_likes,
        'new_token' => generateCSRFToken()
    ]);
}

/**
 * Toggle Bookmark
 */
function handleToggleBookmark($conn, $user_id, $session_id, $project_id) {
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        return;
    }
    
    // Check if bookmark exists
    $check_stmt = $conn->prepare("SELECT id FROM bookmark WHERE project_id = ? AND user_id = ?");
    $check_stmt->bind_param("is", $project_id, $session_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $exists = $result->num_rows > 0;
    $check_stmt->close();
    
    if ($exists) {
        // Remove bookmark
        $stmt = $conn->prepare("DELETE FROM bookmark WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("is", $project_id, $session_id);
        $stmt->execute();
        $stmt->close();
        $bookmarked = false;
    } else {
        // Add bookmark
        $idea_id = 0;
        $stmt = $conn->prepare("INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $project_id, $session_id, $idea_id);
        $stmt->execute();
        $stmt->close();
        $bookmarked = true;
    }
    
    echo json_encode([
        'success' => true,
        'bookmarked' => $bookmarked,
        'new_token' => generateCSRFToken()
    ]);
}

/**
 * Load Projects with Filters (AJAX Pagination & Filtering)
 */
function handleLoadProjects($conn, $user_id, $session_id, $input) {
    try {
        // Get filter parameters
        $search = $input['search'] ?? '';
        $filter_classification = $input['classification'] ?? '';
        $filter_type = $input['type'] ?? '';
        $filter_difficulty = $input['difficulty'] ?? '';
        $filter_dev_time = $input['dev_time'] ?? '';
        $filter_team_size = $input['team_size'] ?? '';
        $filter_license = $input['license'] ?? '';
        $filter_language = $input['language'] ?? '';
        $view_filter = $input['view'] ?? 'all';
        $page = max(1, intval($input['page'] ?? 1));
        
        $projects_per_page = 9;
        $offset = ($page - 1) * $projects_per_page;
        
        $show_only_owned = ($view_filter === 'owned');
        $show_only_bookmarked = ($view_filter === 'bookmarked');
        $show_only_following = ($view_filter === 'following');
    
    // Build query
    if ($user_id) {
        $sql = "SELECT ap.*, 
                   r.name as user_name,
                   CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked,
                   CASE WHEN pl.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked,
                   COALESCE(like_counts.total_likes, 0) AS total_likes
            FROM admin_approved_projects ap
            LEFT JOIN register r ON ap.user_id = r.id
            LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?
            LEFT JOIN project_likes pl ON ap.id = pl.project_id AND pl.user_id = ?
            LEFT JOIN user_follows uf ON CAST(ap.user_id AS UNSIGNED) = uf.following_id AND uf.follower_id = ?
            LEFT JOIN (
                SELECT project_id, COUNT(*) as total_likes 
                FROM project_likes 
                GROUP BY project_id
            ) like_counts ON ap.id = like_counts.project_id
            WHERE 1=1";
        
        $params = [$session_id, $user_id, $user_id];
        $types = "sii";
    } else {
        // Guest user - simpler query
        $sql = "SELECT ap.*, 
                   r.name as user_name,
                   0 AS is_bookmarked,
                   0 AS is_liked,
                   COALESCE(like_counts.total_likes, 0) AS total_likes
            FROM admin_approved_projects ap
            LEFT JOIN register r ON ap.user_id = r.id
            LEFT JOIN (
                SELECT project_id, COUNT(*) as total_likes 
                FROM project_likes 
                GROUP BY project_id
            ) like_counts ON ap.id = like_counts.project_id
            WHERE 1=1";
        
        $params = [];
        $types = "";
    }
    
    // Apply view filters (only if user is logged in)
    if ($user_id) {
        if ($show_only_owned) {
            $sql .= " AND ap.user_id = ?";
            $params[] = $user_id;
            $types .= "i";
        } elseif ($show_only_bookmarked) {
            $sql .= " AND b.project_id IS NOT NULL";
        } elseif ($show_only_following) {
            $sql .= " AND uf.follower_id IS NOT NULL";
        }
    }
    
    // Apply other filters
    if ($search !== '') {
        $sql .= " AND (ap.project_name LIKE ? OR ap.description LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    if ($filter_classification !== '') {
        $sql .= " AND ap.classification = ?";
        $params[] = $filter_classification;
        $types .= "s";
    }
    
    if ($filter_type !== '') {
        $sql .= " AND ap.project_type = ?";
        $params[] = $filter_type;
        $types .= "s";
    }
    
    if ($filter_difficulty !== '') {
        $sql .= " AND ap.difficulty_level = ?";
        $params[] = $filter_difficulty;
        $types .= "s";
    }
    
    if ($filter_dev_time !== '') {
        $sql .= " AND ap.development_time = ?";
        $params[] = $filter_dev_time;
        $types .= "s";
    }
    
    if ($filter_team_size !== '') {
        $sql .= " AND ap.team_size = ?";
        $params[] = $filter_team_size;
        $types .= "s";
    }
    
    if ($filter_license !== '') {
        $sql .= " AND ap.project_license = ?";
        $params[] = $filter_license;
        $types .= "s";
    }
    
    if ($filter_language !== '') {
        $sql .= " AND ap.language LIKE ?";
        $lang_param = "%$filter_language%";
        $params[] = $lang_param;
        $types .= "s";
    }
    
    $sql .= " ORDER BY ap.submission_date DESC LIMIT ? OFFSET ?";
    $params[] = $projects_per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $projects = [];
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    $stmt->close();
    
    // Get total count for pagination - build separate count query
    $count_sql = "SELECT COUNT(DISTINCT ap.id) as total FROM admin_approved_projects ap";
    
    if ($user_id) {
        $count_sql .= " LEFT JOIN register r ON ap.user_id = r.id";
        $count_sql .= " LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?";
        $count_sql .= " LEFT JOIN user_follows uf ON CAST(ap.user_id AS UNSIGNED) = uf.following_id AND uf.follower_id = ?";
    } else {
        $count_sql .= " LEFT JOIN register r ON ap.user_id = r.id";
    }
    
    $count_sql .= " WHERE 1=1";
    
    // Apply same filters to count query
    $count_params = [];
    $count_types = "";
    
    if ($user_id) {
        $count_params[] = $session_id;
        $count_params[] = $user_id;
        $count_types .= "si";
        
        if ($show_only_owned) {
            $count_sql .= " AND ap.user_id = ?";
            $count_params[] = $user_id;
            $count_types .= "i";
        } elseif ($show_only_bookmarked) {
            $count_sql .= " AND b.project_id IS NOT NULL";
        } elseif ($show_only_following) {
            $count_sql .= " AND uf.follower_id IS NOT NULL";
        }
    }
    
    if ($search !== '') {
        $count_sql .= " AND (ap.project_name LIKE ? OR ap.description LIKE ?)";
        $search_param = "%$search%";
        $count_params[] = $search_param;
        $count_params[] = $search_param;
        $count_types .= "ss";
    }
    
    if ($filter_classification !== '') {
        $count_sql .= " AND ap.classification = ?";
        $count_params[] = $filter_classification;
        $count_types .= "s";
    }
    
    if ($filter_type !== '') {
        $count_sql .= " AND ap.project_type = ?";
        $count_params[] = $filter_type;
        $count_types .= "s";
    }
    
    if ($filter_difficulty !== '') {
        $count_sql .= " AND ap.difficulty_level = ?";
        $count_params[] = $filter_difficulty;
        $count_types .= "s";
    }
    
    if ($filter_dev_time !== '') {
        $count_sql .= " AND ap.development_time = ?";
        $count_params[] = $filter_dev_time;
        $count_types .= "s";
    }
    
    if ($filter_team_size !== '') {
        $count_sql .= " AND ap.team_size = ?";
        $count_params[] = $filter_team_size;
        $count_types .= "s";
    }
    
    if ($filter_license !== '') {
        $count_sql .= " AND ap.project_license = ?";
        $count_params[] = $filter_license;
        $count_types .= "s";
    }
    
    if ($filter_language !== '') {
        $count_sql .= " AND ap.language LIKE ?";
        $lang_param = "%$filter_language%";
        $count_params[] = $lang_param;
        $count_types .= "s";
    }
    
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    $count_stmt->execute();
    $total_projects = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
    
    $total_pages = ceil($total_projects / $projects_per_page);
    
    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'total_projects' => $total_projects,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'new_token' => generateCSRFToken()
    ]);
    
    } catch (Exception $e) {
        error_log("Error loading projects: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error loading projects: ' . $e->getMessage()
        ]);
    }
}
?>
