<?php
require_once __DIR__ . '/../includes/security_init.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../Login/Login/db.php';

// Initialize variables
$user = null;
$projects = [];
$ideas = [];
$followers_count = 0;
$following_count = 0;
$is_following = false;
$profile_pic_url = '../assets/image/default_avatar.png';
$error_message = null;
$viewed_user_id = null;
$current_user_id = $_SESSION['user_id'] ?? null;
$projects_total = 0;

if (!function_exists('truncate_text')) {
    /**
     * Generate a short preview for long text blobs.
     */
    function truncate_text($text, $limit = 160) {
        $text = trim(strip_tags((string)$text));
        return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
    }
}

// Determine which user profile to show
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    if ($current_user_id) {
        $viewed_user_id = intval($current_user_id);
    } else {
        $error_message = "Invalid user ID";
    }
} else {
    $viewed_user_id = intval($_GET['user_id']);
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
            $profile_pic = $user['profile_pic'] ?? '';
            if (!empty($profile_pic)) {
                // Normalize to just the file name to avoid path issues
                $profile_pic_file = basename($profile_pic);
                $profile_pic_path = __DIR__ . '/profile_pictures/' . $profile_pic_file;

                if (file_exists($profile_pic_path)) {
                    // Browser-accessible relative URL
                    $profile_pic_url = 'profile_pictures/' . $profile_pic_file;
                } else {
                    $profile_pic_url = '../assets/image/default_avatar.png';
                }
            } else {
                $profile_pic_url = '';
            }
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
            background: #f5f7fa;
            font-family: 'Inter', sans-serif;
            color: #333;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            margin-bottom: 30px;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .container-main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            margin-bottom: 20px;
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
            font-size: 24px;
            font-weight: bold;
            color: white;
        }

        .stat-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 5px;
        }

        .follow-btn {
            background: white;
            color: #667eea;
            border: 2px solid white;
            padding: 10px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .follow-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .follow-btn.following {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .follow-btn.following:hover {
            background: rgba(255, 255, 255, 0.4);
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
            color: #333;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.08);
            padding: 24px;
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
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
        }

        .pill-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 14px;
            color: #667eea;
            background: rgba(102, 126, 234, 0.12);
            text-decoration: none;
            font-weight: 500;
        }

        .project-pill,
        .idea-card {
            border: 1px solid rgba(99, 102, 241, 0.12);
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .project-pill:hover,
        .idea-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 35px rgba(31, 41, 55, 0.1);
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
            .profile-pic {
                width: 120px;
                height: 120px;
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
                font-size: 26px;
            }

            .navbar-brand {
                font-size: 20px;
            }

            .follow-btn {
                width: 100%;
            }
        }
</style>
</head>
<body>
    <?php
        // Standard user layout with sidebar/navigation
        $basePath = './';
        include 'layout.php';
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
            <div class="container-main">
                <div style="display: flex; gap: 30px; align-items: center; flex-wrap: wrap;">
                    <div style="text-align: center;">
                        <?php if (!empty($profile_pic_url)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic_url); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" class="profile-pic">
                        <?php else: ?>
                            <div style="width: 150px; height: 150px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 700; color: white; border: 4px solid white;">
                                <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <h1 style="margin-bottom: 10px;"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h1>
                        <p style="font-size: 16px; margin: 10px 0; opacity: 0.9;">
                            <?php echo htmlspecialchars($user['about'] ?? 'No bio provided'); ?>
                        </p>
                        
                        <div class="user-stats">
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
                        <button class="follow-btn <?php echo $is_following ? 'following' : ''; ?>" onclick="toggleFollow(<?php echo $viewed_user_id; ?>)">
                            <i class="fas fa-<?php echo $is_following ? 'check' : 'plus'; ?>"></i>
                            <?php echo $is_following ? 'Following' : 'Follow'; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-main">
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
                        '<i class="fas fa-plus"></i> Follow' : 
                        '<i class="fas fa-check"></i> Following';
                } else {
                    alert(data.message || 'Error updating follow status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating follow status');
            });
        }
    </script>
</body>
</html>
