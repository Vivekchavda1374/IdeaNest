<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer directly
require_once dirname(__FILE__) . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once dirname(__FILE__) . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once dirname(__FILE__) . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

include "../Login/Login/db.php";
include "project_notification.php";
$site_name = "IdeaNest Admin";

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to admin login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}
if(isset($_SESSION['user_name'])) {
    $user_name = $_SESSION['user_name'];
} else {
    $user_name = "Admin";
}

$total_projects_query = "SELECT 
    (SELECT COUNT(*) FROM projects) as all_projects,
    (SELECT COUNT(*) FROM admin_approved_projects) as approved_projects,
    (SELECT COUNT(*) FROM projects WHERE status = 'pending') as pending_projects,
    (SELECT COUNT(*) FROM denial_projects) as denied_projects";

$total_projects_result = $conn->query($total_projects_query);
$counts = $total_projects_result->fetch_assoc();
// Now you can access each count individually
$all_projects = $counts['all_projects'];
$approved_projects = $counts['approved_projects'];
$pending_projects = $counts['pending_projects'];
$denied_projects = $counts['denied_projects'];
// Or if you want a total of all counts
$total_projects =  $approved_projects + $pending_projects + $denied_projects;

// Handle filters and pagination
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// Filter by project type
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Filter by project category/classification
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Search filter
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';

$view_mode = isset($_GET['view']) ? $_GET['view'] : 'projects';
$valid_views = ['projects', 'approved'];
if (!in_array($view_mode, $valid_views)) {
    $view_mode = 'projects';
}

// Get all project types for filter dropdown
$types_query = "SELECT DISTINCT project_type FROM projects ORDER BY project_type ASC";
$types_result = $conn->query($types_query);
$project_types = [];

while ($row = $types_result->fetch_assoc()) {
    $project_types[] = $row['project_type'];
}

// Get all categories for filter dropdown
$categories_query = "SELECT DISTINCT classification FROM projects WHERE classification IS NOT NULL ORDER BY classification ASC";
$categories_result = $conn->query($categories_query);
$project_categories = [];

while ($row = $categories_result->fetch_assoc()) {
    $project_categories[] = $row['classification'];
}

if ($view_mode == 'projects') {
    $base_query = "FROM projects WHERE 1=1";
    $count_query = "SELECT COUNT(*) as total ";
    $data_query = "SELECT * ";
} else if ($view_mode == 'approved') {
    $base_query = "FROM admin_approved_projects WHERE 1=1";
    $count_query = "SELECT COUNT(*) as total ";
    $data_query = "SELECT * ";
}

// Add status filter - only apply to projects table
if ($status_filter != 'all' && $view_mode == 'projects') {
    $base_query .= " AND status = '$status_filter'";
}
// Add project type filter
if ($type_filter != 'all') {
    $base_query .= " AND project_type = '$type_filter'";
}

// Add category filter
if ($category_filter != 'all') {
    $base_query .= " AND classification = '$category_filter'";
}

// Add search filter
if (!empty($search_filter)) {
    $search_term = $conn->real_escape_string($search_filter);
    $base_query .= " AND (project_name LIKE '%$search_term%' OR description LIKE '%$search_term%')";
}

// Get total records for pagination
$count_result = $conn->query($count_query . $base_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Ensure current page is within valid range
if ($current_page < 1) {
    $current_page = 1;
} elseif ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Final data query with sorting, limit and offset
$data_query .= $base_query . " ORDER BY submission_date DESC LIMIT $offset, $per_page";
$projects_result = $conn->query($data_query);

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

        // Send dynamic email notification with custom options
        $email_options = [
            'subject' => 'Congratulations! Your Project "' . $project['project_name'] . '" Has Been Approved',
            'custom_text' => 'As an approved project creator, you now have access to our developer resources and community forums where you can showcase your work and get feedback from fellow creators.',
            'include_project_details' => true
        ];

        sendProjectStatusEmail($project_id, 'approved', '', $email_options);

        // Redirect back to projects view with success message
        header("Location: admin_view_project.php?message=Project approved successfully");
        exit;
    } else {
        header("Location: admin_view_project.php?error=Project not found");
        exit;
    }
}

