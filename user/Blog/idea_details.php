<?php
require_once __DIR__ . '/../../includes/security_init.php';

// Production-safe error reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

require_once '../../Login/Login/db.php';
require_once '../../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Guest User";

// Get idea ID from URL
$idea_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$idea_id) {
    header("Location: list-project.php?view=all_ideas&error=" . urlencode("Invalid idea ID"));
    exit();
}

// Handle like toggle
if (isset($_POST['toggle_like']) && $user_id) {
    requireCSRF();
    
    $check_like_sql = "SELECT * FROM idea_likes WHERE idea_id = ? AND user_id = ?";
    $check_like_stmt = $conn->prepare($check_like_sql);
    $check_like_stmt->bind_param("ii", $idea_id, $user_id);
    $check_like_stmt->execute();
    $like_result = $check_like_stmt->get_result();

    if ($like_result->num_rows > 0) {
        $delete_like_sql = "DELETE FROM idea_likes WHERE idea_id = ? AND user_id = ?";
        $delete_like_stmt = $conn->prepare($delete_like_sql);
        $delete_like_stmt->bind_param("ii", $idea_id, $user_id);
        $delete_like_stmt->execute();
        $delete_like_stmt->close();
    } else {
        $insert_like_sql = "INSERT INTO idea_likes (idea_id, user_id) VALUES (?, ?)";
        $insert_like_stmt = $conn->prepare($insert_like_sql);
        $insert_like_stmt->bind_param("ii", $idea_id, $user_id);
        $insert_like_stmt->execute();
        $insert_like_stmt->close();
    }
    $check_like_stmt->close();
    
    header("Location: idea_details.php?id=" . $idea_id);
    exit();
}

// Fetch idea details with user profile
$sql = "SELECT b.*, 
               r.name as user_name, r.email as user_email, r.phone_no as user_phone, 
               r.about as user_bio, r.department as user_department, r.passout_year,
               r.enrollment_number, r.gr_number, r.user_image as user_avatar,
               CASE WHEN il.idea_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked,
               COALESCE(like_counts.total_likes, 0) AS total_likes,
               COALESCE(view_counts.total_views, 0) AS total_views,
               COALESCE(share_counts.total_shares, 0) AS total_shares,
               COALESCE(follower_counts.total_followers, 0) AS total_followers,
               COALESCE(rating_stats.avg_rating, 0) AS avg_rating,
               COALESCE(rating_stats.total_ratings, 0) AS total_ratings
        FROM blog b
        LEFT JOIN register r ON b.user_id = r.id
        LEFT JOIN idea_likes il ON b.id = il.idea_id AND il.user_id = ?
        LEFT JOIN (
            SELECT idea_id, COUNT(*) as total_likes 
            FROM idea_likes 
            GROUP BY idea_id
        ) like_counts ON b.id = like_counts.idea_id
        LEFT JOIN (
            SELECT idea_id, COUNT(*) as total_views 
            FROM idea_views 
            GROUP BY idea_id
        ) view_counts ON b.id = view_counts.idea_id
        LEFT JOIN (
            SELECT idea_id, COUNT(*) as total_shares 
            FROM idea_shares 
            GROUP BY idea_id
        ) share_counts ON b.id = share_counts.idea_id
        LEFT JOIN (
            SELECT idea_id, COUNT(*) as total_followers 
            FROM idea_followers 
            GROUP BY idea_id
        ) follower_counts ON b.id = follower_counts.idea_id
        LEFT JOIN (
            SELECT idea_id, AVG(rating) as avg_rating, COUNT(*) as total_ratings 
            FROM idea_ratings 
            GROUP BY idea_id
        ) rating_stats ON b.id = rating_stats.idea_id
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Database prepare failed: " . $conn->error);
    header("Location: list-project.php?view=all_ideas&error=" . urlencode("Database error"));
    exit();
}

$stmt->bind_param("ii", $user_id, $idea_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list-project.php?view=all_ideas&error=" . urlencode("Idea not found"));
    exit();
}

$idea = $result->fetch_assoc();
$stmt->close();

