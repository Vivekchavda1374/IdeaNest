<?php
include "../Login/Login/db.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Process bookmark toggle
$alert_message = '';
$alert_type = '';

// Process bookmark toggle with improved error handling
if (isset($_POST['toggle_bookmark'])) {
    $project_id = $_POST['project_id'];
    $session_id = session_id();

    // Debug - uncomment to check values
    // echo "Project ID: " . $project_id . " Session ID: " . $session_id;

    try {
        // Check if bookmark already exists for this project
        $check_sql = "SELECT * FROM bookmark WHERE project_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $check_stmt->bind_param("is", $project_id, $session_id);
        if (!$check_stmt->execute()) {
            throw new Exception("Execute failed: " . $check_stmt->error);
        }

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            // Bookmark exists, so remove it
            $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            if (!$delete_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $delete_stmt->bind_param("is", $project_id, $session_id);

            if ($delete_stmt->execute()) {
                $alert_message = 'Project removed from bookmarks!';
                $alert_type = 'info';
            } else {
                throw new Exception("Delete failed: " . $delete_stmt->error);
            }
            $delete_stmt->close();
        } else {
            // Add new bookmark
            $idea_id = 0; // Default value for idea_id

            $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            if (!$insert_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $insert_stmt->bind_param("isi", $project_id, $session_id, $idea_id);

            if ($insert_stmt->execute()) {
                $alert_message = 'Project added to bookmarks!';
                $alert_type = 'success';
            } else {
                throw new Exception("Insert failed: " . $insert_stmt->error);
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    } catch (Exception $e) {
        $alert_message = 'Error: ' . $e->getMessage();
        $alert_type = 'danger';
    }

    // For AJAX requests, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['message' => $alert_message, 'type' => $alert_type]);
        exit;
    }
}
// Get projects with pagination
$items_per_page = 8; // Number of projects per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

// Get total projects count for pagination
$count_sql = "SELECT COUNT(*) as total FROM admin_approved_projects";
$count_result = $conn->query($count_sql);
$total_projects = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_projects / $items_per_page);

// Get project type counts for filter stats
$type_counts = [];
$type_sql = "SELECT project_type, COUNT(*) as count FROM admin_approved_projects GROUP BY project_type";
$type_result = $conn->query($type_sql);
if ($type_result) {
    while ($type_row = $type_result->fetch_assoc()) {
        $type_counts[$type_row['project_type']] = $type_row['count'];
    }
}

// Get recent projects count (last 7 days)
$recent_sql = "SELECT COUNT(*) as recent FROM admin_approved_projects WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$recent_result = $conn->query($recent_sql);
$recent_count = $recent_result->fetch_assoc()['recent'];

// Get bookmarked projects count for current user
$bookmarked_sql = "SELECT COUNT(*) as bookmarked FROM bookmark WHERE user_id = ?";
$bookmarked_stmt = $conn->prepare($bookmarked_sql);
$bookmarked_stmt->bind_param("s", $session_id);
$bookmarked_stmt->execute();
$bookmarked_result = $bookmarked_stmt->get_result();
$bookmarked_count = $bookmarked_result->fetch_assoc()['bookmarked'];
$bookmarked_stmt->close();

// Build the query for projects with filtering and sorting
$sql = "SELECT admin_approved_projects.*, 
        CASE WHEN bookmark.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
        FROM admin_approved_projects 
        LEFT JOIN bookmark ON admin_approved_projects.id = bookmark.project_id AND bookmark.user_id = ?";

// Apply filters if provided
$filter_conditions = [];
$filter_params = [$session_id]; // Start with session_id for bookmark join
$param_types = "s"; // Start with string type for session_id

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $filter_conditions[] = "(project_name LIKE ? OR description LIKE ?)";
    $filter_params[] = "%$search%";
    $filter_params[] = "%$search%";
    $param_types .= "ss"; // Add two string types
}

if (isset($_GET['type']) && $_GET['type'] != 'all') {
    $project_type = $_GET['type'];
    $filter_conditions[] = "project_type = ?";
    $filter_params[] = $project_type;
    $param_types .= "s"; // Add string type
}

if (isset($_GET['bookmarked']) && $_GET['bookmarked'] == '1') {
    $filter_conditions[] = "bookmark.project_id IS NOT NULL";
}

// Add WHERE clause if filters are applied
if (!empty($filter_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $filter_conditions);
}

