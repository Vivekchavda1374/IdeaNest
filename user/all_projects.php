<?php
require_once __DIR__ . '/../includes/security_init.php';
// user/all_projects.php - Display all approved projects
// Production-safe error reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}                                                                                                                                                                                                                                                                       

$basePath = './';
include '../Login/Login/db.php';
require_once '../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Guest User";
$session_id = $user_id ?? session_id();

// Handle like toggle
if (isset($_POST['toggle_like']) && isset($_POST['project_id']) && $user_id) {
    requireCSRF();
    $project_id = intval($_POST['project_id']);

    // Check if like already exists
    $check_like_sql = "SELECT * FROM project_likes WHERE project_id = ? AND user_id = ?";
    $check_like_stmt = $conn->prepare($check_like_sql);
    $check_like_stmt->bind_param("ii", $project_id, $user_id);
    $check_like_stmt->execute();
    $like_result = $check_like_stmt->get_result();

    if ($like_result->num_rows > 0) {
        // Remove like
        $delete_like_sql = "DELETE FROM project_likes WHERE project_id = ? AND user_id = ?";
        $delete_like_stmt = $conn->prepare($delete_like_sql);
        $delete_like_stmt->bind_param("ii", $project_id, $user_id);
        $delete_like_stmt->execute();
        $delete_like_stmt->close();
        $like_message = '<div class="alert alert-info">Like removed!</div>';
    } else {
        // Add like
        $insert_like_sql = "INSERT INTO project_likes (project_id, user_id) VALUES (?, ?)";
        $insert_like_stmt = $conn->prepare($insert_like_sql);
        $insert_like_stmt->bind_param("ii", $project_id, $user_id);
        $insert_like_stmt->execute();
        $insert_like_stmt->close();
        $like_message = '<div class="alert alert-success">Project liked!</div>';
    }
    $check_like_stmt->close();
    
    header("Location: all_projects.php?page=" . ($_GET['page'] ?? 1));
    exit();
}

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark']) && isset($_POST['project_id']) && $user_id) {
    requireCSRF();
    $project_id = intval($_POST['project_id']);

    $check_sql = "SELECT * FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $project_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $project_id, $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        $bookmark_message = '<div class="alert alert-info">Bookmark removed!</div>';
    } else {
        $idea_id = 0;
        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $project_id, $user_id, $idea_id);
        $insert_stmt->execute();
        $insert_stmt->close();
        $bookmark_message = '<div class="alert alert-success">Project bookmarked!</div>';
    }
    $check_stmt->close();
    
    header("Location: all_projects.php?page=" . ($_GET['page'] ?? 1));
    exit();
}

// Create temp ownership table
$create_temp_ownership = "CREATE TABLE IF NOT EXISTS temp_project_ownership (
    project_id INT NOT NULL,
    user_session VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (project_id, user_session),
    INDEX idx_session (user_session)
)";
$conn->query($create_temp_ownership);

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_classification = isset($_GET['classification']) ? trim($_GET['classification']) : '';
$filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';
$filter_difficulty = isset($_GET['difficulty']) ? trim($_GET['difficulty']) : '';
$filter_dev_time = isset($_GET['dev_time']) ? trim($_GET['dev_time']) : '';
$filter_team_size = isset($_GET['team_size']) ? trim($_GET['team_size']) : '';
$filter_license = isset($_GET['license']) ? trim($_GET['license']) : '';
$filter_language = isset($_GET['language']) ? trim($_GET['language']) : '';
$view_filter = isset($_GET['view']) ? trim($_GET['view']) : 'all';
$show_only_owned = ($view_filter === 'owned');
$show_only_bookmarked = ($view_filter === 'bookmarked');
$show_only_following = ($view_filter === 'following');

// Pagination settings
$projects_per_page = 9;
$current_page_num = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page_num - 1) * $projects_per_page;

// Build count query
$count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects ap";
$count_joins = "";
$count_conditions = " WHERE 1=1";
$count_params = [];
$count_types = "";

if ($show_only_owned) {
    $count_joins .= " INNER JOIN temp_project_ownership tpo ON ap.id = tpo.project_id AND tpo.user_session = ?";
    $count_params[] = $session_id;
    $count_types .= "s";
} elseif ($show_only_bookmarked) {
    $count_joins .= " INNER JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?";
    $count_params[] = $session_id;
    $count_types .= "s";
} elseif ($show_only_following && $user_id) {
    $count_joins .= " INNER JOIN user_follows uf ON CAST(ap.user_id AS UNSIGNED) = uf.following_id AND uf.follower_id = ?";
    $count_params[] = $user_id;
    $count_types .= "i";
}

$count_sql .= $count_joins . $count_conditions;

