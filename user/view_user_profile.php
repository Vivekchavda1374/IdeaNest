<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/security_init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once '../Login/Login/db.php';
} catch (Exception $e) {
    error_log('Database connection error in view_user_profile.php: ' . $e->getMessage());
    die('Database connection error. Please try again later.');
}

// Initialize variables
$user = null;
$projects = [];
$ideas = [];
$followers_list = [];
$following_list = [];
$followers_count = 0;
$following_count = 0;
$is_following = false;
$profile_pic_url = '../assets/image/default_avatar.png';
$error_message = null;
$viewed_user_id = null;
$current_user_id = $_SESSION['user_id'] ?? null;
$projects_total = 0;
$followers_page = max(1, intval($_GET['followers_page'] ?? 1));
$following_page = max(1, intval($_GET['following_page'] ?? 1));
$follow_per_page = 6;
$followers_total_pages = 1;
$following_total_pages = 1;

if (!function_exists('truncate_text')) {
    /**
     * Generate a short preview for long text blobs.
     */
    function truncate_text($text, $limit = 160) {
        $text = trim(strip_tags((string)$text));
        return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
    }
}

if (!function_exists('resolve_profile_pic_url')) {
    function resolve_profile_pic_url($profile_pic) {
        if (empty($profile_pic)) {
            return '';
        }
        $file = basename($profile_pic);
        $path = __DIR__ . '/profile_pictures/' . $file;
        return file_exists($path) ? 'profile_pictures/' . $file : '';
    }
}

if (!function_exists('build_profile_query')) {
    function build_profile_query(array $overrides = []) {
        $params = $_GET;
        foreach ($overrides as $key => $value) {
            $params[$key] = $value;
        }
        if (empty($params['user_id']) && !empty($GLOBALS['viewed_user_id'])) {
            $params['user_id'] = $GLOBALS['viewed_user_id'];
        }
        $query = http_build_query($params);
        return $query ? '?' . $query : '';
    }
}

// Determine which user profile to show
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    if ($current_user_id) {
        $viewed_user_id = intval($current_user_id);
    } else {
        $error_message = "Please log in to view profiles";
        $viewed_user_id = null;
    }
} else {
    $viewed_user_id = intval($_GET['user_id']);
    if ($viewed_user_id <= 0) {
        $error_message = "Invalid user ID";
        $viewed_user_id = null;
    }
}

