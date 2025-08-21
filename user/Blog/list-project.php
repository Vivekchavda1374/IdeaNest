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
                        <button class="btn btn-outline-secondary btn-sm" disabled title="You can only edit your own projects">
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
            All Projects
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
            <div class="stat-label">Total Projects</div>
        </div>
        <div class="stats-card">
            <div class="stat-icon">
                <i class="fas fa-laptop-code"></i>
            </div>
            <div class="stat-number"><?php echo intval($stats['software']); ?></div>
            <div class="stat-label">Software Projects</div>
        </div>
        <div class="stats-card">
            <div class="stat-icon">
                <i class="fas fa-microchip"></i>
            </div>
            <div class="stat-number"><?php echo intval($stats['hardware']); ?></div>
            <div class="stat-label">Hardware Projects</div>
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
                <label class="form-label fw-semibold">Search Projects</label>
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
                <label class="form-label fw-semibold">Project Type</label>
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
                <h3>No Projects Found</h3>
                <p>No projects match your search criteria. Try adjusting your filters.</p>
                <a href="add_project.php" class="btn btn-purple mt-3">
                    <i class="fas fa-plus me-2"></i>Create New Project
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
                                <button class="btn btn-outline-secondary btn-sm" disabled title="You can only edit your own projects">
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
                    <i class="fas fa-plus-circle me-2"></i>Load More Projects
                </button>
            <?php endif; ?>

            <!-- Loading Spinner -->
            <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                <div class="spinner-border" role="status"></div>
                <div class="loading-text">Loading more projects...</div>
            </div>

            <!-- Pagination Info -->
            <div class="text-center mt-3 text-muted">
                <small>
                    Showing <?php echo count($projects); ?> of <?php echo $total_projects; ?> projects
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
                        <span id="modalProjectTitle">Project Details</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="projectModalContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-purple" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading project details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="editProjectBtn" class="btn btn-purple" style="display: none;">
                        <i class="fas fa-edit me-1"></i> Edit Project
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/list_project.js"></script>
    <script src="../../assets/js/layout_user.js"></script>

    <?php include $basePath . 'layout_footer.php'; ?>
</div>
</body>
</html>