if ($search !== '') {
    $count_sql .= " AND (ap.project_name LIKE ? OR ap.description LIKE ? OR ap.classification LIKE ? OR ap.project_type LIKE ? OR ap.language LIKE ?)";
    $search_param = "%$search%";
    $count_params = array_merge($count_params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $count_types .= "sssss";
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
    $count_sql .= " AND LOWER(ap.language) LIKE LOWER(?)";
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

// Main query - Updated to fetch ALL fields from admin_approved_projects with user details
$sql = "SELECT ap.*, 
               r.name as user_name, r.email as user_email, r.phone_no as user_phone, 
               r.about as user_bio, r.department as user_department,
               CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked,
               CASE WHEN tpo.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_owner,
               CASE WHEN pl.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked,
               COALESCE(like_counts.total_likes, 0) AS total_likes,
               COALESCE(user_stats.user_total_projects, 0) AS user_total_projects,
               COALESCE(user_stats.user_approved_projects, 0) AS user_approved_projects
        FROM admin_approved_projects ap
        LEFT JOIN register r ON ap.user_id = r.id
        LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?
        LEFT JOIN temp_project_ownership tpo ON ap.id = tpo.project_id AND tpo.user_session = ?
        LEFT JOIN project_likes pl ON ap.id = pl.project_id AND pl.user_id = ?
        LEFT JOIN user_follows uf ON CAST(ap.user_id AS UNSIGNED) = uf.following_id AND uf.follower_id = ?
        LEFT JOIN (
            SELECT project_id, COUNT(*) as total_likes 
            FROM project_likes 
            GROUP BY project_id
        ) like_counts ON ap.id = like_counts.project_id
        LEFT JOIN (
            SELECT user_id, 
                   COUNT(*) as user_total_projects,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as user_approved_projects
            FROM admin_approved_projects
            GROUP BY user_id
        ) user_stats ON ap.user_id = user_stats.user_id";

$main_conditions = " WHERE 1=1";
$params = [$session_id, $session_id, $session_id, $user_id];
$types = "sssi";

// Add view filter conditions
if ($show_only_owned) {
    $main_conditions .= " AND tpo.project_id IS NOT NULL";
} elseif ($show_only_bookmarked) {
    $main_conditions .= " AND b.project_id IS NOT NULL";
} elseif ($show_only_following && $user_id) {
    $main_conditions .= " AND uf.follower_id IS NOT NULL";
}

$sql .= $main_conditions;

if ($search !== '') {
    $sql .= " AND (ap.project_name LIKE ? OR ap.description LIKE ? OR ap.classification LIKE ? OR ap.project_type LIKE ? OR ap.language LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= "sssss";
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
    $sql .= " AND LOWER(ap.language) LIKE LOWER(?)";
    $lang_param = "%$filter_language%";
    $params[] = $lang_param;
    $types .= "s";
}
$sql .= " ORDER BY ap.submission_date DESC LIMIT ? OFFSET ?";
$params[] = $projects_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Database query preparation failed. Please try again later.");
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}
$stmt->close();

// Demo ownership setup
if (!empty($projects)) {
    $demo_ownership_sql = "INSERT IGNORE INTO temp_project_ownership (project_id, user_session) VALUES ";
    $demo_values = [];
    $demo_params = [];
    $demo_types = "";

    foreach ($projects as $index => $project) {
        if (($index + 1) % 3 == 0) {
            $demo_values[] = "(?, ?)";
            $demo_params[] = $project['id'];
            $demo_params[] = $session_id;
            $demo_types .= "is";
        }
    }

    if (!empty($demo_values)) {
        $demo_ownership_sql .= implode(", ", $demo_values);
        $demo_stmt = $conn->prepare($demo_ownership_sql);
        $demo_stmt->bind_param($demo_types, ...$demo_params);
        $demo_stmt->execute();
        $demo_stmt->close();

        // Re-fetch projects with updated ownership
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $projects = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
        }
        $stmt->close();
    }
}

// Get counts for filter buttons
$owned_count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects ap 
                    INNER JOIN temp_project_ownership tpo ON ap.id = tpo.project_id AND tpo.user_session = ?";
$owned_count_stmt = $conn->prepare($owned_count_sql);
$owned_count_stmt->bind_param("s", $session_id);
$owned_count_stmt->execute();
$owned_count = $owned_count_stmt->get_result()->fetch_assoc()['total'];
$owned_count_stmt->close();

$bookmarked_count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects ap 
                         INNER JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?";
$bookmarked_count_stmt = $conn->prepare($bookmarked_count_sql);
$bookmarked_count_stmt->bind_param("s", $session_id);
$bookmarked_count_stmt->execute();
$bookmarked_count = $bookmarked_count_stmt->get_result()->fetch_assoc()['total'];
$bookmarked_count_stmt->close();

// Get following count
$following_count = 0;
if ($user_id) {
    $following_count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects ap 
                           INNER JOIN user_follows uf ON CAST(ap.user_id AS UNSIGNED) = uf.following_id 
                           WHERE uf.follower_id = ?";
    $following_count_stmt = $conn->prepare($following_count_sql);
    $following_count_stmt->bind_param("i", $user_id);
    $following_count_stmt->execute();
    $following_count = $following_count_stmt->get_result()->fetch_assoc()['total'];
    $following_count_stmt->close();
}

$all_count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects";
$all_count_stmt = $conn->prepare($all_count_sql);
$all_count_stmt->execute();
$all_count = $all_count_stmt->get_result()->fetch_assoc()['total'];
$all_count_stmt->close();


