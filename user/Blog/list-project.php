<?php
$basePath = '../';

// Check if this is an AJAX request first
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    handleAjaxRequest();
    exit;
}

// Check if this is an AJAX request for project details
if (isset($_GET['get_project_details']) && $_GET['get_project_details'] == '1') {
    handleProjectDetailsRequest();
    exit;
}

// Handle like/unlike requests
if (isset($_POST['action']) && $_POST['action'] == 'toggle_like') {
    handleLikeToggle();
    exit;
}

// Handle comment submission
if (isset($_POST['action']) && $_POST['action'] == 'add_comment') {
    handleAddComment();
    exit;
}

// Handle comment deletion
if (isset($_POST['action']) && $_POST['action'] == 'delete_comment') {
    handleDeleteComment();
    exit;
}

include $basePath . 'layout.php';

// AJAX handler for like/unlike
function handleLikeToggle() {
    header('Content-Type: application/json');
    session_start();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login to like ideas']);
        return;
    }

    $user_id = $_SESSION['user_id'];
    $idea_id = isset($_POST['idea_id']) ? (int)$_POST['idea_id'] : 0;

    if ($idea_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid idea ID']);
        return;
    }

    try {
        $conn = createDBConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Check if user already liked this idea
        $check_sql = "SELECT id FROM idea_likes WHERE idea_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $idea_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // Unlike - remove the like
            $delete_sql = "DELETE FROM idea_likes WHERE idea_id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $idea_id, $user_id);
            $delete_stmt->execute();
            $liked = false;
        } else {
            // Like - add the like
            $insert_sql = "INSERT INTO idea_likes (idea_id, user_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ii", $idea_id, $user_id);
            $insert_stmt->execute();
            $liked = true;
        }

        // Get updated like count
        $count_sql = "SELECT COUNT(*) as like_count FROM idea_likes WHERE idea_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $idea_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $like_count = $count_result->fetch_assoc()['like_count'];

        $conn->close();

        echo json_encode([
                'success' => true,
                'liked' => $liked,
                'like_count' => $like_count
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// AJAX handler for adding comments
function handleAddComment() {
    header('Content-Type: application/json');
    session_start();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login to comment']);
        return;
    }

    $user_id = $_SESSION['user_id'];
    $idea_id = isset($_POST['idea_id']) ? (int)$_POST['idea_id'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    if ($idea_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid idea ID']);
        return;
    }

    if (empty($comment)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        return;
    }

    if (strlen($comment) > 500) {
        echo json_encode(['success' => false, 'message' => 'Comment too long (max 500 characters)']);
        return;
    }

    try {
        $conn = createDBConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Insert comment
        $insert_sql = "INSERT INTO idea_comments (idea_id, user_id, comment) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iis", $idea_id, $user_id, $comment);
        $insert_stmt->execute();

        // Get updated comment count
        $count_sql = "SELECT COUNT(*) as comment_count FROM idea_comments WHERE idea_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $idea_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $comment_count = $count_result->fetch_assoc()['comment_count'];

        $conn->close();

        echo json_encode([
                'success' => true,
                'comment_count' => $comment_count,
                'message' => 'Comment added successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// AJAX handler for deleting comments
function handleDeleteComment() {
    header('Content-Type: application/json');
    session_start();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login to delete comments']);
        return;
    }

    $user_id = $_SESSION['user_id'];
    $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;

    if ($comment_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid comment ID']);
        return;
    }

    try {
        $conn = createDBConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Check if user owns this comment
        $check_sql = "SELECT idea_id FROM idea_comments WHERE id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $comment_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'You can only delete your own comments']);
            return;
        }

        $idea_id = $result->fetch_assoc()['idea_id'];

        // Delete comment
        $delete_sql = "DELETE FROM idea_comments WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $comment_id);
        $delete_stmt->execute();

        // Get updated comment count
        $count_sql = "SELECT COUNT(*) as comment_count FROM idea_comments WHERE idea_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $idea_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $comment_count = $count_result->fetch_assoc()['comment_count'];

        $conn->close();

        echo json_encode([
                'success' => true,
                'comment_count' => $comment_count,
                'message' => 'Comment deleted successfully'
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// AJAX handler function for project details with likes and comments
function handleProjectDetailsRequest() {
    header('Content-Type: application/json');

    try {
        $conn = createDBConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

        if ($project_id <= 0) {
            throw new Exception("Invalid project ID");
        }

        // Get project with user info and engagement data
        $sql = "SELECT b.*, r.id as owner_id, r.name as owner_name,
                       (SELECT COUNT(*) FROM idea_likes WHERE idea_id = b.id) as like_count,
                       (SELECT COUNT(*) FROM idea_comments WHERE idea_id = b.id) as comment_count
                FROM blog b 
                LEFT JOIN register r ON b.user_id = r.id 
                WHERE b.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Project not found");
        }

        $project = $result->fetch_assoc();

        // Check if current user liked this project
        session_start();
        $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $user_liked = false;

        if ($current_user_id) {
            $like_check_sql = "SELECT id FROM idea_likes WHERE idea_id = ? AND user_id = ?";
            $like_stmt = $conn->prepare($like_check_sql);
            $like_stmt->bind_param("ii", $project_id, $current_user_id);
            $like_stmt->execute();
            $like_result = $like_stmt->get_result();
            $user_liked = $like_result->num_rows > 0;
            $like_stmt->close();
        }

        // Get recent comments with user names
        $comments_sql = "SELECT c.*, r.name as commenter_name 
                        FROM idea_comments c 
                        LEFT JOIN register r ON c.user_id = r.id 
                        WHERE c.idea_id = ? 
                        ORDER BY c.created_at DESC 
                        LIMIT 5";
        $comments_stmt = $conn->prepare($comments_sql);
        $comments_stmt->bind_param("i", $project_id);
        $comments_stmt->execute();
        $comments_result = $comments_stmt->get_result();
        $comments = $comments_result->fetch_all(MYSQLI_ASSOC);
        $comments_stmt->close();

        $stmt->close();
        $conn->close();

        $can_edit = ($current_user_id && $current_user_id == $project['user_id']);

        $response = [
                'success' => true,
                'project' => [
                        'id' => $project['id'],
                        'project_name' => htmlspecialchars($project['project_name']),
                        'er_number' => htmlspecialchars($project['er_number']),
                        'project_type' => ucfirst($project['project_type']),
                        'classification' => ucfirst(str_replace('_', ' ', $project['classification'])),
                        'priority1' => ucfirst($project['priority1']),
                        'status' => ucfirst(str_replace('_', ' ', $project['status'])),
                        'description' => nl2br(htmlspecialchars($project['description'])),
                        'submission_datetime' => formatDate($project['submission_datetime']),
                        'assigned_to' => htmlspecialchars(!empty($project['assigned_to']) ? $project['assigned_to'] : 'Not Assigned'),
                        'completion_date' => formatDate($project['completion_date']),
                        'priority_class' => getPriorityClass($project['priority1']),
                        'status_class' => getStatusClass($project['status']),
                        'can_edit' => $can_edit,
                        'user_id' => $project['user_id'],
                        'owner_name' => htmlspecialchars($project['owner_name']),
                        'like_count' => (int)$project['like_count'],
                        'comment_count' => (int)$project['comment_count'],
                        'user_liked' => $user_liked,
                        'comments' => $comments,
                        'current_user_id' => $current_user_id
                ]
        ];

        echo json_encode($response);

    } catch (Exception $e) {
        error_log("Project details error: " . $e->getMessage());
        echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

// AJAX handler function for loading projects with engagement data
function handleAjaxRequest() {
    header('Content-Type: application/json');

    try {
        $conn = createDBConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        session_start();
        $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        // Get parameters
        $filter_type = isset($_GET['type']) ? $_GET['type'] : '';
        $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
        $filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
        $search_term = isset($_GET['search']) ? $_GET['search'] : '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page = 6;
        $offset = ($page - 1) * $per_page;

        // Build query conditions
        $where_conditions = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($filter_type)) {
            $where_conditions[] = "b.project_type = ?";
            $params[] = $filter_type;
            $types .= "s";
        }

        if (!empty($filter_status)) {
            $where_conditions[] = "b.status = ?";
            $params[] = $filter_status;
            $types .= "s";
        }

        if (!empty($filter_priority)) {
            $where_conditions[] = "b.priority1 = ?";
            $params[] = $filter_priority;
            $types .= "s";
        }

        if (!empty($search_term)) {
            $where_conditions[] = "(b.project_name LIKE ? OR b.description LIKE ? OR b.er_number LIKE ?)";
            $search_pattern = "%{$search_term}%";
            $params[] = $search_pattern;
            $params[] = $search_pattern;
            $params[] = $search_pattern;
            $types .= "sss";
        }

        $where_clause = implode(" AND ", $where_conditions);

        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM blog b WHERE " . $where_clause;
        $count_stmt = $conn->prepare($count_sql);
        if (!empty($params)) {
            $count_stmt->bind_param($types, ...$params);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_projects = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_projects / $per_page);
        $count_stmt->close();

        // Get projects with engagement data
        $sql = "SELECT b.*, r.id as owner_id, r.name as owner_name,
                       (SELECT COUNT(*) FROM idea_likes WHERE idea_id = b.id) as like_count,
                       (SELECT COUNT(*) FROM idea_comments WHERE idea_id = b.id) as comment_count,
                       " . ($current_user_id ? "(SELECT COUNT(*) FROM idea_likes WHERE idea_id = b.id AND user_id = ?) as user_liked" : "0 as user_liked") . "
                FROM blog b 
                LEFT JOIN register r ON b.user_id = r.id 
                WHERE " . $where_clause . " ORDER BY 
                CASE b.priority1 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END,
                b.submission_datetime DESC 
                LIMIT ? OFFSET ?";

        if ($current_user_id) {
            array_unshift($params, $current_user_id);
            $types = "i" . $types;
        }

        $params[] = $per_page;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $projects = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();

        // Generate HTML with engagement features
        ob_start();
        foreach ($projects as $project):
            $can_edit = ($current_user_id && $current_user_id == $project['user_id']);
            $user_liked = ($current_user_id && $project['user_liked'] > 0);
            ?>
            <div class="project-card" data-aos="fade-up" data-idea-id="<?php echo $project['id']; ?>">
                <div class="priority-badge <?php echo getPriorityClass($project['priority1']); ?>">
                    <?php echo ucfirst($project['priority1']); ?>
                </div>

                <div class="project-header">
                    <div>
                        <h3 class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                        <div class="project-id">ID: <?php echo htmlspecialchars($project['er_number']); ?></div>
                        <div class="project-owner">
                            <i class="fas fa-user me-1"></i>
                            by <?php echo htmlspecialchars($project['owner_name'] ?: 'Unknown'); ?>
                        </div>
                    </div>
                </div>

                <div class="project-meta">
                    <span class="meta-tag">
                        <i class="<?php echo ($project['project_type'] == 'software') ? 'fas fa-laptop-code' : 'fas fa-microchip'; ?> me-1"></i>
                        <?php echo ucfirst($project['project_type']); ?>
                    </span>
                    <span class="meta-tag">
                        <i class="fas fa-tag me-1"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $project['classification'])); ?>
                    </span>
                </div>

                <div class="status-badge <?php echo getStatusClass($project['status']); ?>">
                    <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                </div>

                <div class="project-description">
                    <?php echo nl2br(htmlspecialchars(truncateText($project['description']))); ?>
                </div>

                <div class="project-date">
                    <i class="fas fa-calendar-alt me-1"></i>
                    Submitted: <?php echo formatDate($project['submission_datetime']); ?>
                </div>

                <!-- Engagement Section -->
                <div class="project-engagement">
                    <div class="engagement-stats">
                        <span class="engagement-stat">
                            <i class="fas fa-heart <?php echo $user_liked ? 'text-danger' : 'text-muted'; ?>"></i>
                            <span class="like-count"><?php echo $project['like_count']; ?></span>
                        </span>
                        <span class="engagement-stat">
                            <i class="fas fa-comment text-muted"></i>
                            <span class="comment-count"><?php echo $project['comment_count']; ?></span>
                        </span>
                    </div>

                    <div class="engagement-actions">
                        <?php if ($current_user_id): ?>
                            <button class="btn-like <?php echo $user_liked ? 'liked' : ''; ?>"
                                    data-idea-id="<?php echo $project['id']; ?>"
                                    title="<?php echo $user_liked ? 'Unlike' : 'Like'; ?> this idea">
                                <i class="fas fa-heart"></i>
                                <span class="like-text"><?php echo $user_liked ? 'Liked' : 'Like'; ?></span>
                            </button>
                        <?php else: ?>
                            <button class="btn-like disabled" title="Login to like ideas">
                                <i class="fas fa-heart"></i>
                                <span class="like-text">Like</span>
                            </button>
                        <?php endif; ?>

                        <button class="btn-comment" data-idea-id="<?php echo $project['id']; ?>" title="View comments">
                            <i class="fas fa-comment"></i>
                            <span>Comment</span>
                        </button>
                    </div>
                </div>

                <div class="project-actions">
                    <button class="btn btn-outline-purple btn-sm view-details-btn" data-project-id="<?php echo $project['id']; ?>">
                        <i class="fas fa-eye me-1"></i>View Details
                    </button>
                    <?php if ($can_edit): ?>
                        <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-purple btn-sm">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                    <?php else: ?>
                        <button class="btn btn-outline-secondary btn-sm" disabled title="You can only edit your own idea">
                            <i class="fas fa-lock me-1"></i>Edit
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach;

        $html = ob_get_clean();

        $currentlyShown = $page * $per_page;
        $paginationInfo = "Showing " . min($currentlyShown, $total_projects) . " of " . $total_projects . " projects";
        if ($page < $total_pages) {
            $paginationInfo .= " (Page " . $page . " of " . $total_pages . ")";
        }

        $response = [
                'success' => true,
                'html' => $html,
                'hasMore' => $page < $total_pages,
                'nextPage' => $page + 1,
                'paginationInfo' => $paginationInfo,
                'projects' => $projects
        ];

        echo json_encode($response);

    } catch (Exception $e) {
        echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

// Helper functions (existing ones remain the same)
function createDBConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ideanest";

    try {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'pending': return 'status-pending';
        case 'in_progress': return 'status-in_progress';
        case 'completed': return 'status-completed';
        case 'rejected': return 'status-rejected';
        default: return 'status-pending';
    }
}

function getPriorityClass($priority) {
    switch ($priority) {
        case 'high': return 'priority-high';
        case 'medium': return 'priority-medium';
        case 'low': return 'priority-low';
        default: return 'priority-medium';
    }
}

function formatDate($date) {
    if (empty($date) || $date === '0000-00-00 00:00:00' || $date === '0000-00-00') {
        return 'N/A';
    }
    try {
        return date('M d, Y', strtotime($date));
    } catch (Exception $e) {
        return 'N/A';
    }
}

function truncateText($text, $length = 150) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Projects - IdeaNest</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/list_project.css">
    <style>
        /* Enhanced Project Card Styles */
        .project-card {
            background: white;
            border-radius: 20px;
            padding: 1.8rem;
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(139, 92, 246, 0.08);
            position: relative;
            overflow: hidden;
            cursor: pointer;
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
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 50px rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .project-card:hover::before {
            transform: scaleX(1);
        }

        .project-card:active {
            transform: translateY(-4px) scale(1.01);
            transition: all 0.1s ease;
        }

        /* Card Content Enhancements */
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.2rem;
            position: relative;
        }

        .project-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            transition: color 0.3s ease;
        }

        .project-card:hover .project-title {
            color: var(--primary-purple);
        }

        .project-id {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
            background: #f1f5f9;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            display: inline-block;
        }

        .project-owner {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .project-owner i {
            color: var(--primary-purple);
        }

        /* Priority Badge Enhancement */
        .priority-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.4rem 1rem;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        /* Meta Tags Enhancement */
        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-bottom: 1.2rem;
        }

        .meta-tag {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            color: #475569;
            padding: 0.4rem 0.9rem;
            border-radius: 18px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .meta-tag:hover {
            background: linear-gradient(135deg, var(--light-purple), #ddd6fe);
            color: var(--dark-purple);
            transform: translateY(-1px);
        }

        /* Status Badge Enhancement */
        .status-badge {
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
            display: inline-flex;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Description Enhancement */
        .project-description {
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .project-description::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 30px;
            height: 20px;
            background: linear-gradient(to left, white, transparent);
        }

        /* Date Enhancement */
        .project-date {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 1rem;
            padding: 0.5rem 0;
            border-top: 1px solid #f1f5f9;
            font-weight: 500;
        }

        .project-date i {
            color: var(--primary-purple);
        }

        /* Engagement Section Styling */
        .project-engagement {
            margin: 1.5rem 0;
            padding: 1rem 0;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
        }

        .engagement-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 1rem;
        }

        .engagement-stat {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #64748b;
        }

        .engagement-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .btn-like, .btn-comment {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #64748b;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-like:hover, .btn-comment:hover {
            border-color: var(--primary-purple);
            color: var(--primary-purple);
            transform: translateY(-2px);
        }

        .btn-like.liked {
            background: #fef2f2;
            border-color: #f87171;
            color: #dc2626;
        }

        .btn-like.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Actions Enhancement */
        .project-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            justify-content: center;
        }

        .btn-outline-purple {
            border: 2px solid var(--primary-purple);
            color: var(--primary-purple);
            background: transparent;
            border-radius: 12px;
            padding: 0.7rem 1.4rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline-purple:hover {
            background: var(--primary-purple);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
        }

        .btn-outline-purple:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Tap Interaction Effects */
        @media (hover: none) and (pointer: coarse) {
            .project-card {
                transition: all 0.2s ease;
            }

            .project-card:active {
                transform: scale(0.98);
                box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2);
            }
        }

        /* Enhanced Responsive Design */
        @media (max-width: 768px) {
            .project-card {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }

            .priority-badge {
                top: 1rem;
                right: 1rem;
                padding: 0.3rem 0.8rem;
                font-size: 0.7rem;
            }

            .project-title {
                font-size: 1.2rem;
            }

            .engagement-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-like, .btn-comment {
                justify-content: center;
            }

            .project-actions {
                flex-direction: column;
            }

            .btn-outline-purple {
                text-align: center;
                justify-content: center;
            }
        }

        /* Loading States */
        .project-card.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .project-card.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        /* Hover Animation for Icons */
        .project-card:hover .fas,
        .project-card:hover .far {
            animation: iconBounce 0.6s ease;
        }

        @keyframes iconBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Grid Enhancements */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 0.5rem;
        }

        @media (max-width: 1200px) {
            .projects-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .projects-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 0;
            }
        }

        /* View Only Button Styling */
        .btn-view-only {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            color: #6c757d;
            border-radius: 12px;
            padding: 0.7rem 1.4rem;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: default;
            position: relative;
            overflow: hidden;
        }

        .btn-view-only::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg,
            transparent 30%,
            rgba(108, 117, 125, 0.1) 50%,
            transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .btn-view-only:hover::before {
            transform: translateX(100%);
        }

        .btn-view-only i.fa-eye {
            color: #28a745;
            font-size: 1rem;
        }

        .btn-view-only i.fa-lock {
            color: #dc3545;
            font-size: 0.85rem;
        }

        /* Modal View Only Badge */
        .modal .btn-view-only,
        .modal-footer .btn-view-only {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px solid #dee2e6;
            color: #495057;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .modal .btn-view-only:hover,
        .modal-footer .btn-view-only:hover {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        /* View Only Badge in Cards */
        .view-only-badge {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #6c757d;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #dee2e6;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .view-only-badge i {
            color: #dc3545;
            font-size: 0.8rem;
        }

        /* Enhanced Badge Styling */
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6c757d, #495057) !important;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
            border: none;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            border: none;
        }

        /* Footer Info Styling */
        .project-footer-info {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f1f5f9;
        }

        .project-footer-info .badge {
            font-size: 0.8rem;
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .project-footer-info .text-muted {
            color: #64748b !important;
            font-weight: 500;
        }

        .project-footer-info .text-muted i {
            color: var(--primary-purple);
            margin-right: 0.5rem;
        }

        /* View Details Button Enhancement */
        .view-details-btn {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            border: none;
            color: white;
            border-radius: 12px;
            padding: 0.7rem 1.4rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2);
        }

        .view-details-btn:hover {
            background: linear-gradient(135deg, #7c3aed, #8b5cf6);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
            color: white;
        }

        .view-details-btn:active {
            transform: translateY(0);
            transition: all 0.1s ease;
        }

        .view-details-btn i {
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }

        .view-details-btn:hover i {
            transform: scale(1.1);
        }

        /* Accessibility Enhancements */
        .project-card:focus {
            outline: 3px solid rgba(139, 92, 246, 0.5);
            outline-offset: 2px;
        }

        /* Smooth Transitions */
        * {
            -webkit-tap-highlight-color: transparent;
        }

        .project-card * {
            transition: inherit;
        }
        /* Enhanced Modal/Dialog Box Styles */

        /* Modal Backdrop */
        .modal-backdrop {
            background-color: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        /* Main Modal Dialog */
        .modal-dialog {
            margin: 1.75rem auto;
            max-width: 90%;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-dialog-xl {
            max-width: 1140px;
        }

        @media (min-width: 576px) {
            .modal-dialog {
                max-width: 500px;
                margin: 1.75rem auto;
            }

            .modal-dialog-xl {
                max-width: 1140px;
            }
        }

        /* Modal Content Container */
        .modal-content {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow:
                    0 25px 50px -12px rgba(139, 92, 246, 0.25),
                    0 0 0 1px rgba(139, 92, 246, 0.05);
            overflow: hidden;
            position: relative;
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Modal Header */
        .modal-header {
            padding: 2rem 2.5rem 1.5rem;
            border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            position: relative;
            background: linear-gradient(135deg, var(--primary-purple, #8b5cf6), var(--secondary-purple, #a78bfa));
            color: white;
        }

        .modal-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-title i {
            font-size: 1.25rem;
            opacity: 0.9;
        }

        /* Close Button */
        .btn-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: rgba(255, 255, 255, 0.8);
            text-shadow: none;
            opacity: 1;
            padding: 0.5rem;
            margin: 0;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: rotate(90deg);
        }

        .btn-close:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
            outline: 0;
        }

        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* Modal Body */
        .modal-body {
            padding: 2.5rem;
            max-height: 70vh;
            overflow-y: auto;
            position: relative;
        }

        /* Custom Scrollbar for Modal Body */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: var(--primary-purple, #8b5cf6);
            border-radius: 4px;
            opacity: 0.7;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: var(--dark-purple, #6d28d9);
        }

        /* Modal Footer */
        .modal-footer {
            padding: 1.5rem 2.5rem 2rem;
            border-top: 1px solid rgba(139, 92, 246, 0.1);
            background: linear-gradient(to top, rgba(139, 92, 246, 0.02), transparent);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            align-items: center;
        }

        /* Enhanced Section Styling */
        .detail-section {
            background: #ffffff;
            border: 1px solid rgba(139, 92, 246, 0.08);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .detail-section:hover {
            border-color: rgba(139, 92, 246, 0.15);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.08);
        }

        .section-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--dark-purple, #6d28d9);
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(139, 92, 246, 0.1);
        }

        .section-title i {
            color: var(--primary-purple, #8b5cf6);
            font-size: 1rem;
        }

        /* Detail Rows */
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(241, 245, 249, 0.8);
            transition: all 0.2s ease;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row:hover {
            background: rgba(139, 92, 246, 0.02);
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            margin-left: -0.5rem;
            margin-right: -0.5rem;
            border-radius: 8px;
        }

        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-label i {
            color: var(--primary-purple, #8b5cf6);
            width: 16px;
        }

        .detail-value {
            font-weight: 500;
            color: #1e293b;
            text-align: right;
            max-width: 60%;
        }

        /* Description Box */
        .description-box {
            background: #f8fafc;
            border: 1px solid rgba(139, 92, 246, 0.1);
            border-radius: 12px;
            padding: 1.25rem;
            line-height: 1.7;
            color: #475569;
            font-size: 0.95rem;
            max-height: 200px;
            overflow-y: auto;
        }

        .description-box::-webkit-scrollbar {
            width: 6px;
        }

        .description-box::-webkit-scrollbar-track {
            background: rgba(139, 92, 246, 0.05);
            border-radius: 3px;
        }

        .description-box::-webkit-scrollbar-thumb {
            background: var(--primary-purple, #8b5cf6);
            border-radius: 3px;
            opacity: 0.6;
        }

        /* Info Cards */
        .info-card {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border: 1px solid rgba(139, 92, 246, 0.1);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-purple, #8b5cf6);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .info-card:hover::before {
            transform: scaleX(1);
        }

        .info-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-purple, #6d28d9);
            margin: 0.5rem 0;
        }

        .info-label {
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Loading States */
        .loading-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 3rem;
            color: #64748b;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
            border-color: var(--primary-purple, #8b5cf6);
            border-right-color: transparent;
        }

        /* Error States */
        .error-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .error-icon {
            font-size: 3rem;
            color: #f59e0b;
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .modal-dialog-xl {
                max-width: 95%;
                margin: 1rem auto;
            }

            .modal-body {
                padding: 2rem;
                max-height: 65vh;
            }

            .modal-header,
            .modal-footer {
                padding-left: 2rem;
                padding-right: 2rem;
            }
        }

        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem auto;
                max-width: 98%;
            }

            .modal-header {
                padding: 1.5rem 1.5rem 1rem;
            }

            .modal-body {
                padding: 1.5rem;
                max-height: 60vh;
            }

            .modal-footer {
                padding: 1rem 1.5rem 1.5rem;
                flex-direction: column;
                gap: 0.75rem;
            }

            .modal-footer .btn {
                width: 100%;
                justify-content: center;
            }

            .modal-title {
                font-size: 1.25rem;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                text-align: left;
            }

            .detail-value {
                max-width: 100%;
                text-align: left;
            }

            .info-card {
                padding: 1rem;
            }

            .info-number {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .modal-content {
                border-radius: 0;
                height: 100vh;
                max-height: none;
            }

            .modal-dialog {
                margin: 0;
                max-width: 100%;
                height: 100vh;
                max-height: none;
            }

            .modal-body {
                max-height: none;
                flex: 1;
                overflow-y: auto;
            }
        }

        /* Animation for Modal Elements */
        .detail-section {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .detail-section:nth-child(1) { animation-delay: 0.1s; }
        .detail-section:nth-child(2) { animation-delay: 0.2s; }
        .detail-section:nth-child(3) { animation-delay: 0.3s; }
        .detail-section:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Focus Styles for Accessibility */
        .modal-content:focus {
            outline: 3px solid rgba(139, 92, 246, 0.3);
            outline-offset: -3px;
        }

        .detail-section:focus-within {
            border-color: var(--primary-purple, #8b5cf6);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        /* Print Styles */
        @media print {
            .modal-backdrop,
            .modal-header,
            .modal-footer {
                display: none !important;
            }

            .modal-content {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .modal-body {
                max-height: none;
                overflow: visible;
            }
        }

    </style>
    <link rel="stylesheet" href="../../assets/css/layout_user.css">
</head>

<body>
<div class="main-content">
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $projects = [];
    $error_message = null;
    $filter_type = isset($_GET['type']) ? $_GET['type'] : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
    $filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 6;
    $offset = ($page - 1) * $per_page;

    try {
        $conn = createDBConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Get statistics with engagement data
        $stats = [
                'total' => 0,
                'software' => 0,
                'hardware' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'high_priority' => 0,
                'total_likes' => 0,
                'total_comments' => 0
        ];

        // Get all basic stats
        $total_result = $conn->query("SELECT COUNT(*) as total FROM blog");
        if ($total_result) {
            $stats['total'] = $total_result->fetch_assoc()['total'];
        }

        $software_result = $conn->query("SELECT COUNT(*) as count FROM blog WHERE project_type = 'software'");
        if ($software_result) {
            $stats['software'] = $software_result->fetch_assoc()['count'];
        }

        $hardware_result = $conn->query("SELECT COUNT(*) as count FROM blog WHERE project_type = 'hardware'");
        if ($hardware_result) {
            $stats['hardware'] = $hardware_result->fetch_assoc()['count'];
        }

        $pending_result = $conn->query("SELECT COUNT(*) as count FROM blog WHERE status = 'pending'");
        if ($pending_result) {
            $stats['pending'] = $pending_result->fetch_assoc()['count'];
        }

        $progress_result = $conn->query("SELECT COUNT(*) as count FROM blog WHERE status = 'in_progress'");
        if ($progress_result) {
            $stats['in_progress'] = $progress_result->fetch_assoc()['count'];
        }

        $completed_result = $conn->query("SELECT COUNT(*) as count FROM blog WHERE status = 'completed'");
        if ($completed_result) {
            $stats['completed'] = $completed_result->fetch_assoc()['count'];
        }

        $high_priority_result = $conn->query("SELECT COUNT(*) as count FROM blog WHERE priority1 = 'high'");
        if ($high_priority_result) {
            $stats['high_priority'] = $high_priority_result->fetch_assoc()['count'];
        }

        // Get engagement stats
        $likes_result = $conn->query("SELECT COUNT(*) as count FROM idea_likes");
        if ($likes_result) {
            $stats['total_likes'] = $likes_result->fetch_assoc()['count'];
        }

        $comments_result = $conn->query("SELECT COUNT(*) as count FROM idea_comments");
        if ($comments_result) {
            $stats['total_comments'] = $comments_result->fetch_assoc()['count'];
        }

        // Build filtered query with engagement data
        $where_conditions = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($filter_type)) {
            $where_conditions[] = "b.project_type = ?";
            $params[] = $filter_type;
            $types .= "s";
        }

        if (!empty($filter_status)) {
            $where_conditions[] = "b.status = ?";
            $params[] = $filter_status;
            $types .= "s";
        }

        if (!empty($filter_priority)) {
            $where_conditions[] = "b.priority1 = ?";
            $params[] = $filter_priority;
            $types .= "s";
        }

        if (!empty($search_term)) {
            $where_conditions[] = "(b.project_name LIKE ? OR b.description LIKE ? OR b.er_number LIKE ?)";
            $search_pattern = "%{$search_term}%";
            $params[] = $search_pattern;
            $params[] = $search_pattern;
            $params[] = $search_pattern;
            $types .= "sss";
        }

        $where_clause = implode(" AND ", $where_conditions);

        // Get total count for pagination
        $count_sql = "SELECT COUNT(*) as total FROM blog b WHERE " . $where_clause;
        if (!empty($params)) {
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param($types, ...$params);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $total_projects = $count_result->fetch_assoc()['total'];
            $count_stmt->close();
        } else {
            $count_result = $conn->query($count_sql);
            $total_projects = $count_result->fetch_assoc()['total'];
        }
        $total_pages = ceil($total_projects / $per_page);

        // Get projects with engagement data and user info
        $sql = "SELECT b.*, r.id as owner_id, r.name as owner_name,
                       (SELECT COUNT(*) FROM idea_likes WHERE idea_id = b.id) as like_count,
                       (SELECT COUNT(*) FROM idea_comments WHERE idea_id = b.id) as comment_count,
                       " . ($current_user_id ? "(SELECT COUNT(*) FROM idea_likes WHERE idea_id = b.id AND user_id = ?) as user_liked" : "0 as user_liked") . "
                FROM blog b 
                LEFT JOIN register r ON b.user_id = r.id 
                WHERE " . $where_clause . " ORDER BY 
                CASE b.priority1 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END,
                b.submission_datetime DESC 
                LIMIT ? OFFSET ?";

        $final_params = [];
        $final_types = "";

        if ($current_user_id) {
            $final_params[] = $current_user_id;
            $final_types .= "i";
        }

        $final_params = array_merge($final_params, $params);
        $final_types .= $types;
        $final_params[] = $per_page;
        $final_params[] = $offset;
        $final_types .= "ii";

        if (!empty($final_params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($final_types, ...$final_params);
            $stmt->execute();
            $result = $stmt->get_result();
            $projects = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        $conn->close();
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        error_log("Projects page error: " . $e->getMessage());
    }
    ?>

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-list me-3"></i>
            All Ideas
        </h1>
        <p class="page-subtitle">Browse and explore all innovative ideas</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Enhanced Statistics Cards -->
    <div class="stats-container">
        <div class="stats-card">
            <div class="stat-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-number"><?php echo intval($stats['total']); ?></div>
            <div class="stat-label">Total Ideas</div>
        </div>
        <div class="stats-card">
            <div class="stat-icon">
                <i class="fas fa-laptop-code"></i>
            </div>
            <div class="stat-number"><?php echo intval($stats['software']); ?></div>
            <div class="stat-label">Software Ideas</div>
        </div>
        <div class="stats-card">
            <div class="stat-icon">
                <i class="fas fa-microchip"></i>
            </div>
            <div class="stat-number"><?php echo intval($stats['hardware']); ?></div>
            <div class="stat-label">Hardware Ideas</div>
        </div>
        <div class="stats-card">
            <div class="stat-icon">
                <i class="fas fa-fire"></i>
            </div>
            <div class="stat-number"><?php echo intval($stats['high_priority']); ?></div>
            <div class="stat-label">High Priority</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-container">
        <form method="GET" class="row g-3 align-items-end" id="filterForm">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Search Ideas</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control search-input border-start-0"
                           placeholder="Search by name, description, or ID..."
                           name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Idea Type</label>
                <select class="form-select filter-select" name="type">
                    <option value="">All Types</option>
                    <option value="software" <?php echo ($filter_type == 'software') ? 'selected' : ''; ?>>Software</option>
                    <option value="hardware" <?php echo ($filter_type == 'hardware') ? 'selected' : ''; ?>>Hardware</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Status</label>
                <select class="form-select filter-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo ($filter_status == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo ($filter_status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="rejected" <?php echo ($filter_status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Priority</label>
                <select class="form-select filter-select" name="priority">
                    <option value="">All Priorities</option>
                    <option value="high" <?php echo ($filter_priority == 'high') ? 'selected' : ''; ?>>High</option>
                    <option value="medium" <?php echo ($filter_priority == 'medium') ? 'selected' : ''; ?>>Medium</option>
                    <option value="low" <?php echo ($filter_priority == 'low') ? 'selected' : ''; ?>>Low</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-purple w-100">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
        </form>

        <?php if (!empty($filter_type) || !empty($filter_status) || !empty($filter_priority) || !empty($search_term)): ?>
            <div class="mt-3">
                <a href="?" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Projects Grid -->
    <div id="projectsContainer">
        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <h3>No Ideas Found</h3>
                <p>No Ideas match your search criteria. Try adjusting your filters.</p>
                <a href="add_project.php" class="btn btn-purple mt-3">
                    <i class="fas fa-plus me-2"></i>Create New Idea
                </a>
            </div>
        <?php else: ?>
            <div class="projects-grid" id="projectsGrid">
                <?php foreach ($projects as $project):
                    $can_edit = ($current_user_id && $current_user_id == $project['user_id']);
                    $user_liked = ($current_user_id && $project['user_liked'] > 0);
                    ?>
                    <div class="project-card" data-aos="fade-up" data-idea-id="<?php echo $project['id']; ?>">
                        <div class="priority-badge <?php echo getPriorityClass($project['priority1']); ?>">
                            <?php echo ucfirst($project['priority1']); ?>
                        </div>

                        <div class="project-header">
                            <div>
                                <h3 class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <div class="project-id">ID: <?php echo htmlspecialchars($project['er_number']); ?></div>
                                <div class="project-owner">
                                    <i class="fas fa-user me-1"></i>
                                    by <?php echo htmlspecialchars($project['owner_name'] ?: 'Unknown'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="project-meta">
                            <span class="meta-tag">
                                <i class="<?php echo ($project['project_type'] == 'software') ? 'fas fa-laptop-code' : 'fas fa-microchip'; ?> me-1"></i>
                                <?php echo ucfirst($project['project_type']); ?>
                            </span>
                            <span class="meta-tag">
                                <i class="fas fa-tag me-1"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $project['classification'])); ?>
                            </span>
                        </div>

                        <div class="status-badge <?php echo getStatusClass($project['status']); ?>">
                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                        </div>

                        <div class="project-description">
                            <?php echo nl2br(htmlspecialchars(truncateText($project['description']))); ?>
                        </div>

                        <div class="project-date">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Submitted: <?php echo formatDate($project['submission_datetime']); ?>
                        </div>

                        <!-- Engagement Section -->
                        <div class="project-engagement">
                            <div class="engagement-stats">
                                <span class="engagement-stat">
                                    <i class="fas fa-heart <?php echo $user_liked ? 'text-danger' : 'text-muted'; ?>"></i>
                                    <span class="like-count"><?php echo $project['like_count']; ?></span>
                                </span>
                                <span class="engagement-stat">
                                    <i class="fas fa-comment text-muted"></i>
                                    <span class="comment-count"><?php echo $project['comment_count']; ?></span>
                                </span>
                            </div>

                            <div class="engagement-actions">
                                <?php if ($current_user_id): ?>
                                    <button class="btn-like <?php echo $user_liked ? 'liked' : ''; ?>"
                                            data-idea-id="<?php echo $project['id']; ?>"
                                            title="<?php echo $user_liked ? 'Unlike' : 'Like'; ?> this idea">
                                        <i class="fas fa-heart"></i>
                                        <span class="like-text"><?php echo $user_liked ? 'Liked' : 'Like'; ?></span>
                                    </button>
                                <?php else: ?>
                                    <button class="btn-like disabled" title="Login to like ideas">
                                        <i class="fas fa-heart"></i>
                                        <span class="like-text">Like</span>
                                    </button>
                                <?php endif; ?>

                                <button class="btn-comment" data-idea-id="<?php echo $project['id']; ?>" title="View comments">
                                    <i class="fas fa-comment"></i>
                                    <span>Comment</span>
                                </button>
                            </div>
                        </div>

                        <div class="project-actions">
                            <button class="btn btn-outline-purple btn-sm view-details-btn" data-project-id="<?php echo $project['id']; ?>">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                            <?php if ($can_edit): ?>
                                <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-purple btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary btn-sm" disabled title="You can only edit your own idea">
                                    <i class="fas fa-lock me-1"></i>Edit
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Load More Button -->
            <?php if ($page < $total_pages): ?>
                <button id="loadMoreBtn" class="load-more-btn" data-page="<?php echo $page + 1; ?>">
                    <i class="fas fa-plus-circle me-2"></i>Load More Ideas
                </button>
            <?php endif; ?>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                <div class="spinner-border" role="status"></div>
                <div class="loading-text">Loading more ideas...</div>
            </div>

            <!-- Pagination Info -->
            <div class="text-center mt-3 text-muted">
                <small>
                    Showing <?php echo count($projects); ?> of <?php echo $total_projects; ?> Ideas
                    <?php if ($page < $total_pages): ?>
                        (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)
                    <?php endif; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>

    <!-- Enhanced Project Detail Modal with Comments -->
    <div class="modal fade" id="projectDetailModal" tabindex="-1" aria-labelledby="projectDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--purple-gradient); color: white;">
                    <h5 class="modal-title" id="projectDetailModalLabel">
                        <i class="fas fa-project-diagram me-2"></i>
                        <span id="modalProjectTitle">Idea Details</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="projectModalContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-purple" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading Idea details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="editProjectBtn" class="btn btn-purple" style="display: none;">
                        <i class="fas fa-edit me-1"></i> Edit Idea
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Override alerts
            window.alert = function(message) {
                console.log('Alert blocked:', message);
            };

            window.confirm = function(message) {
                console.log('Confirm blocked:', message);
                return true;
            };

            // Like functionality
            function setupLikeButtons() {
                const likeButtons = document.querySelectorAll('.btn-like:not(.disabled)');
                likeButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const ideaId = this.getAttribute('data-idea-id');
                        const isLiked = this.classList.contains('liked');

                        // Disable button during request
                        this.disabled = true;

                        fetch(window.location.pathname, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `action=toggle_like&idea_id=${ideaId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update button state
                                    if (data.liked) {
                                        this.classList.add('liked');
                                        this.querySelector('.like-text').textContent = 'Liked';
                                        this.title = 'Unlike this idea';
                                    } else {
                                        this.classList.remove('liked');
                                        this.querySelector('.like-text').textContent = 'Like';
                                        this.title = 'Like this idea';
                                    }

                                    // Update like count in card
                                    const card = this.closest('.project-card');
                                    const likeCountElement = card.querySelector('.like-count');
                                    if (likeCountElement) {
                                        likeCountElement.textContent = data.like_count;
                                    }

                                    // Update heart icon color
                                    const heartIcon = card.querySelector('.engagement-stat i.fa-heart');
                                    if (heartIcon) {
                                        heartIcon.className = data.liked ?
                                            'fas fa-heart text-danger' :
                                            'fas fa-heart text-muted';
                                    }
                                } else {
                                    console.error('Like error:', data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Network error:', error);
                            })
                            .finally(() => {
                                this.disabled = false;
                            });
                    });
                });
            }

            // Comment functionality
            function setupCommentButtons() {
                const commentButtons = document.querySelectorAll('.btn-comment');
                commentButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        const ideaId = this.getAttribute('data-idea-id');
                        showProjectDetails(ideaId, true); // Show modal with comments focus
                    });
                });
            }

            // Enhanced project details function with comments
            function showProjectDetails(projectId, focusComments = false) {
                const modal = new bootstrap.Modal(document.getElementById('projectDetailModal'));
                const modalContent = document.getElementById('projectModalContent');
                const modalTitle = document.getElementById('modalProjectTitle');
                const editBtn = document.getElementById('editProjectBtn');

                modal.show();

                modalContent.innerHTML = `
                    <div class="text-center p-4">
                        <div class="spinner-border text-purple" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading Ideas details...</p>
                    </div>
                `;
                modalTitle.textContent = 'Project Details';
                editBtn.style.display = 'none';

                const baseUrl = window.location.pathname;
                const params = new URLSearchParams({
                    get_project_details: '1',
                    project_id: projectId
                });

                fetch(baseUrl + '?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.project) {
                            const project = data.project;
                            modalTitle.textContent = project.project_name;

                            // Generate enhanced project HTML with comments section
                            modalContent.innerHTML = generateProjectDetailHTML(project);

                            // Setup engagement handlers
                            setupModalLikeButton(project);
                            setupCommentSection(project);

                            if (project.can_edit) {
                                editBtn.style.display = 'inline-block';
                                editBtn.onclick = function() {
                                    modal.hide();
                                    window.location.href = 'edit.php?id=' + project.id;
                                };
                            }

                            addDetailModalStyles();

                            // Focus comments if requested
                            if (focusComments) {
                                setTimeout(() => {
                                    const commentsSection = document.getElementById('commentsSection');
                                    if (commentsSection) {
                                        commentsSection.scrollIntoView({ behavior: 'smooth' });
                                    }
                                }, 100);
                            }

                        } else {
                            modalContent.innerHTML = generateErrorHTML(data.message, projectId);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modalContent.innerHTML = generateNetworkErrorHTML(projectId);
                    });
            }

            // Generate project detail HTML with engagement features
            function generateProjectDetailHTML(project) {
                return `
                    <!-- Project Header with Engagement -->
                    <div class="project-detail-header mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="project-name text-purple fw-bold mb-2">
                                    <i class="fas fa-project-diagram me-2"></i>
                                    ${project.project_name}
                                </h4>
                                <p class="project-id-display mb-2">
                                    <i class="fas fa-hashtag me-1"></i>
                                    <strong>Idea ID:</strong>
                                    <span class="badge bg-secondary ms-1">${project.er_number}</span>
                                </p>
                                <p class="project-owner-display mb-0">
                                    <i class="fas fa-user me-1"></i>
                                    <strong>Created by:</strong> ${project.owner_name}
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="status-priority-badges mb-3">
                                    <div class="mb-2">
                                        <span class="badge ${project.priority_class} fs-6 px-3 py-2">
                                            <i class="fas fa-flag me-1"></i>${project.priority1.toUpperCase()} Priority
                                        </span>
                                    </div>
                                    <div>
                                        <span class="badge ${project.status_class} fs-6 px-3 py-2">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                            ${project.status.replace('_', ' ').toUpperCase()}
                                        </span>
                                    </div>
                                </div>

                                <!-- Engagement Stats -->
                                <div class="modal-engagement-stats">
                                    <div class="engagement-stat-large">
                                        <i class="fas fa-heart text-danger"></i>
                                        <span class="modal-like-count">${project.like_count}</span>
                                        <small>Likes</small>
                                    </div>
                                    <div class="engagement-stat-large">
                                        <i class="fas fa-comment text-info"></i>
                                        <span class="modal-comment-count">${project.comment_count}</span>
                                        <small>Comments</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Engagement Actions in Header -->
                        <div class="modal-engagement-actions mt-3">
                            ${project.current_user_id ? `
                                <button class="btn-like-modal ${project.user_liked ? 'liked' : ''}"
                                        data-idea-id="${project.id}"
                                        title="${project.user_liked ? 'Unlike' : 'Like'} this idea">
                                    <i class="fas fa-heart me-2"></i>
                                    <span class="like-text">${project.user_liked ? 'Liked' : 'Like'}</span>
                                </button>
                            ` : `
                                <button class="btn-like-modal disabled" title="Login to like ideas">
                                    <i class="fas fa-heart me-2"></i>
                                    <span class="like-text">Like</span>
                                </button>
                            `}

                            <button class="btn-comment-modal" onclick="document.getElementById('commentInput').focus()">
                                <i class="fas fa-comment me-2"></i>
                                Add Comment
                            </button>
                        </div>
                    </div>

                    <!-- Main Details Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6 class="section-title">
                                    <i class="fas fa-info-circle me-2"></i>Project Idea Information
                                </h6>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-cog me-2"></i>Project Idea Type
                                    </span>
                                    <span class="detail-value">
                                        <i class="fas fa-${project.project_type === 'software' ? 'laptop-code' : 'microchip'} me-1"></i>
                                        ${project.project_type.charAt(0).toUpperCase() + project.project_type.slice(1)}
                                    </span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-tag me-2"></i>Classification
                                    </span>
                                    <span class="detail-value">${project.classification}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-user me-2"></i>Assigned To
                                    </span>
                                    <span class="detail-value">
                                        ${project.assigned_to === 'Not Assigned' ?
                    '<span class="text-muted"><i class="fas fa-user-slash me-1"></i>Unassigned</span>' :
                    project.assigned_to
                }
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6 class="section-title">
                                    <i class="fas fa-clock me-2"></i>Timeline Information
                                </h6>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-calendar-plus me-2"></i>Submitted
                                    </span>
                                    <span class="detail-value">${project.submission_datetime}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-calendar-check me-2"></i>Completion
                                    </span>
                                    <span class="detail-value">
                                        ${project.completion_date === 'N/A' ?
                    '<span class="text-muted">Not Set</span>' :
                    project.completion_date
                }
                                    </span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-chart-line me-2"></i>Progress
                                    </span>
                                    <span class="detail-value">
                                        ${getProgressInfo(project.status)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Section -->
                    <div class="mb-4">
                        <div class="detail-section">
                            <h6 class="section-title">
                                <i class="fas fa-file-alt me-2"></i>Idea Description
                            </h6>
                            <div class="description-box">
                                ${project.description}
                            </div>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="mb-4" id="commentsSection">
                        <div class="detail-section">
                            <h6 class="section-title">
                                <i class="fas fa-comments me-2"></i>Comments (${project.comment_count})
                            </h6>

                            <!-- Add Comment Form -->
                            ${project.current_user_id ? `
                                <div class="add-comment-form mb-4">
                                    <div class="input-group">
                                        <textarea class="form-control comment-input"
                                                id="commentInput"
                                                placeholder="Share your thoughts on this idea..."
                                                rows="3"
                                                maxlength="500"></textarea>
                                        <button class="btn btn-purple submit-comment-btn"
                                                data-idea-id="${project.id}">
                                            <i class="fas fa-paper-plane"></i>
                                            Post
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <span class="char-count">0</span>/500 characters
                                    </small>
                                </div>
                            ` : `
                                <div class="login-prompt mb-4">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Please login to post comments and like ideas.
                                    </div>
                                </div>
                            `}

                            <!-- Comments List -->
                            <div class="comments-list" id="commentsList">
                                ${generateCommentsHTML(project.comments, project.current_user_id)}
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info Section -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-card text-center">
                                    <i class="fas fa-calendar-day text-primary mb-2"></i>
                                    <div class="info-number">${calculateDaysActive(project.submission_datetime)}</div>
                                    <div class="info-label">Days Active</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-card text-center">
                                    <i class="fas fa-tasks text-info mb-2"></i>
                                    <div class="info-number">${getCompletionPercentage(project.status)}%</div>
                                    <div class="info-label">Complete</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-card text-center">
                                    <i class="fas fa-heart text-danger mb-2"></i>
                                    <div class="info-number modal-like-count">${project.like_count}</div>
                                    <div class="info-label">Likes</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-card text-center">
                                    <i class="fas fa-comment text-info mb-2"></i>
                                    <div class="info-number modal-comment-count">${project.comment_count}</div>
                                    <div class="info-label">Comments</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Info -->
                    <div class="project-footer-info">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-eye me-1"></i>
                                    Viewed on ${new Date().toLocaleString()}
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                ${project.can_edit ?
                    '<span class="badge bg-success"><i class="fas fa-edit me-1"></i>Editable</span>' :
                    '<span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>View Only</span>'
                }
                            </div>
                        </div>
                    </div>
                `;
            }

            // Generate comments HTML
            function generateCommentsHTML(comments, currentUserId) {
                if (!comments || comments.length === 0) {
                    return `
                        <div class="no-comments text-center py-4">
                            <i class="fas fa-comments text-muted mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                            <p class="text-muted">No comments yet. Be the first to share your thoughts!</p>
                        </div>
                    `;
                }

                let html = '';
                comments.forEach(comment => {
                    const canDelete = currentUserId && currentUserId == comment.user_id;
                    const commentDate = new Date(comment.created_at).toLocaleString();

                    html += `
                        <div class="comment-item" data-comment-id="${comment.id}">
                            <div class="comment-header">
                                <div class="comment-author">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <strong>${comment.commenter_name || 'Anonymous'}</strong>
                                </div>
                                <div class="comment-actions">
                                    <small class="text-muted me-2">${commentDate}</small>
                                    ${canDelete ? `
                                        <button class="btn-delete-comment"
                                                data-comment-id="${comment.id}"
                                                title="Delete comment">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="comment-body">
                                ${comment.comment.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                });

                return html;
            }

            // Setup modal like button
            function setupModalLikeButton(project) {
                const likeBtn = document.querySelector('.btn-like-modal:not(.disabled)');
                if (likeBtn) {
                    likeBtn.addEventListener('click', function() {
                        const ideaId = this.getAttribute('data-idea-id');

                        this.disabled = true;

                        fetch(window.location.pathname, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `action=toggle_like&idea_id=${ideaId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Update modal button
                                    if (data.liked) {
                                        this.classList.add('liked');
                                        this.querySelector('.like-text').textContent = 'Liked';
                                        this.title = 'Unlike this idea';
                                    } else {
                                        this.classList.remove('liked');
                                        this.querySelector('.like-text').textContent = 'Like';
                                        this.title = 'Like this idea';
                                    }

                                    // Update modal like count
                                    const modalLikeCount = document.querySelector('.modal-like-count');
                                    if (modalLikeCount) {
                                        modalLikeCount.textContent = data.like_count;
                                    }

                                    // Update card like count if visible
                                    const card = document.querySelector(`[data-idea-id="${ideaId}"]`);
                                    if (card) {
                                        const cardLikeCount = card.querySelector('.like-count');
                                        const cardLikeBtn = card.querySelector('.btn-like');
                                        const cardHeartIcon = card.querySelector('.engagement-stat i.fa-heart');

                                        if (cardLikeCount) cardLikeCount.textContent = data.like_count;
                                        if (cardLikeBtn) {
                                            cardLikeBtn.classList.toggle('liked', data.liked);
                                            cardLikeBtn.querySelector('.like-text').textContent = data.liked ? 'Liked' : 'Like';
                                        }
                                        if (cardHeartIcon) {
                                            cardHeartIcon.className = data.liked ?
                                                'fas fa-heart text-danger' :
                                                'fas fa-heart text-muted';
                                        }
                                    }
                                }
                            })
                            .catch(error => console.error('Like error:', error))
                            .finally(() => {
                                this.disabled = false;
                            });
                    });
                }
            }

            // Setup comment section
            function setupCommentSection(project) {
                // Character count for comment input
                const commentInput = document.getElementById('commentInput');
                const charCount = document.querySelector('.char-count');

                if (commentInput && charCount) {
                    commentInput.addEventListener('input', function() {
                        charCount.textContent = this.value.length;

                        // Change color based on length
                        if (this.value.length > 450) {
                            charCount.style.color = '#ef4444';
                        } else if (this.value.length > 400) {
                            charCount.style.color = '#f59e0b';
                        } else {
                            charCount.style.color = '#64748b';
                        }
                    });
                }

                // Submit comment
                const submitBtn = document.querySelector('.submit-comment-btn');
                if (submitBtn) {
                    submitBtn.addEventListener('click', function() {
                        const comment = commentInput.value.trim();
                        const ideaId = this.getAttribute('data-idea-id');

                        if (!comment) {
                            showToast('Please enter a comment', 'warning');
                            return;
                        }

                        if (comment.length > 500) {
                            showToast('Comment is too long (max 500 characters)', 'danger');
                            return;
                        }

                        this.disabled = true;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';

                        fetch(window.location.pathname, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `action=add_comment&idea_id=${ideaId}&comment=${encodeURIComponent(comment)}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Clear input
                                    commentInput.value = '';
                                    charCount.textContent = '0';
                                    charCount.style.color = '#64748b';

                                    // Update comment count
                                    const modalCommentCount = document.querySelector('.modal-comment-count');
                                    if (modalCommentCount) {
                                        modalCommentCount.textContent = data.comment_count;
                                    }

                                    // Update card comment count
                                    const card = document.querySelector(`[data-idea-id="${ideaId}"]`);
                                    if (card) {
                                        const cardCommentCount = card.querySelector('.comment-count');
                                        if (cardCommentCount) {
                                            cardCommentCount.textContent = data.comment_count;
                                        }
                                    }

                                    // Reload project details to show new comment
                                    showProjectDetails(ideaId, true);

                                    showToast('Comment added successfully!', 'success');
                                } else {
                                    showToast(data.message || 'Failed to add comment', 'danger');
                                }
                            })
                            .catch(error => {
                                console.error('Comment error:', error);
                                showToast('Network error. Please try again.', 'danger');
                            })
                            .finally(() => {
                                this.disabled = false;
                                this.innerHTML = '<i class="fas fa-paper-plane"></i> Post';
                            });
                    });
                }

                // Delete comment buttons
                const deleteButtons = document.querySelectorAll('.btn-delete-comment');
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const commentId = this.getAttribute('data-comment-id');

                        if (confirm('Are you sure you want to delete this comment?')) {
                            this.disabled = true;

                            fetch(window.location.pathname, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: `action=delete_comment&comment_id=${commentId}`
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Remove comment from DOM
                                        const commentItem = this.closest('.comment-item');
                                        commentItem.remove();

                                        // Update comment count
                                        const modalCommentCount = document.querySelector('.modal-comment-count');
                                        if (modalCommentCount) {
                                            modalCommentCount.textContent = data.comment_count;
                                        }

                                        // Update section title
                                        const sectionTitle = document.querySelector('#commentsSection .section-title');
                                        if (sectionTitle) {
                                            sectionTitle.innerHTML = `<i class="fas fa-comments me-2"></i>Comments (${data.comment_count})`;
                                        }

                                        // Show no comments message if needed
                                        if (data.comment_count === 0) {
                                            document.getElementById('commentsList').innerHTML = `
                                            <div class="no-comments text-center py-4">
                                                <i class="fas fa-comments text-muted mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                                                <p class="text-muted">No comments yet. Be the first to share your thoughts!</p>
                                            </div>
                                        `;
                                        }

                                        showToast('Comment deleted successfully', 'success');
                                    } else {
                                        showToast(data.message || 'Failed to delete comment', 'danger');
                                        this.disabled = false;
                                    }
                                })
                                .catch(error => {
                                    console.error('Delete error:', error);
                                    showToast('Network error. Please try again.', 'danger');
                                    this.disabled = false;
                                });
                        }
                    });
                });
            }

            // Generate error HTML
            function generateErrorHTML(message, projectId) {
                return `
                    <div class="text-center p-5">
                        <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">Unable to Load Project Details</h5>
                        <p class="text-muted mb-4">
                            ${message || 'There was an issue loading the project information.'}
                        </p>
                        <button class="btn btn-purple me-2" onclick="showProjectDetails(${projectId})">
                            <i class="fas fa-refresh me-2"></i>Try Again
                        </button>
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                    </div>
                `;
            }

            // Generate network error HTML
            function generateNetworkErrorHTML(projectId) {
                return `
                    <div class="text-center p-5">
                        <i class="fas fa-wifi text-danger mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">Connection Error</h5>
                        <p class="text-muted mb-4">
                            Unable to connect to server. Please check your connection and try again.
                        </p>
                        <button class="btn btn-purple me-2" onclick="showProjectDetails(${projectId})">
                            <i class="fas fa-refresh me-2"></i>Retry
                        </button>
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                    </div>
                `;
            }

            // Toast notification function
            function showToast(message, type = 'info') {
                // Remove existing toasts
                const existingToasts = document.querySelectorAll('.toast-notification');
                existingToasts.forEach(toast => toast.remove());

                const toast = document.createElement('div');
                toast.className = `toast-notification alert alert-${type} alert-dismissible fade show`;
                toast.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    min-width: 300px;
                    animation: slideIn 0.3s ease;
                `;

                const icons = {
                    success: 'fas fa-check-circle',
                    danger: 'fas fa-exclamation-circle',
                    warning: 'fas fa-exclamation-triangle',
                    info: 'fas fa-info-circle'
                };

                toast.innerHTML = `
                    <i class="${icons[type] || icons.info} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                document.body.appendChild(toast);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 5000);
            }

            // Load More Button Functionality (updated to include engagement)
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const projectsGrid = document.getElementById('projectsGrid');

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const currentPage = parseInt(this.getAttribute('data-page'));

                    loadMoreBtn.style.display = 'none';
                    loadingSpinner.style.display = 'flex';

                    const urlParams = new URLSearchParams(window.location.search);
                    const filterParams = new URLSearchParams();

                    if (urlParams.get('type')) filterParams.set('type', urlParams.get('type'));
                    if (urlParams.get('status')) filterParams.set('status', urlParams.get('status'));
                    if (urlParams.get('priority')) filterParams.set('priority', urlParams.get('priority'));
                    if (urlParams.get('search')) filterParams.set('search', urlParams.get('search'));

                    filterParams.set('ajax', '1');
                    filterParams.set('page', currentPage);

                    fetch(window.location.pathname + '?' + filterParams.toString())
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.html) {
                                projectsGrid.insertAdjacentHTML('beforeend', data.html);

                                setupViewDetailsButtons();
                                setupLikeButtons();
                                setupCommentButtons();

                                if (data.hasMore) {
                                    loadMoreBtn.setAttribute('data-page', data.nextPage);
                                    loadMoreBtn.style.display = 'block';
                                } else {
                                    loadMoreBtn.style.display = 'none';
                                    const allLoadedMsg = document.createElement('div');
                                    allLoadedMsg.className = 'text-center mt-3 text-muted';
                                    allLoadedMsg.innerHTML = '<small><i class="fas fa-check-circle me-1"></i>All projects loaded</small>';
                                    loadingSpinner.parentNode.insertBefore(allLoadedMsg, loadingSpinner);
                                }
                            } else {
                                console.error('Error loading more projects:', data.message);
                                showToast('Failed to load more projects. Please try again.', 'danger');
                                loadMoreBtn.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Network error:', error);
                            showToast('Network error. Please check your connection and try again.', 'danger');
                            loadMoreBtn.style.display = 'block';
                        })
                        .finally(() => {
                            loadingSpinner.style.display = 'none';
                        });
                });
            }

            // Helper functions
            function getProgressInfo(status) {
                const progressMap = {
                    'pending': { percent: 10, color: 'warning', text: 'Pending' },
                    'in_progress': { percent: 60, color: 'info', text: 'In Progress' },
                    'completed': { percent: 100, color: 'success', text: 'Completed' },
                    'rejected': { percent: 0, color: 'danger', text: 'Rejected' }
                };

                const progress = progressMap[status] || progressMap['pending'];

                return `
                    <div class="progress mb-1" style="height: 6px;">
                        <div class="progress-bar bg-${progress.color}" style="width: ${progress.percent}%"></div>
                    </div>
                    <small class="text-${progress.color}">${progress.percent}%</small>
                `;
            }

            function calculateDaysActive(startDate) {
                if (!startDate || startDate === 'N/A') return 0;
                const start = new Date(startDate);
                const now = new Date();
                const diffTime = Math.abs(now - start);
                return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            }

            function getCompletionPercentage(status) {
                const percentMap = {
                    'pending': 10,
                    'in_progress': 60,
                    'completed': 100,
                    'rejected': 0
                };
                return percentMap[status] || 10;
            }

            function addDetailModalStyles() {
                const existingStyle = document.querySelector('#detail-modal-styles');
                if (existingStyle) existingStyle.remove();

                const style = document.createElement('style');
                style.id = 'detail-modal-styles';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }

            // Setup view details buttons
            function setupViewDetailsButtons() {
                const viewBtns = document.querySelectorAll('.view-details-btn');
                viewBtns.forEach(btn => {
                    const newBtn = btn.cloneNode(true);
                    btn.parentNode.replaceChild(newBtn, btn);

                    newBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const projectId = this.getAttribute('data-project-id');
                        if (projectId) {
                            showProjectDetails(projectId);
                        }
                    });
                });
            }

            // Initialize all functionality
            setupViewDetailsButtons();
            setupLikeButtons();
            setupCommentButtons();

            // Make functions globally available
            window.showProjectDetails = showProjectDetails;
            window.setupViewDetailsButtons = setupViewDetailsButtons;
            window.setupLikeButtons = setupLikeButtons;
            window.setupCommentButtons = setupCommentButtons;

            // Remove existing alerts
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => alert.remove());
            }, 100);
        });
    </script>
    <script src="../../assets/js/layout_user.js"></script>

    <?php include $basePath . 'layout_footer.php'; ?>
</div>
</body>
</html>