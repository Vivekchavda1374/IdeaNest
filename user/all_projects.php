<?php
// user/all_projects.php - Enhanced version with complete project details modal
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
}

// Handle comment submission
if (isset($_POST['submit_comment']) && isset($_POST['project_id']) && isset($_POST['comment_text']) && $user_id) {
    $project_id = intval($_POST['project_id']);
    $comment_text = trim($_POST['comment_text']);
    $parent_comment_id = isset($_POST['parent_comment_id']) && !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;

    if (!empty($comment_text)) {
        $insert_comment_sql = "INSERT INTO project_comments (project_id, user_id, user_name, comment_text, parent_comment_id) VALUES (?, ?, ?, ?, ?)";
        $insert_comment_stmt = $conn->prepare($insert_comment_sql);
        $insert_comment_stmt->bind_param("iissi", $project_id, $user_id, $user_name, $comment_text, $parent_comment_id);

        if ($insert_comment_stmt->execute()) {
            $comment_message = '<div class="alert alert-success">Comment added successfully!</div>';
        } else {
            $comment_message = '<div class="alert alert-danger">Error adding comment. Please try again.</div>';
        }
        $insert_comment_stmt->close();
    }
}

// Handle comment edit
if (isset($_POST['edit_comment']) && isset($_POST['comment_id']) && isset($_POST['comment_text']) && $user_id) {
    $comment_id = intval($_POST['comment_id']);
    $new_comment_text = trim($_POST['comment_text']);

    if (!empty($new_comment_text)) {
        // Verify comment ownership
        $check_ownership_sql = "SELECT user_id FROM project_comments WHERE id = ?";
        $check_ownership_stmt = $conn->prepare($check_ownership_sql);
        $check_ownership_stmt->bind_param("i", $comment_id);
        $check_ownership_stmt->execute();
        $ownership_result = $check_ownership_stmt->get_result();

        if ($ownership_result->num_rows > 0) {
            $comment_owner = $ownership_result->fetch_assoc();
            if ($comment_owner['user_id'] === $user_id) {
                // Update comment
                $update_comment_sql = "UPDATE project_comments SET comment_text = ?, updated_at = NOW() WHERE id = ?";
                $update_comment_stmt = $conn->prepare($update_comment_sql);
                $update_comment_stmt->bind_param("si", $new_comment_text, $comment_id);

                if ($update_comment_stmt->execute()) {
                    $comment_message = '<div class="alert alert-success">Comment updated successfully!</div>';
                } else {
                    $comment_message = '<div class="alert alert-danger">Error updating comment. Please try again.</div>';
                }
                $update_comment_stmt->close();
            } else {
                $comment_message = '<div class="alert alert-danger">You can only edit your own comments.</div>';
            }
        }
        $check_ownership_stmt->close();
    }
}

// Handle comment delete
if (isset($_POST['delete_comment']) && isset($_POST['comment_id']) && $user_id) {
    $comment_id = intval($_POST['comment_id']);

    // Verify comment ownership
    $check_ownership_sql = "SELECT user_id FROM project_comments WHERE id = ?";
    $check_ownership_stmt = $conn->prepare($check_ownership_sql);
    $check_ownership_stmt->bind_param("i", $comment_id);
    $check_ownership_stmt->execute();
    $ownership_result = $check_ownership_stmt->get_result();

    if ($ownership_result->num_rows > 0) {
        $comment_owner = $ownership_result->fetch_assoc();
        if ($comment_owner['user_id'] === $user_id) {
            // Delete comment
            $delete_comment_sql = "DELETE FROM project_comments WHERE id = ?";
            $delete_comment_stmt = $conn->prepare($delete_comment_sql);
            $delete_comment_stmt->bind_param("i", $comment_id);

            if ($delete_comment_stmt->execute()) {
                $comment_message = '<div class="alert alert-success">Comment deleted successfully!</div>';
            } else {
                $comment_message = '<div class="alert alert-danger">Error deleting comment. Please try again.</div>';
            }
            $delete_comment_stmt->close();
        } else {
            $comment_message = '<div class="alert alert-danger">You can only delete your own comments.</div>';
        }
    }
    $check_ownership_stmt->close();
}

