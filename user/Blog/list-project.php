<?php
$basePath = '../';

// Check if this is an AJAX request first
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // Handle AJAX request for lazy loading
    handleAjaxRequest();
    exit;
}

// Check if this is an AJAX request for project details
if (isset($_GET['get_project_details']) && $_GET['get_project_details'] == '1') {
    handleProjectDetailsRequest();
    exit;
}

include $basePath . 'layout.php';

// AJAX handler function for project details - REMOVED ALERT
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

        // Get project with user info for edit permission check
        $sql = "SELECT b.*, r.id as owner_id FROM blog b 
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
        $stmt->close();
        $conn->close();

        // Check if current user can edit this project
        session_start();
        $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $can_edit = ($current_user_id && $current_user_id == $project['user_id']);

        // Format the response - NO ALERT, JUST RETURN DATA
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
                        'user_id' => $project['user_id']
                ]
        ];

        // Return JSON response without any alerts
        echo json_encode($response);

    } catch (Exception $e) {
        // Log the error instead of showing alert
        error_log("Project details error: " . $e->getMessage());

        $response = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
        ];
        echo json_encode($response);
    }
}

// AJAX handler function - AUTHENTICATION REMOVED for loading
function handleAjaxRequest() {
    header('Content-Type: application/json');

    try {
        $conn = createDBConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Get current user for edit permissions
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

        // Build query - show ALL projects (no user filtering)
        $where_conditions = ["1=1"]; // Always true condition
        $params = [];
        $types = "";

        if (!empty($filter_type)) {
            $where_conditions[] = "project_type = ?";
            $params[] = $filter_type;
            $types .= "s";
        }

        if (!empty($filter_status)) {
            $where_conditions[] = "status = ?";
            $params[] = $filter_status;
            $types .= "s";
        }

        if (!empty($filter_priority)) {
            $where_conditions[] = "priority1 = ?";
            $params[] = $filter_priority;
            $types .= "s";
        }

        if (!empty($search_term)) {
            $where_conditions[] = "(project_name LIKE ? OR description LIKE ? OR er_number LIKE ?)";
            $search_pattern = "%{$search_term}%";
            $params[] = $search_pattern;
            $params[] = $search_pattern;
            $params[] = $search_pattern;
            $types .= "sss";
        }

        $where_clause = implode(" AND ", $where_conditions);

        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM blog WHERE " . $where_clause;
        $count_stmt = $conn->prepare($count_sql);
        if (!empty($params)) {
            $count_stmt->bind_param($types, ...$params);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_projects = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_projects / $per_page);
        $count_stmt->close();

        // Get projects with user info for edit permissions
        $sql = "SELECT b.*, r.id as owner_id FROM blog b 
                LEFT JOIN register r ON b.user_id = r.id 
                WHERE " . $where_clause . " ORDER BY 
                CASE b.priority1 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END,
                b.submission_datetime DESC 
                LIMIT ? OFFSET ?";

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

        // Generate HTML
        ob_start();
        foreach ($projects as $project):
            $can_edit = ($current_user_id && $current_user_id == $project['user_id']);
            ?>
            <div class="project-card" data-aos="fade-up">
                <div class="priority-badge <?php echo getPriorityClass($project['priority1']); ?>">
                    <?php echo ucfirst($project['priority1']); ?>
                </div>

                <div class="project-header">
                    <div>
                        <h3 class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                        <div class="project-id">ID: <?php echo htmlspecialchars($project['er_number']); ?></div>
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

        // Calculate pagination info
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
        $response = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
        ];
        echo json_encode($response);
    }
}

// Helper functions need to be defined before AJAX call
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

</head>