// Check if current user is following the idea creator
$is_following = false;
$follow_counts = ['followers' => 0, 'following' => 0];
$can_message = false;
$message_request_status = null;
if ($user_id && $user_id != $idea['user_id']) {
    $follow_check_sql = "SELECT EXISTS(SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = ?) as is_following";
    $follow_check_stmt = $conn->prepare($follow_check_sql);
    $follow_check_stmt->bind_param("ii", $user_id, $idea['user_id']);
    $follow_check_stmt->execute();
    $follow_check_result = $follow_check_stmt->get_result();
    $follow_check_row = $follow_check_result->fetch_assoc();
    $is_following = (bool)$follow_check_row['is_following'];
    $follow_check_stmt->close();
    
    // Check if both users follow each other OR if there's an accepted message request
    $mutual_follow_sql = "SELECT EXISTS(SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = ?) as mutual";
    $mutual_stmt = $conn->prepare($mutual_follow_sql);
    $mutual_stmt->bind_param("ii", $idea['user_id'], $user_id);
    $mutual_stmt->execute();
    $mutual_result = $mutual_stmt->get_result()->fetch_assoc();
    $mutual_stmt->close();
    
    $request_sql = "SELECT status FROM message_requests WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'accepted' LIMIT 1";
    $request_stmt = $conn->prepare($request_sql);
    $request_stmt->bind_param("iiii", $user_id, $idea['user_id'], $idea['user_id'], $user_id);
    $request_stmt->execute();
    $request_result = $request_stmt->get_result();
    $has_accepted_request = $request_result->num_rows > 0;
    $request_stmt->close();
    
    $can_message = ($is_following && $mutual_result['mutual']) || $has_accepted_request;
    
    // Check pending request status
    $pending_sql = "SELECT id, status FROM message_requests WHERE sender_id = ? AND receiver_id = ? LIMIT 1";
    $pending_stmt = $conn->prepare($pending_sql);
    $pending_stmt->bind_param("ii", $user_id, $idea['user_id']);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    if ($pending_row = $pending_result->fetch_assoc()) {
        $message_request_status = $pending_row['status'];
    }
    $pending_stmt->close();
}

// Get follower/following counts for the idea creator
$follow_stats_sql = "SELECT COALESCE(followers_count, 0) as followers, COALESCE(following_count, 0) as following FROM user_follow_stats WHERE user_id = ?";
$follow_stats_stmt = $conn->prepare($follow_stats_sql);
$follow_stats_stmt->bind_param("i", $idea['user_id']);
$follow_stats_stmt->execute();
$follow_stats_result = $follow_stats_stmt->get_result();
if ($follow_stats_row = $follow_stats_result->fetch_assoc()) {
    $follow_counts = $follow_stats_row;
}
$follow_stats_stmt->close();

// Get comprehensive user statistics
$user_stats_sql = "SELECT 
    (SELECT COUNT(*) FROM blog WHERE user_id = ?) as total_ideas,
    (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ?) as total_projects,
    (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ? AND status = 'approved') as approved_projects,
    (SELECT COUNT(*) FROM idea_likes il JOIN blog b ON il.idea_id = b.id WHERE b.user_id = ?) as total_idea_likes,
    (SELECT COUNT(*) FROM project_likes pl JOIN admin_approved_projects ap ON pl.project_id = ap.id WHERE ap.user_id = ?) as total_project_likes,
    (SELECT COUNT(DISTINCT classification) FROM blog WHERE user_id = ?) as unique_categories";

$user_stats_stmt = $conn->prepare($user_stats_sql);
$user_stats_stmt->bind_param("iiiiii", 
    $idea['user_id'], $idea['user_id'], $idea['user_id'],
    $idea['user_id'], $idea['user_id'], $idea['user_id']
);
$user_stats_stmt->execute();
$user_stats_result = $user_stats_stmt->get_result();
$user_stats = $user_stats_result->fetch_assoc();
$user_stats_stmt->close();

// Get other ideas by the same user
$other_ideas_sql = "SELECT id, project_name, classification, submission_datetime 
                    FROM blog 
                    WHERE user_id = ? AND id != ? 
                    ORDER BY submission_datetime DESC 
                    LIMIT 5";
