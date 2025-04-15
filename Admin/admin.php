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

// Handle project actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $project_id = $_GET['id'];
    $action = $_GET['action'];

    // View project
    if($action == 'view') {
        // Get project details
        $query = "SELECT * FROM projects WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $project = $result->fetch_assoc();
    }

    // Approve project
    if($action == 'approve') {
        approveProject($project_id, $conn);
    }

    // Reject project
    if($action == 'reject') {
        // Show rejection form
        $show_rejection_form = true;
        $reject_project_id = $project_id;
    }
}

// Handle rejection form submission
if(isset($_POST['reject_submit'])) {
    $project_id = $_POST['project_id'];
    $rejection_reason = $_POST['rejection_reason'];
    rejectProject($project_id, $rejection_reason, $conn);
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

        // Redirect back to admin with success message
        header("Location: admin.php?message=Project approved successfully");
        exit;
    } else {
        header("Location: admin.php?error=Project not found");
        exit;
    }
}

// Function to reject a project
function rejectProject($project_id, $rejection_reason, $conn) {
    // Get project details
    $query = "SELECT * FROM projects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $project = $result->fetch_assoc();

        // Insert into rejection table
        $reject_query = "INSERT INTO denial_projects (
            user_id, project_name, project_type, classification, description,
            language, image_path, video_path, code_file_path,
            instruction_file_path, submission_date, status, rejection_date, rejection_reason
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rejected', NOW(), ?)";

        $reject_stmt = $conn->prepare($reject_query);
        $reject_stmt->bind_param(
            "isssssssssss",
            $project['user_id'],
            $project['project_name'],
            $project['project_type'],
            $project['classification'],
            $project['description'],
            $project['language'],
            $project['image_path'],
            $project['video_path'],
            $project['code_file_path'],
            $project['instruction_file_path'],
            $project['submission_date'],
            $rejection_reason
        );

        $reject_stmt->execute();

        // Update status in original projects table
        $update_query = "UPDATE projects SET status = 'rejected' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $project_id);
        $update_stmt->execute();

        // Redirect back to admin with success message
        header("Location: admin.php?message=Project rejected successfully");
        exit;
    } else {
        header("Location: admin.php?error=Project not found");
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
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>IdeaNest Admin - admin</title>
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

            /* Project Details Styles */
            .project-details {
                margin-bottom: 20px;
            }

            .project-detail-label {
                font-weight: 600;
                margin-bottom: 5px;
            }

            .project-detail-value {
                margin-bottom: 15px;
            }

            /* Modal styles */
            .modal-backdrop {
                z-index: 1040;
            }

            .modal {
                z-index: 1050;
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
                <a href="admin.php" class="sidebar-link active">
                    <i class="bi bi-grid-1x2"></i>
                    <span>admin</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="" class="sidebar-link">
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
        <h1 class="page-title">admin</h1>
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

<?php if(isset($action) && $action == 'view' && isset($project)): ?>
    <!-- Project View Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Project Details: <?php echo $project['project_name']; ?></h5>
            <a href="admin.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to admin
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="project-details">
                        <div class="project-detail-label">Project Name:</div>
                        <div class="project-detail-value"><?php echo $project['project_name']; ?></div>

                        <div class="project-detail-label">Project Type:</div>
                        <div class="project-detail-value"><?php echo $project['project_type']; ?></div>

                        <div class="project-detail-label">Classification:</div>
                        <div class="project-detail-value"><?php echo $project['classification']; ?></div>

                        <div class="project-detail-label">Language:</div>
                        <div class="project-detail-value"><?php echo $project['language']; ?></div>

                        <div class="project-detail-label">Submitted By:</div>
                        <div class="project-detail-value">User #<?php echo $project['user_id']; ?></div>

                        <div class="project-detail-label">Submission Date:</div>
                        <div class="project-detail-value"><?php echo date('F j, Y, g:i a', strtotime($project['submission_date'])); ?></div>

                        <div class="project-detail-label">Status:</div>
                        <div class="project-detail-value">
                            <span class="badge bg-<?php echo $project['status'] == 'pending' ? 'warning' : ($project['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="project-detail-label">Description:</div>
                    <div class="project-detail-value"><?php echo $project['description']; ?></div>

                    <?php if(!empty($project['image_path'])): ?>
                        <div class="project-detail-label">Project Image:</div>
                        <div class="project-detail-value">
                            <img src="<?php echo $project['image_path']; ?>" alt="Project Image" class="img-fluid mb-3" style="max-height: 200px;">
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($project['video_path'])): ?>
                        <div class="project-detail-label">Project Video:</div>
                        <div class="project-detail-value mb-3">
                            <a href="<?php echo $project['video_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-play-circle me-1"></i> View Video
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($project['code_file_path'])): ?>
                        <div class="project-detail-label">Code Files:</div>
                        <div class="project-detail-value mb-3">
                            <a href="<?php echo $project['code_file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-code-slash me-1"></i> Download Code
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if(!empty($project['instruction_file_path'])): ?>
                        <div class="project-detail-label">Instructions:</div>
                        <div class="project-detail-value mb-3">
                            <a href="<?php echo $project['instruction_file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-file-text me-1"></i> View Instructions
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-4">
                <a href="admin.php?action=approve&id=<?php echo $project['id']; ?>" class="btn btn-success me-2" onclick="return confirm('Are you sure you want to approve this project?')">
                    <i class="bi bi-check-circle me-1"></i> Approve Project
                </a>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectProjectModal">
                    <i class="bi bi-x-circle me-1"></i> Reject Project
                </button>
            </div>
        </div>
    </div>

    <!-- Reject Project Modal -->
    <div class="modal fade" id="rejectProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="admin.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Rejection Reason</label>
                            <textarea class="form-control" name="rejection_reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reject_submit" class="btn btn-danger">Reject Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- admin Content -->
    <div class="admin-content">
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h5 class="card-title">Total Projects</h5>
                            <h2 class="stat-number"><?php echo $total_projects; ?></h2>
                            <div class="stat-progress">
                                <div class="progress">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $total_projects_percentage; ?>%" aria-valuenow="<?php echo $total_projects_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="stat-progress-text">
                                        <i class="bi bi-arrow-up-right"></i> <?php echo $total_projects_growth; ?> from last period
                                    </span>
                            </div>
                        </div>
                        <div class="stat-icon-container bg-primary-light">
                            <i class="bi bi-folder stat-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h5 class="card-title">Approved Projects</h5>
                            <h2 class="stat-number"><?php echo $approved_projects; ?></h2>
                            <div class="stat-progress">
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $approved_projects_percentage; ?>%" aria-valuenow="<?php echo $approved_projects_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="stat-progress-text">
                                        <i class="bi bi-arrow-up-right"></i> <?php echo $approved_projects_growth; ?> from last period
                                    </span>
                            </div>
                        </div>
                        <div class="stat-icon-container bg-success-light">
                            <i class="bi bi-check-circle stat-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h5 class="card-title">Pending Projects</h5>
                            <h2 class="stat-number"><?php echo $pending_projects; ?></h2>
                            <div class="stat-progress">
                                <div class="progress">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $pending_projects_percentage; ?>%" aria-valuenow="<?php echo $pending_projects_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="stat-progress-text">
                                        <i class="bi bi-arrow-up-right"></i> <?php echo $pending_projects_growth; ?> from last period
                                    </span>
                            </div>
                        </div>
                        <div class="stat-icon-container bg-warning-light">
                            <i class="bi bi-hourglass-split stat-icon text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h5 class="card-title">Rejected Projects</h5>
                            <h2 class="stat-number"><?php echo $rejected_projects; ?></h2>
                            <div class="stat-progress">
                                <div class="progress">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $rejected_projects_percentage; ?>%" aria-valuenow="<?php echo $rejected_projects_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="stat-progress-text">
                                        <i class="bi bi-arrow-up-right"></i> <?php echo $rejected_projects_growth; ?> from last period
                                    </span>
                            </div>
                        </div>
                        <div class="stat-icon-container bg-danger-light">
                            <i class="bi bi-x-circle stat-icon text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Project Activity Chart -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Project Activity</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $selected_time_range; ?>
                            </button>
                            <ul class="dropdown-menu">
                                <?php foreach ($time_ranges as $range): ?>
                                    <li><a class="dropdown-item <?php echo $range == $selected_time_range ? 'active' : ''; ?>" href="?time_range=<?php echo urlencode($range); ?>"><?php echo $range; ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="projectsChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            <!-- Project Categories Chart -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Project Categories</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo $selected_category_range; ?>
                            </button>
                            <ul class="dropdown-menu">
                                <?php foreach ($category_ranges as $range): ?>
                                    <li><a class="dropdown-item <?php echo $range == $selected_category_range ? 'active' : ''; ?>" href="?category_range=<?php echo urlencode($range); ?>"><?php echo $range; ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="categoriesChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Pending Projects Row -->
        <div class="row g-4">
            <!-- Recent Activity -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="activity-timeline">
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon bg-<?php echo $activity['type']; ?>-light text-<?php echo $activity['type']; ?>">
                                        <i class="bi bi-<?php echo $activity['type'] == 'primary' ? 'plus-circle' : ($activity['type'] == 'success' ? 'check-circle' : 'x-circle'); ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h6 class="activity-title"><?php echo $activity['title']; ?></h6>
                                        <p class="activity-text"><?php echo $activity['description']; ?></p>
                                        <span class="activity-time"><?php echo $activity['time_ago']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Pending Projects -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Pending Projects</h5>
                        <a href="projects.php?status=pending" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Project</th>
                                        <th scope="col">Type</th>
                                        <th scope="col">Technologies</th>
                                        <th scope="col">Submitted By</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($pending_projects_list) > 0): ?>
                                        <?php foreach ($pending_projects_list as $project): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm bg-light text-primary rounded me-2">
                                                            <i class="bi bi-<?php echo $project['icon']; ?>"></i>
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
                                                    <span class="badge bg-<?php echo $project['status_class']; ?>"><?php echo $project['status']; ?></span>
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <a href="admin.php?action=view&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="admin.php?action=approve&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-success me-1" onclick="return confirm('Are you sure you want to approve this project?')">
                                                            <i class="bi bi-check"></i>
                                                        </a>
                                                        <a href="admin.php?action=reject&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-x"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No pending projects found</td>
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
    <?php endif; ?>
    </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-content').classList.toggle('pushed');
        });

        // Project chart
        var projectCtx = document.getElementById('projectsChart');
        if (projectCtx) {
            var projectsChart = new Chart(projectCtx, {
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
                            position: 'bottom'
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
        }

        // Categories chart
        var categoryCtx = document.getElementById('categoriesChart');
        if (categoryCtx) {
            var categoriesChart = new Chart(categoryCtx, {
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
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
