<?php
require_once __DIR__ . '/../includes/security_init.php';

// Production-safe error reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

include '../Login/Login/db.php';
require_once '../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Guest User";

// Get project ID from URL
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$project_id) {
    header("Location: all_projects.php?error=" . urlencode("Invalid project ID"));
    exit();
}

// Handle like toggle
if (isset($_POST['toggle_like']) && $user_id) {
    requireCSRF();
    
    $check_like_sql = "SELECT * FROM project_likes WHERE project_id = ? AND user_id = ?";
    $check_like_stmt = $conn->prepare($check_like_sql);
    $check_like_stmt->bind_param("ii", $project_id, $user_id);
    $check_like_stmt->execute();
    $like_result = $check_like_stmt->get_result();

    if ($like_result->num_rows > 0) {
        $delete_like_sql = "DELETE FROM project_likes WHERE project_id = ? AND user_id = ?";
        $delete_like_stmt = $conn->prepare($delete_like_sql);
        $delete_like_stmt->bind_param("ii", $project_id, $user_id);
        $delete_like_stmt->execute();
        $delete_like_stmt->close();
    } else {
        $insert_like_sql = "INSERT INTO project_likes (project_id, user_id) VALUES (?, ?)";
        $insert_like_stmt = $conn->prepare($insert_like_sql);
        $insert_like_stmt->bind_param("ii", $project_id, $user_id);
        $insert_like_stmt->execute();
        $insert_like_stmt->close();
    }
    $check_like_stmt->close();
    
    header("Location: view_idea.php?id=" . $project_id);
    exit();
}



// Handle bookmark toggle
if (isset($_POST['toggle_bookmark']) && $user_id) {
    requireCSRF();
    
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
    } else {
        $idea_id = 0;
        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iii", $project_id, $user_id, $idea_id);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    $check_stmt->close();
    
    header("Location: view_idea.php?id=" . $project_id);
    exit();
}

// Fetch project details with user profile
$sql = "SELECT ap.*, 
               r.name as user_name, r.email as user_email, r.phone_no as user_phone, 
               r.about as user_bio, r.department as user_department, r.passout_year,
               r.enrollment_number, r.gr_number, r.user_image as user_avatar,
               CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked,
               CASE WHEN pl.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked,
               COALESCE(like_counts.total_likes, 0) AS total_likes,
               COALESCE(user_stats.user_total_projects, 0) AS user_total_projects,
               COALESCE(user_stats.user_approved_projects, 0) AS user_approved_projects
        FROM admin_approved_projects ap
        LEFT JOIN register r ON ap.user_id = r.id
        LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?
        LEFT JOIN project_likes pl ON ap.id = pl.project_id AND pl.user_id = ?
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
        ) user_stats ON ap.user_id = user_stats.user_id
        WHERE ap.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Database prepare failed: " . $conn->error);
    header("Location: all_projects.php?error=" . urlencode("Database error"));
    exit();
}

$stmt->bind_param("iii", $user_id, $user_id, $project_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: all_projects.php?error=" . urlencode("Project not found"));
    exit();
}

$project = $result->fetch_assoc();
$stmt->close();

// Check if current user is following the project creator
$is_following = false;
$follow_counts = ['followers' => 0, 'following' => 0];
if ($user_id && $user_id != $project['user_id']) {
    $follow_check_sql = "SELECT EXISTS(SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = ?) as is_following";
    $follow_check_stmt = $conn->prepare($follow_check_sql);
    $follow_check_stmt->bind_param("ii", $user_id, $project['user_id']);
    $follow_check_stmt->execute();
    $follow_check_result = $follow_check_stmt->get_result();
    $follow_check_row = $follow_check_result->fetch_assoc();
    $is_following = (bool)$follow_check_row['is_following'];
    $follow_check_stmt->close();
}

// Get follower/following counts for the project creator
$follow_stats_sql = "SELECT COALESCE(followers_count, 0) as followers, COALESCE(following_count, 0) as following FROM user_follow_stats WHERE user_id = ?";
$follow_stats_stmt = $conn->prepare($follow_stats_sql);
$follow_stats_stmt->bind_param("i", $project['user_id']);
$follow_stats_stmt->execute();
$follow_stats_result = $follow_stats_stmt->get_result();
if ($follow_stats_row = $follow_stats_result->fetch_assoc()) {
    $follow_counts = $follow_stats_row;
}
$follow_stats_stmt->close();