$other_ideas_stmt = $conn->prepare($other_ideas_sql);
$other_ideas_stmt->bind_param("ii", $idea['user_id'], $idea_id);
$other_ideas_stmt->execute();
$other_ideas_result = $other_ideas_stmt->get_result();
$other_ideas = [];
while ($row = $other_ideas_result->fetch_assoc()) {
    $other_ideas[] = $row;
}
$other_ideas_stmt->close();

// Get user's projects
$user_projects_sql = "SELECT id, project_name, project_type, classification, submission_date 
                      FROM admin_approved_projects 
                      WHERE user_id = ? 
                      ORDER BY submission_date DESC 
                      LIMIT 5";
$user_projects_stmt = $conn->prepare($user_projects_sql);
$user_projects_stmt->bind_param("i", $idea['user_id']);
$user_projects_stmt->execute();
$user_projects_result = $user_projects_stmt->get_result();
$user_projects = [];
while ($row = $user_projects_result->fetch_assoc()) {
    $user_projects[] = $row;
}
$user_projects_stmt->close();

// Helper function
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../../assets/js/anti_injection.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($idea['project_name']); ?> - IdeaNest</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
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

        .idea-header {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem;
            border-radius: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .idea-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .idea-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .idea-meta {
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

        .idea-actions {
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
            cursor: pointer;
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

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
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

        .idea-badges {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .idea-badge {
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

        .idea-description {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .other-items {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .other-item {
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 1rem;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            border: 2px solid var(--border-color);
            display: block;
        }

        .other-item:hover {
            background: var(--bg-primary);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .other-item-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .other-item-meta {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .btn-follow {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            width: 100%;
            justify-content: center;
        }

        .btn-follow:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-follow.following {
            background: var(--primary-color);
            color: white;
        }

        .btn-follow.following:hover {
            background: var(--danger-color);
            border-color: var(--danger-color);
        }

        .follow-stats {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin: 1rem 0;
            padding: 1rem;
            background: var(--bg-tertiary);
            border-radius: 1rem;
        }

        .btn-message:hover {
            background: var(--accent-color) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-request-message:hover {
            background: var(--accent-color) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .follow-stat-item {
            text-align: center;
        }

        .follow-stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .follow-stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .idea-title {
                font-size: 2rem;
            }

            .idea-meta {
                gap: 1rem;
            }
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/loader.css">
</head>
<body>
    <?php include '../layout.php'; ?>
    
    <div class="main-content">
        <a href="list-project.php?view=all_ideas" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Ideas
        </a>

        <!-- Idea Header -->
        <div class="idea-header">
            <h1 class="idea-title"><?php echo htmlspecialchars($idea['project_name']); ?></h1>
            
            <div class="idea-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo formatDate($idea['submission_datetime']); ?></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-heart"></i>
                    <span><?php echo $idea['total_likes']; ?> likes</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-eye"></i>
                    <span><?php echo $idea['total_views']; ?> views</span>
                </div>
                <?php if ($idea['avg_rating'] > 0): ?>
                <div class="meta-item">
                    <i class="fas fa-star"></i>
                    <span><?php echo round($idea['avg_rating'], 1); ?>/5</span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($user_id): ?>
            <div class="idea-actions">
                <form method="post" style="display: inline;">
                    <?php echo getCSRFField(); ?>
                    <button type="submit" name="toggle_like" class="action-btn <?php echo $idea['is_liked'] ? 'liked' : ''; ?>">
                        <i class="fas fa-heart"></i>
                        <?php echo $idea['is_liked'] ? 'Unlike' : 'Like'; ?>
                    </button>
                </form>
                
                <button type="button" class="action-btn" onclick="shareThisIdea()" id="shareIdeaBtn">
                    <i class="fas fa-share-alt"></i>
                    Share via Message
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Main Content -->
            <div>
                <!-- Idea Details -->
                <div class="content-card">
                    <h3><i class="fas fa-lightbulb"></i>Idea Details</h3>
                    
                    <div class="idea-badges">
                        <span class="idea-badge badge-classification">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($idea['classification']); ?>
                        </span>
                        <?php if (!empty($idea['project_type'])): ?>
                        <span class="idea-badge badge-type">
                            <i class="fas fa-cube"></i> <?php echo htmlspecialchars(ucfirst($idea['project_type'])); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="idea-description">
                        <?php echo nl2br(htmlspecialchars($idea['description'])); ?>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="content-card" style="margin-top: 2rem;">
                    <h3><i class="fas fa-chart-bar"></i>Engagement Statistics</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem;">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $idea['total_likes']; ?></div>
                            <div class="stat-label"><i class="fas fa-heart text-danger"></i> Likes</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $idea['total_views']; ?></div>
                            <div class="stat-label"><i class="fas fa-eye text-primary"></i> Views</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $idea['total_shares']; ?></div>
                            <div class="stat-label"><i class="fas fa-share text-info"></i> Shares</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $idea['total_followers']; ?></div>
                            <div class="stat-label"><i class="fas fa-users text-success"></i> Followers</div>
                        </div>
                        <?php if ($idea['avg_rating'] > 0): ?>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo round($idea['avg_rating'], 1); ?>/5</div>
                            <div class="stat-label"><i class="fas fa-star text-warning"></i> Rating (<?php echo $idea['total_ratings']; ?>)</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- User Profile -->
                <div class="content-card user-profile-card">
                    <h3><i class="fas fa-user"></i>Creator</h3>
                    
                    <div class="user-avatar">
                        <?php if (!empty($idea['user_avatar'])): ?>
                            <img src="../../<?php echo htmlspecialchars($idea['user_avatar']); ?>" alt="<?php echo htmlspecialchars($idea['user_name']); ?>">
                        <?php else: ?>
                            <?php echo strtoupper(substr($idea['user_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>

                    <div class="user-name"><?php echo htmlspecialchars($idea['user_name']); ?></div>
                    <?php if (!empty($idea['user_department'])): ?>
                    <div class="user-department"><?php echo htmlspecialchars($idea['user_department']); ?></div>
                    <?php endif; ?>

                    <!-- Follow Stats -->
                    <div class="follow-stats">
                        <div class="follow-stat-item">
                            <div class="follow-stat-number follower-count"><?php echo $follow_counts['followers']; ?></div>
                            <div class="follow-stat-label">Followers</div>
                        </div>
                        <div class="follow-stat-item">
                            <div class="follow-stat-number"><?php echo $follow_counts['following']; ?></div>
                            <div class="follow-stat-label">Following</div>
                        </div>
                    </div>

                    <!-- Follow Button -->
                    <?php if ($user_id && $user_id != $idea['user_id']): ?>
                        <button class="btn-follow <?php echo $is_following ? 'following' : ''; ?>" 
                                data-user-id="<?php echo $idea['user_id']; ?>"
                                data-action="<?php echo $is_following ? 'unfollow' : 'follow'; ?>">
                            <i class="fas fa-user-<?php echo $is_following ? 'check' : 'plus'; ?>"></i>
                            <span class="follow-text"><?php echo $is_following ? 'Following' : 'Follow'; ?></span>
                        </button>
                        <?php if ($can_message): ?>
                            <a href="../chat/?user=<?php echo $idea['user_id']; ?>" class="btn-message" style="padding: 0.75rem 1.5rem; border: 2px solid var(--accent-color); background: white; color: var(--accent-color); border-radius: 2rem; cursor: pointer; transition: all 0.3s ease; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; width: 100%; justify-content: center; text-decoration: none;">
                                <i class="fas fa-comment"></i>
                                <span>Message</span>
                            </a>
                        <?php elseif ($message_request_status === 'pending'): ?>
                            <button class="btn-message" disabled style="padding: 0.75rem 1.5rem; border: 2px solid var(--warning-color); background: white; color: var(--warning-color); border-radius: 2rem; transition: all 0.3s ease; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; width: 100%; justify-content: center; opacity: 0.7; cursor: not-allowed;">
                                <i class="fas fa-clock"></i>
                                <span>Request Pending</span>
                            </button>
                        <?php else: ?>
                            <button class="btn-request-message" data-receiver-id="<?php echo $idea['user_id']; ?>" style="padding: 0.75rem 1.5rem; border: 2px solid var(--accent-color); background: white; color: var(--accent-color); border-radius: 2rem; cursor: pointer; transition: all 0.3s ease; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; width: 100%; justify-content: center;">
                                <i class="fas fa-paper-plane"></i>
                                <span>Request to Message</span>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($idea['user_bio'])): ?>
                    <div class="user-bio">
                        <?php echo nl2br(htmlspecialchars($idea['user_bio'])); ?>
                    </div>
                    <?php endif; ?>

                    <div class="user-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_stats['total_ideas']; ?></div>
                            <div class="stat-label">Ideas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_stats['total_projects']; ?></div>
                            <div class="stat-label">Projects</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_stats['total_idea_likes']; ?></div>
                            <div class="stat-label">Idea Likes</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $user_stats['unique_categories']; ?></div>
                            <div class="stat-label">Categories</div>
                        </div>
                    </div>
                </div>

                <!-- Other Ideas by User -->
                <?php if (count($other_ideas) > 0): ?>
                <div class="content-card" style="margin-top: 2rem;">
                    <h3><i class="fas fa-lightbulb"></i>More Ideas by <?php echo htmlspecialchars($idea['user_name']); ?></h3>
                    
                    <div class="other-items">
                        <?php foreach ($other_ideas as $other_idea): ?>
                        <a href="idea_details.php?id=<?php echo $other_idea['id']; ?>" class="other-item">
                            <div class="other-item-title"><?php echo htmlspecialchars($other_idea['project_name']); ?></div>
                            <div class="other-item-meta">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($other_idea['classification']); ?> • 
                                <?php echo formatDate($other_idea['submission_datetime']); ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- User's Projects -->
                <?php if (count($user_projects) > 0): ?>
                <div class="content-card" style="margin-top: 2rem;">
                    <h3><i class="fas fa-project-diagram"></i>Projects by <?php echo htmlspecialchars($idea['user_name']); ?></h3>
                    
                    <div class="other-items">
                        <?php foreach ($user_projects as $project): ?>
                        <a href="../view_idea.php?id=<?php echo $project['id']; ?>" class="other-item">
                            <div class="other-item-title"><?php echo htmlspecialchars($project['project_name']); ?></div>
                            <div class="other-item-meta">
                                <i class="fas fa-cube"></i> <?php echo htmlspecialchars($project['project_type']); ?> • 
                                <?php echo formatDate($project['submission_date']); ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Follow/Unfollow functionality
        document.addEventListener('DOMContentLoaded', function() {
            const followBtn = document.querySelector('.btn-follow');
            const requestBtn = document.querySelector('.btn-request-message');
            
            if (requestBtn) {
                requestBtn.addEventListener('click', function() {
                    const receiverId = this.dataset.receiverId;
                    const btn = this;
                    btn.disabled = true;
                    
                    fetch('../chat/message_request_handler.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=send_request&receiver_id=${receiverId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            btn.innerHTML = '<i class="fas fa-clock"></i> <span>Request Pending</span>';
                            btn.style.borderColor = 'var(--warning-color)';
                            btn.style.color = 'var(--warning-color)';
                            btn.style.opacity = '0.7';
                            btn.style.cursor = 'not-allowed';
                            showNotification(data.message, 'success');
                        } else {
                            showNotification(data.message, 'error');
                            btn.disabled = false;
                        }
                    })
                    .catch(error => {
                        showNotification('An error occurred', 'error');
                        btn.disabled = false;
                    });
                });
            }
            
            if (followBtn) {
                followBtn.addEventListener('click', function() {
                    const userId = this.dataset.userId;
                    const action = this.dataset.action;
                    const btn = this;
                    
                    // Disable button during request
                    btn.disabled = true;
                    
                    fetch('../ajax/follow_handler.php', {
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
        `;
        document.head.appendChild(style);
        
        // Share Idea Function
        function shareThisIdea() {
            const ideaId = <?php echo $idea_id; ?>;
            const title = <?php echo json_encode($idea['project_name']); ?>;
            const description = <?php echo json_encode(substr($idea['description'], 0, 200)); ?>;
            
            initShareModal('idea', ideaId, title, description);
        }
    </script>
    
    <!-- Include Share Modal -->
    <?php include '../share_modal.php'; ?>
<script src="../../assets/js/loader.js"></script>
</body>
</html>
