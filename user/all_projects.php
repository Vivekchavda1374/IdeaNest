<?php
// user/all_projects.php - Updated version with fixed modal structure
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$basePath = './';
include '../Login/Login/db.php';

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
        $like_message = '<div class="alert alert-info">Like removed!</div>';
    } else {
        // Add like
        $insert_like_sql = "INSERT INTO project_likes (project_id, user_id) VALUES (?, ?)";
        $insert_like_stmt = $conn->prepare($insert_like_sql);
        $insert_like_stmt->bind_param("is", $project_id, $session_id);
        $insert_like_stmt->execute();
        $insert_like_stmt->close();
        $like_message = '<div class="alert alert-success">Project liked!</div>';
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
            $comment_message = '<div class="alert alert-success">Comment added successfully!</div>';
        } else {
            $comment_message = '<div class="alert alert-danger">Error adding comment. Please try again.</div>';
        }
        $insert_comment_stmt->close();
    }
}

// Handle comment like toggle
if (isset($_POST['toggle_comment_like']) && isset($_POST['comment_id'])) {
    $comment_id = intval($_POST['comment_id']);

    // Check if comment like already exists
    $check_comment_like_sql = "SELECT * FROM comment_likes WHERE comment_id = ? AND user_id = ?";
    $check_comment_like_stmt = $conn->prepare($check_comment_like_sql);
    $check_comment_like_stmt->bind_param("is", $comment_id, $session_id);
    $check_comment_like_stmt->execute();
    $comment_like_result = $check_comment_like_stmt->get_result();

    if ($comment_like_result->num_rows > 0) {
        // Remove comment like
        $delete_comment_like_sql = "DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?";
        $delete_comment_like_stmt = $conn->prepare($delete_comment_like_sql);
        $delete_comment_like_stmt->bind_param("is", $comment_id, $session_id);
        $delete_comment_like_stmt->execute();
        $delete_comment_like_stmt->close();
    } else {
        // Add comment like
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
        $bookmark_message = '<div class="alert alert-info">Bookmark removed!</div>';
    } else {
        $idea_id = 0;
        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isi", $project_id, $session_id, $idea_id);
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

// Main query
$sql = "SELECT ap.*, 
               CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked,
               CASE WHEN tpo.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_owner,
               CASE WHEN pl.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_liked,
               COALESCE(like_counts.total_likes, 0) AS total_likes,
               COALESCE(comment_counts.total_comments, 0) AS total_comments
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
            WHERE is_deleted = 0
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
                        <option value="Web Development" <?php if ($filter_classification === 'Web Development') echo 'selected'; ?>>Web Development</option>
                        <option value="Mobile App" <?php if ($filter_classification === 'Mobile App') echo 'selected'; ?>>Mobile App</option>
                        <option value="Data Science" <?php if ($filter_classification === 'Data Science') echo 'selected'; ?>>Data Science</option>
                        <option value="AI/ML" <?php if ($filter_classification === 'AI/ML') echo 'selected'; ?>>AI/ML</option>
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
                    <div class="project-card fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <div class="project-card-content">
                            <div class="project-card-header">
                                <h5 class="card-title">
                                    <span><?php echo htmlspecialchars($project['project_name']); ?></span>
                                    <?php if ($project['is_owner']): ?>
                                        <span class="badge badge-owner">
                                            <i class="fas fa-edit me-1"></i>Edit </span>
                                    <?php endif; ?>
                                </h5>

                                <div class="project-badges">
                                    <span class="project-badge badge-classification">
                                        <?php echo htmlspecialchars($project['classification']); ?>
                                    </span>
                                    <?php if (!empty($project['project_type'])): ?>
                                        <span class="project-badge badge-type">
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

                            <p class="card-text">
                                <?php echo htmlspecialchars(mb_strimwidth($project['description'], 0, 200, '...')); ?>
                            </p>

                            <!-- Action Buttons -->
                            <div class="project-actions">
                                <div class="action-buttons">
                                    <!-- Like Button -->
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                        <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                        <button type="submit" name="toggle_like"
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
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                        <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                        <button type="submit" name="toggle_bookmark"
                                                class="action-btn bookmark-btn<?php echo $project['is_bookmarked'] ? ' bookmarked' : ''; ?>">
                                            <i class="fas fa-bookmark"></i>
                                            <span><?php echo $project['is_bookmarked'] ? 'Saved' : 'Save'; ?></span>
                                        </button>
                                    </form>
                                </div>

                                <!-- Edit Button for Owners -->
                                <?php if ($project['is_owner']): ?>
                                <a href="edit_project.php?id=<?php echo $project['id']; ?>"
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-
                                    </a>
                                    edit me-1"></i>Edit Project
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>


                        <div class="project-side-panel">
                            <div class="project-icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                            <div class="project-status">
                                <i class="fas fa-check-circle me-1"></i>Approved
                            </div>
                        </div>
                    </div>

                    <!-- Project Details Modal -->
                    <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-project-diagram me-2"></i>
                                        <?php echo htmlspecialchars($project['project_name']); ?>
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <!-- Project Details -->
                                    <div class="row g-4 mb-4">
                                        <div class="col-md-6">
                                            <strong class="text-secondary d-block mb-1">Submitted:</strong>
                                            <?php echo date('M j, Y', strtotime($project['submission_date'])); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong class="text-secondary d-block mb-1">Status:</strong>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Approved
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Social Stats in Modal -->
                                    <div class="social-stats mb-4">
                                        <div class="stat-item-social stat-likes">
                                            <i class="fas fa-heart"></i>
                                            <span><?php echo $project['total_likes']; ?> likes</span>
                                        </div>
                                        <div class="stat-item-social stat-comments">
                                            <i class="fas fa-comment"></i>
                                            <span><?php echo $project['total_comments']; ?> comments</span>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold mb-3 d-flex align-items-center">
                                            <i class="fas fa-file-text me-2 text-primary"></i>Description
                                        </h6>
                                        <div class="project-modal-desc p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                                        </div>
                                    </div>

                                    <!-- Project Files -->
                                    <?php if (!empty($project['project_file_path'])): ?>
                                        <div class="mb-4">
                                            <h6 class="fw-bold mb-3 d-flex align-items-center">
                                                <i class="fas fa-download me-2 text-success"></i>Project Files
                                            </h6>
                                            <a href="<?php echo htmlspecialchars($project['project_file_path']); ?>"
                                               class="btn btn-success" target="_blank">
                                                <i class="fas fa-download me-2"></i>Download Project Files
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Comments Section in Project Modal -->
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
                                            if (!empty($preview_comments)):
                                            ?>
                                                <?php foreach ($preview_comments as $comment): ?>
                                                    <div class="comment-item">
                                                        <div class="comment-header">
                                                            <div class="comment-author">
                                                                <div class="comment-avatar">
                                                                    <?php echo strtoupper(substr($comment['user_name'], 0, 1)); ?>
                                                                </div>
                                                                <div class="comment-meta">
                                                                    <div class="comment-username">
                                                                        <?php echo htmlspecialchars($comment['user_name']); ?>
                                                                        <?php if ($comment['user_id'] === $session_id): ?>
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
                                                <?php if (count($recent_comments) > 3): ?>
                                                    <div class="text-center">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                                onclick="openCommentsModal(<?php echo $project['id']; ?>)">
                                                            <i class="fas fa-eye me-1"></i>
                                                            View All <?php echo $project['total_comments']; ?> Comments
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
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
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                        <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                        <button type="submit" name="toggle_like"
                                                class="btn <?php echo $project['is_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                            <i class="fas fa-heart me-2"></i>
                                            <?php echo $project['is_liked'] ? 'Unlike' : 'Like'; ?> (<?php echo $project['total_likes']; ?>)
                                        </button>
                                    </form>

                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                        <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                        <button type="submit" name="toggle_bookmark" class="btn btn-warning">
                                            <i class="fas fa-bookmark me-2"></i>
                                            <?php echo $project['is_bookmarked'] ? 'Remove Bookmark' : 'Add Bookmark'; ?>
                                        </button>
                                    </form>

                                    <?php if ($project['is_owner']): ?>
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
                                        if (!empty($modal_comments)):
                                        ?>
                                            <?php foreach ($modal_comments as $comment): ?>
                                                <div class="comment-item">
                                                    <div class="comment-header">
                                                        <div class="comment-author">
                                                            <div class="comment-avatar">
                                                                <?php echo strtoupper(substr($comment['user_name'], 0, 1)); ?>
                                                            </div>
                                                            <div class="comment-meta">
                                                                <div class="comment-username">
                                                                    <?php echo htmlspecialchars($comment['user_name']); ?>
                                                                    <?php if ($comment['user_id'] === $session_id): ?>
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
                                                        <form method="post" style="display:inline;">
                                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                                            <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                                            <button type="submit" name="toggle_comment_like"
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
                                                    </div>

                                                    <!-- Reply Form -->
                                                    <div class="reply-form" id="modalReplyForm<?php echo $comment['id']; ?>" style="display: none;">
                                                        <form method="post">
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
                                                    <?php if (!empty($comment['replies'])): ?>
                                                        <?php foreach ($comment['replies'] as $reply): ?>
                                                            <div class="comment-item reply-comment">
                                                                <div class="comment-header">
                                                                    <div class="comment-author">
                                                                        <div class="comment-avatar">
                                                                            <?php echo strtoupper(substr($reply['user_name'], 0, 1)); ?>
                                                                        </div>
                                                                        <div class="comment-meta">
                                                                            <div class="comment-username">
                                                                                <?php echo htmlspecialchars($reply['user_name']); ?>
                                                                                <?php if ($reply['user_id'] === $session_id): ?>
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
                                                                    <form method="post" style="display:inline;">
                                                                        <input type="hidden" name="comment_id" value="<?php echo $reply['id']; ?>">
                                                                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_filter); ?>">
                                                                        <input type="hidden" name="page" value="<?php echo $current_page_num; ?>">
                                                                        <button type="submit" name="toggle_comment_like"
                                                                                class="comment-like-btn<?php echo $reply['is_liked'] ? ' liked' : ''; ?>">
                                                                            <i class="fas fa-heart"></i>
                                                                            <span><?php echo $reply['comment_likes_count']; ?></span>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
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

            // Prevent action buttons from triggering card click
            const actionElements = document.querySelectorAll('.project-actions, .action-btn, .bookmark-btn, .like-btn');
            actionElements.forEach(element => {
                element.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
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
    </script>
</body>
</html>