// Get comprehensive user statistics
$user_stats_sql = "SELECT 
    (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ?) as total_projects,
    (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ? AND status = 'approved') as approved_projects,
    (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ? AND status = 'pending') as pending_projects,
    (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ? AND status = 'rejected') as rejected_projects,
    (SELECT COUNT(*) FROM blog WHERE user_id = ?) as total_ideas,
    (SELECT COUNT(*) FROM project_likes pl JOIN admin_approved_projects ap ON pl.project_id = ap.id WHERE ap.user_id = ?) as total_project_likes,
    (SELECT COUNT(*) FROM idea_likes il JOIN blog b ON il.idea_id = b.id WHERE b.user_id = ?) as total_idea_likes,
    (SELECT COUNT(DISTINCT classification) FROM admin_approved_projects WHERE user_id = ?) as unique_categories,
    (SELECT COUNT(DISTINCT project_type) FROM admin_approved_projects WHERE user_id = ?) as unique_types";

$user_stats_stmt = $conn->prepare($user_stats_sql);
$user_stats_stmt->bind_param("iiiiiiiii", 
    $project['user_id'], $project['user_id'], $project['user_id'], $project['user_id'],
    $project['user_id'], $project['user_id'], $project['user_id'], $project['user_id'],
    $project['user_id']
);
$user_stats_stmt->execute();
$user_stats_result = $user_stats_stmt->get_result();
$user_stats = $user_stats_result->fetch_assoc();
$user_stats_stmt->close();

// Get other projects by the same user
$other_projects_sql = "SELECT id, project_name, project_type, classification, submission_date 
                       FROM admin_approved_projects 
                       WHERE user_id = ? AND id != ? 
                       ORDER BY submission_date DESC 
                       LIMIT 5";
$other_projects_stmt = $conn->prepare($other_projects_sql);
$other_projects_stmt->bind_param("ii", $project['user_id'], $project_id);
$other_projects_stmt->execute();
$other_projects_result = $other_projects_stmt->get_result();
$other_projects = [];
while ($row = $other_projects_result->fetch_assoc()) {
    $other_projects[] = $row;
}
$other_projects_stmt->close();

// Get user's recent ideas
$user_ideas_sql = "SELECT id, project_name as title, classification as category, submission_datetime 
                   FROM blog 
                   WHERE user_id = ? 
                   ORDER BY submission_datetime DESC 
                   LIMIT 5";
$user_ideas_stmt = $conn->prepare($user_ideas_sql);
$user_ideas_stmt->bind_param("i", $project['user_id']);
$user_ideas_stmt->execute();
$user_ideas_result = $user_ideas_stmt->get_result();
$user_ideas = [];
while ($row = $user_ideas_result->fetch_assoc()) {
    $user_ideas[] = $row;
}
$user_ideas_stmt->close();

// Helper functions
function formatDifficultyLevel($level) {
    $levels = [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate', 
        'advanced' => 'Advanced',
        'expert' => 'Expert'
    ];
    return isset($levels[$level]) ? $levels[$level] : ucfirst($level);
}