// And modify your rejectProject function
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

        // Send dynamic email notification with custom options
        $email_options = [
            'subject' => 'Important Update About Your Project "' . $project['project_name'] . '"',
            'custom_text' => 'Our team is always available to help you improve your project. Feel free to reach out if you need guidance on addressing the issues mentioned.',
            'include_project_details' => true
        ];

        sendProjectStatusEmail($project_id, 'rejected', $rejection_reason, $email_options);

        // Redirect back to projects view with success message
        header("Location: admin_view_project.php?message=Project rejected successfully");
        exit;
    } else {
        header("Location: admin_view_project.php?error=Project not found");
        exit;
    }
}
$message = isset($_GET['message']) ? $_GET['message'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>IdeaNest Admin - Projects</title>
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

            /* Filter Bar Styles */
            .filter-bar {
                background-color: #fff;
                border-radius: 0.5rem;
                box-shadow: 0 0 15px rgba(0,0,0,0.05);
                padding: 1rem;
                margin-bottom: 1.5rem;
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

            /* Table Styles */
            .project-table th {
                font-weight: 600;
                color: #495057;
            }

            .project-table .project-title {
                font-weight: 500;
                color: #212529;
            }

            .project-badge {
                font-weight: 500;
                padding: 0.35rem 0.65rem;
            }

            .avatar-sm {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 0.25rem;
            }

            /* Status Badges */
            .badge-pending {
                background-color: rgba(245, 158, 11, 0.1);
                color: #f59e0b;
            }

            .badge-approved {
                background-color: rgba(16, 185, 129, 0.1);
                color: #10b981;
            }

            .badge-rejected {
                background-color: rgba(239, 68, 68, 0.1);
                color: #ef4444;
            }

            /* Project Type Colors */
            .project-type-web { color: #3b82f6; }
            .project-type-mobile { color: #8b5cf6; }
            .project-type-desktop { color: #10b981; }
            .project-type-game { color: #f59e0b; }
            .project-type-ai { color: #ef4444; }
            .project-type-other { color: #6b7280; }

            /* Pagination */
            .pagination {
                margin-bottom: 0;
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
                <a href="admin.php" class="sidebar-link">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="admin_view_project.php" class="sidebar-link active">
                    <i class="bi bi-kanban"></i>
                    <span>Projects</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="user_manage_by_admin.php" class="sidebar-link">
                    <i class="bi bi-people"></i>
                    <span>Users Management</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="subadmin/add_subadmin.php" class="sidebar-link">
                    <i class="bi bi-person-plus"></i>
                    <span>Add Subadmin</span>
                </a>
            </li>

            <hr class="sidebar-divider">
            <li class="sidebar-item">
                <a href="notifications.php" class="sidebar-link">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
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
        <h1 class="page-title">Projects Management</h1>
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
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(isset($action) && $action == 'view' && isset($project)): ?>
    <!-- Project Details View -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Project Details: <?php echo $project['project_name']; ?></h5>
            <a href="admin_view_project.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Projects
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

            <?php if($project['status'] == 'pending'): ?>
                <div class="d-flex justify-content-center mt-4">
                    <a href="admin_view_project.php?action=approve&id=<?php echo $project['id']; ?>" class="btn btn-success me-2" onclick="return confirm('Are you sure you want to approve this project?')">
                        <i class="bi bi-check-circle me-1"></i> Approve Project
                    </a>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectProjectModal">
                        <i class="bi bi-x-circle me-1"></i> Reject Project
                    </button>
                </div>
            <?php endif; ?>
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
                <form action="admin_view_project.php" method="post">
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
    <!-- View Switcher Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $view_mode == 'projects' ? 'active' : ''; ?>" href="admin_view_project.php?view=projects">
                <i class="bi bi-folder me-2"></i>All Projects
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $view_mode == 'approved' ? 'active' : ''; ?>" href="admin_view_project.php?view=approved">
                <i class="bi bi-check-circle me-2"></i>Approved Projects
            </a>
        </li>
    </ul>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form action="admin_view_project.php" method="get" class="row g-3">
            <input type="hidden" name="view" value="<?php echo $view_mode; ?>">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search projects..." value="<?php echo htmlspecialchars($search_filter); ?>">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>

            <?php if($view_mode == 'projects'): ?>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-2">
                <label for="type" class="form-label">Project Type</label>
                <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                    <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                    <?php foreach($project_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $type_filter == $type ? 'selected' : ''; ?>>
                            <?php echo $type; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category" onchange="this.form.submit()">
                    <option value="all" <?php echo $category_filter == 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach($project_categories as $category): ?>
                        <option value="<?php echo $category; ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                            <?php echo $category; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <a href="admin_view_project.php?view=<?php echo $view_mode; ?>" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle me-1"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>
    <!-- Projects Table -->
    <div class="card">
    <div class="card-body">
    <?php if ($projects_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover project-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Project Name</th>
                    <th>Type</th>
                    <th>Category</th>
                    <?php if($view_mode == 'projects'): ?>
                        <th>Status</th>
                    <?php endif; ?>
                    <th>Submission Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $count = $offset + 1;
                while($project = $projects_result->fetch_assoc()):
                    // Determine project type class for styling
                    $type_class = '';
                    switch(strtolower($project['project_type'])) {
                        case 'web': $type_class = 'project-type-web'; break;
                        case 'mobile': $type_class = 'project-type-mobile'; break;
                        case 'desktop': $type_class = 'project-type-desktop'; break;
                        case 'game': $type_class = 'project-type-game'; break;
                        case 'ai': $type_class = 'project-type-ai'; break;
                        default: $type_class = 'project-type-other'; break;
                    }

                    // Determine status badge class
                    $status_class = '';
                    if(isset($project['status'])) {
                        switch($project['status']) {
                            case 'pending': $status_class = 'badge-pending'; break;
                            case 'approved': $status_class = 'badge-approved'; break;
                            case 'rejected': $status_class = 'badge-rejected'; break;
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td>
                            <span class="project-title"><?php echo $project['project_name']; ?></span>
                        </td>
                        <td>
                            <span class="<?php echo $type_class; ?>"><?php echo $project['project_type']; ?></span>
                        </td>
                        <td><?php echo $project['classification'] ?? 'N/A'; ?></td>
                        <?php if($view_mode == 'projects'): ?>
                            <td>
                                    <span class="badge rounded-pill project-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                            </td>
                        <?php endif; ?>
                        <td>
                            <?php echo date('M d, Y', strtotime($project['submission_date'])); ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="admin_view_project.php?action=view&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <?php if($view_mode == 'projects' && $project['status'] == 'pending'): ?>
                                    <a href="admin_view_project.php?action=approve&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Are you sure you want to approve this project?')">
                                        <i class="bi bi-check-circle"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $project['id']; ?>">
                                        <i class="bi bi-x-circle"></i>
                                    </button>

                                    <!-- Reject Modal for each project -->
                                    <div class="modal fade" id="rejectModal<?php echo $project['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Project</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="admin_view_project.php" method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                        <div class="mb-3">
                                                            <label for="rejection_reason<?php echo $project['id']; ?>" class="form-label">Rejection Reason</label>
                                                            <textarea class="form-control" id="rejection_reason<?php echo $project['id']; ?>" name="rejection_reason" rows="3" required></textarea>
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
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Projects pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="admin_view_project.php?view=<?php echo $view_mode; ?>&page=<?php echo $current_page - 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search_filter); ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $current_page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="admin_view_project.php?view=<?php echo $view_mode; ?>&page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search_filter); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="admin_view_project.php?view=<?php echo $view_mode; ?>&page=<?php echo $current_page + 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&search=<?php echo urlencode($search_filter); ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i> No projects found matching your criteria.
        </div>
    <?php endif; ?>
    </div>
    </div>
<?php endif; ?>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        // Auto dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>