if (!$error_message && $viewed_user_id) {

    // Get the user information
    $query = "SELECT * FROM register WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $viewed_user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error_message = "User not found";
        } else {
            $user = $result->fetch_assoc();

            // Get total approved projects for stats
            $projects_count_query = "SELECT COUNT(*) AS total FROM projects WHERE user_id = ? AND status = 'approved'";
            $projects_count_stmt = $conn->prepare($projects_count_query);
            if ($projects_count_stmt) {
                $projects_count_stmt->bind_param("i", $viewed_user_id);
                $projects_count_stmt->execute();
                $projects_total = (int)($projects_count_stmt->get_result()->fetch_assoc()['total'] ?? 0);
                $projects_count_stmt->close();
            }

            // Get user's recent projects (normalize to schema fields)
            $projects_query = "
                SELECT 
                    id,
                    COALESCE(title, project_name) AS title,
                    description,
                    status,
                    submission_date,
                    NULL AS icon
                FROM projects 
                WHERE user_id = ? AND status = 'approved' 
                ORDER BY submission_date DESC 
                LIMIT 3";
            $projects_stmt = $conn->prepare($projects_query);
            if ($projects_stmt) {
                $projects_stmt->bind_param("i", $viewed_user_id);
                $projects_stmt->execute();
                $projects_result = $projects_stmt->get_result();
                while ($row = $projects_result->fetch_assoc()) {
                    // Provide a friendly fallback for optional fields
                    $row['icon'] = $row['icon'] ?? '';
                    $row['created_at'] = $row['submission_date'] ?? null;
                    $projects[] = $row;
                }
                $projects_stmt->close();
            }

            // Get user's recent ideas from blog table
            $ideas_query = "
                SELECT 
                    id,
                    COALESCE(title, project_name) AS title,
                    classification,
                    project_type,
                    status,
                    submission_datetime,
                    description
                FROM blog
                WHERE user_id = ?
                ORDER BY submission_datetime DESC
                LIMIT 3";
            $ideas_stmt = $conn->prepare($ideas_query);
            if ($ideas_stmt) {
                $ideas_stmt->bind_param("i", $viewed_user_id);
                $ideas_stmt->execute();
                $ideas_result = $ideas_stmt->get_result();
                while ($row = $ideas_result->fetch_assoc()) {
                    $ideas[] = $row;
                }
                $ideas_stmt->close();
            }

            // Get followers count
            $followers_query = "SELECT COUNT(*) as count FROM user_follows WHERE following_id = ?";
            $followers_stmt = $conn->prepare($followers_query);
            if ($followers_stmt) {
                $followers_stmt->bind_param("i", $viewed_user_id);
                $followers_stmt->execute();
                $followers_count = $followers_stmt->get_result()->fetch_assoc()['count'];
                $followers_stmt->close();
            }

            // Get following count
            $following_query = "SELECT COUNT(*) as count FROM user_follows WHERE follower_id = ?";
            $following_stmt = $conn->prepare($following_query);
            if ($following_stmt) {
                $following_stmt->bind_param("i", $viewed_user_id);
                $following_stmt->execute();
                $following_count = $following_stmt->get_result()->fetch_assoc()['count'];
                $following_stmt->close();
            }

            // Followers pagination setup
            $followers_total_pages = $followers_count > 0 ? (int)ceil($followers_count / $follow_per_page) : 1;
            if ($followers_page > $followers_total_pages) {
                $followers_page = $followers_total_pages;
            }
            $followers_offset = max(0, ($followers_page - 1) * $follow_per_page);

            // Following pagination setup
            $following_total_pages = $following_count > 0 ? (int)ceil($following_count / $follow_per_page) : 1;
            if ($following_page > $following_total_pages) {
                $following_page = $following_total_pages;
            }
            $following_offset = max(0, ($following_page - 1) * $follow_per_page);

            // Fetch followers list
            if ($followers_count > 0) {
                $followers_list_query = "
                    SELECT 
                        r.id,
                        r.name,
                        r.email,
                        r.department,
                        r.user_image as profile_pic,
                        r.about
                    FROM user_follows uf
                    INNER JOIN register r ON uf.follower_id = r.id
                    WHERE uf.following_id = ?
                    ORDER BY uf.created_at DESC
                    LIMIT ? OFFSET ?";
                $followers_list_stmt = $conn->prepare($followers_list_query);
                if ($followers_list_stmt) {
                    $followers_list_stmt->bind_param("iii", $viewed_user_id, $follow_per_page, $followers_offset);
                    $followers_list_stmt->execute();
                    $followers_result = $followers_list_stmt->get_result();
                    while ($row = $followers_result->fetch_assoc()) {
                        $followers_list[] = $row;
                    }
                    $followers_list_stmt->close();
                }
            }

            // Fetch following list
            if ($following_count > 0) {
                $following_list_query = "
                    SELECT 
                        r.id,
                        r.name,
                        r.email,
                        r.department,
                        r.user_image as profile_pic,
                        r.about
                    FROM user_follows uf
                    INNER JOIN register r ON uf.following_id = r.id
                    WHERE uf.follower_id = ?
                    ORDER BY uf.created_at DESC
                    LIMIT ? OFFSET ?";
                $following_list_stmt = $conn->prepare($following_list_query);
                if ($following_list_stmt) {
                    $following_list_stmt->bind_param("iii", $viewed_user_id, $follow_per_page, $following_offset);
                    $following_list_stmt->execute();
                    $following_result = $following_list_stmt->get_result();
                    while ($row = $following_result->fetch_assoc()) {
                        $following_list[] = $row;
                    }
                    $following_list_stmt->close();
                }
            }

            // Check if current user follows this user
            if ($current_user_id && $current_user_id !== $viewed_user_id) {
                $follow_check = "SELECT id FROM user_follows WHERE follower_id = ? AND following_id = ?";
                $follow_stmt = $conn->prepare($follow_check);
                if ($follow_stmt) {
                    $follow_stmt->bind_param("ii", $current_user_id, $viewed_user_id);
                    $follow_stmt->execute();
                    $is_following = $follow_stmt->get_result()->num_rows > 0;
                    $follow_stmt->close();
                }
            }

            // Get user's profile picture (stored in user/profile_pictures)
            $profile_pic_url = resolve_profile_pic_url($user['user_image'] ?? '');
        }
        $stmt->close();
    } else {
        $error_message = "Database error";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user ? htmlspecialchars($user['name'] ?? 'User Profile') : 'User Profile'; ?> - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            color: #333;
            min-height: 100vh;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            text-decoration: none;
        }

        .navbar-nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .navbar-nav a {
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .navbar-nav a:hover {
            color: #667eea;
        }

        .error-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .error-icon {
            font-size: 48px;
            color: #e74c3c;
            margin-bottom: 15px;
        }

        .error-message {
            font-size: 18px;
            color: #e74c3c;
            margin-bottom: 20px;
        }

        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .profile-header {
            background: white;
            color: #1f2937;
            padding: 0;
            margin: 20px auto;
            max-width: 1200px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .profile-banner {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }

        .profile-content {
            padding: 0 40px 40px;
            margin-top: -80px;
            position: relative;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .container-main {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .profile-pic {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            background: white;
        }

        .profile-pic-wrapper {
            display: inline-block;
            position: relative;
        }

        .profile-avatar-placeholder {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            font-weight: 700;
            color: white;
            border: 6px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .user-stats {
            display: flex;
            gap: 30px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 13px;
            color: #6b7280;
            margin-top: 5px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .follow-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .follow-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .follow-btn.following {
            background: #10b981;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .follow-btn.following:hover {
            background: #059669;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
        }

        .project-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .project-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .project-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .project-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-clamp: 2;
            overflow: hidden;
        }

        .view-btn {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .view-btn:hover {
            text-decoration: underline;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .info-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .info-item {
            flex: 1;
            min-width: 200px;
        }

        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #1f2937;
            font-weight: 600;
        }

        .info-value a {
            color: #667eea;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .info-value a:hover {
            color: #764ba2;
        }

        .empty-state {
            text-align: center;
            padding: 60px 40px;
            color: #9ca3af;
        }

        .empty-state i {
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .empty-state p {
            font-size: 15px;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .content-grid + .content-card {
            margin-top: 40px;
        }

        .content-card + .content-grid {
            margin-top: 40px;
        }

        .content-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 32px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }

        .section-header h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h3 i {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .pill-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 999px;
            font-size: 14px;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .pill-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .project-pill,
        .idea-card {
            border: 2px solid #f3f4f6;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .project-pill:hover,
        .idea-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.15);
            border-color: #667eea;
            background: white;
        }

        .project-pill-header,
        .idea-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .project-pill-title,
        .idea-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 6px;
        }

        .project-pill-desc,
        .idea-description {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.6;
            margin: 12px 0;
        }

        .project-pill-meta,
        .idea-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            color: #6b7280;
            font-size: 13px;
        }

        .idea-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .text-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .text-link:hover {
            gap: 10px;
            color: #764ba2;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-approved {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }

        .status-in_progress,
        .status-in-progress {
            background: rgba(14, 165, 233, 0.15);
            color: #0284c7;
        }

        .status-completed {
            background: rgba(99, 102, 241, 0.15);
            color: #4c1d95;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }

        .follow-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .follow-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border: 2px solid #f3f4f6;
            border-radius: 16px;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .follow-card:hover {
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            transform: translateY(-3px);
            background: white;
        }

        .follow-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 700;
            font-size: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .follow-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .follow-info h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }

        .follow-info p {
            margin: 4px 0 0;
            font-size: 13px;
            color: #6b7280;
        }

        .follow-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pill-button {
            padding: 10px 20px;
            border-radius: 999px;
            border: 2px solid #667eea;
            font-size: 13px;
            color: #667eea;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            background: white;
        }

        .pill-button:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .pagination-controls {
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pagination-controls a {
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            text-decoration: none;
            color: #4b5563;
            font-weight: 500;
        }

        .pagination-controls a:hover {
            background: #f3f4f6;
        }

        .pagination-controls span {
            font-size: 14px;
            color: #6b7280;
        }

        /* Responsive refinements */
        @media (max-width: 992px) {
            .navbar-content {
                flex-direction: column;
                gap: 15px;
            }

            .navbar-nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .profile-header {
                padding: 30px 15px;
            }

            .profile-header .container-main > div {
                flex-direction: column;
                text-align: center;
            }

            .profile-card {
                padding: 20px;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .profile-pic,
            .profile-avatar-placeholder {
                width: 120px;
                height: 120px;
            }

            .profile-banner {
                height: 150px;
            }

            .profile-content {
                padding: 0 20px 30px;
                margin-top: -60px;
            }

            .user-stats {
                justify-content: center;
                gap: 20px;
            }

            .info-row {
                flex-direction: column;
            }

            .project-card {
                padding: 18px;
            }
        }

        @media (max-width: 576px) {
            body {
                font-size: 14px;
            }

            .container-main {
                padding: 0 12px;
            }

            .profile-header h1 {
                font-size: 24px !important;
            }

            .navbar-brand {
                font-size: 20px;
            }

            .follow-btn {
                width: 100%;
                justify-content: center;
            }

            .content-card {
                padding: 20px;
            }

            .project-pill,
            .idea-card {
                padding: 16px;
            }
        }

        /* Smooth animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-header,
        .content-card {
            animation: fadeIn 0.6s ease-out;
        }

        .content-grid > * {
            animation: fadeIn 0.6s ease-out;
        }

        .content-grid > *:nth-child(1) { animation-delay: 0.1s; }
        .content-grid > *:nth-child(2) { animation-delay: 0.2s; }
        .content-grid > *:nth-child(3) { animation-delay: 0.3s; }
        .content-grid > *:nth-child(4) { animation-delay: 0.4s; }

        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s ease-in-out infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
</style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
    <?php
        // Standard user layout with sidebar/navigation
        $basePath = './';
        if (file_exists(__DIR__ . '/layout.php')) {
            include 'layout.php';
        }
    ?>

    <div class="main-content">
    <?php if ($error_message): ?>
        <!-- Error State -->
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    <?php elseif ($user): ?>
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-banner"></div>
            <div class="profile-content">
                <div style="display: flex; gap: 30px; align-items: flex-start; flex-wrap: wrap;">
                    <div class="profile-pic-wrapper">
                        <?php if (!empty($profile_pic_url)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" class="profile-pic">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder">
                                <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1; padding-top: 20px;">
                        <h1 style="margin-bottom: 8px; font-size: 32px; font-weight: 700; color: #1f2937;"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h1>
                        <p style="font-size: 16px; margin: 0 0 20px 0; color: #6b7280; line-height: 1.6;">
                            <?php echo htmlspecialchars($user['about'] ?? 'No bio provided'); ?>
                        </p>
                        
                        <div class="user-stats" style="margin: 24px 0;">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $projects_total; ?></div>
                                <div class="stat-label">Projects</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $followers_count; ?></div>
                                <div class="stat-label">Followers</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $following_count; ?></div>
                                <div class="stat-label">Following</div>
                            </div>
                        </div>

                        <?php if ($current_user_id && $current_user_id !== $viewed_user_id): ?>
                        <div style="display: flex; gap: 10px;">
                            <button class="follow-btn <?php echo $is_following ? 'following' : ''; ?>" onclick="toggleFollow(<?php echo $viewed_user_id; ?>)">
                                <i class="fas fa-<?php echo $is_following ? 'check' : 'user-plus'; ?>"></i>
                                <span><?php echo $is_following ? 'Following' : 'Follow'; ?></span>
                            </button>
                            <button class="follow-btn" onclick="openChat(<?php echo $viewed_user_id; ?>, '<?php echo htmlspecialchars($user['name']); ?>')" style="background: #10b981; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);">
                                <i class="fas fa-comment"></i>
                                <span>Message</span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="container-main">
            <!-- Gamification Widget -->
            <?php include 'gamification_widget.php'; ?>
            
            <div class="content-grid">
                <!-- Followers Section -->
                <div class="content-card">
                    <div class="section-header">
                        <h3><i class="fas fa-users"></i> Followers (<?php echo $followers_count; ?>)</h3>
                    </div>
                    <?php if ($followers_count > 0): ?>
                        <div class="follow-list">
                            <?php foreach ($followers_list as $follower):
                                $follower_avatar = resolve_profile_pic_url($follower['profile_pic'] ?? '');
                                $follower_initial = strtoupper(substr($follower['name'] ?? 'U', 0, 1));
                            ?>
                                <div class="follow-card">
                                    <div class="follow-avatar">
                                        <?php if (!empty($follower_avatar)): ?>
                                            <img src="<?php echo htmlspecialchars($follower_avatar); ?>" alt="<?php echo htmlspecialchars($follower['name']); ?>">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($follower_initial); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="follow-info">
                                        <h4><?php echo htmlspecialchars($follower['name'] ?? 'User'); ?></h4>
                                        <p><?php echo htmlspecialchars($follower['department'] ?? 'Unknown department'); ?></p>
                                        <p style="font-size: 12px;"><?php echo htmlspecialchars(truncate_text($follower['about'] ?? 'No bio provided', 80)); ?></p>
                                    </div>
                                    <div class="follow-actions">
                                        <a href="view_user_profile.php?user_id=<?php echo $follower['id']; ?>" class="pill-button">View profile</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="pagination-controls">
                            <?php if ($followers_page > 1): ?>
                                <a href="<?php echo htmlspecialchars(build_profile_query([
                                    'followers_page' => $followers_page - 1,
                                    'following_page' => $following_page
                                ])); ?>">&larr; Previous</a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span>Page <?php echo $followers_page; ?> of <?php echo max(1, $followers_total_pages); ?></span>
                            <?php if ($followers_page < $followers_total_pages): ?>
                                <a href="<?php echo htmlspecialchars(build_profile_query([
                                    'followers_page' => $followers_page + 1,
                                    'following_page' => $following_page
                                ])); ?>">Next &rarr;</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-friends" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>No followers yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Following Section -->
                <div class="content-card">
                    <div class="section-header">
                        <h3><i class="fas fa-user-check"></i> Following (<?php echo $following_count; ?>)</h3>
                    </div>
                    <?php if ($following_count > 0): ?>
                        <div class="follow-list">
                            <?php foreach ($following_list as $following):
                                $following_avatar = resolve_profile_pic_url($following['profile_pic'] ?? '');
                                $following_initial = strtoupper(substr($following['name'] ?? 'U', 0, 1));
                            ?>
                                <div class="follow-card">
                                    <div class="follow-avatar">
                                        <?php if (!empty($following_avatar)): ?>
                                            <img src="<?php echo htmlspecialchars($following_avatar); ?>" alt="<?php echo htmlspecialchars($following['name']); ?>">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($following_initial); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="follow-info">
                                        <h4><?php echo htmlspecialchars($following['name'] ?? 'User'); ?></h4>
                                        <p><?php echo htmlspecialchars($following['department'] ?? 'Unknown department'); ?></p>
                                        <p style="font-size: 12px;"><?php echo htmlspecialchars(truncate_text($following['about'] ?? 'No bio provided', 80)); ?></p>
                                    </div>
                                    <div class="follow-actions">
                                        <a href="view_user_profile.php?user_id=<?php echo $following['id']; ?>" class="pill-button">View profile</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="pagination-controls">
                            <?php if ($following_page > 1): ?>
                                <a href="<?php echo htmlspecialchars(build_profile_query([
                                    'following_page' => $following_page - 1,
                                    'followers_page' => $followers_page
                                ])); ?>">&larr; Previous</a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span>Page <?php echo $following_page; ?> of <?php echo max(1, $following_total_pages); ?></span>
                            <?php if ($following_page < $following_total_pages): ?>
                                <a href="<?php echo htmlspecialchars(build_profile_query([
                                    'following_page' => $following_page + 1,
                                    'followers_page' => $followers_page
                                ])); ?>">Next &rarr;</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-check" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>Not following anyone yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- User Information -->
            <div class="content-card">
                <h3 class="section-title"><i class="fas fa-user-circle"></i> About</h3>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['phone_no'] ?? 'Not provided'); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['department'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Passout Year</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['passout_year'] ?? 'Not provided'); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Enrollment Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['enrollment_number'] ?? 'Not provided'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">GR Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['gr_number'] ?? 'Not provided'); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Role</div>
                        <div class="info-value" style="text-transform: capitalize;"><?php echo htmlspecialchars($user['role'] ?? 'student'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Expertise</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['expertise'] ?? 'Not specified'); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Email Notifications</div>
                        <div class="info-value"><?php echo !empty($user['email_notifications']) ? 'Enabled' : 'Disabled'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Availability</div>
                        <div class="info-value"><?php echo isset($user['is_available']) && (int)$user['is_available'] === 1 ? 'Open to connect' : 'Not available'; ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Mentor Rating</div>
                        <div class="info-value">
                            <?php 
                                $rating = isset($user['mentor_rating']) ? (float)$user['mentor_rating'] : 0;
                                echo $rating > 0 ? number_format($rating, 2) . ' / 5' : 'Not rated';
                            ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Last Notification Sent</div>
                        <div class="info-value">
                            <?php echo !empty($user['last_notification_sent']) ? date('M d, Y H:i', strtotime($user['last_notification_sent'])) : 'Never'; ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($user['github_username'])): ?>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">GitHub</div>
                        <div class="info-value">
                            <a href="https://github.com/<?php echo htmlspecialchars($user['github_username']); ?>" target="_blank" style="color: #667eea;">
                                <i class="fab fa-github"></i> <?php echo htmlspecialchars($user['github_username']); ?>
                            </a>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Repositories</div>
                        <div class="info-value"><?php echo (int)($user['github_repos_count'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Profile URL</div>
                        <div class="info-value">
                            <a href="<?php echo htmlspecialchars($user['github_profile_url']); ?>" target="_blank" style="color: #667eea;">
                                <?php echo htmlspecialchars($user['github_profile_url']); ?>
                            </a>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">GitHub Last Sync</div>
                        <div class="info-value">
                            <?php echo !empty($user['github_last_sync']) ? date('M d, Y H:i', strtotime($user['github_last_sync'])) : 'Never synced'; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="content-grid">
                <!-- Recent Projects -->
                <div class="content-card">
                    <div class="section-header">
                        <h3><i class="fas fa-project-diagram"></i> Recent Projects</h3>
                        <a href="all_projects.php" class="pill-link">View all <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (!empty($projects)): ?>
                        <?php foreach ($projects as $project): 
                            $project_date = !empty($project['submission_date']) ? date('M d, Y', strtotime($project['submission_date'])) : 'Not available';
                            $project_desc = truncate_text($project['description'] ?? 'No description provided', 150);
                            $project_status = strtolower($project['status'] ?? 'approved');
                        ?>
                        <div class="project-pill">
                            <div class="project-pill-header">
                                <div class="project-pill-title"><?php echo htmlspecialchars($project['title'] ?? 'Untitled'); ?></div>
                                <span class="status-badge <?php echo 'status-' . $project_status; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $project['status'] ?? '')); ?>
                                </span>
                            </div>
                            <p class="project-pill-desc"><?php echo htmlspecialchars($project_desc); ?></p>
                            <div class="project-pill-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($project_date); ?></span>
                                <a href="view_idea.php?id=<?php echo $project['id']; ?>" class="text-link">
                                    View project <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>No approved projects yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Ideas -->
                <div class="content-card">
                    <div class="section-header">
                        <h3><i class="fas fa-lightbulb"></i> Recent Ideas</h3>
                        <a href="Blog/list-project.php" class="pill-link">View all <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (!empty($ideas)): ?>
                        <?php foreach ($ideas as $idea): 
                            $idea_date = !empty($idea['submission_datetime']) ? date('M d, Y', strtotime($idea['submission_datetime'])) : 'Not available';
                            $idea_desc = truncate_text($idea['description'] ?? 'No description provided', 160);
                           
                        ?>
                        <div class="idea-card">
                            <div class="idea-card-header">
                                <div>
                                    <div class="idea-title"><?php echo htmlspecialchars($idea['title'] ?? 'Untitled Idea'); ?></div>
                                    <div class="idea-meta">
                                        <?php echo htmlspecialchars($idea['classification'] ?? ''); ?> • <?php echo htmlspecialchars($idea['project_type'] ?? ''); ?>
                                    </div>
                                </div>
                            
                            </div>
                            <p class="idea-description"><?php echo htmlspecialchars($idea_desc); ?></p>
                            <div class="idea-card-footer">
                                <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($idea_date); ?></span>
                                <a href="Blog/idea_details.php?id=<?php echo $idea['id']; ?>" class="text-link">
                                    View idea <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-lightbulb" style="font-size: 48px; margin-bottom: 10px;"></i>
                            <p>No ideas shared yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Loading State -->
        <div class="error-container">
            <div style="font-size: 48px; margin-bottom: 15px;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading profile...</p>
        </div>
    <?php endif; ?>
    </div>

    <script>
        function toggleFollow(userId) {
            const btn = event.target.closest('.follow-btn');
            const isFollowing = btn.classList.contains('following');
            const originalHTML = btn.innerHTML;
            
            // Show loading state
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Processing...</span>';
            btn.style.opacity = '0.7';
            
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', isFollowing ? 'unfollow' : 'follow');
            
            fetch('./ajax/follow_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('following');
                    btn.innerHTML = isFollowing ? 
                        '<i class="fas fa-user-plus"></i><span>Follow</span>' : 
                        '<i class="fas fa-check"></i><span>Following</span>';
                    
                    // Update follower count
                    const followerCount = document.querySelector('.stat-item:nth-child(2) .stat-number');
                    if (followerCount) {
                        const currentCount = parseInt(followerCount.textContent);
                        followerCount.textContent = isFollowing ? currentCount - 1 : currentCount + 1;
                    }
                    
                    // Show success animation
                    btn.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        btn.style.transform = '';
                    }, 200);
                } else {
                    btn.innerHTML = originalHTML;
                    showNotification(data.message || 'Error updating follow status', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalHTML;
                showNotification('Network error. Please try again.', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 16px 24px;
                background: ${type === 'error' ? '#ef4444' : '#10b981'};
                color: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
                font-weight: 500;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Smooth scroll for pagination
        document.querySelectorAll('.pagination-controls a').forEach(link => {
            link.addEventListener('click', function(e) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        // Add loading effect on page load
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
            }, 100);
        });

        async function openChat(userId, userName) {
            const formData = new FormData();
            formData.append('action', 'send_request');
            formData.append('receiver_id', userId);
            formData.append('message', 'Hi, I would like to connect with you!');
            
            const response = await fetch('./chat/api.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success || data.message === 'Already connected') {
                window.location.href = './chat/index.php';
            } else {
                showNotification(data.message, 'error');
            }
        }
    </script>
<script src="../assets/js/loader.js"></script>
</body>
</html>