function getFileIcon($filePath) {
    if (empty($filePath)) return 'fas fa-file';
    
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


?><
!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['project_name']); ?> - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .back-button:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .project-header {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem;
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .project-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .project-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .project-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            backdrop-filter: blur(10px);
        }

        .project-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 2rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-2px);
        }

        .action-btn.liked {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
        }

        .action-btn.bookmarked {
            background: rgba(245, 158, 11, 0.2);
            border-color: rgba(245, 158, 11, 0.5);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .main-content-area {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar-area {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .content-card {
            background: var(--bg-primary);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }

        .content-card h3 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .content-card h3 i {
            color: var(--primary-color);
        }

        .user-profile-card {
            text-align: center;
        }

        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: white;
            box-shadow: var(--shadow-lg);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .user-department {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .user-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 1rem;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .user-bio {
            background: var(--bg-tertiary);
            padding: 1.5rem;
            border-radius: 1rem;
            margin: 1.5rem 0;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .project-badges {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .project-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
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

        .badge-difficulty {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1));
            color: var(--accent-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .project-description {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .project-links {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .project-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 1rem;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .project-link:hover {
            background: var(--bg-primary);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .project-link i {
            width: 20px;
            text-align: center;
            color: var(--primary-color);
        }

        .project-files {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .file-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 1rem;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .file-item:hover {
            background: var(--bg-primary);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .keywords {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .keyword-tag {
            padding: 0.5rem 1rem;
            background: var(--bg-tertiary);
            border-radius: 2rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }



        .other-projects {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Empty state for no items */
        .other-projects:empty::after {
            content: 'No items to display';
            display: block;
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-style: italic;
        }

        /* Stagger animation for list items */
        .other-project-item {
            animation: fadeInUp 0.5s ease-out backwards;
        }

        .other-project-item:nth-child(1) { animation-delay: 0.1s; }
        .other-project-item:nth-child(2) { animation-delay: 0.2s; }
        .other-project-item:nth-child(3) { animation-delay: 0.3s; }
        .other-project-item:nth-child(4) { animation-delay: 0.4s; }
        .other-project-item:nth-child(5) { animation-delay: 0.5s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .other-project-item {
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 1rem;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid var(--border-color);
            position: relative;
            overflow: hidden;
            display: block;
        }

        .other-project-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .other-project-item:hover {
            background: var(--bg-primary);
            color: var(--primary-color);
            transform: translateY(-4px) translateX(2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
            border-color: var(--primary-color);
        }

        .other-project-item:hover::before {
            left: 100%;
        }

        .other-project-item:active {
            transform: translateY(-2px) translateX(1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
            transition: all 0.1s ease;
        }

        .other-project-item:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        .other-project-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            color: var(--text-primary);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .other-project-item:hover .other-project-title {
            color: var(--primary-color);
            transform: translateX(4px);
        }

        .other-project-meta {
            font-size: 0.875rem;
            color: var(--text-muted);
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .other-project-item:hover .other-project-meta {
            color: var(--text-secondary);
        }

        .other-project-meta i {
            font-size: 0.75rem;
            opacity: 0.7;
        }

        /* Special styling for idea items */
        .other-project-item.idea-item {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(251, 191, 36, 0.05));
            border-color: rgba(245, 158, 11, 0.2);
        }

        .other-project-item.idea-item:hover {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.1));
            border-color: var(--warning-color);
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.2);
        }

        .other-project-item.idea-item:hover .other-project-title {
            color: var(--warning-color);
        }

        .other-project-item.idea-item::before {
            background: linear-gradient(90deg, transparent, rgba(245, 158, 11, 0.15), transparent);
        }

        /* Click animation */
        @keyframes clickPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(99, 102, 241, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
            }
        }

        .other-project-item:active {
            animation: clickPulse 0.4s ease-out;
        }

        .other-project-item.idea-item:active {
            animation: clickPulse 0.4s ease-out;
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
        }

        /* Add icon animation on hover */
        .other-project-title i {
            transition: transform 0.3s ease;
        }

        .other-project-item:hover .other-project-title i {
            transform: scale(1.2) rotate(10deg);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .project-title {
                font-size: 2rem;
            }

            .project-meta {
                gap: 1rem;
            }

            .project-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'layout.php'; ?>
    
    <div class="main-content">
        <a href="all_projects.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Projects
        </a>

        <!-- Project Header -->
        <div class="project-header">
            <h1 class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></h1>
            
            <div class="project-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo date('M d, Y', strtotime($project['submission_date'])); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-heart"></i>
                    <span><?php echo $project['total_likes']; ?> likes</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo htmlspecialchars($project['development_time']); ?></span>
                </div>
            </div>

            <?php if ($user_id): ?>
            <div class="project-actions">
                <form method="post" style="display: inline;">
                    <?php echo getCSRFField(); ?>
                    <button type="submit" name="toggle_like" class="action-btn <?php echo $project['is_liked'] ? 'liked' : ''; ?>">
                        <i class="fas fa-heart"></i>
                        <?php echo $project['is_liked'] ? 'Unlike' : 'Like'; ?>
                    </button>
                </form>
                
                <form method="post" style="display: inline;">
                    <?php echo getCSRFField(); ?>
                    <button type="submit" name="toggle_bookmark" class="action-btn <?php echo $project['is_bookmarked'] ? 'bookmarked' : ''; ?>">
                        <i class="fas fa-bookmark"></i>
                        <?php echo $project['is_bookmarked'] ? 'Unbookmark' : 'Bookmark'; ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Comprehensive Statistics Banner -->
        <div class="content-card" style="margin-bottom: 2rem;">
            <h3><i class="fas fa-chart-pie"></i>Creator Statistics - <?php echo htmlspecialchars($project['user_name']); ?></h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                <!-- Projects Stats -->
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 1rem; border: 2px solid rgba(99, 102, 241, 0.2);">
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo $user_stats['total_projects']; ?>
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 600;">Total Projects</div>
                    <div style="margin-top: 0.75rem; font-size: 0.75rem; color: var(--text-muted);">
                        <span style="color: var(--success-color);">✓ <?php echo $user_stats['approved_projects']; ?> Approved</span> • 
                        <span style="color: var(--warning-color);">⏱ <?php echo $user_stats['pending_projects']; ?> Pending</span>
                    </div>
                </div>

                <!-- Ideas Stats -->
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1)); border-radius: 1rem; border: 2px solid rgba(16, 185, 129, 0.2);">
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--accent-color); margin-bottom: 0.5rem;">
                        <?php echo $user_stats['total_ideas']; ?>
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 600;">Total Ideas</div>
                    <div style="margin-top: 0.75rem; font-size: 0.75rem; color: var(--text-muted);">
                        <i class="fas fa-lightbulb"></i> Innovative Concepts
                    </div>
                </div>

                <!-- Likes Stats -->
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(248, 113, 113, 0.1)); border-radius: 1rem; border: 2px solid rgba(239, 68, 68, 0.2);">
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--danger-color); margin-bottom: 0.5rem;">
                        <?php echo $user_stats['total_project_likes'] + $user_stats['total_idea_likes']; ?>
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 600;">Total Likes</div>
                    <div style="margin-top: 0.75rem; font-size: 0.75rem; color: var(--text-muted);">
                        <i class="fas fa-heart"></i> Community Appreciation
                    </div>
                </div>

                <!-- Categories Stats -->
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.1)); border-radius: 1rem; border: 2px solid rgba(245, 158, 11, 0.2);">
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--warning-color); margin-bottom: 0.5rem;">
                        <?php echo $user_stats['unique_categories']; ?>
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 600;">Categories</div>
                    <div style="margin-top: 0.75rem; font-size: 0.75rem; color: var(--text-muted);">
                        <i class="fas fa-tags"></i> Diverse Expertise
                    </div>
                </div>

                <!-- Engagement Score -->
                <div style="text-align: center; padding: 1.5rem; background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(168, 85, 247, 0.1)); border-radius: 1rem; border: 2px solid rgba(139, 92, 246, 0.2);">
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--secondary-color); margin-bottom: 0.5rem;">
                        <?php 
                        $engagement_score = $user_stats['total_project_likes'] + $user_stats['total_idea_likes'];
                        echo $engagement_score;
                        ?>
                    </div>
                    <div style="font-size: 0.875rem; color: var(--text-secondary); font-weight: 600;">Engagement Score</div>
                    <div style="margin-top: 0.75rem; font-size: 0.75rem; color: var(--text-muted);">
                        <i class="fas fa-fire"></i> Community Impact
                    </div>
                </div>
            </div>

            <!-- Detailed Breakdown -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: var(--bg-tertiary); border-radius: 1rem;">
                <h6 style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem;">
                    <i class="fas fa-info-circle me-2"></i>Detailed Breakdown
                </h6>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div>
                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">Projects by Status</div>
                        <div style="display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.875rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);"><i class="fas fa-check-circle" style="color: var(--success-color);"></i> Approved:</span>
                                <span style="font-weight: 600;"><?php echo $user_stats['approved_projects']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);"><i class="fas fa-clock" style="color: var(--warning-color);"></i> Pending:</span>
                                <span style="font-weight: 600;"><?php echo $user_stats['pending_projects']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);"><i class="fas fa-times-circle" style="color: var(--danger-color);"></i> Rejected:</span>
                                <span style="font-weight: 600;"><?php echo $user_stats['rejected_projects']; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">Engagement Metrics</div>
                        <div style="display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.875rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);"><i class="fas fa-heart" style="color: var(--danger-color);"></i> Project Likes:</span>
                                <span style="font-weight: 600;"><?php echo $user_stats['total_project_likes']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);"><i class="fas fa-heart" style="color: var(--danger-color);"></i> Idea Likes:</span>
                                <span style="font-weight: 600;"><?php echo $user_stats['total_idea_likes']; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">Diversity Metrics</div>
                        <div style="display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.875rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);"><i class="fas fa-tags" style="color: var(--warning-color);"></i> Unique Categories:</span>
                                <span style="font-weight: 600;"><?php echo $user_stats['unique_categories']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);"><i class="fas fa-layer-group" style="color: var(--secondary-color);"></i> Project Types:</span>
                                <span style="font-weight: 600;"><?php echo $user_stats['unique_types']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-secondary);"><i class="fas fa-trophy" style="color: var(--warning-color);"></i> Success Rate:</span>
                                <span style="font-weight: 600;">
                                    <?php 
                                    $success_rate = $user_stats['total_projects'] > 0 
                                        ? round(($user_stats['approved_projects'] / $user_stats['total_projects']) * 100) 
                                        : 0;
                                    echo $success_rate . '%';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Main Content Area -->
            <div class="main-content-area">
                <!-- Project Details -->
                <div class="content-card">
                    <h3><i class="fas fa-info-circle"></i>Project Details</h3>
                    
                    <div class="project-badges">
                        <span class="project-badge badge-classification"><?php echo htmlspecialchars($project['classification']); ?></span>
                        <span class="project-badge badge-type"><?php echo htmlspecialchars($project['project_type']); ?></span>
                        <span class="project-badge badge-difficulty"><?php echo formatDifficultyLevel($project['difficulty_level']); ?></span>
                    </div>

                    <div class="project-description">
                        <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                    </div>
                </div>

                <!-- Project Goals -->
                <?php if (!empty($project['project_goals'])): ?>
                <div class="content-card">
                    <h3><i class="fas fa-bullseye"></i>Project Goals</h3>
                    <div class="project-description">
                        <?php echo nl2br(htmlspecialchars($project['project_goals'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Challenges Faced -->
                <?php if (!empty($project['challenges_faced'])): ?>
                <div class="content-card">
                    <h3><i class="fas fa-exclamation-triangle"></i>Challenges Faced</h3>
                    <div class="project-description">
                        <?php echo nl2br(htmlspecialchars($project['challenges_faced'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Future Enhancements -->
                <?php if (!empty($project['future_enhancements'])): ?>
                <div class="content-card">
                    <h3><i class="fas fa-rocket"></i>Future Enhancements</h3>
                    <div class="project-description">
                        <?php echo nl2br(htmlspecialchars($project['future_enhancements'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Project Links -->
                <div class="content-card">
                    <h3><i class="fas fa-link"></i>Links & Resources</h3>
                    <div class="project-links">
                        <?php if (!empty($project['github_repo'])): ?>
                        <a href="<?php echo htmlspecialchars($project['github_repo']); ?>" target="_blank" class="project-link">
                            <i class="fab fa-github"></i>
                            <div>
                                <div style="font-weight: 600;">GitHub Repository</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">View source code</div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($project['live_demo_url'])): ?>
                        <a href="<?php echo htmlspecialchars($project['live_demo_url']); ?>" target="_blank" class="project-link">
                            <i class="fas fa-external-link-alt"></i>
                            <div>
                                <div style="font-weight: 600;">Live Demo</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">Try the application</div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($project['contact_email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($project['contact_email']); ?>" class="project-link">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <div style="font-weight: 600;">Contact Email</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);"><?php echo htmlspecialchars($project['contact_email']); ?></div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Files -->
                <div class="content-card">
                    <h3><i class="fas fa-file"></i>Project Files</h3>
                    <div class="project-files">
                        <?php if (!empty($project['image_path'])): ?>
                        <a href="<?php echo htmlspecialchars($project['image_path']); ?>" target="_blank" class="file-item">
                            <i class="fas fa-image"></i>
                            <div>
                                <div style="font-weight: 600;">Project Image</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">View screenshot</div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($project['video_path'])): ?>
                        <a href="<?php echo htmlspecialchars($project['video_path']); ?>" target="_blank" class="file-item">
                            <i class="fas fa-video"></i>
                            <div>
                                <div style="font-weight: 600;">Demo Video</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">Watch demo</div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($project['code_file_path'])): ?>
                        <a href="<?php echo htmlspecialchars($project['code_file_path']); ?>" target="_blank" class="file-item">
                            <i class="fas fa-code"></i>
                            <div>
                                <div style="font-weight: 600;">Source Code</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">Download code</div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($project['presentation_file_path'])): ?>
                        <a href="<?php echo htmlspecialchars($project['presentation_file_path']); ?>" target="_blank" class="file-item">
                            <i class="fas fa-presentation"></i>
                            <div>
                                <div style="font-weight: 600;">Presentation</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">View slides</div>
                            </div>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($project['instruction_file_path'])): ?>
                        <a href="<?php echo htmlspecialchars($project['instruction_file_path']); ?>" target="_blank" class="file-item">
                            <i class="fas fa-file-alt"></i>
                            <div>
                                <div style="font-weight: 600;">Instructions</div>
                                <div style="font-size: 0.875rem; color: var(--text-muted);">Setup guide</div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Keywords -->
                <?php if (!empty($project['keywords'])): ?>
                <div class="content-card">
                    <h3><i class="fas fa-tags"></i>Keywords</h3>
                    <div class="keywords">
                        <?php 
                        $keywords = explode(',', $project['keywords']);
                        foreach ($keywords as $keyword): 
                        ?>
                        <span class="keyword-tag"><?php echo trim(htmlspecialchars($keyword)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Sidebar Area -->
            <div class="sidebar-area">
                <!-- User Profile Card -->
                <div class="content-card user-profile-card">
                    <h3><i class="fas fa-user"></i>Project Creator</h3>
                    
                    <div class="user-avatar">
                        <?php if (!empty($project['user_avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($project['user_avatar']); ?>" alt="User Avatar">
                        <?php else: ?>
                            <?php echo strtoupper(substr($project['user_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>

                    <div class="user-name"><?php echo htmlspecialchars($project['user_name']); ?></div>
                    <div class="user-department"><?php echo htmlspecialchars($project['user_department']); ?></div>

                    <!-- Follow Stats -->
                    <div class="follow-stats" style="display: flex; gap: 1.5rem; justify-content: center; margin: 1rem 0; padding: 1rem; background: var(--bg-tertiary); border-radius: 1rem;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color);" class="follower-count"><?php echo $follow_counts['followers']; ?></div>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);">Followers</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color);"><?php echo $follow_counts['following']; ?></div>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);">Following</div>
                        </div>
                    </div>

                    <!-- Follow Button -->
                    <?php if ($user_id && $user_id != $project['user_id']): ?>
                        <button class="btn-follow <?php echo $is_following ? 'following' : ''; ?>" 
                                data-user-id="<?php echo $project['user_id']; ?>"
                                data-action="<?php echo $is_following ? 'unfollow' : 'follow'; ?>"
                                style="padding: 0.75rem 1.5rem; border: 2px solid var(--primary-color); background: <?php echo $is_following ? 'var(--primary-color)' : 'white'; ?>; color: <?php echo $is_following ? 'white' : 'var(--primary-color)'; ?>; border-radius: 2rem; cursor: pointer; transition: all 0.3s ease; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 1rem; width: 100%; justify-content: center;">
                            <i class="fas fa-user-<?php echo $is_following ? 'check' : 'plus'; ?>"></i>
                            <span class="follow-text"><?php echo $is_following ? 'Following' : 'Follow'; ?></span>
                        </button>
                    <?php endif; ?>

                    <div class="user-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_stats['total_projects']; ?></div>
                            <div class="stat-label">Total Projects</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_stats['approved_projects']; ?></div>
                            <div class="stat-label">Approved</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_stats['total_ideas']; ?></div>
                            <div class="stat-label">Total Ideas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_stats['pending_projects']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>

                    <!-- Additional Statistics -->
                    <div class="user-engagement-stats" style="margin-top: 1.5rem; padding: 1.5rem; background: var(--bg-tertiary); border-radius: 1rem;">
                        <h6 style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem; text-align: center;">
                            <i class="fas fa-chart-line me-2"></i>Engagement Statistics
                        </h6>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <div style="text-align: center; padding: 0.75rem; background: var(--bg-primary); border-radius: 0.5rem;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: var(--danger-color);">
                                    <i class="fas fa-heart"></i> <?php echo $user_stats['total_project_likes'] + $user_stats['total_idea_likes']; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Total Likes</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background: var(--bg-primary); border-radius: 0.5rem;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: var(--accent-color);">
                                    <i class="fas fa-tags"></i> <?php echo $user_stats['unique_categories']; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Categories</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background: var(--bg-primary); border-radius: 0.5rem;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: var(--secondary-color);">
                                    <i class="fas fa-layer-group"></i> <?php echo $user_stats['unique_types']; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Project Types</div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Status Breakdown -->
                    <div class="project-status-breakdown" style="margin-top: 1.5rem; padding: 1.5rem; background: var(--bg-tertiary); border-radius: 1rem;">
                        <h6 style="font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem; text-align: center;">
                            <i class="fas fa-tasks me-2"></i>Project Status
                        </h6>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: var(--bg-primary); border-radius: 0.5rem;">
                                <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                    <i class="fas fa-check-circle" style="color: var(--success-color);"></i> Approved
                                </span>
                                <span style="font-weight: 600; color: var(--success-color);"><?php echo $user_stats['approved_projects']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: var(--bg-primary); border-radius: 0.5rem;">
                                <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                    <i class="fas fa-clock" style="color: var(--warning-color);"></i> Pending
                                </span>
                                <span style="font-weight: 600; color: var(--warning-color);"><?php echo $user_stats['pending_projects']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: var(--bg-primary); border-radius: 0.5rem;">
                                <span style="font-size: 0.875rem; color: var(--text-secondary);">
                                    <i class="fas fa-times-circle" style="color: var(--danger-color);"></i> Rejected
                                </span>
                                <span style="font-weight: 600; color: var(--danger-color);"><?php echo $user_stats['rejected_projects']; ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($project['user_bio'])): ?>
                    <div class="user-bio">
                        <?php echo nl2br(htmlspecialchars($project['user_bio'])); ?>
                    </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                            <i class="fas fa-id-card me-2"></i>Enrollment: <?php echo htmlspecialchars($project['enrollment_number']); ?>
                        </div>
                        <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                            <i class="fas fa-graduation-cap me-2"></i>Passout: <?php echo htmlspecialchars($project['passout_year']); ?>
                        </div>
                        <?php if (!empty($project['user_email'])): ?>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:<?php echo htmlspecialchars($project['user_email']); ?>" style="color: var(--primary-color); text-decoration: none;">
                                <?php echo htmlspecialchars($project['user_email']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Technical Details -->
                <div class="content-card">
                    <h3><i class="fas fa-cogs"></i>Technical Details</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">Language</div>
                            <div style="color: var(--text-secondary);"><?php echo htmlspecialchars($project['language']); ?></div>
                        </div>
                        
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">Team Size</div>
                            <div style="color: var(--text-secondary);"><?php echo htmlspecialchars($project['team_size']); ?></div>
                        </div>
                        
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">Target Audience</div>
                            <div style="color: var(--text-secondary);"><?php echo htmlspecialchars($project['target_audience']); ?></div>
                        </div>
                        
                        <?php if (!empty($project['project_license'])): ?>
                        <div>
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">License</div>
                            <div style="color: var(--text-secondary);"><?php echo htmlspecialchars($project['project_license']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- User Activity Overview -->
                <div class="content-card">
                    <h3><i class="fas fa-chart-bar"></i>Activity Overview</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="padding: 1rem; background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 0.75rem; border-left: 4px solid var(--primary-color);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Projects</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);"><?php echo $user_stats['total_projects']; ?></div>
                                </div>
                                <i class="fas fa-project-diagram" style="font-size: 2rem; color: var(--primary-color); opacity: 0.3;"></i>
                            </div>
                        </div>
                        
                        <div style="padding: 1rem; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(52, 211, 153, 0.1)); border-radius: 0.75rem; border-left: 4px solid var(--accent-color);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Ideas</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent-color);"><?php echo $user_stats['total_ideas']; ?></div>
                                </div>
                                <i class="fas fa-lightbulb" style="font-size: 2rem; color: var(--accent-color); opacity: 0.3;"></i>
                            </div>
                        </div>
                        
                        <div style="padding: 1rem; background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(248, 113, 113, 0.1)); border-radius: 0.75rem; border-left: 4px solid var(--danger-color);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Total Likes</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger-color);">
                                        <?php echo $user_stats['total_project_likes'] + $user_stats['total_idea_likes']; ?>
                                    </div>
                                </div>
                                <i class="fas fa-heart" style="font-size: 2rem; color: var(--danger-color); opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other Projects by User -->
                <?php if (!empty($other_projects)): ?>
                <div class="content-card">
                    <h3><i class="fas fa-folder"></i>More Projects by <?php echo htmlspecialchars($project['user_name']); ?></h3>
                    
                    <div class="other-projects">
                        <?php foreach ($other_projects as $other_project): ?>
                        <a href="view_idea.php?id=<?php echo $other_project['id']; ?>" class="other-project-item">
                            <div class="other-project-title">
                                <i class="fas fa-project-diagram"></i>
                                <?php echo htmlspecialchars($other_project['project_name']); ?>
                            </div>
                            <div class="other-project-meta">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($other_project['project_type']); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M Y', strtotime($other_project['submission_date'])); ?></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- User's Recent Ideas -->
                <?php if (!empty($user_ideas)): ?>
                <div class="content-card">
                    <h3><i class="fas fa-lightbulb"></i>Recent Ideas by <?php echo htmlspecialchars($project['user_name']); ?></h3>
                    
                    <div class="other-projects">
                        <?php foreach ($user_ideas as $idea): ?>
                        <a href="Blog/idea_details.php?id=<?php echo $idea['id']; ?>" class="other-project-item idea-item">
                            <div class="other-project-title">
                                <i class="fas fa-lightbulb"></i>
                                <?php echo htmlspecialchars($idea['title']); ?>
                            </div>
                            <div class="other-project-meta">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($idea['category']); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M Y', strtotime($idea['submission_datetime'])); ?></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Follow/Unfollow functionality
        document.addEventListener('DOMContentLoaded', function() {
            const followBtn = document.querySelector('.btn-follow');
            
            if (followBtn) {
                followBtn.addEventListener('click', function() {
                    const userId = this.dataset.userId;
                    const action = this.dataset.action;
                    const btn = this;
                    
                    // Disable button during request
                    btn.disabled = true;
                    
                    fetch('ajax/follow_handler.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=${action}&user_id=${userId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Toggle button state
                            if (action === 'follow') {
                                btn.dataset.action = 'unfollow';
                                btn.classList.add('following');
                                btn.style.background = 'var(--primary-color)';
                                btn.style.color = 'white';
                                btn.innerHTML = '<i class="fas fa-user-check"></i> <span class="follow-text">Following</span>';
                                
                                // Update follower count
                                const followerCount = document.querySelector('.follower-count');
                                if (followerCount) {
                                    let count = parseInt(followerCount.textContent);
                                    followerCount.textContent = count + 1;
                                }
                            } else {
                                btn.dataset.action = 'follow';
                                btn.classList.remove('following');
                                btn.style.background = 'white';
                                btn.style.color = 'var(--primary-color)';
                                btn.innerHTML = '<i class="fas fa-user-plus"></i> <span class="follow-text">Follow</span>';
                                
                                // Update follower count
                                const followerCount = document.querySelector('.follower-count');
                                if (followerCount) {
                                    let count = parseInt(followerCount.textContent);
                                    followerCount.textContent = Math.max(0, count - 1);
                                }
                            }
                            
                            // Show success message
                            showNotification(data.message, 'success');
                        } else {
                            showNotification(data.message, 'error');
                        }
                        
                        btn.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred', 'error');
                        btn.disabled = false;
                    });
                });
                
                // Add hover effect for following button
                followBtn.addEventListener('mouseenter', function() {
                    if (this.classList.contains('following')) {
                        this.style.background = 'var(--danger-color)';
                        this.style.borderColor = 'var(--danger-color)';
                    }
                });
                
                followBtn.addEventListener('mouseleave', function() {
                    if (this.classList.contains('following')) {
                        this.style.background = 'var(--primary-color)';
                        this.style.borderColor = 'var(--primary-color)';
                    }
                });
            }
        });

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                color: white;
                border-radius: 0.75rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                z-index: 9999;
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            .btn-follow:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>