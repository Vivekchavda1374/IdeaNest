<?php
// user/all_projects.php - Fixed version with improved error handling and functionality
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fix path resolution
$basePath = dirname(__FILE__) . '/';
$dbPath = realpath($basePath . '../Login/Login/db.php');
if (!$dbPath || !file_exists($dbPath)) {
    die("Database connection file not found. Please check the path.");
}
include $dbPath;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$session_id = session_id();
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "Guest User";

// Handle like toggle
if (isset($_POST['toggle_like']) && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);

    // Check if like already exists
    $check_like_sql = "SELECT * FROM project_likes WHERE project_id = ? AND user_id = ?";
    $check_like_stmt = $conn->prepare($check_like_sql);
    $check_like_stmt->bind_param("is", $project_id, $session_id);
    $check_like_stmt->execute();
    $like_result = $check_like_stmt->get_result();

    if ($like_result->num_rows > 0) {
        // Remove like
        $delete_like_sql = "DELETE FROM project_likes WHERE project_id = ? AND user_id = ?";
        $delete_like_stmt = $conn->prepare($delete_like_sql);
        $delete_like_stmt->bind_param("is", $project_id, $session_id);
        $delete_like_stmt->execute();
        $delete_like_stmt->close();
        $like_message = '<div class="alert alert-info alert-dismissible fade show" role="alert">Like removed!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        // Add like
        $insert_like_sql = "INSERT INTO project_likes (project_id, user_id) VALUES (?, ?)";
        $insert_like_stmt = $conn->prepare($insert_like_sql);
        $insert_like_stmt->bind_param("is", $project_id, $session_id);
        $insert_like_stmt->execute();
        $insert_like_stmt->close();
        $like_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Project liked!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    $check_like_stmt->close();
}

