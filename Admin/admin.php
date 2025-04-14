<?php
// Database connection
include "../Login/Login/db.php";

// Site name
$site_name = "IdeaNest Admin";

// Current user information (replace with session variable or actual login system)
session_start();
if(isset($_SESSION['user_name'])) {
    $user_name = $_SESSION['user_name'];
} else {
    $user_name = "Admin"; // Default if not logged in
}

// Check if there's an action to handle
if(isset($_GET['action']) && isset($_GET['project_id'])) {
    $action = $_GET['action'];
    $project_id = intval($_GET['project_id']);

    // Perform action based on request
    switch($action) {
        case 'view':
            // Redirect to view page with project ID
            header("Location: view_project.php?id=$project_id");
            exit;
            break;

        case 'approve':
            // Approve the project
            approveProject($project_id, $conn);
            break;

        case 'reject':
            // Show rejection form
            header("Location: reject_project.php?id=$project_id");
            exit;
            break;
    }
}

// Function to approve a project
function approveProject($project_id, $conn) {
    // Get project details
    $query = "SELECT * FROM projects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $project = $result->fetch_assoc();

        // Insert into approved projects table
        $approve_query = "INSERT INTO admin_approved_projects (
            project_name, project_type, classification, description, 
            language, image_path, video_path, code_file_path, 
            instruction_file_path, submission_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')";

        $approve_stmt = $conn->prepare($approve_query);
        $approve_stmt->bind_param(
            "ssssssssss",
            $project['project_name'],
            $project['project_type'],
            $project['classification'],
            $project['description'],
            $project['language'],
            $project['image_path'],
            $project['video_path'],
            $project['code_file_path'],
            $project['instruction_file_path'],
            $project['submission_date']
        );

        $approve_stmt->execute();

        // Update status in original projects table
        $update_query = "UPDATE projects SET status = 'approved' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $project_id);
        $update_stmt->execute();

        // Redirect back to dashboard with success message
        header("Location: dashboard.php?message=Project approved successfully");
        exit;
    } else {
        header("Location: dashboard.php?error=Project not found");
        exit;
    }
}

// Project statistics
// Total Projects
$total_projects_query = "SELECT COUNT(*) as count FROM projects";
$total_projects_result = $conn->query($total_projects_query);
$total_projects = $total_projects_result->fetch_assoc()['count'];

// Total approved projects
$approved_projects_query = "SELECT COUNT(*) as count FROM `admin_approved_projects`";
$approved_projects_result = $conn->query($approved_projects_query);
$approved_projects = $approved_projects_result->fetch_assoc()['count'];

// Total pending projects
$pending_projects_query = "SELECT COUNT(*) as count FROM projects WHERE status = 'pending'";
$pending_projects_result = $conn->query($pending_projects_query);
$pending_projects = $pending_projects_result->fetch_assoc()['count'];

// Total rejected projects
$rejected_projects_query = "SELECT COUNT(*) as count FROM denial_projects";
$rejected_projects_result = $conn->query($rejected_projects_query);
$rejected_projects = $rejected_projects_result->fetch_assoc()['count'];

// Calculate percentages and growth
// This is placeholder data - you'd typically compare with previous periods
$total_projects_percentage = 75;
$total_projects_growth = "12%";
$approved_projects_percentage = 60;
$approved_projects_growth = "8%";
$pending_projects_percentage = 40;
$pending_projects_growth = "5%";
$rejected_projects_percentage = 20;
$rejected_projects_growth = "3%";

// Time range for charts
$selected_time_range = "Last 7 Days";
$time_ranges = ["Last 7 Days", "Last 30 Days", "Last 3 Months", "Last Year"];

// Category ranges
$selected_category_range = "All Time";
$category_ranges = ["All Time", "This Month", "This Year"];

// Chart data for projects
$project_chart_labels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

// Query for submitted projects per day for the last 7 days
$submitted_projects_data = [];
$approved_projects_data = [];
$rejected_projects_data = [];

// Get today's date
$today = date('Y-m-d');