// Apply sorting
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
switch ($sort_order) {
    case 'oldest':
        $sql .= " ORDER BY admin_approved_projects.submission_date ASC";
        break;
    case 'a-z':
        $sql .= " ORDER BY admin_approved_projects.project_name ASC";
        break;
    case 'z-a':
        $sql .= " ORDER BY admin_approved_projects.project_name DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY admin_approved_projects.submission_date DESC";
        break;
}

// Add pagination limit
$sql .= " LIMIT ?, ?";
$filter_params[] = $offset;
$filter_params[] = $items_per_page;
$param_types .= "ii"; // Add two integer types

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$filter_params);
$stmt->execute();
$result = $stmt->get_result();

// Check if we should show bookmarked only
$show_bookmarked = isset($_GET['bookmarked']) && $_GET['bookmarked'] == '1';

// Check if we're filtering by type
$selected_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Get search term if any
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Projects | IdeaNest</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <!-- Add Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary-color: #4361ee;
                --primary-light: rgba(67, 97, 238, 0.1);
                --primary-dark: #fbfbff;
                --secondary-color: #f50057;
                --success-color: #10b981;
                --success-light: rgba(16, 185, 129, 0.1);
                --warning-color: #f59e0b;
                --warning-light: rgba(245, 158, 11, 0.1);
                --danger-color: #ef4444;
                --danger-light: rgba(239, 68, 68, 0.1);
                --info-color: #0ea5e9;
                --info-light: rgba(14, 165, 233, 0.1);
                --light-bg: #f8fafc;
                --dark-text: #1e293b;
                --light-text: #64748b;
                --gray-100: #f1f5f9;
                --gray-200: #e2e8f0;
                --gray-300: #cbd5e1;
                --gray-400: #94a3b8;
                --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
                --card-hover-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
                --transition: all 0.3s ease;
                --border-radius: 0.75rem;
                --border-radius-sm: 0.5rem;
                --font-primary: 'Inter', sans-serif;
            }

            body {
                background-color: var(--light-bg);
                font-family: var(--font-primary);
                color: var(--dark-text);
                line-height: 1.6;
            }

            /* Main content styles */
            .main-content {
                padding: 1.5rem;
            }

            .section-title {
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                color: var(--dark-text);
                display: flex;
                align-items: center;
            }

            .section-title i {
                color: var(--primary-color);
                margin-right: 0.75rem;
                font-size: 1.25rem;
            }

            /* Project stats */
            .project-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            .stat-item {
                background: white;
                border-radius: var(--border-radius);
                padding: 1rem 1.5rem;
                flex: 1;
                min-width: 150px;
                box-shadow: var(--card-shadow);
                display: flex;
                flex-direction: column;
                align-items: center;
                transition: var(--transition);
            }

            .stat-item:hover {
                transform: translateY(-3px);
                box-shadow: var(--card-hover-shadow);
            }

            .stat-value {
                font-size: 1.75rem;
                font-weight: 700;
                color: var(--primary-color);
                margin-bottom: 0.25rem;
            }

            .stat-label {
                color: var(--light-text);
                font-size: 0.9rem;
                font-weight: 500;
            }

            /* Search and filter container */
            .search-filter-container {
                background: white;
                border-radius: var(--border-radius);
                padding: 1.25rem;
                margin-bottom: 1.5rem;
                box-shadow: var(--card-shadow);
            }

            .search-input {
                border-radius: var(--border-radius-sm);
                padding: 0.65rem 1rem;
            }

            .search-filter-container .form-select {
                border-radius: var(--border-radius-sm);
                padding: 0.65rem 1rem;
            }

            /* Project cards */
            .project-card {
                background: white;
                border-radius: var(--border-radius);
                overflow: hidden;
                box-shadow: var(--card-shadow);
                transition: var(--transition);
                height: 100%;
                margin-bottom: 1.5rem;
                border: 1px solid var(--gray-200);
            }

            .project-card:hover {
                transform: translateY(-5px);
                box-shadow: var(--card-hover-shadow);
            }

            .card-header {
                background-color: white;
                padding: 1.25rem;
                border-bottom: 1px solid var(--gray-200);
            }

            .card-body {
                padding: 1.25rem;
            }

            .description-container {
                max-height: 100px;
                overflow: hidden;
                position: relative;
            }

            .description-container p {
                margin-bottom: 0;
            }

            .description-container:after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 30px;
                background: linear-gradient(transparent, white);
            }

            .project-detail {
                margin-bottom: 1rem;
            }

            .project-detail strong {
                display: block;
                color: var(--light-text);
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
            }

            .project-detail p {
                margin-bottom: 0;
                font-weight: 500;
            }

            /* Badges and pills */
            .badge {
                font-weight: 600;
                padding: 0.4rem 0.85rem;
                border-radius: 2rem;
            }

            .badge-approved {
                background-color: var(--success-light);
                color: var(--success-color);
            }

            .category-pill {
                display: inline-block;
                padding: 0.3rem 0.8rem;
                background-color: var(--gray-100);
                color: var(--light-text);
                border-radius: 1rem;
                margin-right: 0.5rem;
                margin-bottom: 0.5rem;
                font-size: 0.875rem;
                font-weight: 500;
                transition: var(--transition);
            }

            .category-pill:hover {
                background-color: var(--primary-light);
                color: var(--primary-color);
            }

            /* Bookmark button */
            .bookmark-btn {
                background: none;
                border: none;
                color: var(--gray-400);
                font-size: 1.25rem;
                cursor: pointer;
                transition: var(--transition);
                margin-left: 0.75rem;
                padding: 0.25rem;
            }

            .bookmark-btn:hover {
                color: var(--warning-color);
                transform: scale(1.1);
            }

            .bookmark-btn.active {
                color: var(--warning-color);
            }

            /* File link styles */
            .files-container {
                border: 1px dashed var(--gray-300);
                padding: 1rem;
                border-radius: var(--border-radius-sm);
                background-color: var(--gray-100);
            }

            .file-link {
                display: flex;
                align-items: center;
                color: var(--primary-color);
                text-decoration: none;
                font-weight: 500;
                transition: var(--transition);
            }

            .file-link i {
                margin-right: 0.5rem;
                font-size: 1.25rem;
            }

            .file-link:hover {
                color: var(--primary-dark);
                text-decoration: underline;
            }

            /* Empty state */
            .empty-projects {
                text-align: center;
                padding: 3rem 1.5rem;
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--card-shadow);
            }

            .empty-projects i {
                font-size: 3rem;
                color: var(--gray-300);
                margin-bottom: 1.5rem;
            }

            .empty-projects h3 {
                font-weight: 600;
                margin-bottom: 0.75rem;
            }

            .empty-projects .btn {
                margin-top: 1rem;
                padding: 0.6rem 1.5rem;
            }

            /* Pagination */
            .pagination {
                gap: 0.25rem;
            }

            .pagination .page-link {
                color: var(--primary-color);
                border-radius: var(--border-radius-sm);
                border: 1px solid var(--gray-200);
                padding: 0.5rem 0.9rem;
                transition: var(--transition);
            }

            .pagination .page-link:hover {
                background-color: var(--primary-light);
                border-color: var(--primary-light);
                color: var(--primary-color);
            }

            .pagination .page-item.active .page-link {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
            }

            .pagination .page-item.disabled .page-link {
                color: var(--gray-400);
                pointer-events: none;
            }

            /* Alert styles */
            .alert {
                border-radius: var(--border-radius);
                padding: 1rem 1.25rem;
                margin-bottom: 1.5rem;
                border: none;
                box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
            }

            .alert-success {
                background-color: var(--success-light);
                color: var(--success-color);
            }

            .alert-info {
                background-color: var(--info-light);
                color: var(--info-color);
            }

            .alert-danger {
                background-color: var(--danger-light);
                color: var(--danger-color);
            }

            /* Responsive adjustments */
            @media (max-width: 991.98px) {
                .project-stats {
                    flex-direction: row;
                    flex-wrap: wrap;
                }

                .stat-item {
                    min-width: calc(33.33% - 1rem);
                }
            }

            @media (max-width: 767.98px) {
                .search-filter-container .row {
                    flex-direction: column;
                    gap: 1rem;
                }

                .search-filter-container .form-select {
                    width: 100%;
                    max-width: 100%;
                    margin-right: 0 !important;
                }

                .stat-item {
                    min-width: calc(50% - 0.5rem);
                }
            }

            @media (max-width: 575.98px) {
                .project-stats {
                    flex-direction: column;
                }

                .stat-item {
                    width: 100%;
                }

                .main-content {
                    padding: 1rem;
                }
            }

            /* Filter badges */
            .filter-badge {
                display: inline-flex;
                align-items: center;
                background-color: var(--primary-light);
                color: var(--primary-color);
                border-radius: 2rem;
                padding: 0.35rem 0.85rem;
                margin-right: 0.5rem;
                margin-bottom: 0.5rem;
                font-size: 0.875rem;
                font-weight: 500;
            }

            .filter-badge .close-icon {
                margin-left: 0.5rem;
                cursor: pointer;
                font-size: 1rem;
            }

            .active-filters {
                margin-bottom: 1rem;
            }

            /* Toast container */
            .toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
            }

            /* Animation for filtering */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .project-item {
                animation: fadeIn 0.3s ease forwards;
            }

            /* Equal height for project cards */
            .project-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 1.5rem;
            }

            /* Show more button for description */
            .show-more-btn {
                color: var(--primary-color);
                background: none;
                border: none;
                padding: 0;
                font-size: 0.875rem;
                font-weight: 500;
                margin-top: 0.5rem;
                cursor: pointer;
                display: block;
            }

            .show-more-btn:hover {
                text-decoration: underline;
            }

            /* Loading indicator */
            .loading-spinner {
                display: none;
                text-align: center;
                padding: 2rem 0;
            }

            .loading-spinner .spinner-border {
                color: var(--primary-color);
                width: 3rem;
                height: 3rem;
            }
        </style>
    </head>

    <body>
    <div class="main-content">
        <!-- Page header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">
                <i class="bi bi-check-circle-fill"></i>
                Approved Projects
            </h2>
            <a href="submit_project.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Submit New Project
            </a>
        </div>

        <!-- Project Stats -->
        <div class="project-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_projects; ?></div>
                <div class="stat-label">Total Projects</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $bookmarked_count; ?></div>
                <div class="stat-label">Bookmarked</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $recent_count; ?></div>
                <div class="stat-label">Recent (7d)</div>
            </div>
            <?php foreach ($type_counts as $type => $count): ?>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $count; ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars($type); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Alert Container -->
        <?php if (!empty($alert_message)): ?>
            <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <?php if ($alert_type == 'success'): ?>
                        <i class="bi bi-check-circle-fill me-2"></i>
                    <?php elseif ($alert_type == 'info'): ?>
                        <i class="bi bi-info-circle-fill me-2"></i>
                    <?php elseif ($alert_type == 'danger'): ?>
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php endif; ?>
                    <strong><?php echo $alert_message; ?></strong>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Toast Container -->
        <div class="toast-container"></div>

        <!-- Search and Filter Section -->
        <div class="search-filter-container">
            <form action="" method="get" id="filterForm">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control search-input border-start-0"
                                   name="search" id="searchProjects" value="<?php echo htmlspecialchars($search_term); ?>"
                                   placeholder="Search projects by name or description...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6 col-sm-4 mb-3 mb-sm-0">
                                <select class="form-select" name="type" id="projectTypeFilter">
                                    <option value="all" <?php echo ($selected_type == 'all') ? 'selected' : ''; ?>>All Types</option>
                                    <?php foreach ($type_counts as $type => $count): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($selected_type == $type) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-sm-4 mb-3 mb-sm-0">
                                <select class="form-select" name="sort" id="sortByFilter">
                                    <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="oldest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="a-z" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'a-z') ? 'selected' : ''; ?>>A-Z</option>
                                    <option value="z-a" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'z-a') ? 'selected' : ''; ?>>Z-A</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="bookmarked" value="1"
                                           id="bookmarkedFilter" <?php echo $show_bookmarked ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="bookmarkedFilter">Bookmarked Only</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active filters section -->
                <?php if (!empty($search_term) || $selected_type != 'all' || $show_bookmarked): ?>
                    <div class="active-filters mt-3">
                        <?php if (!empty($search_term)): ?>
                            <div class="filter-badge">
                                <span>Search: <?php echo htmlspecialchars($search_term); ?></span>
                                <a href="<?php echo remove_query_param('search'); ?>" class="close-icon text-decoration-none">&times;</a>
                            </div>
                        <?php endif; ?>

                        <?php if ($selected_type != 'all'): ?>
                            <div class="filter-badge">
                                <span>Type: <?php echo htmlspecialchars($selected_type); ?></span>
                                <a href="<?php echo remove_query_param('type'); ?>" class="close-icon text-decoration-none">&times;</a>
                            </div>
                        <?php endif; ?>

                        <?php if ($show_bookmarked): ?>
                            <div class="filter-badge">
                                <span>Bookmarked Only</span>
                                <a href="<?php echo remove_query_param('bookmarked'); ?>" class="close-icon text-decoration-none">&times;</a>
                            </div>
                        <?php endif; ?>

                        <a href="?" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="bi bi-x-circle me-1"></i>Clear All
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Loading spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading projects...</p>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="row" id="projectContainer">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-lg-6 project-item">
                        <div class="project-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold text-truncate" title="<?php echo htmlspecialchars($row["project_name"]); ?>">
                                    <?php echo htmlspecialchars($row["project_name"]); ?>
                                </h5>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-approved">Approved</span>
                                    <form method="post" class="d-inline bookmark-form">
                                        <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                                        <button type="submit" name="toggle_bookmark" class="bookmark-btn <?php echo $row["is_bookmarked"] ? 'active' : ''; ?>">
                                            <i class="bi <?php echo $row["is_bookmarked"] ? 'bi-bookmark-fill' : 'bi-bookmark'; ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="description-container mb-3">
                                    <?php echo htmlspecialchars($row["description"]); ?></p>
                                </div>
                                <button class="show-more-btn" data-bs-toggle="modal" data-bs-target="#projectDetailModal<?php echo $row["id"]; ?>">
                                    <i class="bi bi-three-dots"></i> Show details
                                </button>

                                <div class="row mt-3">
                                    <div class="col-6">
                                        <div class="project-detail">
                                            <strong>Type</strong>
                                            <p><?php echo htmlspecialchars($row["project_type"]); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="project-detail">
                                            <strong>Submitted</strong>
                                            <p><?php echo date("M d, Y", strtotime($row["submission_date"])); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <?php if(!empty($row["file_path"])): ?>
                                    <div class="files-container mt-3">
                                        <a href="download.php?file=<?php echo urlencode($row["file_path"]); ?>" class="file-link">
                                            <i class="bi bi-file-earmark-arrow-down"></i>
                                            Download Project Files
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <a href="project_details.php?id=<?php echo $row["id"]; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye me-1"></i> View Details
                                    </a>
                                    <span class="category-pill">
                                        <?php echo htmlspecialchars($row["classification"]); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Project Detail Modal -->
                        <div class="modal fade" id="projectDetailModal<?php echo $row["id"]; ?>" tabindex="-1" aria-labelledby="projectDetailModalLabel<?php echo $row["id"]; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="projectDetailModalLabel<?php echo $row["id"]; ?>"><?php echo htmlspecialchars($row["project_name"]); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <h6 class="mb-3">Description</h6>
                                        <p><?php echo htmlspecialchars($row["description"]); ?></p>

                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <h6 class="mb-3">Project Details</h6>
                                                <table class="table table-borderless">
                                                    <tr>
                                                        <th>Type:</th>
                                                        <td><?php echo htmlspecialchars($row["project_type"]); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Category:</th>
                                                        <td><?php echo htmlspecialchars($row["classification"]); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Submitted:</th>
                                                        <td><?php echo date("F d, Y", strtotime($row["submission_date"])); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Status:</th>
                                                        <td><span class="badge badge-approved">Approved</span></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <?php if(!empty($row["file_path"])): ?>
                                                    <h6 class="mb-3">Resources</h6>
                                                    <div class="files-container">
                                                        <a href="download.php?file=<?php echo urlencode($row["file_path"]); ?>" class="file-link">
                                                            <i class="bi bi-file-earmark-arrow-down"></i>
                                                            Download Project Files
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <a href="project_details.php?id=<?php echo $row["id"]; ?>" class="btn btn-primary">
                                            <i class="bi bi-eye me-1"></i> View Complete Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Project pagination">
                    <ul class="pagination">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo update_query_params(['page' => $page - 1]); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($start_page + 4, $total_pages);

                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo update_query_params(['page' => 1]); ?>">1</a>
                            </li>
                            <?php if($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo update_query_params(['page' => $i]); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if($end_page < $total_pages): ?>
                            <?php if($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo update_query_params(['page' => $total_pages]); ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>

                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo update_query_params(['page' => $page + 1]); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php else: ?>
            <!-- Empty state when no projects found -->
            <div class="empty-projects">
                <i class="bi bi-folder-x"></i>
                <h3>No projects found</h3>
                <p>
                    <?php if(!empty($search_term) || $selected_type != 'all' || $show_bookmarked): ?>
                        We couldn't find any projects matching your criteria.<br>
                        Try adjusting your filters or search terms.
                    <?php else: ?>
                        There are no approved projects available at the moment.
                    <?php endif; ?>
                </p>
                <?php if(!empty($search_term) || $selected_type != 'all' || $show_bookmarked): ?>
                    <a href="?" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Filters
                    </a>
                <?php else: ?>
                    <a href="submit_project.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Submit a Project
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Helper functions for query parameters -->
    <?php
    function remove_query_param($param) {
        $params = $_GET;
        unset($params[$param]);
        return '?' . http_build_query($params);
    }

    function update_query_params($new_params) {
        $params = $_GET;
        foreach ($new_params as $key => $value) {
            $params[$key] = $value;
        }
        return '?' . http_build_query($params);
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle filter changes
            const filterForm = document.getElementById('filterForm');
            const projectTypeFilter = document.getElementById('projectTypeFilter');
            const sortByFilter = document.getElementById('sortByFilter');
            const bookmarkedFilter = document.getElementById('bookmarkedFilter');
            const searchInput = document.getElementById('searchProjects');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const projectContainer = document.getElementById('projectContainer');

            // Function to show loading spinner
            function showLoading() {
                if (loadingSpinner) {
                    loadingSpinner.style.display = 'block';
                }
                if (projectContainer) {
                    projectContainer.style.opacity = '0.5';
                }
            }

            // Submit form when filters change
            if (projectTypeFilter) {
                projectTypeFilter.addEventListener('change', function() {
                    showLoading();
                    filterForm.submit();
                });
            }

            if (sortByFilter) {
                sortByFilter.addEventListener('change', function() {
                    showLoading();
                    filterForm.submit();
                });
            }

            if (bookmarkedFilter) {
                bookmarkedFilter.addEventListener('change', function() {
                    showLoading();
                    filterForm.submit();
                });
            }

            // Handle search with debounce
            let searchTimeout;
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        showLoading();
                        filterForm.submit();
                    }, 500); // Submit after 500ms of inactivity
                });
            }

            // Handle bookmark toggle with AJAX
            const bookmarkForms = document.querySelectorAll('.bookmark-form');
            bookmarkForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const bookmarkBtn = this.querySelector('.bookmark-btn');

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.text())
                        .then(() => {
                            // Toggle bookmark icon
                            bookmarkBtn.classList.toggle('active');
                            const icon = bookmarkBtn.querySelector('i');
                            if (icon.classList.contains('bi-bookmark')) {
                                icon.classList.replace('bi-bookmark', 'bi-bookmark-fill');
                                showToast('Project added to bookmarks!', 'success');
                            } else {
                                icon.classList.replace('bi-bookmark-fill', 'bi-bookmark');
                                showToast('Project removed from bookmarks!', 'info');
                            }
                        })
                        .catch(error => {
                            showToast('Error updating bookmark status.', 'danger');
                            console.error('Error:', error);
                        });
                });
            });
            // Toast notification function
            function showToast(message, type) {
                const toastContainer = document.querySelector('.toast-container');
                const toastId = 'toast-' + Date.now();

                const toast = document.createElement('div');
                toast.className = `toast align-items-center border-0 bg-${type === 'success' ? 'success' : type === 'info' ? 'info' : 'danger'} text-white`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                toast.setAttribute('id', toastId);

                const toastContent = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi ${type === 'success' ? 'bi-check-circle' : type === 'info' ? 'bi-info-circle' : 'bi-x-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;

                toast.innerHTML = toastContent;
                toastContainer.appendChild(toast);

                const bsToast = new bootstrap.Toast(toast, {
                    autohide: true,
                    delay: 3000
                });

                bsToast.show();

                toast.addEventListener('hidden.bs.toast', function () {
                    toast.remove();
                });
            }
        });
        // Handle bookmark toggle with AJAX
        document.addEventListener('DOMContentLoaded', function() {
            const bookmarkForms = document.querySelectorAll('.bookmark-form');
            bookmarkForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const bookmarkBtn = this.querySelector('.bookmark-btn');

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Toggle bookmark icon
                            bookmarkBtn.classList.toggle('active');
                            const icon = bookmarkBtn.querySelector('i');

                            if (icon.classList.contains('bi-bookmark')) {
                                icon.classList.replace('bi-bookmark', 'bi-bookmark-fill');
                            } else {
                                icon.classList.replace('bi-bookmark-fill', 'bi-bookmark');
                            }

                            // Show toast with the response message
                            showToast(data.message, data.type);
                        })
                        .catch(error => {
                            showToast('Error updating bookmark status.', 'danger');
                            console.error('Error:', error);
                        });
                });
            });
        });
    </script>
    </body>
    </html>
<?php
// Close connection
$stmt->close();
$conn->close();
?>