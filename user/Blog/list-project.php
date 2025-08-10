<?php
$basePath = '../';

// Check if this is an AJAX request first
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // Handle AJAX request for lazy loading
    handleAjaxRequest();
    exit;
}

include $basePath . 'layout.php';

// AJAX handler function
function handleAjaxRequest() {
    header('Content-Type: application/json');

    try {
        $conn = createDBConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Get parameters
        $filter_type = isset($_GET['type']) ? $_GET['type'] : '';
        $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
        $filter_priority = isset($_GET['priority']) ? $_GET['priority'] : '';
        $search_term = isset($_GET['search']) ? $_GET['search'] : '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page = 6;
        $offset = ($page - 1) * $per_page;

        // Build query
        $where_conditions = ["1=1"];
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

        // Get projects
        $sql = "SELECT * FROM blog WHERE " . $where_clause . " ORDER BY 
                CASE priority1 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END,
                submission_datetime DESC 
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
        foreach ($projects as $project): ?>
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
                    <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-purple btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
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
    <style>
        :root {
            --primary-purple: #8b5cf6;
            --secondary-purple: #a78bfa;
            --dark-purple: #6d28d9;
            --light-purple: #ede9fe;
            --purple-gradient: linear-gradient(135deg, #8b5cf6, #a78bfa);
            --purple-gradient-hover: linear-gradient(135deg, #7c3aed, #8b5cf6);
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-bg: #fafafa;
            --card-shadow: 0 10px 25px rgba(139, 92, 246, 0.1);
            --card-hover-shadow: 0 20px 40px rgba(139, 92, 246, 0.2);
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, var(--light-purple) 100%);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin-left: var(--sidebar-width, 280px);
        }

        .main-content {
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            background: var(--purple-gradient);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(139, 92, 246, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--purple-gradient);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: var(--light-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary-purple);
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-purple);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-weight: 500;
        }

        .filters-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            border: 1px solid rgba(139, 92, 246, 0.1);
        }

        .search-input {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            outline: none;
        }

        .filter-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            border-color: var(--primary-purple);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
            outline: none;
        }

        .btn-purple {
            background: var(--purple-gradient);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-purple:hover {
            background: var(--purple-gradient-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
            color: white;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .project-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(139, 92, 246, 0.1);
            position: relative;
            overflow: hidden;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .project-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-purple);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .project-id {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .priority-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .priority-high {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .priority-medium {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .priority-low {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .meta-tag {
            background: var(--light-purple);
            color: var(--dark-purple);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-in_progress {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info-color);
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .project-description {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .project-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .btn-outline-purple {
            border: 2px solid var(--primary-purple);
            color: var(--primary-purple);
            background: transparent;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-purple:hover {
            background: var(--primary-purple);
            color: white;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--primary-purple);
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .project-date {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 1rem;
        }

        /* Lazy loading styles */
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            color: var(--primary-purple);
        }

        .loading-text {
            margin-left: 1rem;
            font-weight: 500;
        }

        .load-more-btn {
            display: block;
            margin: 2rem auto;
            background: var(--purple-gradient);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .load-more-btn:hover {
            background: var(--purple-gradient-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
        }

        .load-more-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Mobile responsive */
        @media (max-width: 1024px) {
            body {
                margin-left: 0;
            }

            .main-content {
                padding: 1rem;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .project-card {
                padding: 1rem;
            }
        }

        .text-purple {
            color: var(--primary-purple) !important;
        }
    </style>
</head>

<body>
<div class="main-content">
    <?php
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

        // Get statistics
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

        // Build filtered query
        $where_conditions = ["1=1"];
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

        // Get total count for pagination
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

        // Get projects with pagination
        $sql = "SELECT * FROM blog WHERE " . $where_clause . " ORDER BY 
                CASE priority1 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END,
                submission_datetime DESC 
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
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
        error_log("Projects page error: " . $e->getMessage());
    }
    ?>

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-project-diagram me-3"></i>
            All Projects
        </h1>
        <p class="page-subtitle">Discover and explore innovative ideas from our community</p>
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
                <p>Try adjusting your search criteria or explore all projects.</p>
                <a href="?" class="btn btn-purple mt-3">
                    <i class="fas fa-refresh me-2"></i>View All Projects
                </a>
            </div>
        <?php else: ?>
            <div class="projects-grid" id="projectsGrid">
                <?php foreach ($projects as $project): ?>
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
                            <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-purple btn-sm">
                                <i class="fas fa-edit me-1"></i>Edit
                            </a>
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
</div>

<!-- Project Detail Modals -->
<?php foreach ($projects as $project): ?>
    <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="projectModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--purple-gradient); color: white;">
                    <h5 class="modal-title" id="projectModalLabel<?php echo $project['id']; ?>">
                        <i class="fas fa-project-diagram me-2"></i>
                        <?php echo htmlspecialchars($project['project_name']); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-purple fw-bold">Project Details</h6>
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($project['er_number']); ?></p>
                            <p><strong>Type:</strong> <?php echo ucfirst($project['project_type']); ?></p>
                            <p><strong>Classification:</strong> <?php echo ucfirst(str_replace('_', ' ', $project['classification'])); ?></p>
                            <p><strong>Priority:</strong>
                                <span class="badge <?php echo getPriorityClass($project['priority1']); ?>">
                                    <?php echo ucfirst($project['priority1']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-purple fw-bold">Status & Dates</h6>
                            <p><strong>Status:</strong>
                                <span class="badge <?php echo getStatusClass($project['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                </span>
                            </p>
                            <p><strong>Submitted:</strong> <?php echo formatDate($project['submission_datetime']); ?></p>
                            <p><strong>Assigned To:</strong>
                                <?php echo htmlspecialchars(!empty($project['assigned_to']) ? $project['assigned_to'] : 'Not Assigned'); ?>
                            </p>
                            <p><strong>Completion Date:</strong>
                                <?php echo formatDate($project['completion_date']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-purple fw-bold">Description</h6>
                        <div class="p-3 rounded" style="background-color: var(--light-purple);">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                        </div>
                    </div>

                    <?php if ($project['status'] == 'in_progress'): ?>
                        <div class="mb-4">
                            <h6 class="text-purple fw-bold">Progress</h6>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: 65%; background: var(--purple-gradient);"
                                     aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">65% Complete</small>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-purple">
                        <i class="fas fa-edit me-1"></i> Edit Project
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lazy Loading Implementation
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const projectsGrid = document.getElementById('projectsGrid');

        // Load more projects function
        function loadMoreProjects(page) {
            if (loadMoreBtn) {
                loadMoreBtn.style.display = 'none';
            }
            loadingSpinner.style.display = 'flex';

            // Get current filter parameters
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('page', page);
            urlParams.set('ajax', '1');

            // Use the current page URL for the request
            const baseUrl = window.location.pathname;

            fetch(baseUrl + '?' + urlParams.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.html) {
                        // Create temporary container
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.html;

                        // Add new cards to grid with animation
                        const newCards = tempDiv.querySelectorAll('.project-card');
                        newCards.forEach((card, index) => {
                            card.style.opacity = '0';
                            card.style.transform = 'translateY(20px)';
                            projectsGrid.appendChild(card);

                            // Animate in
                            setTimeout(() => {
                                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                                card.style.opacity = '1';
                                card.style.transform = 'translateY(0)';
                            }, index * 100);
                        });

                        // Set up event listeners for new view details buttons
                        setupViewDetailsButtons();

                        // Update load more button
                        if (data.hasMore) {
                            loadMoreBtn.setAttribute('data-page', data.nextPage);
                            loadMoreBtn.style.display = 'block';
                        } else {
                            if (loadMoreBtn) {
                                loadMoreBtn.style.display = 'none';
                            }
                        }

                        // Update pagination info
                        const paginationInfo = document.querySelector('.text-center.mt-3.text-muted small');
                        if (paginationInfo && data.paginationInfo) {
                            paginationInfo.innerHTML = data.paginationInfo;
                        }
                    } else {
                        throw new Error(data.message || 'Failed to load projects');
                    }
                })
                .catch(error => {
                    console.error('Error loading projects:', error);
                    alert('Failed to load more projects: ' + error.message);
                    if (loadMoreBtn) {
                        loadMoreBtn.style.display = 'block';
                    }
                })
                .finally(() => {
                    loadingSpinner.style.display = 'none';
                });
        }


        // Setup view details buttons for dynamically loaded content
        function setupViewDetailsButtons() {
            const viewBtns = document.querySelectorAll('.view-details-btn');
            viewBtns.forEach(btn => {
                if (!btn.hasAttribute('data-listener-added')) {
                    btn.setAttribute('data-listener-added', 'true');
                    btn.addEventListener('click', function() {
                        const projectId = this.getAttribute('data-project-id');
                        // For now, just show an alert with project ID
                        // You can implement modal functionality here
                        alert('View details for project ID: ' + projectId);
                        // Or redirect to a details page
                        // window.location.href = 'project_details.php?id=' + projectId;
                    });
                }
            });
        }

        // Load more button click handler
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                const nextPage = parseInt(this.getAttribute('data-page'));
                loadMoreProjects(nextPage);
            });
        }

        // Setup initial view details buttons
        setupViewDetailsButtons();

        // Infinite scroll (optional - uncomment to enable)
        /*
        let isLoading = false;
        window.addEventListener('scroll', function() {
            if (isLoading) return;

            const scrollTop = document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight;
            const clientHeight = document.documentElement.clientHeight;

            if (scrollTop + clientHeight >= scrollHeight - 1000) {
                if (loadMoreBtn && loadMoreBtn.style.display !== 'none') {
                    isLoading = true;
                    const nextPage = parseInt(loadMoreBtn.getAttribute('data-page'));
                    loadMoreProjects(nextPage);
                    setTimeout(() => { isLoading = false; }, 1000);
                }
            }
        });
        */

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Filter form handling
        const filterForm = document.getElementById('filterForm');
        const filterButton = filterForm.querySelector('button[type="submit"]');

        if (filterForm && filterButton) {
            filterForm.addEventListener('submit', function() {
                filterButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                filterButton.disabled = true;
            });
        }

        // Project card animations on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe initial project cards
        const projectCards = document.querySelectorAll('.project-card');
        projectCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });

        // Search input live feedback
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                if (searchTerm.length > 0) {
                    this.style.borderColor = 'var(--primary-purple)';
                } else {
                    this.style.borderColor = '#e2e8f0';
                }
            });
        }

        // Project ID click to copy functionality
        const projectIds = document.querySelectorAll('.project-id');
        projectIds.forEach(id => {
            id.style.cursor = 'pointer';
            id.title = 'Click to copy ID';

            id.addEventListener('click', function() {
                const idText = this.textContent.replace('ID: ', '');
                navigator.clipboard.writeText(idText).then(() => {
                    const originalText = this.textContent;
                    this.textContent = 'ID: Copied!';
                    this.style.color = 'var(--success-color)';

                    setTimeout(() => {
                        this.textContent = originalText;
                        this.style.color = '';
                    }, 2000);
                }).catch(() => {
                    // Fallback for browsers that don't support clipboard API
                    const textArea = document.createElement('textarea');
                    textArea.value = idText;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);

                    const originalText = this.textContent;
                    this.textContent = 'ID: Copied!';
                    this.style.color = 'var(--success-color)';

                    setTimeout(() => {
                        this.textContent = originalText;
                        this.style.color = '';
                    }, 2000);
                });
            });
        });

        // Keyboard navigation for modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    const modal = bootstrap.Modal.getInstance(openModal);
                    if (modal) modal.hide();
                }
            }
        });

        // Auto-hide alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const alertInstance = new bootstrap.Alert(alert);
                alertInstance.close();
            }, 5000);
        });
    });
</script>

<?php include $basePath . 'layout_footer.php'; ?>
</body>
</html>