<body>
<div class="main-content">
    <?php
    // Start session to get user ID for edit permissions
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Get current user ID for edit permissions
    $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Initialize variables
    $projects = [];
    $error_message = null;
    $filter_type = isset($_GET['type']) ? $_GET['type'] : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
    $filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    // Lazy loading parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = 6; // Reduced for better lazy loading experience
    $offset = ($page - 1) * $per_page;

    try {
        $conn = createDBConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Get statistics for ALL projects (no user filtering)
        $stats = [
                'total' => 0,
                'software' => 0,
                'hardware' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'high_priority' => 0
        ];

        // Get total count
        $total_query = "SELECT COUNT(*) as total FROM blog";
        $total_result = $conn->query($total_query);
        if ($total_result) {
            $stats['total'] = $total_result->fetch_assoc()['total'];
        }

        // Get software projects count
        $software_query = "SELECT COUNT(*) as count FROM blog WHERE project_type = 'software'";
        $software_result = $conn->query($software_query);
        if ($software_result) {
            $stats['software'] = $software_result->fetch_assoc()['count'];
        }

        // Get hardware projects count
        $hardware_query = "SELECT COUNT(*) as count FROM blog WHERE project_type = 'hardware'";
        $hardware_result = $conn->query($hardware_query);
        if ($hardware_result) {
            $stats['hardware'] = $hardware_result->fetch_assoc()['count'];
        }

        // Get pending projects count
        $pending_query = "SELECT COUNT(*) as count FROM blog WHERE status = 'pending'";
        $pending_result = $conn->query($pending_query);
        if ($pending_result) {
            $stats['pending'] = $pending_result->fetch_assoc()['count'];
        }

        // Get in_progress projects count
        $progress_query = "SELECT COUNT(*) as count FROM blog WHERE status = 'in_progress'";
        $progress_result = $conn->query($progress_query);
        if ($progress_result) {
            $stats['in_progress'] = $progress_result->fetch_assoc()['count'];
        }

        // Get completed projects count
        $completed_query = "SELECT COUNT(*) as count FROM blog WHERE status = 'completed'";
        $completed_result = $conn->query($completed_query);
        if ($completed_result) {
            $stats['completed'] = $completed_result->fetch_assoc()['count'];
        }

        // Get high priority projects count
        $high_priority_query = "SELECT COUNT(*) as count FROM blog WHERE priority1 = 'high'";
        $high_priority_result = $conn->query($high_priority_query);
        if ($high_priority_result) {
            $stats['high_priority'] = $high_priority_result->fetch_assoc()['count'];
        }

        // Build filtered query - show ALL projects
        $where_conditions = ["1=1"]; // Always true condition
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

        // Get projects with pagination and user info for edit permissions
        $sql = "SELECT b.*, r.id as owner_id FROM blog b 
                LEFT JOIN register r ON b.user_id = r.id 
                WHERE " . $where_clause . " ORDER BY 
                CASE b.priority1 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END,
                b.submission_datetime DESC 
                LIMIT ? OFFSET ?";

        $params[] = $per_page;
        $params[] = $offset;
        $types .= "ii";

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
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
    <link rel="stylesheet" href="../../assets/css/list_project.css">
    <link rel="stylesheet" href="../../assets/css/layout_user.css">

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

    <!-- Statistics Cards -->
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
                <h3>No idea Found</h3>
                <p>No Ideas match your search criteria. Try adjusting your filters.</p>
                <a href="add_project.php" class="btn btn-purple mt-3">
                    <i class="fas fa-plus me-2"></i>Create New Idea
                </a>
            </div>
        <?php else: ?>
            <div class="projects-grid" id="projectsGrid">
                <?php foreach ($projects as $project):
                    $can_edit = ($current_user_id && $current_user_id == $project['user_id']);
                    ?>
                    <div class="project-card" data-aos="fade-up">
                        <div class="priority-badge <?php echo getPriorityClass($project['priority1']); ?>">
                            <?php echo ucfirst($project['priority1']); ?>
                        </div>

                        <div class="project-header">
                            <div>
                                <h3 class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></h3>
                                <div class="project-id">ID: <?php echo htmlspecialchars($project['er_number']); ?></div>
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

                        <div class="project-actions">
                            <button class="btn btn-outline-purple btn-sm view-details-btn" data-project-id="<?php echo $project['id']; ?>">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                            <?php if ($can_edit): ?>
                                <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-purple btn-sm">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary btn-sm" disabled title="You can only edit your own ideas">
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
                    <i class="fas fa-plus-circle me-2"></i>Load More ideas
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

    <!-- Project Detail Modal -->
    <div class="modal fade" id="projectDetailModal" tabindex="-1" aria-labelledby="projectDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
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
<!--    <script src="../../assets/js/list_project.js"></script>-->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Override any alert functions to prevent browser alerts
            window.alert = function(message) {
                console.log('Alert blocked:', message);
                // Do nothing - no alerts will show
            };

            // Override confirm to prevent confirmation dialogs
            window.confirm = function(message) {
                console.log('Confirm blocked:', message);
                return true; // Always return true
            };

            // Function to show project details in modal - COMPLETELY ALERT FREE
            function showProjectDetails(projectId) {
                // Remove any existing alerts on the page
                const existingAlerts = document.querySelectorAll('.alert');
                existingAlerts.forEach(alert => alert.remove());

                const modal = new bootstrap.Modal(document.getElementById('projectDetailModal'));
                const modalContent = document.getElementById('projectModalContent');
                const modalTitle = document.getElementById('modalProjectTitle');
                const editBtn = document.getElementById('editProjectBtn');

                // Show modal immediately - no alerts
                modal.show();

                // Set loading content
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

                // Fetch project details
                const baseUrl = window.location.pathname;
                const params = new URLSearchParams({
                    get_project_details: '1',
                    project_id: projectId
                });

                fetch(baseUrl + '?' + params.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to load project details');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.project) {
                            const project = data.project;
                            modalTitle.textContent = project.project_name;

                            // Generate detailed project HTML - NO ALERTS ANYWHERE
                            modalContent.innerHTML = `
                    <!-- Project Header -->
                    <div class="project-detail-header mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="project-name text-purple fw-bold mb-2">
                                    <i class="fas fa-project-diagram me-2"></i>
                                    ${project.project_name}
                                </h4>
                                <p class="project-id-display mb-0">
                                    <i class="fas fa-hashtag me-1"></i>
                                    <strong>Idea ID:</strong>
                                    <span class="badge bg-secondary ms-1">${project.er_number}</span>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="status-priority-badges">
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
                            </div>
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

                    <!-- Additional Info Section -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-card text-center">
                                    <i class="fas fa-calendar-day text-primary mb-2"></i>
                                    <div class="info-number">${calculateDaysActive(project.submission_datetime)}</div>
                                    <div class="info-label">Days Active</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card text-center">
                                    <i class="fas fa-tasks text-info mb-2"></i>
                                    <div class="info-number">${getCompletionPercentage(project.status)}%</div>
                                    <div class="info-label">Complete</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card text-center">
                                    <i class="fas fa-database text-secondary mb-2"></i>
                                    <div class="info-number">#${project.id}</div>
                                    <div class="info-label">Internal ID</div>
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

                            // Handle edit button
                            if (project.can_edit) {
                                editBtn.style.display = 'inline-block';
                                editBtn.onclick = function() {
                                    modal.hide();
                                    window.location.href = 'edit.php?id=' + project.id;
                                };
                            } else {
                                editBtn.style.display = 'none';
                            }

                            // Add styling
                            addDetailModalStyles();

                        } else {
                            // Handle error without alerts
                            modalContent.innerHTML = `
                    <div class="text-center p-5">
                        <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">Unable to Load Project Details</h5>
                        <p class="text-muted mb-4">
                            ${data.message || 'There was an issue loading the project information.'}
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
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Handle error without alerts
                        modalContent.innerHTML = `
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
                    });
            }

            // Load More Button Functionality
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const projectsGrid = document.getElementById('projectsGrid');

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const currentPage = parseInt(this.getAttribute('data-page'));

                    // Show loading state
                    loadMoreBtn.style.display = 'none';
                    loadingSpinner.style.display = 'flex';

                    // Get current filter parameters from the URL or form
                    const urlParams = new URLSearchParams(window.location.search);
                    const filterParams = new URLSearchParams();

                    // Add existing filters
                    if (urlParams.get('type')) filterParams.set('type', urlParams.get('type'));
                    if (urlParams.get('status')) filterParams.set('status', urlParams.get('status'));
                    if (urlParams.get('priority')) filterParams.set('priority', urlParams.get('priority'));
                    if (urlParams.get('search')) filterParams.set('search', urlParams.get('search'));

                    // Add pagination and AJAX parameters
                    filterParams.set('ajax', '1');
                    filterParams.set('page', currentPage);

                    // Make AJAX request
                    fetch(window.location.pathname + '?' + filterParams.toString())
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.html) {
                                // Append new projects to the grid
                                projectsGrid.insertAdjacentHTML('beforeend', data.html);

                                // Setup view details buttons for new projects
                                setupViewDetailsButtons();

                                // Update pagination info if available
                                const paginationInfo = document.querySelector('.pagination-info');
                                if (paginationInfo && data.paginationInfo) {
                                    paginationInfo.textContent = data.paginationInfo;
                                }

                                // Update load more button
                                if (data.hasMore) {
                                    loadMoreBtn.setAttribute('data-page', data.nextPage);
                                    loadMoreBtn.style.display = 'block';
                                } else {
                                    // No more projects to load
                                    loadMoreBtn.style.display = 'none';

                                    // Show "all loaded" message
                                    const allLoadedMsg = document.createElement('div');
                                    allLoadedMsg.className = 'text-center mt-3 text-muted';
                                    allLoadedMsg.innerHTML = '<small><i class="fas fa-check-circle me-1"></i>All projects loaded</small>';
                                    loadingSpinner.parentNode.insertBefore(allLoadedMsg, loadingSpinner);
                                }
                            } else {
                                // Handle error
                                console.error('Error loading more projects:', data.message);
                                showErrorMessage('Failed to load more projects. Please try again.');
                                loadMoreBtn.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Network error:', error);
                            showErrorMessage('Network error. Please check your connection and try again.');
                            loadMoreBtn.style.display = 'block';
                        })
                        .finally(() => {
                            // Hide loading spinner
                            loadingSpinner.style.display = 'none';
                        });
                });
            }

            // Helper function to show error messages
            function showErrorMessage(message) {
                // Remove existing error messages
                const existingErrors = document.querySelectorAll('.load-more-error');
                existingErrors.forEach(error => error.remove());

                // Create and show new error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-warning alert-dismissible fade show load-more-error mt-3';
                errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

                if (loadMoreBtn) {
                    loadMoreBtn.parentNode.insertBefore(errorDiv, loadMoreBtn);
                } else {
                    projectsGrid.parentNode.appendChild(errorDiv);
                }

                // Auto-hide after 5 seconds
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        errorDiv.remove();
                    }
                }, 5000);
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
            .project-detail-header {
                background: linear-gradient(135deg, #f8fafc 0%, var(--light-purple) 100%);
                border: 2px solid rgba(139, 92, 246, 0.1);
                border-radius: 12px;
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .project-name {
                margin-bottom: 0.5rem;
                color: var(--primary-purple);
            }

            .status-priority-badges .badge {
                font-size: 0.85rem;
                padding: 0.5rem 1rem;
            }

            .detail-section {
                background: rgba(248, 250, 252, 0.6);
                border: 1px solid rgba(139, 92, 246, 0.1);
                border-radius: 12px;
                padding: 1.5rem;
                height: 100%;
            }

            .section-title {
                color: var(--primary-purple);
                font-weight: 700;
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
                border-bottom: 2px solid rgba(139, 92, 246, 0.2);
            }

            .detail-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 0;
                border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            }

            .detail-row:last-child {
                border-bottom: none;
            }

            .detail-label {
                font-weight: 600;
                color: #374151;
                min-width: 120px;
            }

            .detail-value {
                text-align: right;
                color: #1f2937;
                font-weight: 500;
            }

            .description-box {
                background: white;
                border: 2px solid rgba(139, 92, 246, 0.1);
                border-radius: 8px;
                padding: 1rem;
                min-height: 100px;
                max-height: 200px;
                overflow-y: auto;
                line-height: 1.6;
                color: #374151;
            }

            .info-card {
                background: white;
                border: 2px solid rgba(139, 92, 246, 0.1);
                border-radius: 12px;
                padding: 1.5rem;
                transition: all 0.3s ease;
            }

            .info-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(139, 92, 246, 0.15);
            }

            .info-card i {
                font-size: 2rem;
            }

            .info-number {
                font-size: 1.5rem;
                font-weight: 700;
                color: var(--dark-purple);
                margin: 0.5rem 0;
            }

            .info-label {
                color: #64748b;
                font-weight: 500;
                font-size: 0.9rem;
            }

            .project-footer-info {
                background: rgba(248, 250, 252, 0.8);
                border-top: 2px solid rgba(139, 92, 246, 0.1);
                margin: 1.5rem -1.5rem -1.5rem -1.5rem;
                padding: 1rem 1.5rem;
                border-radius: 0 0 12px 12px;
            }

            .project-footer-info .badge {
                font-size: 0.85rem;
                padding: 0.5rem 1rem;
            }

            /* Priority and status badge colors */
            .priority-high { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
            .priority-medium { background: rgba(245, 158, 11, 0.1); color: #d97706; }
            .priority-low { background: rgba(16, 185, 129, 0.1); color: #059669; }

            .status-pending { background: rgba(245, 158, 11, 0.1); color: #d97706; }
            .status-in_progress { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
            .status-completed { background: rgba(16, 185, 129, 0.1); color: #059669; }
            .status-rejected { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        `;
                document.head.appendChild(style);
            }

            // Setup view details buttons
            function setupViewDetailsButtons() {
                const viewBtns = document.querySelectorAll('.view-details-btn');
                viewBtns.forEach(btn => {
                    // Remove any existing listeners
                    const newBtn = btn.cloneNode(true);
                    btn.parentNode.replaceChild(newBtn, btn);

                    // Add new listener
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

            // Initialize
            setupViewDetailsButtons();

            // Make functions globally available
            window.showProjectDetails = showProjectDetails;
            window.setupViewDetailsButtons = setupViewDetailsButtons;

            // Remove any existing alerts on page load
            document.querySelectorAll('.alert').forEach(alert => alert.remove());

            // Prevent any future alerts
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