// Loop through last 7 days
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $next_date = date('Y-m-d', strtotime("-" . ($i - 1) . " days"));

    // Submitted projects
    $submitted_query = "SELECT COUNT(*) as count FROM projects WHERE DATE(submission_date) = '$date'";
    $submitted_result = $conn->query($submitted_query);
    $submitted_projects_data[] = $submitted_result->fetch_assoc()['count'];

    // Approved projects
    $approved_query = "SELECT COUNT(*) as count FROM `admin_approved_projects` WHERE DATE(submission_date) = '$date'";
    $approved_result = $conn->query($approved_query);
    $approved_projects_data[] = $approved_result->fetch_assoc()['count'];

    // Rejected projects
    $rejected_query = "SELECT COUNT(*) as count FROM denial_projects WHERE DATE(rejection_date) = '$date'";
    $rejected_result = $conn->query($rejected_query);
    $rejected_projects_data[] = $rejected_result->fetch_assoc()['count'];
}

// Category data
$category_query = "SELECT classification, COUNT(*) as count FROM projects GROUP BY classification";
$category_result = $conn->query($category_query);

$category_labels = [];
$category_data = [];
$category_colors = ['#4361ee', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#8b5cf6'];

$color_index = 0;
while ($row = $category_result->fetch_assoc()) {
    $category_labels[] = $row['classification'];
    $category_data[] = $row['count'];
    $color_index++;

    // Reset color index if we run out of colors
    if ($color_index >= count($category_colors)) {
        $color_index = 0;
    }
}

// Recent activities
$recent_activities = [];

// Get recent project submissions
$recent_submissions_query = "SELECT id, project_name, user_id, submission_date FROM projects ORDER BY submission_date DESC LIMIT 3";
$recent_submissions_result = $conn->query($recent_submissions_query);

while ($row = $recent_submissions_result->fetch_assoc()) {
    $time_ago = time_elapsed_string($row['submission_date']);
    $recent_activities[] = [
        'type' => 'primary',
        'title' => 'New Project Submitted',
        'description' => 'Project "' . $row['project_name'] . '" was submitted by User #' . $row['user_id'],
        'time_ago' => $time_ago
    ];
}

// Get recent approvals
$recent_approvals_query = "SELECT project_name, submission_date FROM `admin_approved_projects` ORDER BY submission_date DESC LIMIT 2";
$recent_approvals_result = $conn->query($recent_approvals_query);

while ($row = $recent_approvals_result->fetch_assoc()) {
    $time_ago = time_elapsed_string($row['submission_date']);
    $recent_activities[] = [
        'type' => 'success',
        'title' => 'Project Approved',
        'description' => 'Project "' . $row['project_name'] . '" was approved',
        'time_ago' => $time_ago
    ];
}

// Get recent rejections
$recent_rejections_query = "SELECT project_name, rejection_date, rejection_reason FROM denial_projects ORDER BY rejection_date DESC LIMIT 2";
$recent_rejections_result = $conn->query($recent_rejections_query);

while ($row = $recent_rejections_result->fetch_assoc()) {
    $time_ago = time_elapsed_string($row['rejection_date']);
    $recent_activities[] = [
        'type' => 'danger',
        'title' => 'Project Rejected',
        'description' => 'Project "' . $row['project_name'] . '" was rejected. Reason: ' . $row['rejection_reason'],
        'time_ago' => $time_ago
    ];
}

// Sort activities by time
usort($recent_activities, function($a, $b) {
    return strtotime($b['time_ago']) - strtotime($a['time_ago']);
});

// Pending projects list
$pending_projects_list = [];
$pending_projects_query = "SELECT p.id, p.project_name, p.project_type, p.classification, p.user_id, p.status 
                          FROM projects p 
                          WHERE p.status = 'pending' 
                          ORDER BY p.submission_date DESC 
                          LIMIT 5";
$pending_projects_result = $conn->query($pending_projects_query);

while ($row = $pending_projects_result->fetch_assoc()) {
    // Determine icon based on project type
    $icon = 'folder';
    switch (strtolower($row['project_type'])) {
        case 'web':
            $icon = 'globe';
            break;
        case 'mobile':
            $icon = 'phone';
            break;
        case 'desktop':
            $icon = 'pc-display';
            break;
        case 'game':
            $icon = 'controller';
            break;
        case 'ai':
            $icon = 'cpu';
            break;
    }

    $pending_projects_list[] = [
        'id' => $row['id'],
        'name' => $row['project_name'],
        'type' => $row['project_type'],
        'technologies' => $row['classification'],
        'submitted_by' => 'User #' . $row['user_id'],
        'status' => 'Pending Review',
        'status_class' => 'warning',
        'icon' => $icon
    ];
}

// Helper function to convert MySQL datetime to "time ago" format
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Check for messages
$message = isset($_GET['message']) ? $_GET['message'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest Admin - Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        /* Custom Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
            padding: 1rem;
        }

        .sidebar-header {
            padding: 1rem 0;
            text-align: center;
            border-bottom: 1px solid #f1f1f1;
            margin-bottom: 1rem;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: #4361ee;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .sidebar-brand i {
            margin-right: 0.5rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-item {
            margin-bottom: 0.5rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #6c757d;
            text-decoration: none;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }

        .sidebar-link i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        .sidebar-link.active {
            background-color: #4361ee;
            color: #fff;
        }

        .sidebar-link:hover:not(.active) {
            background-color: #f8f9fa;
            color: #4361ee;
        }

        .sidebar-divider {
            margin: 1rem 0;
            border-top: 1px solid #f1f1f1;
        }

        .sidebar-footer {
            padding: 1rem 0;
            border-top: 1px solid #f1f1f1;
            margin-top: 1rem;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 1rem;
            transition: all 0.3s;
        }

        /* Topbar Styles */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .topbar-action {
            font-size: 1.25rem;
            color: #6c757d;
            margin-left: 1rem;
            position: relative;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4361ee;
            margin-left: 1rem;
        }

        /* Stats Card Styles */
        .stats-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-icon.primary { background-color: rgba(67, 97, 238, 0.1); color: #4361ee; }
        .stats-icon.success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stats-icon.warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stats-icon.danger { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .stats-info {
            flex-grow: 1;
        }

        .stats-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .stats-progress {
            margin-top: auto;
        }

        .stats-percentage {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.5rem;
            text-align: right;
        }

        /* Timeline Styles */
        .timeline {
            position: relative;
        }

        .timeline-item {
            padding-left: 2rem;
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .timeline-icon.primary { background-color: rgba(67, 97, 238, 0.1); border: 2px solid #4361ee; }
        .timeline-icon.success { background-color: rgba(16, 185, 129, 0.1); border: 2px solid #10b981; }
        .timeline-icon.danger { background-color: rgba(239, 68, 68, 0.1); border: 2px solid #ef4444; }

        .timeline-content {
            position: relative;
        }

        .timeline-title {
            font-size: 0.9375rem;
            margin-bottom: 0.25rem;
        }

        .timeline-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .timeline-time {
            font-size: 0.75rem;
            color: #adb5bd;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Alert banner */
        .alert-banner {
            margin-bottom: 20px;
        }

        /* Media Query for Responsive Sidebar */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.pushed {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
            <i class="bi bi-lightbulb"></i>
            <span><?php echo $site_name; ?></span>
        </a>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link active">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="projects.php" class="sidebar-link">
                <i class="bi bi-kanban"></i>
                <span>Projects</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="users.php" class="sidebar-link">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <li class="sidebar-item">
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title">Dashboard</h1>
        <div class="topbar-actions">
            <div class="dropdown">
                <a href="#" class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if($message): ?>
        <div class="alert alert-success alert-banner alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-banner alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Welcome Stats -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-1">Welcome back, <?php echo $user_name; ?>!</h4>
                    <p class="text-muted">Here's what's happening with your projects today.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="add_project.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Project
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="stats-card">
                <div class="stats-icon primary">
                    <i class="bi bi-folder"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Total Projects</div>
                    <div class="stats-value"><?php echo $total_projects; ?></div>
                </div>
                <div class="stats-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $total_projects_percentage; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <i class="bi bi-arrow-up"></i> <?php echo $total_projects_growth; ?> from last period
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="stats-card">
                <div class="stats-icon success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Approved Projects</div>
                    <div class="stats-value"><?php echo $approved_projects; ?></div>
                </div>
                <div class="stats-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $approved_projects_percentage; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <i class="bi bi-arrow-up"></i> <?php echo $approved_projects_growth; ?> from last period
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="stats-card">
                <div class="stats-icon warning">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Pending Projects</div>
                    <div class="stats-value"><?php echo $pending_projects; ?></div>
                </div>
                <div class="stats-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pending_projects_percentage; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <i class="bi bi-arrow-up"></i> <?php echo $pending_projects_growth; ?> from last period
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon danger">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stats-info">
                    <div class="stats-label">Rejected Projects</div>
                    <div class="stats-value"><?php echo $rejected_projects; ?></div>
                </div>
                <div class="stats-progress">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $rejected_projects_percentage; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <i class="bi bi-arrow-up"></i> <?php echo $rejected_projects_growth; ?> from last period
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Project Submissions</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $selected_time_range; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($time_ranges as $range): ?>
                                <li><a class="dropdown-item" href="#"><?php echo $range; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="projectsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Project Categories</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $selected_category_range; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($category_ranges as $range): ?>
                                <li><a class="dropdown-item" href="#"><?php echo $range; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoriesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities and Pending Projects -->
    <!-- Recent Activities and Pending Projects -->
    <div class="row">
        <!-- Recent Activities Column -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activities</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach($recent_activities as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon <?php echo $activity['type']; ?>">
                                    <?php if($activity['type'] == 'primary'): ?>
                                        <i class="bi bi-plus-circle text-primary small"></i>
                                    <?php elseif($activity['type'] == 'success'): ?>
                                        <i class="bi bi-check-circle text-success small"></i>
                                    <?php elseif($activity['type'] == 'danger'): ?>
                                        <i class="bi bi-x-circle text-danger small"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title"><?php echo $activity['title']; ?></h6>
                                    <p class="timeline-text"><?php echo $activity['description']; ?></p>
                                    <p class="timeline-time"><?php echo $activity['time_ago']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Projects Column -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pending Projects</h5>
                    <a href="" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Project</th>
                                <th>Type</th>
                                <th>Technologies</th>
                                <th>Submitted By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if(count($pending_projects_list) > 0): ?>
                                <?php foreach($pending_projects_list as $project): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="bi bi-<?php echo $project['icon']; ?> text-primary"></i>
                                                </div>
                                                <div>
                                                    <?php echo $project['name']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $project['type']; ?></td>
                                        <td><?php echo $project['technologies']; ?></td>
                                        <td><?php echo $project['submitted_by']; ?></td>
                                        <td>
                                                <span class="badge bg-<?php echo $project['status_class']; ?>">
                                                    <?php echo $project['status']; ?>
                                                </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                                <a href="" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-x-lg"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">No pending projects</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap 5 JS and Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Sidebar toggle on mobile
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
        document.querySelector('.main-content').classList.toggle('pushed');
    });

    // Projects Chart
    const projectsChartCtx = document.getElementById('projectsChart').getContext('2d');
    const projectsChart = new Chart(projectsChartCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($project_chart_labels); ?>,
            datasets: [
                {
                    label: 'Submitted',
                    data: <?php echo json_encode($submitted_projects_data); ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Approved',
                    data: <?php echo json_encode($approved_projects_data); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Rejected',
                    data: <?php echo json_encode($rejected_projects_data); ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Categories Chart
    const categoriesChartCtx = document.getElementById('categoriesChart').getContext('2d');
    const categoriesChart = new Chart(categoriesChartCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($category_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($category_data); ?>,
                backgroundColor: <?php echo json_encode($category_colors); ?>,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            },
            cutout: '70%'
        }
    });
</script>
</body>
</html>