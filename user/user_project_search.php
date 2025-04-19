<?php
include "../Login/Login/db.php";
error_reporting(E_ERROR);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

function isProjectBookmarked($project_id, $user_id, $conn) {
    $sql = "SELECT * FROM user_bookmarks WHERE project_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function toggleBookmark($project_id, $user_id, $conn) {
    try {
        if (isProjectBookmarked($project_id, $user_id, $conn)) {
            // Remove bookmark
            $sql = "DELETE FROM user_bookmarks WHERE project_id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $project_id, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            return ["status" => "removed", "message" => "Bookmark removed"];
        } else {
            // Add bookmark
            $sql = "INSERT INTO user_bookmarks (project_id, user_id, bookmarked_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ii", $project_id, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            return ["status" => "added", "message" => "Project bookmarked"];
        }
    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

// Handle bookmark toggle AJAX request
if (isset($_POST['action']) && $_POST['action'] == 'toggle_bookmark' && isset($_POST['project_id'])) {
    // Make sure user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "You must be logged in to bookmark projects"]);
        exit;
    }

    // Enable more detailed error reporting just for this section
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    try {
        $project_id = intval($_POST['project_id']);
        $user_id = $_SESSION['user_id'];

        $result = toggleBookmark($project_id, $user_id, $conn);
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
function getBookmarkCount($project_id, $conn) {
    $sql = "SELECT COUNT(*) as count FROM user_bookmarks WHERE project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Initialize filter parameters
$filter_conditions = [];
$filter_params = [];
$param_types = "";

// Build the query for projects with filtering and sorting
$sql = "SELECT admin_approved_projects.*, 
        (SELECT COUNT(*) FROM user_bookmarks WHERE project_id = admin_approved_projects.id) AS bookmark_count
        FROM admin_approved_projects";

// Handle search filtering
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $filter_conditions[] = "(project_name LIKE ? OR description LIKE ?)";
    $filter_params[] = "%$search%";
    $filter_params[] = "%$search%";
    $param_types .= "ss"; // Add two string types
}

// Handle type filtering
if (isset($_GET['type']) && $_GET['type'] != 'all') {
    $project_type = $_GET['type'];
    $filter_conditions[] = "project_type = ?";
    $filter_params[] = $project_type;
    $param_types .= "s"; // Add string type
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

// Only bind parameters if we have any
if (!empty($filter_params)) {
    $stmt->bind_param($param_types, ...$filter_params);
}

$stmt->execute();
$result = $stmt->get_result();

// Check if we're filtering by type
$selected_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Get search term if any
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Helper functions for query parameters
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
// After including db.php
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
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

            .bookmark-btn {
                background: none;
                border: none;
                padding: 0.25rem 0.5rem;
                transition: all 0.2s ease;
            }

            .bookmark-btn:hover {
                color: var(--primary-color);
            }

            .bookmark-btn.bookmarked {
                color: var(--primary-color);
            }

            .bookmark-count {
                font-size: 0.8rem;
                margin-left: 0.25rem;
            }

            /* Bookmark animation */
            @keyframes bookmark-pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }

            .bookmark-animate {
                animation: bookmark-pulse 0.3s ease-in-out;
            }
        </style>
    </head>

<body>
<div class="main-content">
    <!-- Page header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back
        </a>

        <h2 class="section-title mb-0">
            <i class="bi bi-check-circle-fill"></i>
            Approved Projects
        </h2>

        <a href="./forms/new_project_add.php" class="btn btn-primary">
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
    <div id="alertContainer"></div>

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
                    </div>
                </div>
            </div>

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


            </div>
        </form>
    </div>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Projects Container -->
<?php if ($result->num_rows > 0): ?>
    <div class="project-container">
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="project-item">
            <div class="project-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($row['project_name']); ?></h5>
                        <button class="btn btn-sm ms-2 bookmark-btn <?php echo (isset($_SESSION['user_id']) && isProjectBookmarked($row['id'], $_SESSION['user_id'], $conn)) ? 'bookmarked' : ''; ?>"
                                data-project-id="<?php echo $row['id']; ?>"
                                data-bs-toggle="tooltip"
                                title="<?php echo (isset($_SESSION['user_id']) && isProjectBookmarked($row['id'], $_SESSION['user_id'], $conn)) ? 'Remove Bookmark' : 'Bookmark Project'; ?>">
                            <i class="bi <?php echo (isset($_SESSION['user_id']) && isProjectBookmarked($row['id'], $_SESSION['user_id'], $conn)) ? 'bi-bookmark-fill text-primary' : 'bi-bookmark'; ?>"></i>
                            <span class="bookmark-count"><?php echo getBookmarkCount($row['id'], $conn); ?></span>
                        </button>
                    </div>
                    <div class="mt-2">
                        <span class="category-pill"><?php echo htmlspecialchars($row['project_type']); ?></span>
                        <span class="badge badge-approved">Approved</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="description-container">
                        <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                    </div>
                    <button class="show-more-btn" data-bs-toggle="modal" data-bs-target="#descriptionModal<?php echo $row['id']; ?>">
                        Read more
                    </button>

                    <div class="project-detail mt-3">
                        <strong>Submitted by</strong>
                        <p><?php echo htmlspecialchars($row['submitter_name']); ?></p>
                    </div>

                    <div class="project-detail">
                        <strong>Submission Date</strong>
                        <p><?php echo date('M d, Y', strtotime($row['submission_date'])); ?></p>
                    </div>

                    <div class="mt-3">
                        <a href="user_project_search.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-eye me-2"></i>View Details
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description Modal -->
    <div class="modal fade" id="descriptionModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="descriptionModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="descriptionModalLabel<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['project_name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6>Project Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="user_project_search.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo update_query_params(['page' => $page - 1]); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo update_query_params(['page' => $i]); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo update_query_params(['page' => $page + 1]); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <!-- Empty state when no projects are found -->
    <div class="empty-projects">
        <i class="bi bi-clipboard-x"></i>
        <h3>No projects found</h3>
        <p>There are no projects that match your current filters.</p>
        <a href="user_project_search.php" class="btn btn-primary">Clear Filters</a>
    </div>
<?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Auto-submit form when filters change
        $('#projectTypeFilter, #sortByFilter').change(function() {
            $('#filterForm').submit();
        });

        // Show loading spinner when form is submitted
        $('#filterForm').submit(function() {
            $('#loadingSpinner').show();
        });

        // Handle bookmark buttons
        $('.bookmark-btn').click(function() {
            var button = $(this);
            var projectId = button.data('project-id');

            // Check if user is logged in
            <?php if (!isset($_SESSION['user_id'])): ?>
            // Create toast for login required
            showToast('You must be logged in to bookmark projects', 'warning');
            return false;
            <?php endif; ?>

            // Add animation
            button.addClass('bookmark-animate');
            setTimeout(function() {
                button.removeClass('bookmark-animate');
            }, 300);

            // Toggle bookmark via AJAX
            $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                type: 'POST',
                data: {
                    action: 'toggle_bookmark',
                    project_id: projectId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'added') {
                        button.addClass('bookmarked');
                        button.find('i').removeClass('bi-bookmark').addClass('bi-bookmark-fill text-primary');
                        button.attr('title', 'Remove Bookmark');
                        // Update count
                        var count = parseInt(button.find('.bookmark-count').text()) + 1;
                        button.find('.bookmark-count').text(count);
                        showToast('Project bookmarked successfully', 'success');
                    } else if (response.status === 'removed') {
                        button.removeClass('bookmarked');
                        button.find('i').removeClass('bi-bookmark-fill text-primary').addClass('bi-bookmark');
                        button.attr('title', 'Bookmark Project');
                        // Update count
                        var count = parseInt(button.find('.bookmark-count').text()) - 1;
                        button.find('.bookmark-count').text(count);
                        showToast('Bookmark removed', 'info');
                    } else {
                        showToast('An error occurred', 'danger');
                    }

                    // Refresh tooltips
                    var tooltip = bootstrap.Tooltip.getInstance(button[0]);
                    if (tooltip) {
                        tooltip.dispose();
                    }
                    new bootstrap.Tooltip(button[0]);
                },
                error: function() {
                    showToast('An error occurred while processing your request', 'danger');
                }
            });
        });

        // Function to show toast messages
        function showToast(message, type) {
            var toastId = 'toast-' + Date.now();
            var toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            $('.toast-container').append(toastHtml);
            var toastElement = document.getElementById(toastId);
            var toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 3000
            });
            toast.show();

            // Remove toast from DOM after it's hidden
            $(toastElement).on('hidden.bs.toast', function() {
                $(this).remove();
            });
        }
    });
</script>
</body>
</html>