function formatDifficultyLevel($level)
{
    $levels = [
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            'expert' => 'Expert'
    ];
    return isset($levels[$level]) ? $levels[$level] : ucfirst($level);
}

// Helper function to get file extension icon
function getFileIcon($filePath)
{
    if (empty($filePath)) {
        return 'fas fa-file';
    }

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $icons = [
            'zip' => 'fas fa-file-archive',
            'rar' => 'fas fa-file-archive',
            '7z' => 'fas fa-file-archive',
            'pdf' => 'fas fa-file-pdf',
            'doc' => 'fas fa-file-word',
            'docx' => 'fas fa-file-word',
            'ppt' => 'fas fa-file-powerpoint',
            'pptx' => 'fas fa-file-powerpoint',
            'jpg' => 'fas fa-file-image',
            'jpeg' => 'fas fa-file-image',
            'png' => 'fas fa-file-image',
            'gif' => 'fas fa-file-image',
            'mp4' => 'fas fa-file-video',
            'avi' => 'fas fa-file-video',
            'mov' => 'fas fa-file-video'
    ];

    return isset($icons[$extension]) ? $icons[$extension] : 'fas fa-file';
}

$user_initial = !empty($user_name) ? strtoupper(substr($user_name, 0, 1)) : "G";
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../assets/js/anti_injection.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>All Approved Projects - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --accent-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --success-color: #10b981;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            --gradient-accent: linear-gradient(135deg, var(--accent-color), #34d399);
            --gradient-warm: linear-gradient(135deg, var(--warning-color), #fbbf24);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            font-size: 14px;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            background: var(--bg-secondary);
            transition: margin-left 0.3s ease;
        }

        .projects-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .projects-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .projects-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 2.25rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .projects-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Loader Styles */
        .loader-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 99999;
            justify-content: center;
            align-items: center;
        }

        .loader {
            text-align: center;
            color: white;
        }

        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .loader-text {
            font-size: 16px;
            font-weight: 500;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .view-filter-buttons {
            margin-bottom: 2rem;
        }

        .filter-btn-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.75rem;
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 1rem;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: var(--shadow-sm);
        }

        .filter-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        }

        .filter-btn.active {
            background: var(--gradient-primary);
            border-color: var(--primary-color);
            color: white;
            box-shadow: var(--shadow-lg);
        }

        .filter-btn-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .filter-form {
            background: var(--bg-primary);
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .projects-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 0.5rem;
        }

        .project-card {
            background: var(--bg-primary);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(139, 92, 246, 0.08);
            position: relative;
            transform: translateY(0);
        }

        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #8b5cf6, #a78bfa, #c084fc);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .project-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 25px 60px rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .project-card:hover::before {
            transform: scaleX(1);
        }

        .project-card-content {
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .project-card-header {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            line-height: 1.3;
            transition: color 0.3s ease;
        }

        .project-card:hover .card-title {
            color: var(--primary-color);
        }

        .badge-owner {
            background: linear-gradient(45deg, #ffd700, #ffed4a);
            color: #856404;
            font-size: 0.75rem;
            padding: 0.4rem 0.8rem;
            border-radius: 1rem;
            margin-left: 0.75rem;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }

        .project-badges {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .project-badge {
            padding: 0.6rem 1.2rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .project-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .badge-classification {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(14, 165, 233, 0.1));
            color: var(--info-color);
            border: 1px solid rgba(6, 182, 212, 0.2);
        }

        .badge-type {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(168, 85, 247, 0.1));
            color: var(--secondary-color);
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .social-stats {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            color: #64748b;
            padding: 1rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 1rem;
            border: 1px solid rgba(139, 92, 246, 0.05);
        }

        .stat-item-social {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .stat-item-social i {
            color: var(--primary-color);
            font-size: 1rem;
        }

        .project-description {
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            flex: 1;
        }

        .project-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 1.5rem;
            border-top: 2px solid #f1f5f9;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border: 2px solid var(--border-color);
            background: var(--bg-primary);
            border-radius: 1rem;
            color: var(--text-secondary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.2);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.05));
        }

        .action-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .action-btn:active {
            transform: scale(0.95);
        }

        .like-btn.liked {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(248, 113, 113, 0.1));
            border-color: var(--danger-color);
            color: var(--danger-color);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .bookmark-btn.bookmarked {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.1));
            border-color: var(--warning-color);
            color: var(--warning-color);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }

        .view-details-btn {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            border-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }

        .view-details-btn:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }




        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--bg-primary);
            border-radius: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #64748b;
            margin-bottom: 1rem;
        }

        .pagination-container {
            background: var(--bg-primary);
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .pagination-stats {
            color: #64748b;
            font-size: 0.9rem;
        }













        @media (max-width: 1200px) {
            .projects-list {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 1.5rem;
            }
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }

            .projects-list {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .projects-header {
                padding: 2rem;
            }

            .projects-header h2 {
                font-size: 1.75rem;
            }

            .projects-list {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                padding: 0;
            }

            .project-card {
                border-radius: 16px;
            }

            .project-card-content {
                padding: 1.5rem;
            }

            .card-title {
                font-size: 1.3rem;
            }



            .social-stats {
                flex-direction: column;
                gap: 0.75rem;
                padding: 0.75rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }

            .action-btn {
                justify-content: center;
                text-align: center;
            }

            .filter-btn-group {
                flex-direction: column;
            }



            .filter-form {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .projects-header {
                padding: 1.5rem;
            }

            .projects-header h2 {
                font-size: 1.5rem;
            }

            .filter-form {
                padding: 1rem;
            }

            .project-card-content {
                padding: 1.25rem;
            }

            .card-title {
                font-size: 1.2rem;
            }

            .project-badges {
                gap: 0.5rem;
            }

            .project-badge {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }

            .social-stats {
                padding: 0.5rem;
                font-size: 0.85rem;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<?php include "layout.php"; ?>

<!-- Main Content -->
<main class="main-content">
    <!-- Projects Header -->
    <div class="projects-header fade-in-up">
        <h2><i class="fas fa-project-diagram me-3"></i>All Approved Projects</h2>
        <p class="mb-0">Discover and like innovative projects from our community</p>
    </div>

    <!-- View Filter Buttons -->
    <div class="view-filter-buttons fade-in-up">
        <h5><i class="fas fa-filter"></i> View Options</h5>
        <div class="filter-btn-group">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'all', 'page' => 1])); ?>"
               class="filter-btn <?php echo $view_filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>All Projects</span>
                <span class="filter-btn-count"><?php echo $all_count; ?></span>
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'owned', 'page' => 1])); ?>"
               class="filter-btn <?php echo $view_filter === 'owned' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>My Projects</span>
                <span class="filter-btn-count"><?php echo $owned_count; ?></span>
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'bookmarked', 'page' => 1])); ?>"
               class="filter-btn <?php echo $view_filter === 'bookmarked' ? 'active' : ''; ?>">
                <i class="fas fa-bookmark"></i>
                <span>Bookmarked</span>
                <span class="filter-btn-count"><?php echo $bookmarked_count; ?></span>
            </a>
            <?php if ($user_id): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['view' => 'following', 'page' => 1])); ?>"
               class="filter-btn <?php echo $view_filter === 'following' ? 'active' : ''; ?>">
                <i class="fas fa-user-friends"></i>
                <span>Following</span>
                <span class="filter-btn-count"><?php echo $following_count; ?></span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filter-form fade-in-up">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
            
            <!-- Search -->
            <div class="col-12 col-md-6 col-lg-4">
                <label for="search" class="form-label"><i class="fas fa-search me-1"></i>Search Projects</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="search" name="search"
                           placeholder="Search by name, description..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            
            <!-- Classification -->
            <div class="col-6 col-md-4 col-lg-2">
                <label for="classification" class="form-label"><i class="fas fa-tag me-1"></i>Classification</label>
                <select class="form-select" id="classification" name="classification">
                    <option value="">All</option>
                    <option value="web" <?= $filter_classification === 'web' ? 'selected' : '' ?>>Web Development</option>
                    <option value="mobile_app" <?= $filter_classification === 'mobile_app' ? 'selected' : '' ?>>Mobile App</option>
                    <option value="data_science" <?= $filter_classification === 'data_science' ? 'selected' : '' ?>>Data Science</option>
                    <option value="ai_ml" <?= $filter_classification === 'ai_ml' ? 'selected' : '' ?>>AI/ML</option>
                    <option value="iot" <?= $filter_classification === 'iot' ? 'selected' : '' ?>>IoT</option>
                    <option value="blockchain" <?= $filter_classification === 'blockchain' ? 'selected' : '' ?>>Blockchain</option>
                    <option value="game_dev" <?= $filter_classification === 'game_dev' ? 'selected' : '' ?>>Game Development</option>
                    <option value="cybersecurity" <?= $filter_classification === 'cybersecurity' ? 'selected' : '' ?>>Cybersecurity</option>
                </select>
            </div>
            
            <!-- Project Type -->
            <div class="col-6 col-md-4 col-lg-2">
                <label for="type" class="form-label"><i class="fas fa-cube me-1"></i>Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All</option>
                    <option value="software" <?= $filter_type === 'software' ? 'selected' : '' ?>>Software</option>
                    <option value="hardware" <?= $filter_type === 'hardware' ? 'selected' : '' ?>>Hardware</option>
                    <option value="research" <?= $filter_type === 'research' ? 'selected' : '' ?>>Research</option>
                </select>
            </div>
            
            <!-- Difficulty Level -->
            <div class="col-6 col-md-4 col-lg-2">
                <label for="difficulty" class="form-label"><i class="fas fa-signal me-1"></i>Difficulty</label>
                <select class="form-select" id="difficulty" name="difficulty">
                    <option value="">All</option>
                    <option value="beginner" <?= $filter_difficulty === 'beginner' ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= $filter_difficulty === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="advanced" <?= $filter_difficulty === 'advanced' ? 'selected' : '' ?>>Advanced</option>
                    <option value="expert" <?= $filter_difficulty === 'expert' ? 'selected' : '' ?>>Expert</option>
                </select>
            </div>
            
            <!-- Development Time -->
            <div class="col-6 col-md-4 col-lg-2">
                <label for="dev_time" class="form-label"><i class="fas fa-clock me-1"></i>Dev Time</label>
                <select class="form-select" id="dev_time" name="dev_time">
                    <option value="">All</option>
                    <option value="< 1 month" <?= $filter_dev_time === '< 1 month' ? 'selected' : '' ?>>< 1 month</option>
                    <option value="1-2 months" <?= $filter_dev_time === '1-2 months' ? 'selected' : '' ?>>1-2 months</option>
                    <option value="2-3 months" <?= $filter_dev_time === '2-3 months' ? 'selected' : '' ?>>2-3 months</option>
                    <option value="3-6 months" <?= $filter_dev_time === '3-6 months' ? 'selected' : '' ?>>3-6 months</option>
                    <option value="6+ months" <?= $filter_dev_time === '6+ months' ? 'selected' : '' ?>>6+ months</option>
                </select>
            </div>
            
            <!-- Team Size -->
            <div class="col-6 col-md-4 col-lg-2">
                <label for="team_size" class="form-label"><i class="fas fa-users me-1"></i>Team Size</label>
                <select class="form-select" id="team_size" name="team_size">
                    <option value="">All</option>
                    <option value="1" <?= $filter_team_size === '1' ? 'selected' : '' ?>>Solo (1)</option>
                    <option value="2" <?= $filter_team_size === '2' ? 'selected' : '' ?>>2 members</option>
                    <option value="3" <?= $filter_team_size === '3' ? 'selected' : '' ?>>3 members</option>
                    <option value="4" <?= $filter_team_size === '4' ? 'selected' : '' ?>>4 members</option>
                    <option value="5+" <?= $filter_team_size === '5+' ? 'selected' : '' ?>>5+ members</option>
                </select>
            </div>
            
            <!-- License -->
            <div class="col-6 col-md-4 col-lg-2">
                <label for="license" class="form-label"><i class="fas fa-certificate me-1"></i>License</label>
                <select class="form-select" id="license" name="license">
                    <option value="">All</option>
                    <option value="MIT" <?= $filter_license === 'MIT' ? 'selected' : '' ?>>MIT</option>
                    <option value="GPL" <?= $filter_license === 'GPL' ? 'selected' : '' ?>>GPL</option>
                    <option value="Apache-2.0" <?= $filter_license === 'Apache-2.0' ? 'selected' : '' ?>>Apache 2.0</option>
                    <option value="BSD" <?= $filter_license === 'BSD' ? 'selected' : '' ?>>BSD</option>
                    <option value="ISC" <?= $filter_license === 'ISC' ? 'selected' : '' ?>>ISC</option>
                    <option value="Proprietary" <?= $filter_license === 'Proprietary' ? 'selected' : '' ?>>Proprietary</option>
                </select>
            </div>
            
            <!-- Language -->
            <div class="col-6 col-md-4 col-lg-2">
                <label for="language" class="form-label"><i class="fas fa-code me-1"></i>Language</label>
                <input type="text" class="form-control" id="language" name="language"
                       placeholder="e.g., Python, Java"
                       value="<?php echo htmlspecialchars($filter_language); ?>">
            </div>
            
            <!-- Buttons -->
            <div class="col-6 col-md-4 col-lg-2 d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Apply Filters
                </button>
            </div>
            <div class="col-6 col-md-4 col-lg-2 d-grid">
                <a href="?view=<?= htmlspecialchars($view_filter) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-redo me-2"></i>Reset
                </a>
            </div>
        </form>
        
        <!-- Active Filters Display -->
        <?php
        $active_filters = [];
        if ($search) $active_filters[] = "Search: " . htmlspecialchars($search);
        if ($filter_classification) $active_filters[] = "Classification: " . htmlspecialchars($filter_classification);
        if ($filter_type) $active_filters[] = "Type: " . htmlspecialchars($filter_type);
        if ($filter_difficulty) $active_filters[] = "Difficulty: " . htmlspecialchars($filter_difficulty);
        if ($filter_dev_time) $active_filters[] = "Dev Time: " . htmlspecialchars($filter_dev_time);
        if ($filter_team_size) $active_filters[] = "Team: " . htmlspecialchars($filter_team_size);
        if ($filter_license) $active_filters[] = "License: " . htmlspecialchars($filter_license);
        if ($filter_language) $active_filters[] = "Language: " . htmlspecialchars($filter_language);
        
        if (!empty($active_filters)): ?>
        <div class="mt-3">
            <small class="text-muted"><i class="fas fa-filter me-1"></i>Active Filters:</small>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <?php foreach ($active_filters as $filter): ?>
                    <span class="badge bg-primary"><?= $filter ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Alert Messages -->
    <?php
    if (isset($bookmark_message)) {
        echo $bookmark_message;
    }
    if (isset($like_message)) {
        echo $like_message;
    }
    ?>

    <!-- Projects List -->
    <div class="projects-list">
        <?php if (count($projects) > 0) : ?>
            <?php foreach ($projects as $index => $project) : ?>
                <div class="project-card fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <!-- Status Badge -->


                    <div class="project-card-content">
                        <div class="project-card-header">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($project['project_name']); ?>
                                <?php if ($project['is_owner']) : ?>
                                    <span class="badge badge-owner">
                                        <i class="fas fa-crown me-1"></i>Owner
                                    </span>
                                <?php endif; ?>
                            </h5>

                            <div class="project-badges">
                                <?php if (!empty($project['classification'])) : ?>
                                    <span class="project-badge badge-classification">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo htmlspecialchars($project['classification']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($project['project_type'])) : ?>
                                    <span class="project-badge badge-type">
                                        <i class="fas fa-cogs me-1"></i>
                                        <?php echo htmlspecialchars($project['project_type']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Social Stats -->
                            <div class="social-stats">
                                <div class="stat-item-social stat-likes">
                                    <i class="fas fa-heart"></i>
                                    <span><?php echo $project['total_likes']; ?> likes</span>
                                </div>
                                <div class="stat-item-social">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo date('M j, Y', strtotime($project['submission_date'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="project-description">
                            <?php echo htmlspecialchars(mb_strimwidth($project['description'], 0, 180, '...')); ?>
                        </div>

                        <!-- Action Buttons -->
                        <div class="project-actions">
                            <div class="action-buttons">
                                <!-- Like Button -->
                                <button type="button"
                                        class="action-btn like-btn<?php echo $project['is_liked'] ? ' liked' : ''; ?>"
                                        data-project-id="<?php echo $project['id']; ?>"
                                        data-action="like"
                                        onclick="toggleLike(this)">
                                    <i class="fas fa-heart"></i>
                                    <span class="like-count"><?php echo $project['total_likes']; ?></span>
                                </button>

                                <!-- Bookmark Button -->
                                <button type="button"
                                        class="action-btn bookmark-btn<?php echo $project['is_bookmarked'] ? ' bookmarked' : ''; ?>"
                                        data-project-id="<?php echo $project['id']; ?>"
                                        data-action="bookmark"
                                        onclick="toggleBookmark(this)">
                                    <i class="fas fa-bookmark"></i>
                                    <span class="bookmark-text"><?php echo $project['is_bookmarked'] ? 'Saved' : 'Save'; ?></span>
                                </button>

                                <!-- View Details Button -->
                                <a href="view_idea.php?id=<?php echo $project['id']; ?>" class="action-btn view-details-btn">
                                    <i class="fas fa-eye"></i>
                                    <span>View Details</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="empty-state fade-in-up">
                <div class="empty-state-icon">
                    <?php if ($view_filter === 'owned') : ?>
                        <i class="fas fa-user-plus"></i>
                    <?php elseif ($view_filter === 'bookmarked') : ?>
                        <i class="fas fa-bookmark"></i>
                    <?php elseif ($view_filter === 'following') : ?>
                        <i class="fas fa-user-friends"></i>
                    <?php else : ?>
                        <i class="fas fa-search"></i>
                    <?php endif; ?>
                </div>
                <h4>
                    <?php
                    if ($view_filter === 'owned') {
                        echo 'No projects found in your collection';
                    } elseif ($view_filter === 'bookmarked') {
                        echo 'No bookmarked projects found';
                    } elseif ($view_filter === 'following') {
                        echo 'No projects from followed users';
                    } else {
                        echo 'No projects found';
                    }
                    ?>
                </h4>
                <p>
                    <?php
                    if ($view_filter === 'owned') {
                        echo 'You haven\'t created any projects yet. Start by submitting your first project!';
                    } elseif ($view_filter === 'bookmarked') {
                        echo 'You haven\'t bookmarked any projects yet. Browse and bookmark interesting projects.';
                    } elseif ($view_filter === 'following') {
                        echo 'You\'re not following anyone yet, or they haven\'t created any projects. Start following users to see their projects here!';
                    } else {
                        echo 'No projects match your current filters. Try adjusting your search criteria.';
                    }
                    ?>
                </p>
                <?php if ($view_filter === 'owned') : ?>
                    <a href="submit_project.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i>Submit Your First Project
                    </a>
                <?php else : ?>
                    <a href="?view=all" class="btn btn-primary mt-3">
                        <i class="fas fa-th-large me-2"></i>View All Projects
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
        <div class="pagination-container fade-in-up">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="pagination-stats">
                    Showing <strong><?php echo (($current_page_num - 1) * $projects_per_page) + 1; ?></strong> to
                    <strong><?php echo min($current_page_num * $projects_per_page, $total_projects); ?></strong> of
                    <strong><?php echo $total_projects; ?></strong> projects
                </div>
            </div>

            <nav aria-label="Project pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($current_page_num <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link"
                           href="<?php echo ($current_page_num <= 1) ? '#' : '?' . http_build_query(array_merge($_GET, ['page' => $current_page_num - 1])); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    $start_page = max(1, $current_page_num - 2);
                    $end_page = min($total_pages, $current_page_num + 2);

                    for ($i = $start_page; $i <= $end_page; $i++) : ?>
                        <li class="page-item <?php echo ($i == $current_page_num) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo ($current_page_num >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link"
                           href="<?php echo ($current_page_num >= $total_pages) ? '#' : '?' . http_build_query(array_merge($_GET, ['page' => $current_page_num + 1])); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</main>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Get CSRF token from meta tag
    function getCSRFToken() {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        return csrfMeta ? csrfMeta.content : '';
    }

    // Toast notification function
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }

    // Toggle Like Function
    async function toggleLike(button) {
        const projectId = button.getAttribute('data-project-id');
        const likeCountSpan = button.querySelector('.like-count');
        const isLiked = button.classList.contains('liked');
        
        // Disable button during request
        button.disabled = true;
        button.style.opacity = '0.6';
        
        try {
            const csrfToken = getCSRFToken();
            const response = await fetch('ajax_project_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'toggle_like',
                    project_id: projectId,
                    csrf_token: csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update CSRF token for next request
                if (data.new_token) {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfMeta.content = data.new_token;
                    }
                }
                
                // Update button state
                if (data.liked) {
                    button.classList.add('liked');
                    showToast('<i class="fas fa-heart me-2"></i>Project liked!', 'success');
                } else {
                    button.classList.remove('liked');
                    showToast('<i class="fas fa-heart me-2"></i>Like removed', 'info');
                }
                
                // Update like count
                likeCountSpan.textContent = data.total_likes;
                
                // Update social stats like count
                const card = button.closest('.project-card');
                const statLikes = card.querySelector('.stat-likes span');
                if (statLikes) {
                    statLikes.textContent = data.total_likes + ' likes';
                }
                
                // Animation
                button.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    button.style.transform = 'scale(1)';
                }, 200);
            } else {
                showToast('<i class="fas fa-exclamation-circle me-2"></i>' + data.message, 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('<i class="fas fa-exclamation-circle me-2"></i>An error occurred. Please try again.', 'danger');
        } finally {
            // Re-enable button
            button.disabled = false;
            button.style.opacity = '1';
        }
    }

    // Toggle Bookmark Function
    async function toggleBookmark(button) {
        const projectId = button.getAttribute('data-project-id');
        const bookmarkTextSpan = button.querySelector('.bookmark-text');
        const isBookmarked = button.classList.contains('bookmarked');
        
        // Disable button during request
        button.disabled = true;
        button.style.opacity = '0.6';
        
        try {
            const csrfToken = getCSRFToken();
            const response = await fetch('ajax_project_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'toggle_bookmark',
                    project_id: projectId,
                    csrf_token: csrfToken
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update CSRF token for next request
                if (data.new_token) {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfMeta.content = data.new_token;
                    }
                }
                
                // Update button state
                if (data.bookmarked) {
                    button.classList.add('bookmarked');
                    bookmarkTextSpan.textContent = 'Saved';
                    showToast('<i class="fas fa-bookmark me-2"></i>Project bookmarked!', 'success');
                } else {
                    button.classList.remove('bookmarked');
                    bookmarkTextSpan.textContent = 'Save';
                    showToast('<i class="fas fa-bookmark me-2"></i>Bookmark removed', 'info');
                }
                
                // Animation
                button.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    button.style.transform = 'scale(1)';
                }, 200);
            } else {
                showToast('<i class="fas fa-exclamation-circle me-2"></i>' + data.message, 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('<i class="fas fa-exclamation-circle me-2"></i>An error occurred. Please try again.', 'danger');
        } finally {
            // Re-enable button
            button.disabled = false;
            button.style.opacity = '1';
        }
    }

    // AJAX Filter Form Submission
    function handleFilterSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        // Show loading
        showLoader('Applying filters...');
        
        // Load projects with filters
        loadProjects(params);
    }

    // AJAX Load Projects
    async function loadProjects(params) {
        try {
            const csrfToken = getCSRFToken();
            const data = {
                action: 'load_projects',
                csrf_token: csrfToken,
                search: params.get('search') || '',
                classification: params.get('classification') || '',
                type: params.get('type') || '',
                difficulty: params.get('difficulty') || '',
                dev_time: params.get('dev_time') || '',
                team_size: params.get('team_size') || '',
                license: params.get('license') || '',
                language: params.get('language') || '',
                view: params.get('view') || 'all',
                page: params.get('page') || 1
            };
            
            const response = await fetch('ajax_project_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update CSRF token
                if (result.new_token) {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfMeta.content = result.new_token;
                    }
                }
                
                // Render projects
                renderProjects(result.projects);
                
                // Update pagination
                updatePagination(result.current_page, result.total_pages, params);
                
                // Update URL without reload
                const newUrl = window.location.pathname + '?' + params.toString();
                window.history.pushState({}, '', newUrl);
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                hideLoader();
            } else {
                hideLoader();
                console.error('Server error:', result.message);
                showToast('<i class="fas fa-exclamation-circle me-2"></i>' + (result.message || 'An error occurred'), 'danger');
            }
        } catch (error) {
            console.error('Error loading projects:', error);
            hideLoader();
            showToast('<i class="fas fa-exclamation-circle me-2"></i>An error occurred loading projects. Please refresh the page.', 'danger');
        }
    }

    // Render Projects
    function renderProjects(projects) {
        const projectsList = document.querySelector('.projects-list');
        
        if (!projects || projects.length === 0) {
            projectsList.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4>No Projects Found</h4>
                    <p class="text-muted">No projects match your current filters. Try adjusting your search criteria.</p>
                    <a href="?view=all" class="btn btn-primary mt-3">
                        <i class="fas fa-th-large me-2"></i>View All Projects
                    </a>
                </div>
            `;
            return;
        }
        
        let html = '<div class="row g-4">';
        
        projects.forEach(project => {
            html += `
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="project-card">
                        <div class="project-card-header">
                            <h5 class="project-card-title">${escapeHtml(project.project_name)}</h5>
                            <div class="project-badges">
                                ${project.classification ? `<span class="badge badge-classification">${escapeHtml(project.classification)}</span>` : ''}
                                ${project.project_type ? `<span class="badge badge-type">${escapeHtml(project.project_type)}</span>` : ''}
                            </div>
                        </div>
                        <div class="project-card-body">
                            <p class="project-description">${escapeHtml(project.description ? project.description.substring(0, 150) + '...' : '')}</p>
                            <div class="project-meta">
                                ${project.difficulty_level ? `<span><i class="fas fa-signal"></i> ${escapeHtml(project.difficulty_level)}</span>` : ''}
                                ${project.development_time ? `<span><i class="fas fa-clock"></i> ${escapeHtml(project.development_time)}</span>` : ''}
                                ${project.team_size ? `<span><i class="fas fa-users"></i> ${escapeHtml(project.team_size)}</span>` : ''}
                            </div>
                        </div>
                        <div class="project-card-footer">
                            <div class="project-actions">
                                <button type="button" class="action-btn like-btn${project.is_liked ? ' liked' : ''}"
                                        data-project-id="${project.id}"
                                        data-action="like"
                                        onclick="toggleLike(this)">
                                    <i class="fas fa-heart"></i>
                                    <span class="like-count">${project.total_likes}</span>
                                </button>
                                <button type="button" class="action-btn bookmark-btn${project.is_bookmarked ? ' bookmarked' : ''}"
                                        data-project-id="${project.id}"
                                        data-action="bookmark"
                                        onclick="toggleBookmark(this)">
                                    <i class="fas fa-bookmark"></i>
                                    <span class="bookmark-text">${project.is_bookmarked ? 'Saved' : 'Save'}</span>
                                </button>
                            </div>
                            <a href="view_idea.php?id=${project.id}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        projectsList.innerHTML = html;
    }

    // Update Pagination
    function updatePagination(currentPage, totalPages, params) {
        const paginationContainer = document.querySelector('.pagination-container');
        if (!paginationContainer) return;
        
        let html = '<nav><ul class="pagination justify-content-center">';
        
        // Previous button
        if (currentPage > 1) {
            params.set('page', currentPage - 1);
            html += `<li class="page-item"><a class="page-link" href="#" onclick="event.preventDefault(); loadProjectsPage(${currentPage - 1})">Previous</a></li>`;
        }
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="event.preventDefault(); loadProjectsPage(${i})">${i}</a></li>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        // Next button
        if (currentPage < totalPages) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="event.preventDefault(); loadProjectsPage(${currentPage + 1})">Next</a></li>`;
        }
        
        html += '</ul></nav>';
        paginationContainer.innerHTML = html;
    }

    // Load Projects Page
    function loadProjectsPage(page) {
        const form = document.querySelector('.filter-form form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        params.set('page', page);
        
        showLoader('Loading page ' + page + '...');
        loadProjects(params);
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Show Loader
    function showLoader(message = 'Loading...') {
        const loader = document.getElementById('universalLoader');
        const loaderText = document.getElementById('loaderText');
        if (loader) {
            if (loaderText) loaderText.textContent = message;
            loader.style.display = 'flex';
        }
    }

    // Hide Loader
    function hideLoader() {
        const loader = document.getElementById('universalLoader');
        if (loader) {
            loader.style.display = 'none';
        }
    }

    // AJAX View Filter Click
    function handleViewFilterClick(event, view) {
        event.preventDefault();
        const form = document.querySelector('.filter-form form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        params.set('view', view);
        params.set('page', 1);
        
        // Update active state
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');
        
        showLoader('Loading ' + view + ' projects...');
        loadProjects(params);
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }, 4000);
        });

        // Attach AJAX to filter form - DISABLED for now to prevent styling issues
        // Let filters work with normal page loads
        /*
        const filterForm = document.querySelector('.filter-form form');
        if (filterForm) {
            filterForm.addEventListener('submit', handleFilterSubmit);
        }
        */

        // Attach AJAX to view filter buttons - DISABLED for now to prevent styling issues
        // Let view filters work with normal page loads
        /*
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const view = new URL(this.href).searchParams.get('view');
                if (view) {
                    handleViewFilterClick(e, view);
                }
            });
        });
        */

        console.log('All projects page with comprehensive AJAX initialized successfully');
    });
</script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
</body>
</html>