// Handle comment submission
if (isset($_POST['submit_comment']) && isset($_POST['project_id']) && isset($_POST['comment_text'])) {
    $project_id = intval($_POST['project_id']);
    $comment_text = trim($_POST['comment_text']);
    $parent_comment_id = isset($_POST['parent_comment_id']) && !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : NULL;

    if (!empty($comment_text)) {
        $insert_comment_sql = "INSERT INTO project_comments (project_id, user_id, user_name, comment_text, parent_comment_id) VALUES (?, ?, ?, ?, ?)";
        $insert_comment_stmt = $conn->prepare($insert_comment_sql);
        $insert_comment_stmt->bind_param("isssi", $project_id, $session_id, $user_name, $comment_text, $parent_comment_id);

        if ($insert_comment_stmt->execute()) {
            $comment_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Comment added successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        } else {
            $comment_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">Error adding comment. Please try again.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
        $insert_comment_stmt->close();
    }
}

// Handle comment like toggle
if (isset($_POST['toggle_comment_like']) && isset($_POST['comment_id'])) {
    $comment_id = intval($_POST['comment_id']);

    $check_comment_like_sql = "SELECT * FROM comment_likes WHERE comment_id = ? AND user_id = ?";
    $check_comment_like_stmt = $conn->prepare($check_comment_like_sql);
    $check_comment_like_stmt->bind_param("is", $comment_id, $session_id);
    $check_comment_like_stmt->execute();
    $comment_like_result = $check_comment_like_stmt->get_result();

    if ($comment_like_result->num_rows > 0) {
        $delete_comment_like_sql = "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?";
        $delete_comment_like_stmt = $conn->prepare($delete_comment_like_sql);
        $delete_comment_like_stmt->bind_param("is", $comment_id, $session_id);
        $delete_comment_like_stmt->execute();
        $delete_comment_like_stmt->close();
    } else {
        $insert_comment_like_sql = "INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)";
        $insert_comment_like_stmt = $conn->prepare($insert_comment_like_sql);
        $insert_comment_like_stmt->bind_param("is", $comment_id, $session_id);
        $insert_comment_like_stmt->execute();
        $insert_comment_like_stmt->close();
    }
    $check_comment_like_stmt->close();
}

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark']) && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);

    $check_sql = "SELECT * FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $project_id, $session_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("is", $project_id, $session_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        $bookmark_message = '<div class="alert alert-info alert-dismissible fade show" role="alert">Bookmark removed!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $idea_id = 0;
        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isi", $project_id, $session_id, $idea_id);
        $insert_stmt->execute();
        $insert_stmt->close();
        $bookmark_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">Project bookmarked!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
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

// Main query with improved field handling
$sql = "SELECT ap.*, 
               CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked,
               CASE WHEN tpo.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_owner,
               CASE WHEN pl.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked,
               COALESCE(like_counts.total_likes, 0) AS total_likes,
               COALESCE(comment_counts.total_comments, 0) AS total_comments,
               COALESCE(ap.difficulty_level, 'beginner') as difficulty_level,
               COALESCE(ap.project_category, 'Other') as project_category,
               COALESCE(ap.development_time, 'Not specified') as development_time,
               COALESCE(ap.team_size, '1') as team_size
        FROM admin_approved_projects ap
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
            WHERE is_deleted = 0 OR is_deleted IS NULL
            GROUP BY project_id
        ) comment_counts ON ap.id = comment_counts.project_id";

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
function getProjectComments($conn, $project_id, $session_id) {
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
                     WHERE pc.project_id = ? AND (pc.is_deleted = 0 OR pc.is_deleted IS NULL)
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
    <link rel="stylesheet" href="../assets/css/all_project.css">
    <style>
        /* Additional styles for better video handling */
        .video-container {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            border-radius: 8px 8px 0 0;
        }

        .project-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }

        .video-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .video-overlay:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: translate(-50%, -50%) scale(1.1);
        }

        .video-playing .video-overlay {
            opacity: 0;
            pointer-events: none;
        }

        /* Fix for missing buttons */
        .action-btn {
            display: inline-flex !important;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background: white;
            color: #495057;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .action-btn:hover {
            border-color: #007bff;
            color: #007bff;
            transform: translateY(-1px);
        }

        .project-card {
            transition: all 0.3s ease;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<?php
$layoutPath = realpath($basePath . 'layout.php');
if ($layoutPath && file_exists($layoutPath)) {
    include $layoutPath;
} else {
    echo '<div class="alert alert-warning">Layout file not found. Please check the path.</div>';
}
?>

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
                    <option value="Web Development" <?php if ($filter_classification === 'Web Development') echo 'selected'; ?>>Web Development</option>
                    <option value="Mobile App" <?php if ($filter_classification === 'Mobile App') echo 'selected'; ?>>Mobile App</option>
                    <option value="Data Science" <?php if ($filter_classification === 'Data Science') echo 'selected'; ?>>Data Science</option>
                    <option value="AI/ML" <?php if ($filter_classification === 'AI/ML') echo 'selected'; ?>>AI/ML</option>
                    <option value="Game Development" <?php if ($filter_classification === 'Game Development') echo 'selected'; ?>>Game Development</option>
                    <option value="Desktop Application" <?php if ($filter_classification === 'Desktop Application') echo 'selected'; ?>>Desktop Application</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="type" class="form-label">Project Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="Frontend" <?php if ($filter_type === 'Frontend') echo 'selected'; ?>>Frontend</option>
                    <option value="Backend" <?php if ($filter_type === 'Backend') echo 'selected'; ?>>Backend</option>
                    <option value="Full Stack" <?php if ($filter_type === 'Full Stack') echo 'selected'; ?>>Full Stack</option>
                    <option value="API" <?php if ($filter_type === 'API') echo 'selected'; ?>>API</option>
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
    if (isset($bookmark_message)) echo $bookmark_message;
    if (isset($like_message)) echo $like_message;
    if (isset($comment_message)) echo $comment_message;
    ?>

    <!-- Projects List -->
    <div class="projects-list">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $index => $project): ?>
                <div class="project-card fade-in-up"
                     style="animation-delay: <?php echo $index * 0.1; ?>s;"
                     data-project-id="<?php echo $project['id']; ?>">

                    <!-- Project Header Image/Video -->
                    <?php if (!empty($project['image_path']) || !empty($project['video_path'])): ?>
                        <div class="project-media">
                            <?php if (!empty($project['video_path'])): ?>
                                <div class="video-container">
                                    <video class="project-video" preload="metadata" muted
                                           data-project-id="<?php echo $project['id']; ?>"
                                           onclick="toggleVideo(this)">
                                        <source src="<?php echo htmlspecialchars($project['video_path']); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                    <div class="video-overlay" onclick="toggleVideo(this.parentElement.querySelector('video'))">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                            <?php elseif (!empty($project['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($project['image_path']); ?>"
                                     alt="<?php echo htmlspecialchars($project['project_name']); ?>"
                                     class="project-image"
                                     loading="lazy"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDQwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNGM0Y0RjYiLz48L3N2Zz4='">
                            <?php endif; ?>

                            <!-- Project Stats Overlay -->
                            <?php if (!empty($project['total_views']) || !empty($project['avg_rating'])): ?>
                                <div class="project-stats-overlay">
                                    <?php if (!empty($project['total_views']) && $project['total_views'] > 0): ?>
                                        <div class="stat-badge">
                                            <i class="fas fa-eye"></i>
                                            <span><?php echo number_format($project['total_views']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($project['avg_rating']) && $project['avg_rating'] > 0): ?>
                                        <div class="stat-badge rating-badge">
                                            <i class="fas fa-star"></i>
                                            <span><?php echo round($project['avg_rating'], 1); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="project-card-content">
                        <div class="project-card-header">
                            <div class="project-title-row">
                                <h5 class="card-title">
                                    <span><?php echo htmlspecialchars($project['project_name']); ?></span>
                                    <?php if ($project['is_owner']): ?>
                                        <span class="badge badge-owner" title="You own this project">
                                            <i class="fas fa-crown me-1"></i>Yours
                                        </span>
                                    <?php endif; ?>
                                </h5>

                                <!-- Project Status Badge -->
                                <div class="project-status-badge">
                                    <?php
                                    $statusConfig = [
                                            'approved' => ['class' => 'bg-success', 'icon' => 'fa-check-circle'],
                                            'pending' => ['class' => 'bg-warning text-dark', 'icon' => 'fa-clock'],
                                            'rejected' => ['class' => 'bg-danger', 'icon' => 'fa-times-circle']
                                    ];
                                    $config = $statusConfig[$project['status']] ?? $statusConfig['pending'];
                                    ?>
                                    <span class="badge <?php echo $config['class']; ?>"
                                          title="Project Status: <?php echo ucfirst($project['status']); ?>">
                                        <i class="fas <?php echo $config['icon']; ?> me-1"></i>
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Project Badges -->
                            <div class="project-badges">
                                <span class="project-badge badge-classification"
                                      title="Classification: <?php echo htmlspecialchars($project['classification']); ?>">
                                    <i class="fas fa-layer-group me-1"></i>
                                    <?php echo htmlspecialchars($project['classification']); ?>
                                </span>

                                <?php if (!empty($project['project_type'])): ?>
                                    <span class="project-badge badge-type"
                                          title="Type: <?php echo htmlspecialchars($project['project_type']); ?>">
                                        <i class="fas fa-code me-1"></i>
                                        <?php echo htmlspecialchars($project['project_type']); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (!empty($project['language'])): ?>
                                    <span class="project-badge badge-language"
                                          title="Language: <?php echo htmlspecialchars($project['language']); ?>">
                                        <i class="fas fa-terminal me-1"></i>
                                        <?php echo htmlspecialchars($project['language']); ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Difficulty Level Badge -->
                                <?php if (!empty($project['difficulty_level'])): ?>
                                    <span class="project-badge badge-difficulty badge-difficulty-<?php echo strtolower($project['difficulty_level']); ?>"
                                          title="Difficulty: <?php echo ucfirst($project['difficulty_level']); ?>">
                                        <i class="fas fa-signal me-1"></i>
                                        <?php echo ucfirst($project['difficulty_level']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Development Info -->
                            <?php if (!empty($project['development_time']) || !empty($project['team_size']) || !empty($project['project_category'])): ?>
                                <div class="project-meta">
                                    <?php if (!empty($project['development_time']) && $project['development_time'] !== 'Not specified'): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?php echo htmlspecialchars($project['development_time']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($project['team_size']) && $project['team_size'] !== '1'): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo htmlspecialchars($project['team_size']); ?> members</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($project['project_category']) && $project['project_category'] !== 'Other'): ?>
                                        <div class="meta-item">
                                            <i class="fas fa-tag"></i>
                                            <span><?php echo htmlspecialchars($project['project_category']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Social Stats -->
                            <div class="social-stats">
                                <div class="stat-item-social stat-likes">
                                    <i class="fas fa-heart"></i>
                                    <span><?php echo number_format($project['total_likes']); ?> likes</span>
                                </div>
                                <div class="stat-item-social stat-comments">
                                    <i class="fas fa-comment"></i>
                                    <span><?php echo number_format($project['total_comments']); ?> comments</span>
                                </div>
                                <div class="stat-item-social stat-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo date('M j, Y', strtotime($project['submission_date'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="project-description">
                            <p class="card-text">
                                <?php echo htmlspecialchars(mb_strimwidth($project['description'], 0, 180, '...')); ?>
                            </p>

                            <!-- Project Goals Preview -->
                            <?php if (!empty($project['project_goals'])): ?>
                                <div class="project-goals-preview">
                                    <strong>Goals:</strong> <?php echo htmlspecialchars(mb_strimwidth($project['project_goals'], 0, 100, '...')); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Target Audience Preview -->
                            <?php if (!empty($project['target_audience'])): ?>
                                <div class="target-audience-preview">
                                    <strong>Target:</strong> <?php echo htmlspecialchars(mb_strimwidth($project['target_audience'], 0, 80, '...')); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Keywords Tags -->
                            <?php if (!empty($project['keywords'])): ?>
                                <div class="project-keywords">
                                    <?php
                                    $keywords = array_map('trim', explode(',', $project['keywords']));
                                    $displayKeywords = array_slice($keywords, 0, 4);
                                    foreach ($displayKeywords as $keyword):
                                        if (!empty($keyword)):
                                            ?>
                                            <span class="keyword-tag">#<?php echo htmlspecialchars($keyword); ?></span>
                                        <?php
                                        endif;
                                    endforeach;
                                    if (count($keywords) > 4):
                                        ?>
                                        <span class="keyword-tag more-keywords">+<?php echo count($keywords) - 4; ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Project Links Section -->
                        <?php if (!empty($project['github_repo']) || !empty($project['live_demo_url'])): ?>
                            <div class="project-links">
                                <?php if (!empty($project['github_repo'])): ?>
                                    <a href="<?php echo htmlspecialchars($project['github_repo']); ?>"
                                       target="_blank" class="project-link github-link"
                                       onclick="event.stopPropagation();"
                                       title="View source code on GitHub">
                                        <i class="fab fa-github"></i>
                                        <span>GitHub</span>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($project['live_demo_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($project['live_demo_url']); ?>"
                                       target="_blank" class="project-link demo-link"
                                       onclick="event.stopPropagation();"
                                       title="View live demo">
                                        <i class="fas fa-external-link-alt"></i>
                                        <span>Live Demo</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="project-actions">
                            <div class="action-buttons">
                                <!-- Like Button -->
                                <form method="post" style="display:inline;" onsubmit="return handleFormSubmit(event, this);">
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                    <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                    <button type="submit" name="toggle_like"
                                            class="action-btn like-btn<?php echo $project['is_liked'] ? ' liked' : ''; ?>"
                                            onclick="event.stopPropagation();"
                                            title="<?php echo $project['is_liked'] ? 'Unlike this project' : 'Like this project'; ?>">
                                        <i class="fas fa-heart"></i>
                                        <span><?php echo number_format($project['total_likes']); ?></span>
                                    </button>
                                </form>

                                <!-- Comment Button -->
                                <button type="button" class="action-btn comment-btn"
                                        onclick="event.stopPropagation(); openCommentsModal(<?php echo $project['id']; ?>)"
                                        title="View and add comments">
                                    <i class="fas fa-comment"></i>
                                    <span><?php echo number_format($project['total_comments']); ?></span>
                                </button>

                                <!-- Bookmark Button -->
                                <form method="post" style="display:inline;" onsubmit="return handleFormSubmit(event, this);">
                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                    <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                    <button type="submit" name="toggle_bookmark"
                                            class="action-btn bookmark-btn<?php echo $project['is_bookmarked'] ? ' bookmarked' : ''; ?>"
                                            onclick="event.stopPropagation();"
                                            title="<?php echo $project['is_bookmarked'] ? 'Remove from bookmarks' : 'Add to bookmarks'; ?>">
                                        <i class="fas fa-bookmark"></i>
                                        <span><?php echo $project['is_bookmarked'] ? 'Saved' : 'Save'; ?></span>
                                    </button>
                                </form>

                                <!-- Download Files Button -->
                                <?php
                                $hasFiles = !empty($project['code_file_path']) ||
                                        !empty($project['instruction_file_path']) ||
                                        !empty($project['presentation_file_path']) ||
                                        !empty($project['additional_files_path']);
                                if ($hasFiles):
                                    ?>
                                    <div class="dropdown">
                                        <button class="action-btn dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false"
                                                onclick="event.stopPropagation();"
                                                title="Download project files">
                                            <i class="fas fa-download"></i>
                                            <span>Files</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if (!empty($project['code_file_path'])): ?>
                                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($project['code_file_path']); ?>"
                                                       target="_blank" download onclick="event.stopPropagation();">
                                                        <i class="fas fa-code me-2"></i>Source Code
                                                    </a></li>
                                            <?php endif; ?>

                                            <?php if (!empty($project['instruction_file_path'])): ?>
                                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($project['instruction_file_path']); ?>"
                                                       target="_blank" download onclick="event.stopPropagation();">
                                                        <i class="fas fa-file-alt me-2"></i>Instructions
                                                    </a></li>
                                            <?php endif; ?>

                                            <?php if (!empty($project['presentation_file_path'])): ?>
                                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($project['presentation_file_path']); ?>"
                                                       target="_blank" download onclick="event.stopPropagation();">
                                                        <i class="fas fa-presentation me-2"></i>Presentation
                                                    </a></li>
                                            <?php endif; ?>

                                            <?php if (!empty($project['additional_files_path'])): ?>
                                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($project['additional_files_path']); ?>"
                                                       target="_blank" download onclick="event.stopPropagation();">
                                                        <i class="fas fa-folder me-2"></i>Additional Files
                                                    </a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <!-- View Details Button -->
                                <button type="button" class="action-btn view-btn"
                                        onclick="event.stopPropagation(); openProjectModal(<?php echo $project['id']; ?>)"
                                        title="View full project details">
                                    <i class="fas fa-eye"></i>
                                    <span>View</span>
                                </button>
                            </div>

                            <!-- Edit Button for Owners -->
                            <?php if ($project['is_owner']): ?>
                                <a href="edit_project.php?id=<?php echo $project['id']; ?>"
                                   class="btn btn-warning btn-sm"
                                   onclick="event.stopPropagation();"
                                   title="Edit this project">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Project Side Panel -->
                    <div class="project-side-panel">
                        <div class="project-icon">
                            <?php
                            // Dynamic icon based on project type and classification
                            $icon = 'fa-project-diagram'; // default
                            switch(strtolower($project['classification'] ?? '')) {
                                case 'web development':
                                    $icon = 'fa-globe';
                                    break;
                                case 'mobile app':
                                    $icon = 'fa-mobile-alt';
                                    break;
                                case 'data science':
                                    $icon = 'fa-chart-bar';
                                    break;
                                case 'ai/ml':
                                    $icon = 'fa-robot';
                                    break;
                                case 'game development':
                                    $icon = 'fa-gamepad';
                                    break;
                                case 'desktop application':
                                    $icon = 'fa-desktop';
                                    break;
                                default:
                                    switch(strtolower($project['project_type'] ?? '')) {
                                        case 'frontend':
                                            $icon = 'fa-paint-brush';
                                            break;
                                        case 'backend':
                                            $icon = 'fa-server';
                                            break;
                                        case 'full stack':
                                            $icon = 'fa-layer-group';
                                            break;
                                        case 'api':
                                            $icon = 'fa-plug';
                                            break;
                                    }
                                    break;
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?>" title="<?php echo htmlspecialchars($project['classification'] ?: $project['project_type']); ?>"></i>
                        </div>

                        <!-- Difficulty Indicator -->
                        <?php if (!empty($project['difficulty_level'])): ?>
                            <div class="difficulty-indicator difficulty-<?php echo strtolower($project['difficulty_level']); ?>">
                                <?php
                                $difficultyLevels = [
                                        'beginner' => 1,
                                        'intermediate' => 2,
                                        'advanced' => 3,
                                        'expert' => 4
                                ];
                                $level = $difficultyLevels[strtolower($project['difficulty_level'])] ?? 1;
                                for ($i = 1; $i <= 4; $i++):
                                    ?>
                                    <i class="fas fa-star<?php echo $i <= $level ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>

                        <div class="project-status status-<?php echo $project['status']; ?>"
                             title="Status: <?php echo ucfirst($project['status']); ?>">
                            <i class="fas <?php echo $config['icon']; ?> me-1"></i>
                            <small><?php echo ucfirst($project['status']); ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state fade-in-up">
                <div class="empty-state-icon">
                    <?php if ($view_filter === 'owned'): ?>
                        <i class="fas fa-user-plus"></i>
                    <?php elseif ($view_filter === 'bookmarked'): ?>
                        <i class="fas fa-bookmark"></i>
                    <?php else: ?>
                        <i class="fas fa-search"></i>
                    <?php endif; ?>
                </div>
                <h4>
                    <?php
                    if ($view_filter === 'owned') echo 'No projects found in your collection';
                    elseif ($view_filter === 'bookmarked') echo 'No bookmarked projects found';
                    else echo 'No projects found';
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
                <?php if ($view_filter === 'owned'): ?>
                    <a href="submit_project.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i>Submit Your First Project
                    </a>
                <?php else: ?>
                    <a href="?view=all" class="btn btn-primary mt-3">
                        <i class="fas fa-th-large me-2"></i>View All Projects
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
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

                    for ($i = $start_page; $i <= $end_page; $i++): ?>
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

<!-- Project Detail Modal Template -->
<?php foreach ($projects as $project): ?>
    <!-- Project Details Modal -->
    <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-project-diagram me-2"></i>
                        <?php echo htmlspecialchars($project['project_name']); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <?php if (!empty($project['image_path']) || !empty($project['video_path'])): ?>
                                <div class="mb-4">
                                    <?php if (!empty($project['video_path'])): ?>
                                        <video class="w-100" controls style="border-radius: 8px;">
                                            <source src="<?php echo htmlspecialchars($project['video_path']); ?>" type="video/mp4">
                                        </video>
                                    <?php elseif (!empty($project['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($project['image_path']); ?>"
                                             class="w-100" style="border-radius: 8px;"
                                             alt="<?php echo htmlspecialchars($project['project_name']); ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <h6>Description</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>

                            <?php if (!empty($project['project_goals'])): ?>
                                <h6>Project Goals</h6>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['project_goals'])); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($project['target_audience'])): ?>
                                <h6>Target Audience</h6>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['target_audience'])); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($project['challenges_faced'])): ?>
                                <h6>Challenges Faced</h6>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['challenges_faced'])); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($project['future_enhancements'])): ?>
                                <h6>Future Enhancements</h6>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['future_enhancements'])); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($project['keywords'])): ?>
                                <h6>Keywords</h6>
                                <div class="mb-3">
                                    <?php
                                    $keywords = array_map('trim', explode(',', $project['keywords']));
                                    foreach ($keywords as $keyword):
                                        if (!empty($keyword)):
                                            ?>
                                            <span class="badge bg-light text-dark me-1 mb-1">#<?php echo htmlspecialchars($keyword); ?></span>
                                        <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($project['github_repo']) || !empty($project['live_demo_url'])): ?>
                                <h6>Links</h6>
                                <div class="mb-3">
                                    <?php if (!empty($project['github_repo'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['github_repo']); ?>"
                                           target="_blank" class="btn btn-outline-dark btn-sm me-2 mb-2">
                                            <i class="fab fa-github me-2"></i>GitHub Repository
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($project['live_demo_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['live_demo_url']); ?>"
                                           target="_blank" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                            <i class="fas fa-external-link-alt me-2"></i>Live Demo
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <h6>Project Details</h6>
                            <ul class="list-unstyled">
                                <li><strong>Classification:</strong> <?php echo htmlspecialchars($project['classification']); ?></li>
                                <?php if (!empty($project['project_type'])): ?>
                                    <li><strong>Type:</strong> <?php echo htmlspecialchars($project['project_type']); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($project['language'])): ?>
                                    <li><strong>Language:</strong> <?php echo htmlspecialchars($project['language']); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($project['difficulty_level'])): ?>
                                    <li><strong>Difficulty:</strong> <?php echo ucfirst($project['difficulty_level']); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($project['development_time']) && $project['development_time'] !== 'Not specified'): ?>
                                    <li><strong>Development Time:</strong> <?php echo htmlspecialchars($project['development_time']); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($project['team_size']) && $project['team_size'] !== '1'): ?>
                                    <li><strong>Team Size:</strong> <?php echo htmlspecialchars($project['team_size']); ?></li>
                                <?php endif; ?>
                                <?php if (!empty($project['project_license'])): ?>
                                    <li><strong>License:</strong> <?php echo htmlspecialchars($project['project_license']); ?></li>
                                <?php endif; ?>
                                <li><strong>Status:</strong>
                                    <span class="badge <?php echo $config['class']; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </li>
                                <li><strong>Submitted:</strong> <?php echo date('M j, Y', strtotime($project['submission_date'])); ?></li>
                            </ul>

                            <h6>Engagement</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-heart text-danger me-2"></i><?php echo $project['total_likes']; ?> likes</li>
                                <li><i class="fas fa-comment text-primary me-2"></i><?php echo $project['total_comments']; ?> comments</li>
                            </ul>

                            <?php
                            $hasFiles = !empty($project['code_file_path']) ||
                                    !empty($project['instruction_file_path']) ||
                                    !empty($project['presentation_file_path']) ||
                                    !empty($project['additional_files_path']);
                            if ($hasFiles):
                                ?>
                                <h6>Download Files</h6>
                                <div class="d-grid gap-2">
                                    <?php if (!empty($project['code_file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['code_file_path']); ?>"
                                           target="_blank" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-code me-2"></i>Source Code
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($project['instruction_file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['instruction_file_path']); ?>"
                                           target="_blank" class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-file-alt me-2"></i>Instructions
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($project['presentation_file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['presentation_file_path']); ?>"
                                           target="_blank" class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-presentation me-2"></i>Presentation
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($project['additional_files_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($project['additional_files_path']); ?>"
                                           target="_blank" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-folder me-2"></i>Additional Files
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
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
                        Comments - <?php echo htmlspecialchars($project['project_name']); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Add Comment Form -->
                    <form method="post" class="mb-4">
                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                        <div class="mb-3">
                            <label for="comment_text_<?php echo $project['id']; ?>" class="form-label">Add a comment</label>
                            <textarea class="form-control"
                                      id="comment_text_<?php echo $project['id']; ?>"
                                      name="comment_text"
                                      rows="3"
                                      placeholder="Share your thoughts about this project..."></textarea>
                        </div>
                        <button type="submit" name="submit_comment" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Post Comment
                        </button>
                    </form>

                    <!-- Comments List -->
                    <div class="comments-list">
                        <?php
                        $comments = getProjectComments($conn, $project['id'], $session_id);
                        if (empty($comments)):
                            ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-comment-slash fa-2x mb-2"></i>
                                <p>No comments yet. Be the first to comment!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment mb-3 p-3 bg-light rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                <button type="submit" name="toggle_comment_like"
                                                        class="btn btn-sm <?php echo $comment['is_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                                    <i class="fas fa-heart"></i>
                                                    <span class="ms-1"><?php echo $comment['comment_likes_count']; ?></span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="mb-2 mt-2"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>

                                    <!-- Reply button -->
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            onclick="toggleModalReplyForm(<?php echo $comment['id']; ?>)">
                                        <i class="fas fa-reply me-1"></i>Reply
                                    </button>

                                    <!-- Reply form -->
                                    <div id="modalReplyForm<?php echo $comment['id']; ?>" style="display: none;" class="mt-3">
                                        <form method="post">
                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                            <input type="hidden" name="parent_comment_id" value="<?php echo $comment['id']; ?>">
                                            <div class="mb-2">
                                                <textarea class="form-control form-control-sm"
                                                          name="comment_text" rows="2"
                                                          placeholder="Write a reply..."></textarea>
                                            </div>
                                            <button type="submit" name="submit_comment" class="btn btn-primary btn-sm">
                                                <i class="fas fa-paper-plane me-1"></i>Reply
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Replies -->
                                    <?php if (!empty($comment['replies'])): ?>
                                        <div class="replies mt-3 ms-4">
                                            <?php foreach ($comment['replies'] as $reply): ?>
                                                <div class="reply mb-2 p-2 bg-white rounded border">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($reply['user_name']); ?></strong>
                                                            <small class="text-muted">
                                                                <?php echo date('M j, Y \a\t g:i A', strtotime($reply['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <div>
                                                            <form method="post" style="display:inline;">
                                                                <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                                                                <button type="submit" name="toggle_comment_like"
                                                                        class="btn btn-sm <?php echo $reply['is_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                                                    <i class="fas fa-heart"></i>
                                                                    <span class="ms-1"><?php echo $reply['comment_likes_count']; ?></span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($reply['comment_text'])); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Enhanced JavaScript with better video handling and form submission
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initializing project page...');

        // Initialize components
        initializeProjectCards();
        initializeModals();
        initializeVideoPlayers();
        autoHideAlerts();

        console.log('Project page initialized successfully');
    });

    // Video player functionality
    function toggleVideo(video) {
        if (!video) return;

        const container = video.closest('.video-container');
        const overlay = container ? container.querySelector('.video-overlay') : null;

        if (video.paused) {
            video.play().then(() => {
                if (container) container.classList.add('video-playing');
                video.controls = true;
            }).catch(err => {
                console.error('Video play failed:', err);
            });
        } else {
            video.pause();
            if (container) container.classList.remove('video-playing');
        }
    }

    function initializeVideoPlayers() {
        const videos = document.querySelectorAll('.project-video');
        videos.forEach(video => {
            video.addEventListener('ended', function() {
                const container = this.closest('.video-container');
                if (container) container.classList.remove('video-playing');
                this.controls = false;
            });

            video.addEventListener('pause', function() {
                const container = this.closest('.video-container');
                if (container && this.currentTime === 0) {
                    container.classList.remove('video-playing');
                    this.controls = false;
                }
            });
        });
    }

    // Enhanced form submission handling
    function handleFormSubmit(event, form) {
        event.stopPropagation();
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            const icon = button.querySelector('i');
            const originalClass = icon.className;
            icon.className = 'fas fa-spinner fa-spin';

            // Re-enable after a short delay
            setTimeout(() => {
                button.disabled = false;
                icon.className = originalClass;
            }, 2000);
        }
        return true; // Allow form submission
    }

    function initializeProjectCards() {
        const projectCards = document.querySelectorAll('.project-card');

        projectCards.forEach(card => {
            card.removeEventListener('click', handleCardClick);
            card.addEventListener('click', handleCardClick);

            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handleCardClick.call(this, e);
                }
            });
        });
    }

    function handleCardClick(e) {
        if (isInteractiveElement(e.target)) {
            return;
        }

        const projectId = getProjectIdFromCard(this);
        if (projectId) {
            openProjectModal(projectId);
        }
    }

    function isInteractiveElement(element) {
        const interactiveSelectors = [
            '.action-btn', 'button', 'a', 'form', 'input', 'select', 'textarea',
            '.dropdown', '.project-link', '.badge-owner', '.btn', 'video',
            '.video-overlay', '.video-container', '[data-bs-toggle]', '[onclick]'
        ];

        for (let selector of interactiveSelectors) {
            if (element.matches && element.matches(selector)) {
                return true;
            }
        }

        let parent = element.parentElement;
        while (parent) {
            for (let selector of interactiveSelectors) {
                if (parent.matches && parent.matches(selector)) {
                    return true;
                }
            }
            parent = parent.parentElement;
            if (parent && parent.classList.contains('project-card')) {
                break;
            }
        }

        return false;
    }

    function getProjectIdFromCard(card) {
        const input = card.querySelector('input[name="project_id"]');
        return input ? input.value : card.dataset.projectId;
    }

    function openProjectModal(projectId) {
        console.log('Opening project modal for ID:', projectId);
        const modal = document.getElementById('projectModal' + projectId);
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            console.error('Modal not found for project ID:', projectId);
        }
    }

    function openCommentsModal(projectId) {
        console.log('Opening comments modal for ID:', projectId);
        const modal = document.getElementById('commentsModal' + projectId);
        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            console.error('Comments modal not found for project ID:', projectId);
        }
    }

    function toggleModalReplyForm(commentId) {
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
    }

    function initializeModals() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const textarea = this.querySelector('textarea[name="comment_text"]');
                if (textarea) {
                    setTimeout(() => textarea.focus(), 100);
                }
            });

            modal.addEventListener('hidden.bs.modal', function() {
                const replyForms = this.querySelectorAll('[id^="modalReplyForm"]');
                replyForms.forEach(form => {
                    form.style.display = 'none';
                });
            });
        });

        const dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        dropdowns.forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    }

    function autoHideAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('alert-dismissible')) {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            }
        });
    }

    // Enhanced keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.show');
            if (activeModal) {
                const bsModal = bootstrap.Modal.getInstance(activeModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        }

        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    // Smooth pagination scrolling
    document.addEventListener('DOMContentLoaded', function() {
        const paginationLinks = document.querySelectorAll('.pagination .page-link');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!this.closest('.page-item').classList.contains('disabled')) {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });

    // Enhanced tooltip initialization
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]:not([title=""])'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: { show: 500, hide: 100 },
                placement: 'top'
            });
        });
    });

    // Image error handling
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.project-image');
        images.forEach(img => {
            img.addEventListener('error', function() {
                const placeholder = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDQwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNGM0Y0RjYiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzZCNzI4MCIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTRweCIgZm9udC13ZWlnaHQ9IjUwMCI+Tm8gSW1hZ2U8L3RleHQ+PC9zdmc+';
                this.src = placeholder;
            });
        });
    });

    // Like and Bookmark functionality
    async function handleAction(actionType, projectId, button) {
        try {
            const formData = new FormData();
            formData.append(actionType, '1');
            formData.append('project_id', projectId);

            button.disabled = true;
            const icon = button.querySelector('i');
            const originalClass = icon.className;
            icon.className = 'fas fa-spinner fa-spin';

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();

            if (data.success) {
                const isActive = actionType === 'toggle_like' ? data.isLiked : data.isBookmarked;
                icon.className = isActive ? 'fas fa-' + (actionType === 'toggle_like' ? 'heart' : 'bookmark')
                                       : 'far fa-' + (actionType === 'toggle_like' ? 'heart' : 'bookmark');

                if (actionType === 'toggle_like') {
                    button.classList.toggle('liked', isActive);
                    const countSpan = button.querySelector('span');
                    if (countSpan && data.likeCount !== undefined) {
                        countSpan.textContent = data.likeCount;
                    }
                } else {
                    button.classList.toggle('bookmarked', isActive);
                    const textSpan = button.querySelector('span');
                    if (textSpan) {
                        textSpan.textContent = isActive ? 'Saved' : 'Save';
                    }
                }

                const toastId = actionType === 'toggle_like' ? 'likeToast' : 'bookmarkToast';
                const toast = new bootstrap.Toast(document.getElementById(toastId));
                const toastBody = document.getElementById(toastId + 'Body');
                toastBody.textContent = data.message;
                toast.show();
            } else {
                throw new Error(data.message || 'Action failed');
            }
        } catch (error) {
            console.error('Error:', error);
            const toast = new bootstrap.Toast(document.getElementById('errorToast'));
            document.getElementById('errorToastBody').textContent = error.message || 'An error occurred. Please try again.';
            toast.show();
        } finally {
            button.disabled = false;
        }
    }

    // Form submission handler for like and bookmark buttons
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const button = this.querySelector('button[type="submit"]');
                const action = button.name;
                const projectId = this.querySelector('input[name="project_id"]').value;
                handleAction(action, projectId, button);
            });
        });
    });

    // Make functions globally available
    window.openProjectModal = openProjectModal;
    window.openCommentsModal = openCommentsModal;
    window.toggleModalReplyForm = toggleModalReplyForm;
    window.toggleVideo = toggleVideo;
    window.handleFormSubmit = handleFormSubmit;
    window.handleAction = handleAction;

    console.log('All project page scripts loaded successfully');
</script>
</body>
</html>
