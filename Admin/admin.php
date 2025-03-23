<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = "";
$projects = [];

// Process form submissions for approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['project_id'])) {
        $project_id = intval($_POST['project_id']);
        $action = $_POST['action'];
        $feedback = isset($_POST['feedback']) ? $conn->real_escape_string($_POST['feedback']) : '';

        if ($action === 'approve') {
            $updateSql = "UPDATE projects SET status = 'approved', feedback = '$feedback', review_date = NOW() WHERE id = $project_id";
            $message = "Project has been successfully approved!";
        } elseif ($action === 'reject') {
            $updateSql = "UPDATE projects SET status = 'rejected', feedback = '$feedback', review_date = NOW() WHERE id = $project_id";
            $message = "Project has been rejected with feedback.";
        }

        if ($conn->query($updateSql) === TRUE) {
            // Success message already set above

            // Get user email to send notification
            $userSql = "SELECT u.email, p.project_name
                        FROM projects p 
                        JOIN users u ON p.user_id = u.id 
                        WHERE p.id = $project_id";
            $userResult = $conn->query($userSql);

            if ($userResult && $userResult->num_rows > 0) {
                $userData = $userResult->fetch_assoc();
                // Email notification could be implemented here
            }
        } else {
            $message = "Error updating project: " . $conn->error;
        }
    }
}

// Fetch pending projects for review
$sql = "SELECT p.*, u.name as user_name, u.email as user_email 
        FROM projects p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'pending'
        ORDER BY p.submission_date DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Get counts for different statuses
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

$count_sql = "SELECT status, COUNT(*) as count FROM projects GROUP BY status";
$count_result = $conn->query($count_sql);

if ($count_result && $count_result->num_rows > 0) {
    while ($row = $count_result->fetch_assoc()) {
        if ($row['status'] === 'pending') {
            $pending_count = $row['count'];
        } elseif ($row['status'] === 'approved') {
            $approved_count = $row['count'];
        } elseif ($row['status'] === 'rejected') {
            $rejected_count = $row['count'];
        }
    }
}

// Fetch recently reviewed projects
$recent_sql = "SELECT 
    p.id, 
    p.project_name, 
    p.project_type, 
    p.classification, 
    p.language,
    p.submission_date,
    p.status,
    u.name as submitted_by
FROM 
    projects p
JOIN 
    users u ON p.user_id = u.id
WHERE 
    p.submission_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND p.language = 'Python'
ORDER BY 
    p.submission_date DESC;";
$recent_result = $conn->query($recent_sql);
$recent_projects = [];

if ($recent_result && $recent_result->num_rows > 0) {
    while ($row = $recent_result->fetch_assoc()) {
        $recent_projects[] = $row;
    }
}

// Get project types for filtering
$types_sql = "SELECT DISTINCT project_type FROM projects ORDER BY project_type";
$types_result = $conn->query($types_sql);
$project_types = [];

