<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include PHPMailer directly
require_once dirname(__DIR__) . '/vendor/autoload.php';

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
if (isset($_SESSION['user_name'])) {
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

// Filter by difficulty level
$difficulty_filter = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'all';

// Filter by development time
$dev_time_filter = isset($_GET['dev_time']) ? $_GET['dev_time'] : 'all';

// Filter by team size
$team_size_filter = isset($_GET['team_size']) ? $_GET['team_size'] : 'all';

// Search filter
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';

$view_mode = isset($_GET['view']) ? $_GET['view'] : 'projects';
$valid_views = ['projects', 'approved'];
if (!in_array($view_mode, $valid_views)) {
    $view_mode = 'projects';
}

// Get all project types for filter dropdown
$types_query = "SELECT DISTINCT project_type FROM projects WHERE project_type IS NOT NULL ORDER BY project_type ASC";
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

// Get all difficulty levels for filter dropdown
$difficulty_query = "SELECT DISTINCT difficulty_level FROM projects WHERE difficulty_level IS NOT NULL ORDER BY difficulty_level ASC";
$difficulty_result = $conn->query($difficulty_query);
$difficulty_levels = [];
while ($row = $difficulty_result->fetch_assoc()) {
    $difficulty_levels[] = $row['difficulty_level'];
}

// Get all development times for filter dropdown
$dev_time_query = "SELECT DISTINCT development_time FROM projects WHERE development_time IS NOT NULL ORDER BY development_time ASC";
$dev_time_result = $conn->query($dev_time_query);
$development_times = [];
while ($row = $dev_time_result->fetch_assoc()) {
    $development_times[] = $row['development_time'];
}

// Get all team sizes for filter dropdown
$team_size_query = "SELECT DISTINCT team_size FROM projects WHERE team_size IS NOT NULL ORDER BY team_size ASC";
$team_size_result = $conn->query($team_size_query);
$team_sizes = [];
while ($row = $team_size_result->fetch_assoc()) {
    $team_sizes[] = $row['team_size'];
}

if ($view_mode == 'projects') {
    $base_query = "FROM projects WHERE 1=1";
    $count_query = "SELECT COUNT(*) as total ";
    $data_query = "SELECT * ";
} elseif ($view_mode == 'approved') {
    $base_query = "FROM admin_approved_projects WHERE 1=1";
    $count_query = "SELECT COUNT(*) as total ";
    $data_query = "SELECT * ";
}

// Add status filter - only apply to projects table
if ($status_filter != 'all' && $view_mode == 'projects') {
    $base_query .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}

// Add project type filter
if ($type_filter != 'all') {
    $base_query .= " AND project_type = '" . $conn->real_escape_string($type_filter) . "'";
}

// Add category filter
if ($category_filter != 'all') {
    $base_query .= " AND classification = '" . $conn->real_escape_string($category_filter) . "'";
}

// Add difficulty filter
if ($difficulty_filter != 'all') {
    $base_query .= " AND difficulty_level = '" . $conn->real_escape_string($difficulty_filter) . "'";
}

// Add development time filter
if ($dev_time_filter != 'all') {
    $base_query .= " AND development_time = '" . $conn->real_escape_string($dev_time_filter) . "'";
}

// Add team size filter
if ($team_size_filter != 'all') {
    $base_query .= " AND team_size = '" . $conn->real_escape_string($team_size_filter) . "'";
}

// Add search filter
if (!empty($search_filter)) {
    $search_term = $conn->real_escape_string($search_filter);
    $base_query .= " AND (project_name LIKE '%$search_term%' OR description LIKE '%$search_term%' OR keywords LIKE '%$search_term%' OR target_audience LIKE '%$search_term%')";
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
if (isset($_GET['action']) && isset($_GET['id'])) {
    $project_id = $_GET['id'];
    $action = $_GET['action'];

    // View project
    if ($action == 'view') {
        // Get project details
        $query = "SELECT * FROM projects WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $project = $result->fetch_assoc();
    }

    // Approve project
    if ($action == 'approve') {
        approveProject($project_id, $conn);
    }

    // Reject project
    if ($action == 'reject') {
        // Show rejection form
        $show_rejection_form = true;
        $reject_project_id = $project_id;
    }
}

// Handle rejection form submission
if (isset($_POST['reject_submit'])) {
    $project_id = $_POST['project_id'];
    $rejection_reason = $_POST['rejection_reason'];
    rejectProject($project_id, $rejection_reason, $conn);
}

// Function to approve a project
function approveProject($project_id, $conn)
{
    // Get project details
    $query = "SELECT * FROM projects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();

        // Insert into approved projects table with all fields
        $approve_query = "INSERT INTO admin_approved_projects (
            user_id, project_name, project_type, classification, project_category,
            difficulty_level, development_time, team_size, target_audience, project_goals,
            challenges_faced, future_enhancements, github_repo, live_demo_url, project_license,
            keywords, contact_email, social_links, description, language, image_path, video_path,
            code_file_path, instruction_file_path, presentation_file_path, additional_files_path,
            submission_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')";

        $approve_stmt = $conn->prepare($approve_query);
        $approve_stmt->bind_param(
            "sssssssssssssssssssssssssss",
            $project['user_id'],
            $project['project_name'],
            $project['project_type'],
            $project['classification'],
            $project['project_category'],
            $project['difficulty_level'],
            $project['development_time'],
            $project['team_size'],
            $project['target_audience'],
            $project['project_goals'],
            $project['challenges_faced'],
            $project['future_enhancements'],
            $project['github_repo'],
            $project['live_demo_url'],
            $project['project_license'],
            $project['keywords'],
            $project['contact_email'],
            $project['social_links'],
            $project['description'],
            $project['language'],
            $project['image_path'],
            $project['video_path'],
            $project['code_file_path'],
            $project['instruction_file_path'],
            $project['presentation_file_path'],
            $project['additional_files_path'],
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

// Function to reject a project
function rejectProject($project_id, $rejection_reason, $conn)
{
    // Get project details
    $query = "SELECT * FROM projects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();

        // Insert into rejection table with all fields
        $reject_query = "INSERT INTO denial_projects (
            user_id, project_name, project_type, classification, project_category,
            difficulty_level, development_time, team_size, target_audience, project_goals,
            challenges_faced, future_enhancements, github_repo, live_demo_url, project_license,
            keywords, contact_email, social_links, description, language, image_path, video_path,
            code_file_path, instruction_file_path, presentation_file_path, additional_files_path,
            submission_date, status, rejection_date, rejection_reason
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rejected', NOW(), ?)";

        $reject_stmt = $conn->prepare($reject_query);
        $reject_stmt->bind_param(
            "ssssssssssssssssssssssssssss",
            $project['user_id'],
            $project['project_name'],
            $project['project_type'],
            $project['classification'],
            $project['project_category'],
            $project['difficulty_level'],
            $project['development_time'],
            $project['team_size'],
            $project['target_audience'],
            $project['project_goals'],
            $project['challenges_faced'],
            $project['future_enhancements'],
            $project['github_repo'],
            $project['live_demo_url'],
            $project['project_license'],
            $project['keywords'],
            $project['contact_email'],
            $project['social_links'],
            $project['description'],
            $project['language'],
            $project['image_path'],
            $project['video_path'],
            $project['code_file_path'],
            $project['instruction_file_path'],
            $project['presentation_file_path'],
            $project['additional_files_path'],
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
    <link rel="stylesheet" href="../assets/css/admin_view_project.css">
</head>
<body>
<!-- Sidebar -->
<?php include 'sidebar_admin.php'?>

<!-- Main Content -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title">Projects Management</h1>

    </div>

    <!-- Alert Messages -->
    <?php if ($message) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($action) && $action == 'view' && isset($project)) : ?>
        <!-- Project Details View -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Project Details: <?php echo htmlspecialchars($project['project_name']); ?></h5>
                <a href="admin_view_project.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Projects
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="project-details">
                            <div class="project-detail-label">Project Name:</div>
                            <div class="project-detail-value"><?php echo htmlspecialchars($project['project_name']); ?></div>

                            <div class="project-detail-label">Project Type:</div>
                            <div class="project-detail-value"><?php echo htmlspecialchars($project['project_type']); ?></div>

                            <div class="project-detail-label">Classification:</div>
                            <div class="project-detail-value"><?php echo htmlspecialchars($project['classification'] ?? 'N/A'); ?></div>

                            <div class="project-detail-label">Project Category:</div>
                            <div class="project-detail-value"><?php echo htmlspecialchars($project['project_category'] ?? 'N/A'); ?></div>

                            <div class="project-detail-label">Difficulty Level:</div>
                            <div class="project-detail-value">
                                <?php if ($project['difficulty_level']) : ?>
                                    <span class="badge bg-info"><?php echo ucfirst($project['difficulty_level']); ?></span>
                                <?php else : ?>
                                    N/A
                                <?php endif; ?>
                            </div>

                            <div class="project-detail-label">Development Time:</div>
                            <div class="project-detail-value"><?php echo htmlspecialchars($project['development_time'] ?? 'N/A'); ?></div>

                            <div class="project-detail-label">Team Size:</div>
                            <div class="project-detail-value"><?php echo htmlspecialchars($project['team_size'] ?? 'N/A'); ?></div>

                            <div class="project-detail-label">Language:</div>
                            <div class="project-detail-value"><?php echo htmlspecialchars($project['language']); ?></div>

                            <div class="project-detail-label">Submitted By:</div>
                            <div class="project-detail-value">User #<?php echo htmlspecialchars($project['user_id']); ?></div>

                            <div class="project-detail-label">Submission Date:</div>
                            <div class="project-detail-value"><?php echo date('F j, Y, g:i a', strtotime($project['submission_date'])); ?></div>

                            <div class="project-detail-label">Status:</div>
                            <div class="project-detail-value">
                            <span class="badge bg-<?php echo $project['status'] == 'pending' ? 'warning' : ($project['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                            </div>

                            <?php if ($project['project_license']) : ?>
                                <div class="project-detail-label">License:</div>
                                <div class="project-detail-value"><?php echo htmlspecialchars($project['project_license']); ?></div>
                            <?php endif; ?>

                            <?php if ($project['contact_email']) : ?>
                                <div class="project-detail-label">Contact Email:</div>
                                <div class="project-detail-value">
                                    <a href="mailto:<?php echo htmlspecialchars($project['contact_email']); ?>">
                                        <?php echo htmlspecialchars($project['contact_email']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="project-detail-label">Description:</div>
                        <div class="project-detail-value"><?php echo nl2br(htmlspecialchars($project['description'])); ?></div>

                        <?php if ($project['target_audience']) : ?>
                            <div class="project-detail-label">Target Audience:</div>
                            <div class="project-detail-value"><?php echo nl2br(htmlspecialchars($project['target_audience'])); ?></div>
                        <?php endif; ?>

                        <?php if ($project['project_goals']) : ?>
                            <div class="project-detail-label">Project Goals:</div>
                            <div class="project-detail-value"><?php echo nl2br(htmlspecialchars($project['project_goals'])); ?></div>
                        <?php endif; ?>

                        <?php if ($project['challenges_faced']) : ?>
                            <div class="project-detail-label">Challenges Faced:</div>
                            <div class="project-detail-value"><?php echo nl2br(htmlspecialchars($project['challenges_faced'])); ?></div>
                        <?php endif; ?>

                        <?php if ($project['future_enhancements']) : ?>
                            <div class="project-detail-label">Future Enhancements:</div>
                            <div class="project-detail-value"><?php echo nl2br(htmlspecialchars($project['future_enhancements'])); ?></div>
                        <?php endif; ?>

                        <?php if ($project['keywords']) : ?>
                            <div class="project-detail-label">Keywords:</div>
                            <div class="project-detail-value">
                                <?php
                                $keywords = explode(',', $project['keywords']);
                                foreach ($keywords as $keyword) :
                                    ?>
                                    <span class="badge bg-light text-dark me-1"><?php echo trim(htmlspecialchars($keyword)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($project['github_repo']) : ?>
                            <div class="project-detail-label">GitHub Repository:</div>
                            <div class="project-detail-value mb-3">
                                <a href="<?php echo htmlspecialchars($project['github_repo']); ?>" class="btn btn-sm btn-outline-dark" target="_blank">
                                    <i class="bi bi-github me-1"></i> View Repository
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($project['live_demo_url']) : ?>
                            <div class="project-detail-label">Live Demo:</div>
                            <div class="project-detail-value mb-3">
                                <a href="<?php echo htmlspecialchars($project['live_demo_url']); ?>" class="btn btn-sm btn-outline-success" target="_blank">
                                    <i class="bi bi-globe me-1"></i> View Live Demo
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($project['social_links']) : ?>
                            <div class="project-detail-label">Social Links:</div>
                            <div class="project-detail-value mb-3">
                                <?php
                                $social_links = json_decode($project['social_links'], true);
                                if ($social_links && is_array($social_links)) :
                                    foreach ($social_links as $platform => $url) :
                                        ?>
                                        <a href="<?php echo htmlspecialchars($url); ?>" class="btn btn-sm btn-outline-info me-1" target="_blank">
                                            <i class="bi bi-<?php echo strtolower($platform); ?> me-1"></i> <?php echo ucfirst($platform); ?>
                                        </a>
                                        <?php
                                    endforeach;
                                else :
                                    echo nl2br(htmlspecialchars($project['social_links']));
                                endif;
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['image_path'])) : ?>
                            <div class="project-detail-label">Project Image:</div>
                            <div class="project-detail-value">
                                <img src="<?php echo htmlspecialchars($project['image_path']); ?>" alt="Project Image" class="img-fluid mb-3" style="max-height: 200px;">
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['video_path'])) : ?>
                            <div class="project-detail-label">Project Video:</div>
                            <div class="project-detail-value mb-3">
                                <a href="<?php echo htmlspecialchars($project['video_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-play-circle me-1"></i> View Video
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['code_file_path'])) : ?>
                            <div class="project-detail-label">Code Files:</div>
                            <div class="project-detail-value mb-3">
                                <a href="<?php echo htmlspecialchars($project['code_file_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-code-slash me-1"></i> Download Code
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['instruction_file_path'])) : ?>
                            <div class="project-detail-label">Instructions:</div>
                            <div class="project-detail-value mb-3">
                                <a href="<?php echo htmlspecialchars($project['instruction_file_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-file-text me-1"></i> View Instructions
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['presentation_file_path'])) : ?>
                            <div class="project-detail-label">Presentation:</div>
                            <div class="project-detail-value mb-3">
                                <a href="<?php echo htmlspecialchars($project['presentation_file_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-file-slides me-1"></i> View Presentation
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['additional_files_path'])) : ?>
                            <div class="project-detail-label">Additional Files:</div>
                            <div class="project-detail-value mb-3">
                                <a href="<?php echo htmlspecialchars($project['additional_files_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-file-earmark me-1"></i> Download Files
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($project['status'] == 'pending') : ?>
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
    <?php else : ?>
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

        <!-- Enhanced Filter Bar -->
        <div class="filter-bar">
            <form action="admin_view_project.php" method="get" class="row g-3">
                <input type="hidden" name="view" value="<?php echo $view_mode; ?>">

                <!-- Search -->
                <div class="col-md-4">
                    <label for="search" class="form-label">Search Projects</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search"
                               placeholder="Search by name, description, keywords..."
                               value="<?php echo htmlspecialchars($search_filter); ?>">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Status Filter -->
                <?php if ($view_mode == 'projects') : ?>
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

                <!-- Project Type Filter -->
                <div class="col-md-2">
                    <label for="type" class="form-label">Project Type</label>
                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                        <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <?php foreach ($project_types as $type) : ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $type_filter == $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" onchange="this.form.submit()">
                        <option value="all" <?php echo $category_filter == 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($project_categories as $category) : ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Difficulty Filter -->
                <div class="col-md-2">
                    <label for="difficulty" class="form-label">Difficulty</label>
                    <select class="form-select" id="difficulty" name="difficulty" onchange="this.form.submit()">
                        <option value="all" <?php echo $difficulty_filter == 'all' ? 'selected' : ''; ?>>All Levels</option>
                        <?php foreach ($difficulty_levels as $difficulty) : ?>
                            <option value="<?php echo htmlspecialchars($difficulty); ?>" <?php echo $difficulty_filter == $difficulty ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($difficulty)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Development Time Filter -->
                <div class="col-md-2">
                    <label for="dev_time" class="form-label">Dev Time</label>
                    <select class="form-select" id="dev_time" name="dev_time" onchange="this.form.submit()">
                        <option value="all" <?php echo $dev_time_filter == 'all' ? 'selected' : ''; ?>>All Times</option>
                        <?php foreach ($development_times as $dev_time) : ?>
                            <option value="<?php echo htmlspecialchars($dev_time); ?>" <?php echo $dev_time_filter == $dev_time ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dev_time); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Team Size Filter -->
                <div class="col-md-2">
                    <label for="team_size" class="form-label">Team Size</label>
                    <select class="form-select" id="team_size" name="team_size" onchange="this.form.submit()">
                        <option value="all" <?php echo $team_size_filter == 'all' ? 'selected' : ''; ?>>All Sizes</option>
                        <?php foreach ($team_sizes as $team_size) : ?>
                            <option value="<?php echo htmlspecialchars($team_size); ?>" <?php echo $team_size_filter == $team_size ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team_size); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Clear Filters -->
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
                <?php if ($projects_result->num_rows > 0) : ?>
                    <div class="table-responsive">
                        <table class="table table-hover project-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Project Name</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Difficulty</th>
                                <th>Dev Time</th>
                                <th>Team Size</th>
                                <?php if ($view_mode == 'projects') : ?>
                                    <th>Status</th>
                                <?php endif; ?>
                                <th>Submission Date</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $count = $offset + 1;
                            while ($project = $projects_result->fetch_assoc()) :
                                // Determine project type class for styling
                                $type_class = '';
                                switch (strtolower($project['project_type'])) {
                                    case 'web':
                                        $type_class = 'project-type-web';
                                        break;
                                    case 'mobile':
                                        $type_class = 'project-type-mobile';
                                        break;
                                    case 'desktop':
                                        $type_class = 'project-type-desktop';
                                        break;
                                    case 'game':
                                        $type_class = 'project-type-game';
                                        break;
                                    case 'ai':
                                        $type_class = 'project-type-ai';
                                        break;
                                    default:
                                        $type_class = 'project-type-other';
                                        break;
                                }

                                // Determine status badge class
                                $status_class = '';
                                if (isset($project['status'])) {
                                    switch ($project['status']) {
                                        case 'pending':
                                            $status_class = 'badge-pending';
                                            break;
                                        case 'approved':
                                            $status_class = 'badge-approved';
                                            break;
                                        case 'rejected':
                                            $status_class = 'badge-rejected';
                                            break;
                                    }
                                }

                                // Determine difficulty badge class
                                $difficulty_class = '';
                                if (isset($project['difficulty_level'])) {
                                    switch ($project['difficulty_level']) {
                                        case 'beginner':
                                            $difficulty_class = 'bg-success';
                                            break;
                                        case 'intermediate':
                                            $difficulty_class = 'bg-warning';
                                            break;
                                        case 'advanced':
                                            $difficulty_class = 'bg-danger';
                                            break;
                                        case 'expert':
                                            $difficulty_class = 'bg-dark';
                                            break;
                                        default:
                                            $difficulty_class = 'bg-secondary';
                                            break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td>
                                        <div class="project-info">
                                            <span class="project-title"><?php echo htmlspecialchars($project['project_name']); ?></span>
                                            <?php if ($project['github_repo']) : ?>
                                                <a href="<?php echo htmlspecialchars($project['github_repo']); ?>" target="_blank" class="text-muted ms-1">
                                                    <i class="bi bi-github"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($project['live_demo_url']) : ?>
                                                <a href="<?php echo htmlspecialchars($project['live_demo_url']); ?>" target="_blank" class="text-muted ms-1">
                                                    <i class="bi bi-globe"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="<?php echo $type_class; ?>"><?php echo htmlspecialchars($project['project_type']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($project['classification'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($project['difficulty_level']) : ?>
                                            <span class="badge <?php echo $difficulty_class; ?> difficulty-badge">
                                            <?php echo ucfirst($project['difficulty_level']); ?>
                                    </span>
                                        <?php else : ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($project['development_time'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo htmlspecialchars($project['team_size'] ?? 'N/A'); ?></small>
                                    </td>
                                    <?php if ($view_mode == 'projects') : ?>
                                        <td>
                                    <span class="badge rounded-pill project-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($project['submission_date'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="admin_view_project.php?action=view&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($view_mode == 'projects' && $project['status'] == 'pending') : ?>
                                                <a href="admin_view_project.php?action=approve&id=<?php echo $project['id']; ?>"
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('Are you sure you want to approve this project?')"
                                                   title="Approve Project">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectModal<?php echo $project['id']; ?>"
                                                        title="Reject Project">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>

                                                <!-- Reject Modal for each project -->
                                                <div class="modal fade" id="rejectModal<?php echo $project['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Reject Project: <?php echo htmlspecialchars($project['project_name']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="admin_view_project.php" method="post">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="rejection_reason<?php echo $project['id']; ?>" class="form-label">Rejection Reason</label>
                                                                        <textarea class="form-control"
                                                                                  id="rejection_reason<?php echo $project['id']; ?>"
                                                                                  name="rejection_reason"
                                                                                  rows="4"
                                                                                  required
                                                                                  placeholder="Please provide a detailed reason for rejecting this project..."></textarea>
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
                    <?php if ($total_pages > 1) : ?>
                        <nav aria-label="Projects pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="admin_view_project.php?view=<?php echo $view_mode; ?>&page=<?php echo $current_page - 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&difficulty=<?php echo $difficulty_filter; ?>&dev_time=<?php echo $dev_time_filter; ?>&team_size=<?php echo $team_size_filter; ?>&search=<?php echo urlencode($search_filter); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <?php
                                // Smart pagination - show first, last, and pages around current
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                if ($start_page > 1) :
                                    ?>
                                    <li class="page-item">
                                        <a class="page-link" href="admin_view_project.php?view=<?php echo $view_mode; ?>&page=1&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&difficulty=<?php echo $difficulty_filter; ?>&dev_time=<?php echo $dev_time_filter; ?>&team_size=<?php echo $team_size_filter; ?>&search=<?php echo urlencode($search_filter); ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2) : ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start_page; $i <= $end_page; $i++) : ?>
                                    <li class="page-item <?php echo $current_page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="admin_view_project.php?view=<?php echo $view_mode; ?>&page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&difficulty=<?php echo $difficulty_filter; ?>&dev_time=<?php echo $dev_time_filter; ?>&team_size=<?php echo $team_size_filter; ?>&search=<?php echo urlencode($search_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages) : ?>
                                    <?php if ($end_page < $total_pages - 1) : ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="admin_view_project.php?view=<?php echo $view_mode; ?>&page=<?php echo $total_pages; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&difficulty=<?php echo $difficulty_filter; ?>&dev_time=<?php echo $dev_time_filter; ?>&team_size=<?php echo $team_size_filter; ?>&search=<?php echo urlencode($search_filter); ?>"><?php echo $total_pages; ?></a>
                                    </li>
                                <?php endif; ?>

                                <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="admin_view_project.php?view=<?php echo $view_mode; ?>&page=<?php echo $current_page + 1; ?>&status=<?php echo $status_filter; ?>&type=<?php echo $type_filter; ?>&category=<?php echo $category_filter; ?>&difficulty=<?php echo $difficulty_filter; ?>&dev_time=<?php echo $dev_time_filter; ?>&team_size=<?php echo $team_size_filter; ?>&search=<?php echo urlencode($search_filter); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>

                            <div class="text-center mt-2">
                                <small class="text-muted">
                                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> projects
                                </small>
                            </div>
                        </nav>
                    <?php endif; ?>

                <?php else : ?>
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i> No projects found matching your criteria.
                        <div class="mt-2">
                            <a href="admin_view_project.php?view=<?php echo $view_mode; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-clockwise me-1"></i> Reset Filters
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/admin_view_project.js"></script>
</body>
</html>