// Handle comment like toggle
if (isset($_POST['toggle_comment_like']) && isset($_POST['comment_id']) && $user_id) {
    $comment_id = intval($_POST['comment_id']);

    // Check if comment like already exists
    $check_comment_like_sql = "SELECT * FROM comment_likes WHERE comment_id = ? AND user_id = ?";
    $check_comment_like_stmt = $conn->prepare($check_comment_like_sql);
    $check_comment_like_stmt->bind_param("ii", $comment_id, $user_id);
    $check_comment_like_stmt->execute();
    $comment_like_result = $check_comment_like_stmt->get_result();

    if ($comment_like_result->num_rows > 0) {
        // Remove comment like
        $delete_comment_like_sql = "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?";
        $delete_comment_like_stmt = $conn->prepare($delete_comment_like_sql);
        $delete_comment_like_stmt->bind_param("ii", $comment_id, $user_id);
        $delete_comment_like_stmt->execute();
        $delete_comment_like_stmt->close();
    } else {
        // Add comment like
        $insert_comment_like_sql = "INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)";
        $insert_comment_like_stmt = $conn->prepare($insert_comment_like_sql);
        $insert_comment_like_stmt->bind_param("ii", $comment_id, $user_id);
        $insert_comment_like_stmt->execute();
        $insert_comment_like_stmt->close();
    }
    $check_comment_like_stmt->close();
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
$view_filter = isset($_GET['view']) ? trim($_GET['view']) : 'all';
$show_only_owned = ($view_filter === 'owned');
$show_only_bookmarked = ($view_filter === 'bookmarked');

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
               COALESCE(comment_counts.total_comments, 0) AS total_comments,
               COALESCE(user_stats.user_total_projects, 0) AS user_total_projects,
               COALESCE(user_stats.user_approved_projects, 0) AS user_approved_projects
        FROM admin_approved_projects ap
        LEFT JOIN register r ON ap.user_id = r.id
        LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?
        LEFT JOIN temp_project_ownership tpo ON ap.id = tpo.project_id AND tpo.user_session = ?
        LEFT JOIN project_likes pl ON ap.id = pl.project_id AND pl.user_id = ?
        LEFT JOIN (
            SELECT project_id, COUNT(*) as total_likes 
            FROM project_likes 
            GROUP BY project_id
        ) like_counts ON ap.id = like_counts.project_id
        LEFT JOIN (
            SELECT project_id, COUNT(*) as total_comments 
            FROM project_comments 
            WHERE is_deleted = 0
            GROUP BY project_id
        ) comment_counts ON ap.id = comment_counts.project_id
        LEFT JOIN (
            SELECT user_id, 
                   COUNT(*) as user_total_projects,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as user_approved_projects
            FROM admin_approved_projects
            GROUP BY user_id
        ) user_stats ON ap.user_id = user_stats.user_id";

$main_conditions = " WHERE 1=1";
$params = [$session_id, $session_id, $session_id];
$types = "sss";

// Add view filter conditions
if ($show_only_owned) {
    $main_conditions .= " AND tpo.project_id IS NOT NULL";
} elseif ($show_only_bookmarked) {
    $main_conditions .= " AND b.project_id IS NOT NULL";
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

$all_count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects";
$all_count_stmt = $conn->prepare($all_count_sql);
$all_count_stmt->execute();
$all_count = $all_count_stmt->get_result()->fetch_assoc()['total'];
$all_count_stmt->close();

// Function to get comments for a project
function getProjectComments($conn, $project_id, $session_id)
{
    $comments_sql = "SELECT pc.*, 
                            CASE WHEN cl.comment_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked,
                            COALESCE(comment_like_counts.total_likes, 0) AS comment_likes_count
                     FROM project_comments pc
                     LEFT JOIN comment_likes cl ON pc.id = cl.comment_id AND cl.user_id = ?
                     LEFT JOIN (
                         SELECT comment_id, COUNT(*) as total_likes 
                         FROM comment_likes 
                         GROUP BY comment_id
                     ) comment_like_counts ON pc.id = comment_like_counts.comment_id
                     WHERE pc.project_id = ? AND pc.is_deleted = 0
                     ORDER BY pc.created_at ASC";

    $comments_stmt = $conn->prepare($comments_sql);
    $comments_stmt->bind_param("si", $session_id, $project_id);
    $comments_stmt->execute();
    $comments_result = $comments_stmt->get_result();

    $comments = [];
    while ($comment = $comments_result->fetch_assoc()) {
        $comments[] = $comment;
    }
    $comments_stmt->close();

    // Organize comments in tree structure
    $comment_tree = [];
    $comment_map = [];

    foreach ($comments as $comment) {
        $comment_map[$comment['id']] = $comment;
        $comment_map[$comment['id']]['replies'] = [];
    }

    foreach ($comments as $comment) {
        if ($comment['parent_comment_id'] === null) {
            $comment_tree[] = &$comment_map[$comment['id']];
        } else {
            if (isset($comment_map[$comment['parent_comment_id']])) {
                $comment_map[$comment['parent_comment_id']]['replies'][] = &$comment_map[$comment['id']];
            }
        }
    }

    return $comment_tree;
}

// Helper function to format difficulty level
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Approved Projects - IdeaNest</title>
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
            cursor: pointer;
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

        .project-card:active {
            transform: translateY(-6px) scale(1.01);
            transition: all 0.1s ease;
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

        /* User Details Section Styles */
        .user-details-section {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .user-info-card {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            box-shadow: var(--shadow-md);
            flex-shrink: 0;
        }

        .user-details h5 {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .user-details p {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-details i {
            color: var(--primary-color);
            width: 16px;
        }

        .user-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .stat-badge {
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 1.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-sm);
        }

        /* Modal Styles */
        .modal-xl {
            max-width: 1200px;
        }

        .modal-header {
            background: var(--gradient-primary);
            color: white;
            border-bottom: none;
            position: relative;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .modal-footer {
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
        }

        .modal-backdrop {
            background-color: rgba(30, 41, 59, 0.75);
        }

        .project-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-card {
            background: var(--bg-tertiary);
            border-radius: 1rem;
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .detail-card h6 {
            color: #475569;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-card p {
            margin: 0;
            color: #1e293b;
            font-weight: 500;
        }

        .detail-card.highlight {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            border-left-color: var(--warning-color);
        }

        .detail-card.success {
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
            border-left-color: var(--success-color);
        }

        .detail-card.info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-left-color: var(--info-color);
        }

        .project-description {
            background: var(--bg-primary);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .project-goals, .challenges-section, .enhancements-section {
            background: var(--bg-primary);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.3rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-downloads {
            background: var(--bg-primary);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 0.75rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border-color);
        }

        .file-item:hover {
            background: var(--bg-secondary);
            transform: translateX(8px);
            box-shadow: var(--shadow-md);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .file-icon {
            font-size: 1.75rem;
            color: var(--primary-color);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .file-details h6 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
        }

        .file-details p {
            margin: 0;
            font-size: 0.8rem;
            color: #64748b;
        }

        .download-btn {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-sm);
        }

        .download-btn:hover {
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .social-links {
            background: var(--bg-primary);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .social-link-item {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            background: var(--bg-tertiary);
            border-radius: 2rem;
            margin: 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border-color);
        }

        .social-link-item:hover {
            background: var(--gradient-primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .keywords-section {
            background: var(--bg-primary);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .keyword-tag {
            display: inline-block;
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 1.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            margin: 0.25rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .keyword-tag:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .difficulty-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .difficulty-beginner {
            background: linear-gradient(45deg, #4caf50, #8bc34a);
            color: white;
        }

        .difficulty-intermediate {
            background: linear-gradient(45deg, #ff9800, #ffc107);
            color: white;
        }

        .difficulty-advanced {
            background: linear-gradient(45deg, #f44336, #e91e63);
            color: white;
        }

        .difficulty-expert {
            background: linear-gradient(45deg, #9c27b0, #673ab7);
            color: white;
        }

        .comments-section {
            max-height: 500px;
            overflow-y: auto;
        }

        .comment-item {
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: var(--bg-primary);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .comment-item:hover {
            box-shadow: var(--shadow-md);
        }

        .reply-comment {
            margin-left: 2rem;
            border-left: 4px solid var(--primary-color);
            background: var(--bg-tertiary);
        }

        .comment-header {
            margin-bottom: 0.75rem;
        }

        .comment-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .comment-avatar {
            width: 3rem;
            height: 3rem;
            background: var(--gradient-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: var(--shadow-sm);
        }

        .comment-username {
            font-weight: 600;
            color: #1e293b;
        }

        .comment-date {
            font-size: 0.8rem;
            color: #64748b;
        }

        .comment-text {
            color: #1e293b;
            line-height: 1.6;
            margin-bottom: 0.75rem;
        }

        .comment-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .comment-like-btn {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
        }

        .comment-like-btn:hover {
            background: #ffe6e6;
            color: var(--danger-color);
        }

        .comment-like-btn.liked {
            color: var(--danger-color);
            background: #ffe6e6;
        }

        .reply-btn {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
        }

        .reply-btn:hover {
            background: #f8fafc;
            color: var(--primary-purple);
        }

        .reply-form {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }

        .reply-form textarea {
            width: 100%;
            border: 1px solid #f1f5f9;
            border-radius: 0.5rem;
            padding: 0.75rem;
            resize: vertical;
            min-height: 80px;
        }

        .comment-form {
            background: var(--bg-tertiary);
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .comments-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .comments-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .project-meta-info {
            background: linear-gradient(135deg, var(--bg-tertiary), var(--bg-secondary));
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }

        .meta-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            text-align: center;
        }

        .meta-stat {
            background: var(--bg-primary);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .meta-stat:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .meta-stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary-color);
            display: block;
        }

        .meta-stat-label {
            font-size: 0.8rem;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.25rem;
        }

        .project-modal-desc {
            color: #475569;
            line-height: 1.6;
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

            .project-details-grid {
                grid-template-columns: 1fr;
            }

            .meta-stats {
                grid-template-columns: repeat(2, 1fr);
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
        <p class="mb-0">Discover, like, and comment on innovative projects from our community</p>
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
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="filter-form fade-in-up">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
            <div class="col-12 col-md-4">
                <label for="search" class="form-label">Search Projects</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="search" name="search"
                           placeholder="Search by name, description, type..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label for="classification" class="form-label">Classification</label>
                <select class="form-select" id="classification" name="classification">
                    <option value="">All Classifications</option>
                    <option value="Web Development" <?php if ($filter_classification === 'Web Development') {
                        echo 'selected';
                                                    } ?>>Web Development</option>
                    <option value="Mobile App" <?php if ($filter_classification === 'Mobile App') {
                        echo 'selected';
                                               } ?>>Mobile App</option>
                    <option value="Data Science" <?php if ($filter_classification === 'Data Science') {
                        echo 'selected';
                                                 } ?>>Data Science</option>
                    <option value="AI/ML" <?php if ($filter_classification === 'AI/ML') {
                        echo 'selected';
                                          } ?>>AI/ML</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="type" class="form-label">Project Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="Frontend" <?php if ($filter_type === 'Frontend') {
                        echo 'selected';
                                             } ?>>Frontend</option>
                    <option value="Backend" <?php if ($filter_type === 'Backend') {
                        echo 'selected';
                                            } ?>>Backend</option>
                    <option value="Full Stack" <?php if ($filter_type === 'Full Stack') {
                        echo 'selected';
                                               } ?>>Full Stack</option>
                    <option value="API" <?php if ($filter_type === 'API') {
                        echo 'selected';
                                        } ?>>API</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Alert Messages -->
    <?php
    if (isset($bookmark_message)) {
        echo $bookmark_message;
    }
    if (isset($like_message)) {
        echo $like_message;
    }
    if (isset($comment_message)) {
        echo $comment_message;
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
                                <div class="stat-item-social stat-comments">
                                    <i class="fas fa-comment"></i>
                                    <span><?php echo $project['total_comments']; ?> comments</span>
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
                                <form method="POST" action="all_projects.php" style="display:inline;" onsubmit="return true;">
                                    <?php echo getCSRFField(); ?>
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                    <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                    <input type="hidden" name="toggle_like" value="1">
                                    <button type="submit"
                                            class="action-btn like-btn<?php echo $project['is_liked'] ? ' liked' : ''; ?>">
                                        <i class="fas fa-heart"></i>
                                        <span><?php echo $project['total_likes']; ?></span>
                                    </button>
                                </form>

                                <!-- Comment Button -->
                                <button type="button" class="action-btn comment-btn"
                                        onclick="openCommentsModal(<?php echo $project['id']; ?>)">
                                    <i class="fas fa-comment"></i>
                                    <span><?php echo $project['total_comments']; ?></span>
                                </button>

                                <!-- Bookmark Button -->
                                <form method="POST" action="all_projects.php" style="display:inline;" onsubmit="return true;">
                                    <?php echo getCSRFField(); ?>
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                    <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                    <input type="hidden" name="toggle_bookmark" value="1">
                                    <button type="submit"
                                            class="action-btn bookmark-btn<?php echo $project['is_bookmarked'] ? ' bookmarked' : ''; ?>">
                                        <i class="fas fa-bookmark"></i>
                                        <span><?php echo $project['is_bookmarked'] ? 'Saved' : 'Save'; ?></span>
                                    </button>
                                </form>
                            </div>

                            <!-- Edit Button for Owners -->
                            <?php if ($project['is_owner']) : ?>
                                <a href="edit_project.php?id=<?php echo $project['id']; ?>"
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit Project
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Project Details Modal -->
                <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-project-diagram me-2"></i>
                                    <?php echo htmlspecialchars($project['project_name']); ?>
                                    <?php if (!empty($project['difficulty_level'])) : ?>
                                        <span class="difficulty-badge difficulty-<?php echo $project['difficulty_level']; ?> ms-3">
                                            <i class="fas fa-signal"></i>
                                            <?php echo formatDifficultyLevel($project['difficulty_level']); ?>
                                        </span>
                                    <?php endif; ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <!-- Project Meta Information -->
                                <div class="project-meta-info">
                                    <div class="meta-stats">
                                        <div class="meta-stat">
                                            <span class="meta-stat-number"><?php echo $project['total_likes']; ?></span>
                                            <span class="meta-stat-label">Likes</span>
                                        </div>
                                        <div class="meta-stat">
                                            <span class="meta-stat-number"><?php echo $project['total_comments']; ?></span>
                                            <span class="meta-stat-label">Comments</span>
                                        </div>
                                        <div class="meta-stat">
                                            <span class="meta-stat-number"><?php echo date('M j', strtotime($project['submission_date'])); ?></span>
                                            <span class="meta-stat-label">Submitted</span>
                                        </div>
                                        <?php if (!empty($project['development_time'])) : ?>
                                            <div class="meta-stat">
                                                <span class="meta-stat-number"><?php echo htmlspecialchars($project['development_time']); ?></span>
                                                <span class="meta-stat-label">Dev Time</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- User Details Section -->
                                <div class="user-details-section">
                                    <h6 class="section-title">
                                        <i class="fas fa-user"></i>Project Creator Details
                                    </h6>
                                    <div class="user-info-card">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($project['user_name'] ?? $project['submitter_name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <h5><?php echo htmlspecialchars($project['user_name'] ?? $project['submitter_name'] ?? 'Unknown User'); ?></h5>
                                            <?php if (!empty($project['user_email'])) : ?>
                                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($project['user_email']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($project['user_phone'])) : ?>
                                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($project['user_phone']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($project['user_department'])) : ?>
                                                <p><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($project['user_department']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($project['user_bio'])) : ?>
                                                <p><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($project['user_bio']); ?></p>
                                            <?php endif; ?>
                                            <div class="user-stats">
                                                <span class="stat-badge">
                                                    <i class="fas fa-project-diagram"></i>
                                                    <?php echo $project['user_total_projects'] ?? 0; ?> Total Projects
                                                </span>
                                                <span class="stat-badge">
                                                    <i class="fas fa-check-circle"></i>
                                                    <?php echo $project['user_approved_projects'] ?? 0; ?> Approved
                                                </span>
                                                <?php if (!empty($project['user_joined'])) : ?>
                                                    <span class="stat-badge">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        Joined <?php echo date('M Y', strtotime($project['user_joined'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Project Details Grid -->
                                <div class="project-details-grid">
                                    <?php if (!empty($project['classification'])) : ?>
                                        <div class="detail-card">
                                            <h6><i class="fas fa-tags"></i> Classification</h6>
                                            <p><?php echo htmlspecialchars($project['classification']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($project['project_type'])) : ?>
                                        <div class="detail-card">
                                            <h6><i class="fas fa-cogs"></i> Project Type</h6>
                                            <p><?php echo htmlspecialchars($project['project_type']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($project['project_category'])) : ?>
                                        <div class="detail-card highlight">
                                            <h6><i class="fas fa-folder"></i> Category</h6>
                                            <p><?php echo htmlspecialchars($project['project_category']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($project['language'])) : ?>
                                        <div class="detail-card info">
                                            <h6><i class="fas fa-code"></i> Language</h6>
                                            <p><?php echo htmlspecialchars($project['language']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($project['team_size'])) : ?>
                                        <div class="detail-card">
                                            <h6><i class="fas fa-users"></i> Team Size</h6>
                                            <p><?php echo htmlspecialchars($project['team_size']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($project['project_license'])) : ?>
                                        <div class="detail-card success">
                                            <h6><i class="fas fa-certificate"></i> License</h6>
                                            <p><?php echo htmlspecialchars($project['project_license']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Description -->
                                <div class="project-description">
                                    <h6 class="section-title">
                                        <i class="fas fa-file-text"></i>Description
                                    </h6>
                                    <div class="project-modal-desc">
                                        <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                                    </div>
                                </div>

                                <!-- Target Audience -->
                                <?php if (!empty($project['target_audience'])) : ?>
                                    <div class="project-goals">
                                        <h6 class="section-title">
                                            <i class="fas fa-users"></i>Target Audience
                                        </h6>
                                        <p><?php echo nl2br(htmlspecialchars($project['target_audience'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Project Goals -->
                                <?php if (!empty($project['project_goals'])) : ?>
                                    <div class="project-goals">
                                        <h6 class="section-title">
                                            <i class="fas fa-bullseye"></i>Project Goals
                                        </h6>
                                        <p><?php echo nl2br(htmlspecialchars($project['project_goals'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Challenges Faced -->
                                <?php if (!empty($project['challenges_faced'])) : ?>
                                    <div class="challenges-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-mountain"></i>Challenges Faced
                                        </h6>
                                        <p><?php echo nl2br(htmlspecialchars($project['challenges_faced'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Future Enhancements -->
                                <?php if (!empty($project['future_enhancements'])) : ?>
                                    <div class="enhancements-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-rocket"></i>Future Enhancements
                                        </h6>
                                        <p><?php echo nl2br(htmlspecialchars($project['future_enhancements'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Keywords -->
                                <?php if (!empty($project['keywords'])) : ?>
                                    <div class="keywords-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-hashtag"></i>Keywords & Tags
                                        </h6>
                                        <div>
                                            <?php
                                            $keywords = explode(',', $project['keywords']);
                                            foreach ($keywords as $keyword) :
                                                $keyword = trim($keyword);
                                                if (!empty($keyword)) :
                                                    ?>
                                                    <span class="keyword-tag"><?php echo htmlspecialchars($keyword); ?></span>
                                                    <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Links Section -->
                                <?php if (!empty($project['github_repo']) || !empty($project['live_demo_url']) || !empty($project['social_links'])) : ?>
                                    <div class="social-links">
                                        <h6 class="section-title">
                                            <i class="fas fa-link"></i>Links & Resources
                                        </h6>
                                        <div>
                                            <?php if (!empty($project['github_repo'])) : ?>
                                                <a href="<?php echo htmlspecialchars($project['github_repo']); ?>"
                                                   target="_blank" class="social-link-item">
                                                    <i class="fab fa-github"></i>
                                                    GitHub Repository
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!empty($project['live_demo_url'])) : ?>
                                                <a href="<?php echo htmlspecialchars($project['live_demo_url']); ?>"
                                                   target="_blank" class="social-link-item">
                                                    <i class="fas fa-external-link-alt"></i>
                                                    Live Demo
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!empty($project['social_links'])) : ?>
                                                <?php
                                                $social_links = explode(',', $project['social_links']);
                                                foreach ($social_links as $link) :
                                                    $link = trim($link);
                                                    if (!empty($link)) :
                                                        ?>
                                                        <a href="<?php echo htmlspecialchars($link); ?>"
                                                           target="_blank" class="social-link-item">
                                                            <i class="fas fa-globe"></i>
                                                            Social Link
                                                        </a>
                                                        <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Project Files Downloads -->
                                <?php
                                $files = [
                                        ['path' => $project['code_file_path'], 'name' => 'Source Code', 'icon' => 'fas fa-code'],
                                        ['path' => $project['instruction_file_path'], 'name' => 'Instructions', 'icon' => 'fas fa-book'],
                                        ['path' => $project['presentation_file_path'], 'name' => 'Presentation', 'icon' => 'fas fa-presentation'],
                                        ['path' => $project['additional_files_path'], 'name' => 'Additional Files', 'icon' => 'fas fa-folder'],
                                        ['path' => $project['image_path'], 'name' => 'Project Files (ZIP)', 'icon' => 'fas fa-file-archive'],
                                        ['path' => $project['video_path'], 'name' => 'Project Video', 'icon' => 'fas fa-video']
                                ];

                                $available_files = array_filter($files, function ($file) {
                                    return !empty($file['path']);
                                });

                                if (!empty($available_files)) :
                                    ?>
                                    <div class="file-downloads">
                                        <h6 class="section-title">
                                            <i class="fas fa-download"></i>Downloads & Resources
                                        </h6>
                                        <?php foreach ($available_files as $file) : ?>
                                            <div class="file-item">
                                                <div class="file-info">
                                                    <div class="file-icon">
                                                        <i class="<?php echo getFileIcon($file['path']); ?>"></i>
                                                    </div>
                                                    <div class="file-details">
                                                        <h6><?php echo $file['name']; ?></h6>
                                                        <p><?php echo basename($file['path']); ?></p>
                                                    </div>
                                                </div>
                                                <a href="download.php?file=<?php echo urlencode(basename($file['path'])); ?>"
                                                   target="_blank" class="download-btn">
                                                    <i class="fas fa-download"></i>
                                                    Download
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Contact Information -->
                                <?php if (!empty($project['contact_email'])) : ?>
                                    <div class="social-links">
                                        <h6 class="section-title">
                                            <i class="fas fa-envelope"></i>Contact Information
                                        </h6>
                                        <div>
                                            <a href="mailto:<?php echo htmlspecialchars($project['contact_email']); ?>"
                                               class="social-link-item">
                                                <i class="fas fa-envelope"></i>
                                                <?php echo htmlspecialchars($project['contact_email']); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Comments Section -->
                                <div class="comments-section">
                                    <div class="comments-header">
                                        <div class="comments-title">
                                            <i class="fas fa-comments"></i>
                                            <span>Comments (<?php echo $project['total_comments']; ?>)</span>
                                        </div>
                                        <button type="button" class="btn btn-primary btn-sm"
                                                onclick="openCommentsModal(<?php echo $project['id']; ?>)">
                                            <i class="fas fa-expand me-1"></i>View All Comments
                                        </button>
                                    </div>

                                    <!-- Quick Comment Form -->
                                    <div class="comment-form mb-3">
                                        <form method="post">
                                            <?php echo getCSRFField(); ?>
                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                            <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                            <div class="mb-3">
                                                <textarea class="form-control" name="comment_text" rows="2"
                                                          placeholder="Add a quick comment..."></textarea>
                                            </div>
                                            <button type="submit" name="submit_comment" class="btn btn-primary btn-sm">
                                                <i class="fas fa-paper-plane me-2"></i>Post Comment
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Recent Comments Preview -->
                                    <div class="comments-preview">
                                        <?php
                                        $recent_comments = getProjectComments($conn, $project['id'], $session_id);
                                        $preview_comments = array_slice($recent_comments, 0, 3);
                                        if (!empty($preview_comments)) :
                                            ?>
                                            <?php foreach ($preview_comments as $comment) : ?>
                                            <div class="comment-item">
                                                <div class="comment-header">
                                                    <div class="comment-author">
                                                        <div class="comment-avatar">
                                                            <?php echo strtoupper(substr($comment['user_name'], 0, 1)); ?>
                                                        </div>
                                                        <div class="comment-meta">
                                                            <div class="comment-username">
                                                                <?php echo htmlspecialchars($comment['user_name']); ?>
                                                                <?php if ($comment['user_id'] === $session_id) : ?>
                                                                    <span class="badge bg-primary ms-1" style="font-size: 0.6rem;">You</span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="comment-date">
                                                                <?php echo date('M j, Y', strtotime($comment['created_at'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="comment-text">
                                                    <?php echo nl2br(htmlspecialchars(mb_strimwidth($comment['comment_text'], 0, 150, '...'))); ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php if (count($recent_comments) > 3) : ?>
                                            <div class="text-center">
                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                        onclick="openCommentsModal(<?php echo $project['id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>
                                                    View All <?php echo $project['total_comments']; ?> Comments
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <div class="text-center py-3">
                                                <i class="fas fa-comments text-muted" style="font-size: 2rem;"></i>
                                                <p class="text-muted mb-0">No comments yet. Be the first!</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Close
                                </button>

                                <!-- Quick Actions in Modal Footer -->
                                <form method="POST" action="all_projects.php" style="display:inline;">
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                    <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                    <input type="hidden" name="toggle_like" value="1">
                                    <button type="submit"
                                            class="btn <?php echo $project['is_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                        <i class="fas fa-heart me-2"></i>
                                        <?php echo $project['is_liked'] ? 'Unlike' : 'Like'; ?> (<?php echo $project['total_likes']; ?>)
                                    </button>
                                </form>

                                <form method="POST" action="all_projects.php" style="display:inline;">
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                    <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                    <input type="hidden" name="toggle_bookmark" value="1">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-bookmark me-2"></i>
                                        <?php echo $project['is_bookmarked'] ? 'Remove Bookmark' : 'Add Bookmark'; ?>
                                    </button>
                                </form>

                                <?php if ($project['is_owner']) : ?>
                                    <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit me-2"></i>Edit Project
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Modal -->
                <div class="modal fade" id="commentsModal<?php echo $project['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-comments me-2"></i>
                                    Comments for "<?php echo htmlspecialchars($project['project_name']); ?>"
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Comment Form -->
                                <div class="comment-form mb-4">
                                    <form method="post">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                        <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                        <div class="mb-3">
                                            <label for="modal_comment_text_<?php echo $project['id']; ?>" class="form-label fw-bold">
                                                <i class="fas fa-user-circle me-2"></i>Commenting as <?php echo htmlspecialchars($user_name); ?>
                                            </label>
                                            <textarea class="form-control" id="modal_comment_text_<?php echo $project['id']; ?>"
                                                      name="comment_text" rows="3"
                                                      placeholder="Share your thoughts about this project..."></textarea>
                                        </div>
                                        <div class="comment-form-actions">
                                            <button type="submit" name="submit_comment" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Post Comment
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Comments List -->
                                <div class="comments-list">
                                    <?php
                                    $modal_comments = getProjectComments($conn, $project['id'], $session_id);
                                    if (!empty($modal_comments)) :
                                        ?>
                                        <?php foreach ($modal_comments as $comment) : ?>
                                        <div class="comment-item" data-comment-id="<?php echo $comment['id']; ?>">
                                            <div class="comment-header">
                                                <div class="comment-author">
                                                    <div class="comment-avatar">
                                                        <?php echo strtoupper(substr($comment['user_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="comment-meta">
                                                        <div class="comment-username">
                                                            <?php echo htmlspecialchars($comment['user_name']); ?>
                                                            <?php if ($comment['user_id'] === $session_id) : ?>
                                                                <span class="badge bg-primary ms-1" style="font-size: 0.6rem;">You</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="comment-date">
                                                            <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="comment-text">
                                                <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                                            </div>
                                            <div class="comment-actions">
                                                <form method="POST" action="all_projects.php" style="display:inline;">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                                    <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                                    <input type="hidden" name="toggle_comment_like" value="1">
                                                    <button type="submit"
                                                            class="comment-like-btn<?php echo $comment['is_liked'] ? ' liked' : ''; ?>">
                                                        <i class="fas fa-heart"></i>
                                                        <span><?php echo $comment['comment_likes_count']; ?></span>
                                                    </button>
                                                </form>

                                                <button type="button" class="reply-btn"
                                                        onclick="toggleModalReplyForm(<?php echo $comment['id']; ?>)">
                                                    <i class="fas fa-reply"></i>
                                                    <span>Reply</span>
                                                </button>
                                                
                                                <?php if ($comment['user_id'] === $session_id) : ?>
                                                    <button type="button" class="reply-btn"
                                                            onclick="editComment(<?php echo $comment['id']; ?>, '<?php echo htmlspecialchars(addslashes($comment['comment_text'])); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                        <span>Edit</span>
                                                    </button>
                                                    <button type="button" class="reply-btn text-danger"
                                                            onclick="deleteComment(<?php echo $comment['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                        <span>Delete</span>
                                                    </button>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Reply Form -->
                                            <div class="reply-form" id="modalReplyForm<?php echo $comment['id']; ?>" style="display: none;">
                                                <form method="post">
                                            <?php echo getCSRFField(); ?>
                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                    <input type="hidden" name="parent_comment_id" value="<?php echo $comment['id']; ?>">
                                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                                    <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                                    <textarea name="comment_text" rows="2" placeholder="Write a reply..." required></textarea>
                                                    <div class="d-flex justify-content-end gap-2 mt-2">
                                                        <button type="button" class="btn btn-secondary btn-sm"
                                                                onclick="toggleModalReplyForm(<?php echo $comment['id']; ?>)">
                                                            Cancel
                                                        </button>
                                                        <button type="submit" name="submit_comment" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-paper-plane me-1"></i>Reply
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Display Replies -->
                                            <?php if (!empty($comment['replies'])) : ?>
                                                <?php foreach ($comment['replies'] as $reply) : ?>
                                                    <div class="comment-item reply-comment" data-comment-id="<?php echo $reply['id']; ?>">
                                                        <div class="comment-header">
                                                            <div class="comment-author">
                                                                <div class="comment-avatar">
                                                                    <?php echo strtoupper(substr($reply['user_name'], 0, 1)); ?>
                                                                </div>
                                                                <div class="comment-meta">
                                                                    <div class="comment-username">
                                                                        <?php echo htmlspecialchars($reply['user_name']); ?>
                                                                        <?php if ($reply['user_id'] === $session_id) : ?>
                                                                            <span class="badge bg-primary ms-1" style="font-size: 0.6rem;">You</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="comment-date">
                                                                        <?php echo date('M j, Y \a\t g:i A', strtotime($reply['created_at'])); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="comment-text">
                                                            <?php echo nl2br(htmlspecialchars($reply['comment_text'])); ?>
                                                        </div>
                                                        <div class="comment-actions">
                                                            <form method="POST" action="all_projects.php" style="display:inline;">
                                                                <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                                                                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                                                <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                                                <input type="hidden" name="toggle_comment_like" value="1">
                                                                <button type="submit"
                                                                        class="comment-like-btn<?php echo $reply['is_liked'] ? ' liked' : ''; ?>">
                                                                    <i class="fas fa-heart"></i>
                                                                    <span><?php echo $reply['comment_likes_count']; ?></span>
                                                                </button>
                                                            </form>
                                                            
                                                            <?php if ($reply['user_id'] === $session_id) : ?>
                                                                <button type="button" class="reply-btn"
                                                                        onclick="editComment(<?php echo $reply['id']; ?>, '<?php echo htmlspecialchars(addslashes($reply['comment_text'])); ?>')">
                                                                    <i class="fas fa-edit"></i>
                                                                    <span>Edit</span>
                                                                </button>
                                                                <button type="button" class="reply-btn text-danger"
                                                                        onclick="deleteComment(<?php echo $reply['id']; ?>)">
                                                                    <i class="fas fa-trash"></i>
                                                                    <span>Delete</span>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-comments text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                            <p class="text-muted">No comments yet. Be the first to share your thoughts!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
    // Embed the JavaScript directly to avoid loading issues
    let activeReplyForm = null;
    let commentCache = new Map();

    // Ensure functions are in global scope
    window.openProjectModal = function(projectId) {
        const modal = new bootstrap.Modal(document.getElementById('projectModal' + projectId));
        modal.show();
    };

    window.openCommentsModal = function(projectId) {
        const modal = new bootstrap.Modal(document.getElementById('commentsModal' + projectId));
        modal.show();
    };

    window.toggleReplyForm = function(commentId) {
        if (activeReplyForm && activeReplyForm !== commentId) {
            const previousForm = document.getElementById('replyForm' + activeReplyForm);
            if (previousForm) {
                previousForm.style.display = 'none';
            }
        }

        const replyForm = document.getElementById('replyForm' + commentId);
        if (replyForm) {
            if (replyForm.style.display === 'none' || replyForm.style.display === '') {
                replyForm.style.display = 'block';
                const textarea = replyForm.querySelector('textarea');
                if (textarea) {
                    setTimeout(() => textarea.focus(), 100);
                }
                activeReplyForm = commentId;
            } else {
                replyForm.style.display = 'none';
                activeReplyForm = null;
            }
        }
    };

    window.toggleModalReplyForm = function(commentId) {
        const replyForm = document.getElementById('modalReplyForm' + commentId);
        if (replyForm) {
            if (replyForm.style.display === 'none' || replyForm.style.display === '') {
                replyForm.style.display = 'block';
                const textarea = replyForm.querySelector('textarea');
                if (textarea) {
                    setTimeout(() => textarea.focus(), 100);
                }
            } else {
                replyForm.style.display = 'none';
            }
        }
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize card clicks
        const projectCards = document.querySelectorAll('.project-card');
        projectCards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Check if click was on an interactive element
                if (e.target.closest('.project-actions') ||
                    e.target.closest('.action-btn') ||
                    e.target.closest('form') ||
                    e.target.closest('button') ||
                    e.target.closest('a')) {
                    return; // Don't open modal
                }

                // Get project ID and open modal
                const projectIdInput = this.querySelector('input[name="project_id"]');
                if (projectIdInput) {
                    const projectId = projectIdInput.value;
                    openProjectModal(projectId);
                }
            });
        });

        // Prevent action buttons and forms from triggering card click
        const actionElements = document.querySelectorAll('.project-actions, .action-btn, .bookmark-btn, .like-btn, form');
        actionElements.forEach(element => {
            element.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Also prevent form submission from triggering card click
            if (element.tagName === 'FORM') {
                element.addEventListener('submit', function(e) {
                    e.stopPropagation();
                });
            }
        });

        // Initialize comment system
        const textareas = document.querySelectorAll('textarea[name="comment_text"]');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            const form = textarea.closest('form');
            const submitBtn = form.querySelector('button[name="submit_comment"]');

            if (submitBtn) {
                textarea.addEventListener('input', function() {
                    const text = this.value.trim();
                    if (text.length > 0 && text.length <= 500) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('btn-secondary');
                        submitBtn.classList.add('btn-primary');
                    } else {
                        submitBtn.disabled = true;
                        submitBtn.classList.remove('btn-primary');
                        submitBtn.classList.add('btn-secondary');
                    }
                });

                // Initial state
                if (textarea.value.trim().length === 0) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('btn-secondary');
                    submitBtn.classList.remove('btn-primary');
                }
            }
        });

        // Initialize like buttons
        const likeButtons = document.querySelectorAll('.like-btn, .comment-like-btn');
        likeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                this.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            });
        });

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

        console.log('All projects page initialized successfully');
    });
    
    // Edit comment function
    window.editComment = function(commentId, currentText) {
        const commentElement = document.querySelector(`[data-comment-id="${commentId}"] .comment-text`);
        if (!commentElement) return;
        
        const originalText = commentElement.innerHTML;
        
        // Create edit form
        const editForm = `
            <div class="edit-comment-form">
                <textarea class="form-control mb-2" rows="3">${currentText}</textarea>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" onclick="saveComment(${commentId}, this)">
                        <i class="fas fa-save me-1"></i>Save
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="cancelEdit(${commentId}, '${originalText.replace(/'/g, "\\'")}')">Cancel</button>
                </div>
            </div>
        `;
        
        commentElement.innerHTML = editForm;
        commentElement.querySelector('textarea').focus();
    };
    
    // Save edited comment
    window.saveComment = function(commentId, button) {
        const textarea = button.parentElement.parentElement.querySelector('textarea');
        const newText = textarea.value.trim();
        
        if (!newText) {
            alert('Comment cannot be empty');
            return;
        }
        
        // Show loading
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        button.disabled = true;
        
        // Create hidden form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="edit_comment" value="1">
            <input type="hidden" name="comment_id" value="${commentId}">
            <input type="hidden" name="comment_text" value="${newText}">
        `;
        document.body.appendChild(form);
        form.submit();

    };
    
    // Cancel edit
    window.cancelEdit = function(commentId, originalText) {
        const commentElement = document.querySelector(`[data-comment-id="${commentId}"] .comment-text`);
        if (commentElement) {
            commentElement.innerHTML = originalText;
        }
    };
    
    // Delete comment function
    window.deleteComment = function(commentId) {
        if (!confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
            return;
        }
        
        // Create hidden form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_comment" value="1">
            <input type="hidden" name="comment_id" value="${commentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    };
    
    // Toast notification function
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }
</script>
</body>
</html>