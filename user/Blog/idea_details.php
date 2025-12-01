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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