if ($types_result && $types_result->num_rows > 0) {
    while ($row = $types_result->fetch_assoc()) {
        if (!empty($row['project_type'])) {
            $project_types[] = $row['project_type'];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest Admin - Project Approval</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --dark-color: #5a5c69;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15);
            z-index: 1;
        }

        .sidebar .sidebar-brand {
            height: 4.375rem;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 800;
            padding: 1.5rem 1rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: .05rem;
        }

        .sidebar hr.sidebar-divider {
            margin: 0 1rem 1rem;
        }

        .sidebar .nav-item {
            position: relative;
        }

        .sidebar .nav-item .nav-link {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.2s ease;
        }

        .sidebar .nav-item .nav-link i {
            margin-right: 0.25rem;
        }

        .sidebar .nav-item .nav-link:hover,
        .sidebar .nav-item .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 0.35rem;
        }

        .topbar {
            height: 4.375rem;
            background-color: #fff;
            box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15);
        }

        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .stat-card {
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .bg-gradient-primary {
            background: linear-gradient(180deg, var(--primary-color) 10%, #224abe 100%);
        }

        .bg-gradient-success {
            background: linear-gradient(180deg, var(--secondary-color) 10%, #169b6b 100%);
        }

        .bg-gradient-warning {
            background: linear-gradient(180deg, var(--warning-color) 10%, #d4a431 100%);
        }

        .bg-gradient-danger {
            background: linear-gradient(180deg, var(--danger-color) 10%, #be2617 100%);
        }

        .project-card {
            transition: all 0.3s ease;
            border-left: 0.25rem solid var(--primary-color);
        }

        .project-card:hover {
            box-shadow: 0 0.5rem 1.5rem 0 rgba(58, 59, 69, 0.15);
        }

        .badge-pending {
            background-color: var(--warning-color);
            color: #fff;
        }

        .badge-approved {
            background-color: var(--secondary-color);
            color: #fff;
        }

        .badge-rejected {
            background-color: var(--danger-color);
            color: #fff;
        }

        .project-details {
            border-left: 3px solid #e3e6f0;
            padding-left: 15px;
            margin-bottom: 15px;
        }

        .project-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .project-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: var(--dark-color);
        }

        .project-files {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 4px;
            background-color: #f8f9fa;
            color: var(--dark-color);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            border: 1px solid #e3e6f0;
        }

        .file-link:hover {
            background-color: #e9ecef;
            color: var(--primary-color);
        }

        .btn-circle {
            border-radius: 100%;
            height: 2.5rem;
            width: 2.5rem;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .dropdown-list-image {
            position: relative;
            height: 2.5rem;
            width: 2.5rem;
        }

        .dropdown-list-image img {
            height: 2.5rem;
            width: 2.5rem;
        }

        .dropdown-list-image .status-indicator {
            background-color: #eaecf4;
            height: 0.75rem;
            width: 0.75rem;
            border-radius: 100%;
            position: absolute;
            bottom: 0;
            right: 0;
            border: .125rem solid #fff;
        }

        /* Filter section styles */
        .filter-section {
            background-color: #f1f4f9;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-badge {
            background-color: #e3e6f0;
            color: var(--dark-color);
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            padding: 0.4rem 0.6rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .filter-badge:hover {
            background-color: var(--primary-color);
            color: white;
            cursor: pointer;
        }

        .filter-badge.active {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>

<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse p-0">
            <div class="position-sticky">
                <div class="sidebar-brand text-white d-flex align-items-center justify-content-center">
                    <i class="bi bi-lightbulb fs-4 me-2"></i>
                    <span>IdeaNest Admin</span>
                </div>
                <hr class="sidebar-divider my-2 bg-light opacity-25">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="projects.php">
                            <i class="bi bi-lightbulb"></i>
                            <span>Projects</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-text"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <hr class="sidebar-divider my-2 bg-light opacity-25">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Topbar -->
            <nav class="navbar navbar-expand topbar mb-4 static-top shadow-sm">
                <button class="btn btn-link d-md-none rounded-circle me-3" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3+</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="alertsDropdown">
                            <h6 class="dropdown-header">Alerts Center</h6>
                            <a class="dropdown-item d-flex align-items-center" href="#">
                                <div class="me-3">
                                    <div class="icon-circle bg-primary text-white p-2">
                                        <i class="bi bi-file-text"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="small text-gray-500">March 22, 2025</div>
                                    <span>New project submissions need review</span>
                                </div>
                            </a>
                            <a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown no-arrow mx-1">
                        <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-envelope fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">7</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="messagesDropdown">
                            <h6 class="dropdown-header">Message Center</h6>
                            <a class="dropdown-item d-flex align-items-center" href="#">
                                <div class="dropdown-list-image me-3">
                                    <img class="rounded-circle" src="https://source.unsplash.com/Mv9hjnEUHR4/60x60" alt="User">
                                </div>
                                <div>
                                    <div class="text-truncate">I need help with my project submission...</div>
                                    <div class="small text-gray-500">Emily Jones · 58m</div>
                                </div>
                            </a>
                            <a class="dropdown-item text-center small text-gray-500" href="#">Read More Messages</a>
                        </div>
                    </li>

                    <div class="topbar-divider d-none d-sm-block"></div>

                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2 d-none d-lg-inline text-gray-600 small">Admin User</span>
                            <img class="img-profile rounded-circle" src="https://source.unsplash.com/QAB-WJcbgJk/60x60" width="32" height="32">
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person fa-sm fa-fw me-2 text-gray-400"></i>
                                Profile
                            </a>
                            <a class="dropdown-item" href="settings.php">
                                <i class="bi bi-gear fa-sm fa-fw me-2 text-gray-400"></i>
                                Settings
                            </a>
                            <a class="dropdown-item" href="activity-log.php">
                                <i class="bi bi-list fa-sm fa-fw me-2 text-gray-400"></i>
                                Activity Log
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right fa-sm fa-fw me-2 text-gray-400"></i>
                                Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>

            <!-- Page Content -->
            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Project Approval</h1>
                    <a href="export-projects.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                        <i class="bi bi-download text-white-50 me-1"></i> Generate Report
                    </a>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Projects</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-hourglass-split fa-2x text-gray-300 fs-1 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Approved Projects</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approved_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-check-circle fa-2x text-gray-300 fs-1 text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Rejected Projects</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $rejected_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-x-circle fa-2x text-gray-300 fs-1 text-danger opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Projects</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_count + $approved_count + $rejected_count; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-folder fa-2x text-gray-300 fs-1 text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <?php if (!empty($project_types)): ?>
                    <div class="filter-section shadow-sm mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Projects</h6>
                            <button id="clearFilters" class="btn btn-sm btn-outline-secondary">Clear Filters</button>
                        </div>
                        <div>
                            <div class="mb-2">
                                <strong><small>Project Type:</small></strong>
                            </div>
                            <div id="typeFilters">
                                <?php foreach($project_types as $type): ?>
                                    <span class="filter-badge" data-filter="type" data-value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Pending Projects List -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Pending Projects</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical text-gray-400"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                                <li><a class="dropdown-item" href="#" id="sortDate">Sort by Date</a></li>
                                <li><a class="dropdown-item" href="#" id="sortCategory">Sort by Category</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="export-pending.php">Export List</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($projects)): ?>
                            <div class="alert alert-info shadow-sm">
                                <i class="bi bi-info-circle me-2"></i>
                                No pending projects to review at this time.
                            </div>
                        <?php else: ?>
                        <div class="row" id="projectsList">
                            <?php foreach ($projects as $project): ?>
                            <div class="col-lg-6 mb-4 project-item" data-type="<?php echo htmlspecialchars(isset($project['project_type']) ? $project['project_type'] : ''); ?>">
                                <div class="card project-card h-100 shadow-sm">
                                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <?php echo htmlspecialchars(isset($project['project_name']) ? $project['project_name'] : $project['title']); ?>
                                        </h6>
                                        <span class="badge badge-pending">Pending</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="project-details">
                                            <div class="project-meta">
                                                <div class="project-meta-item">
                                                    <i class="bi bi-tag me-2"></i>
                                                    <span><?php echo htmlspecialchars(isset($project['project_type']) ? $project['project_type'] : 'N/A'); ?></span>
                                                </div>
                                                <div class="project-meta-item">
                                                    <i class="bi bi-collection me-2"></i>
                                                    <span><?php echo htmlspecialchars(isset($project['classification']) ? $project['classification'] : 'N/A'); ?></span>
                                                </div>
                                                <div class="project-meta-item">
                                                    <i class="bi bi-code-slash me-2"></i>
                                                    <span><?php echo htmlspecialchars(isset($project['language']) ? $project['language'] : 'N/A'); ?></span>
                                                </div>
                                            </div>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($project['description']); ?></p>

                                            <?php if (!empty($project['image_path']) || !empty($project['video_path']) || !empty($project['code_file_path']) || !empty($project['instruction_file_path'])): ?>
                                                <div class="project-files">
                                                    <?php if (!empty($project['image_path'])): ?>
                                                        <a href="<?php echo htmlspecialchars($project['image_path']); ?>" class="file-link" target="_blank">
                                                            <i class="bi bi-image me-1"></i> Preview
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (!empty($project['video_path'])): ?>
                                                        <a href="<?php echo htmlspecialchars($project['video_path']); ?>" class="file-link" target="_blank">
                                                            <i class="bi bi-play-btn me-1"></i> Video
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (!empty($project['code_file_path'])): ?>
                                                        <a href="<?php echo htmlspecialchars($project['code_file_path']); ?>" class="file-link" target="_blank">
                                                            <i class="bi bi-file-code me-1"></i> Code
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if (!empty($project['instruction_file_path'])): ?>
                                                        <a href="<?php echo htmlspecialchars($project['instruction_file_path']); ?>" class="file-link" target="_blank">
                                                            <i class="bi bi-file-text me-1"></i> Instructions
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="bi bi-person me-1"></i> Submitted by: <strong><?php echo htmlspecialchars($project['user_name']); ?></strong>
                                                <br>
                                                <i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($project['user_email']); ?>
                                                <br>
                                                <i class="bi bi-calendar me-1"></i> <?php echo date('M d, Y', strtotime($project['submission_date'])); ?>
                                            </small>
                                        </div>

                                        <!-- Project Actions -->
                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $project['id']; ?>">
                                                <i class="bi bi-check-circle me-1"></i> Approve
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $project['id']; ?>">
                                                <i class="bi bi-x-circle me-1"></i> Reject
                                            </button>
                                        </div>

                                        <!-- Approve Modal -->
                                        <div class="modal fade" id="approveModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-success text-white">
                                                        <h5 class="modal-title" id="approveModalLabel">Approve Project</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post" action="">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <p>Are you sure you want to approve <strong><?php echo htmlspecialchars($project['project_name']); ?></strong>?</p>
                                                            <div class="mb-3">
                                                                <label for="approvalFeedback<?php echo $project['id']; ?>" class="form-label">Feedback (optional):</label>
                                                                <textarea class="form-control" id="approvalFeedback<?php echo $project['id']; ?>" name="feedback" rows="3" placeholder="Provide any feedback for the project creator..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Approve Project</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title" id="rejectModalLabel">Reject Project</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post" action="">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <p>Are you sure you want to reject <strong><?php echo htmlspecialchars($project['project_name']); ?></strong>?</p>
                                                            <div class="mb-3">
                                                                <label for="rejectionFeedback<?php echo $project['id']; ?>" class="form-label">Feedback (required):</label>
                                                                <textarea class="form-control" id="rejectionFeedback<?php echo $project['id']; ?>" name="feedback" rows="3" placeholder="Provide feedback on why the project was rejected..." required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Reject Project</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recently Reviewed Projects -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recently Reviewed Projects</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_projects)): ?>
                            <div class="alert alert-info shadow-sm">
                                <i class="bi bi-info-circle me-2"></i>
                                No recently reviewed projects.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Status</th>
                                        <th>Reviewed</th>
                                        <th>Submitted By</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($recent_projects as $recent): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($recent['title']); ?></td>
                                            <td>
                                                <?php if ($recent['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($recent['updated_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($recent['user_name']); ?></td>
                                            <td>
                                                <a href="view-project.php?id=<?php echo $recent['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('show');
            });
        }

        // Filter functionality
        const filterBadges = document.querySelectorAll('.filter-badge');
        const projectItems = document.querySelectorAll('.project-item');
        const clearFiltersBtn = document.getElementById('clearFilters');

        filterBadges.forEach(badge => {
            badge.addEventListener('click', function() {
                const filterType = this.getAttribute('data-filter');
                const filterValue = this.getAttribute('data-value');

                // Toggle active class
                this.classList.toggle('active');

                // Get all active filters
                const activeFilters = {};
                document.querySelectorAll('.filter-badge.active').forEach(activeBadge => {
                    const type = activeBadge.getAttribute('data-filter');
                    const value = activeBadge.getAttribute('data-value');

                    if (!activeFilters[type]) {
                        activeFilters[type] = [];
                    }
                    activeFilters[type].push(value);
                });

                // Apply filters
                projectItems.forEach(item => {
                    let shouldShow = true;

                    // Check each filter type
                    for (const type in activeFilters) {
                        const filterValues = activeFilters[type];
                        const itemValue = item.getAttribute(`data-${type}`);

                        // If item doesn't match any value in this filter type, hide it
                        if (filterValues.length > 0 && !filterValues.includes(itemValue)) {
                            shouldShow = false;
                            break;
                        }
                    }

                    // Show/hide based on filter match
                    item.style.display = shouldShow ? '' : 'none';
                });
            });
        });

        // Clear all filters
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                // Remove active class from all badges
                filterBadges.forEach(badge => {
                    badge.classList.remove('active');
                });

                // Show all projects
                projectItems.forEach(item => {
                    item.style.display = '';
                });
            });
        }

        // Sorting functionality
        const sortDate = document.getElementById('sortDate');
        const sortCategory = document.getElementById('sortCategory');
        const projectsList = document.getElementById('projectsList');

        if (sortDate) {
            sortDate.addEventListener('click', function(e) {
                e.preventDefault();

                // Get all projects in array form
                const projects = Array.from(projectItems);

                // Sort by date attribute
                projects.sort((a, b) => {
                    const dateA = new Date(a.querySelector('.text-muted').textContent.match(/\d{4}-\d{2}-\d{2}/));
                    const dateB = new Date(b.querySelector('.text-muted').textContent.match(/\d{4}-\d{2}-\d{2}/));
                    return dateB - dateA; // Newest first
                });

                // Rearrange DOM
                projects.forEach(project => {
                    projectsList.appendChild(project);
                });
            });
        }

        if (sortCategory) {
            sortCategory.addEventListener('click', function(e) {
                e.preventDefault();

                // Get all projects in array form
                const projects = Array.from(projectItems);

                // Sort by category
                projects.sort((a, b) => {
                    const typeA = a.getAttribute('data-type').toLowerCase();
                    const typeB = b.getAttribute('data-type').toLowerCase();
                    return typeA.localeCompare(typeB);
                });

                // Rearrange DOM
                projects.forEach(project => {
                    projectsList.appendChild(project);
                });
            });
        }
    });
</script>
</body>
</html>