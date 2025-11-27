<?php
require_once __DIR__ . '/includes/security_init.php';
require_once __DIR__ . '/../includes/html_helpers.php';
// Start output buffering to prevent header errors
ob_start();

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Production-safe error reporting (after session)
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

// Database connection
include "../Login/Login/db.php";
include "project_notification.php";

// Site name
$site_name = "IdeaNest Admin";
$current_page = "dashboard";

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to admin login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

// Set admin_id for compatibility with new pages
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;
}

$user_name = "Admin";

// Handle project actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $project_id = (int)$_GET['id'];
    $action = $_GET['action'];

    // View project - Updated to include all columns
    if ($action == 'view') {
        // Get project details with all columns
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
        // Get project details for rejection modal
        $query = "SELECT * FROM projects WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $project = $result->fetch_assoc();
        $show_rejection_form = true;
        $reject_project_id = $project_id;
    }
}

// Handle rejection form submission
if (isset($_POST['reject_submit'])) {
    $project_id = (int)$_POST['project_id'];
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    rejectProject($project_id, $rejection_reason, $conn);
}

// Enhanced function to approve a project with all columns
function approveProject($project_id, $conn)
{
    // Get project details with all columns
    $query = "SELECT * FROM projects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();

        // Check if approval_date column exists and insert accordingly
        $check_column = $conn->query("SHOW COLUMNS FROM admin_approved_projects LIKE 'approval_date'");
        if ($check_column->num_rows > 0) {
            $approve_query = "INSERT INTO admin_approved_projects (
                user_id, project_name, project_type, classification, project_category,
                difficulty_level, development_time, team_size, target_audience, project_goals,
                challenges_faced, future_enhancements, github_repo, live_demo_url, project_license,
                keywords, contact_email, social_links, description, language, 
                image_path, video_path, code_file_path, instruction_file_path,
                presentation_file_path, additional_files_path, submission_date, status, approval_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        } else {
            $approve_query = "INSERT INTO admin_approved_projects (
                user_id, project_name, project_type, classification, project_category,
                difficulty_level, development_time, team_size, target_audience, project_goals,
                challenges_faced, future_enhancements, github_repo, live_demo_url, project_license,
                keywords, contact_email, social_links, description, language, 
                image_path, video_path, code_file_path, instruction_file_path,
                presentation_file_path, additional_files_path, submission_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }

        $approve_stmt = $conn->prepare($approve_query);
        $status = 'approved';
        $approve_stmt->bind_param(
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
            $status
        );

        $approve_stmt->execute();

        // Update status in original projects table
        $update_query = "UPDATE projects SET status = 'approved' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $project_id);
        $update_stmt->execute();

        // Send email notification
        $email_options = [
                'subject' => 'Congratulations! Your Project "' . $project['project_name'] . '" Has Been Approved',
                'custom_text' => 'As an approved project creator, you now have access to our developer resources and community forums where you can showcase your work and get feedback from fellow creators.',
                'include_project_details' => true
        ];

        sendProjectStatusEmail($project_id, 'approved', '', null, $email_options);

        // Redirect back to admin with success message
        header("Location: admin.php?message=Project approved successfully");
        exit;
    } else {
        header("Location: admin.php?error=Project not found");
        exit;
    }
}

// Enhanced function to reject a project with all columns
function rejectProject($project_id, $rejection_reason, $conn)
{
    // Get project details with all columns
    $query = "SELECT * FROM projects WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();

        // Insert into rejection table with all columns
        $reject_query = "INSERT INTO denial_projects (
            user_id, project_name, project_type, classification, project_category,
            difficulty_level, development_time, team_size, target_audience, project_goals,
            challenges_faced, future_enhancements, github_repo, live_demo_url, project_license,
            keywords, contact_email, social_links, description, language,
            image_path, video_path, code_file_path, instruction_file_path,
            presentation_file_path, additional_files_path, submission_date, 
            status, rejection_date, rejection_reason
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $reject_stmt = $conn->prepare($reject_query);
        $status = 'rejected';
        $reject_stmt->bind_param(
            "sssssssssssssssssssssssssssss",
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
            $status,
            $rejection_reason
        );

        $reject_stmt->execute();

        // Update status in original projects table
        $update_query = "UPDATE projects SET status = 'rejected' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $project_id);
        $update_stmt->execute();

        // Send email notification
        $email_options = [
                'subject' => 'Important Update About Your Project "' . $project['project_name'] . '"',
                'custom_text' => 'Our team is always available to help you improve your project. Feel free to reach out if you need guidance on addressing the issues mentioned.',
                'include_project_details' => true
        ];

        sendProjectStatusEmail($project_id, 'rejected', $rejection_reason, null, $email_options);

        // Redirect back to admin with success message
        header("Location: admin.php?message=Project rejected successfully");
        exit;
    } else {
        header("Location: admin.php?error=Project not found");
        exit;
    }
}

// Get time range filter from URL
$selected_time_range = isset($_GET['time_range']) ? $_GET['time_range'] : "Last 7 Days";
$time_ranges = ["Last 7 Days", "Last 30 Days", "Last 3 Months", "Last Year"];

// Get category filter from URL
$selected_category_range = isset($_GET['category_range']) ? $_GET['category_range'] : "All Time";
$category_ranges = ["All Time", "This Month", "This Year"];

// Initialize date values based on selected time range
$start_date = null;
$end_date = date('Y-m-d');

switch ($selected_time_range) {
    case "Last 7 Days":
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $project_chart_labels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        break;
    case "Last 30 Days":
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $project_chart_labels = [];
        for ($i = 29; $i >= 0; $i -= 5) {
            $project_chart_labels[] = date('M d', strtotime("-$i days"));
        }
        break;
    case "Last 3 Months":
        $start_date = date('Y-m-d', strtotime('-3 months'));
        $project_chart_labels = [];
        for ($i = 3; $i >= 0; $i--) {
            $project_chart_labels[] = date('M', strtotime("-$i months"));
        }
        break;
    case "Last Year":
        $start_date = date('Y-m-d', strtotime('-1 year'));
        $project_chart_labels = [];
        for ($i = 11; $i >= 0; $i--) {
            $project_chart_labels[] = date('M', strtotime("-$i months"));
        }
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $project_chart_labels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        break;
}

// Process category range filter
$category_where_clause = "";
switch ($selected_category_range) {
    case "This Month":
        $category_where_clause = " WHERE DATE_FORMAT(submission_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
        break;
    case "This Year":
        $category_where_clause = " WHERE DATE_FORMAT(submission_date, '%Y') = DATE_FORMAT(NOW(), '%Y')";
        break;
    default: // "All Time"
        $category_where_clause = "";
        break;
}

// Get project statistics
$total_projects_query = "SELECT 
    (SELECT COUNT(*) FROM projects) as all_projects,
    (SELECT COUNT(*) FROM admin_approved_projects) as approved_projects,
    (SELECT COUNT(*) FROM projects WHERE status = 'pending') as pending_projects,
    (SELECT COUNT(*) FROM denial_projects) as denied_projects";

$total_projects_result = $conn->query($total_projects_query);
if (!$total_projects_result) {
    die("Error in query: " . $conn->error);
}
$counts = $total_projects_result->fetch_assoc();

$all_projects = $counts['all_projects'];
$approved_projects = $counts['approved_projects'];
$pending_projects = $counts['pending_projects'];
$denied_projects = $counts['denied_projects'];
$total_projects = $approved_projects + $pending_projects + $denied_projects;

// Statistical growth values (these could be calculated from actual data in a real app)
$total_projects_percentage = 75;
$total_projects_growth = "12%";
$approved_projects_percentage = 60;
$approved_projects_growth = "8%";
$pending_projects_percentage = 40;
$pending_projects_growth = "5%";
$rejected_projects_percentage = 20;
$rejected_projects_growth = "3%";

// Initialize data arrays for chart
$submitted_projects_data = [];
$approved_projects_data = [];
$rejected_projects_data = [];
$total_projects_data = [];

// Get data for the selected time range
if ($selected_time_range == "Last 7 Days") {
    // Daily data for last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));

        // Submitted projects (cumulative up to this date)
        $submitted_query = "SELECT COUNT(*) as count FROM projects WHERE DATE(submission_date) <= ?";
        $submitted_stmt = $conn->prepare($submitted_query);
        $submitted_stmt->bind_param("s", $date);
        $submitted_stmt->execute();
        $submitted_result = $submitted_stmt->get_result();
        $submitted_projects_data[] = $submitted_result->fetch_assoc()['count'];

        // Approved projects from admin_approved_projects table (cumulative)
        $approved_query = "SELECT COUNT(*) as count FROM admin_approved_projects WHERE DATE(submission_date) <= ?";
        $approved_stmt = $conn->prepare($approved_query);
        $approved_stmt->bind_param("s", $date);
        $approved_stmt->execute();
        $approved_result = $approved_stmt->get_result();
        $approved_projects_data[] = $approved_result->fetch_assoc()['count'];

        // Rejected projects (cumulative)
        $rejected_query = "SELECT COUNT(*) as count FROM denial_projects WHERE DATE(COALESCE(rejection_date, submission_date)) <= ?";
        $rejected_stmt = $conn->prepare($rejected_query);
        $rejected_stmt->bind_param("s", $date);
        $rejected_stmt->execute();
        $rejected_result = $rejected_stmt->get_result();
        $rejected_projects_data[] = $rejected_result->fetch_assoc()['count'];
        
        // Total projects from projects table (cumulative)
        $total_query = "SELECT COUNT(*) as count FROM projects WHERE DATE(submission_date) <= ?";
        $total_stmt = $conn->prepare($total_query);
        $total_stmt->bind_param("s", $date);
        $total_stmt->execute();
        $total_result = $total_stmt->get_result();
        $total_projects_data[] = $total_result->fetch_assoc()['count'];
    }
} elseif ($selected_time_range == "Last 30 Days") {
    // Weekly data for last 30 days
    for ($i = 0; $i < 6; $i++) {
        $start = date('Y-m-d', strtotime("-" . (30 - $i * 5) . " days"));
        $end = date('Y-m-d', strtotime("-" . (26 - $i * 5) . " days"));

        // Submitted projects
        $submitted_query = "SELECT COUNT(*) as count FROM projects WHERE DATE(submission_date) BETWEEN ? AND ?";
        $submitted_stmt = $conn->prepare($submitted_query);
        $submitted_stmt->bind_param("ss", $start, $end);
        $submitted_stmt->execute();
        $submitted_result = $submitted_stmt->get_result();
        $submitted_projects_data[] = $submitted_result->fetch_assoc()['count'];

        // Approved projects from admin_approved_projects table
        $approved_query = "SELECT COUNT(*) as count FROM admin_approved_projects WHERE DATE(submission_date) BETWEEN ? AND ?";
        $approved_stmt = $conn->prepare($approved_query);
        $approved_stmt->bind_param("ss", $start, $end);
        $approved_stmt->execute();
        $approved_result = $approved_stmt->get_result();
        $approved_projects_data[] = $approved_result->fetch_assoc()['count'];

        // Rejected projects
        $rejected_query = "SELECT COUNT(*) as count FROM denial_projects WHERE DATE(COALESCE(rejection_date, submission_date)) BETWEEN ? AND ?";
        $rejected_stmt = $conn->prepare($rejected_query);
        $rejected_stmt->bind_param("ss", $start, $end);
        $rejected_stmt->execute();
        $rejected_result = $rejected_stmt->get_result();
        $rejected_projects_data[] = $rejected_result->fetch_assoc()['count'];
        
        // Total projects from projects table
        $total_query = "SELECT COUNT(*) as count FROM projects WHERE DATE(submission_date) BETWEEN ? AND ?";
        $total_stmt = $conn->prepare($total_query);
        $total_stmt->bind_param("ss", $start, $end);
        $total_stmt->execute();
        $total_result = $total_stmt->get_result();
        $total_projects_data[] = $total_result->fetch_assoc()['count'];
    }
} elseif ($selected_time_range == "Last 3 Months") {
    // Monthly data for last 3 months
    for ($i = 3; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));

        // Submitted projects
        $submitted_query = "SELECT COUNT(*) as count FROM projects WHERE DATE(submission_date) BETWEEN ? AND ?";
        $submitted_stmt = $conn->prepare($submitted_query);
        $submitted_stmt->bind_param("ss", $month_start, $month_end);
        $submitted_stmt->execute();
        $submitted_result = $submitted_stmt->get_result();
        $submitted_projects_data[] = $submitted_result->fetch_assoc()['count'];

        // Approved projects - check if approval_date column exists
        $check_column = $conn->query("SHOW COLUMNS FROM admin_approved_projects LIKE 'approval_date'");
        if ($check_column->num_rows > 0) {
            $approved_query = "SELECT COUNT(*) as count FROM admin_approved_projects WHERE DATE(COALESCE(approval_date, submission_date)) BETWEEN ? AND ?";
        } else {
            $approved_query = "SELECT COUNT(*) as count FROM admin_approved_projects WHERE DATE(submission_date) BETWEEN ? AND ?";
        }
        $approved_stmt = $conn->prepare($approved_query);
        $approved_stmt->bind_param("ss", $month_start, $month_end);
        $approved_stmt->execute();
        $approved_result = $approved_stmt->get_result();
        $approved_projects_data[] = $approved_result->fetch_assoc()['count'];

        // Rejected projects
        $rejected_query = "SELECT COUNT(*) as count FROM denial_projects WHERE DATE(rejection_date) BETWEEN ? AND ?";
        $rejected_stmt = $conn->prepare($rejected_query);
        $rejected_stmt->bind_param("ss", $month_start, $month_end);
        $rejected_stmt->execute();
        $rejected_result = $rejected_stmt->get_result();
        $rejected_projects_data[] = $rejected_result->fetch_assoc()['count'];
        
        // Calculate total projects for this month
        $total_projects_data[] = end($submitted_projects_data) + end($approved_projects_data) + end($rejected_projects_data);
    }
} else { // Last Year
    // Monthly data for last year
    for ($i = 11; $i >= 0; $i--) {
        $month_start = date('Y-m-01', strtotime("-$i months"));
        $month_end = date('Y-m-t', strtotime("-$i months"));

        // Submitted projects
        $submitted_query = "SELECT COUNT(*) as count FROM projects WHERE DATE(submission_date) BETWEEN ? AND ?";
        $submitted_stmt = $conn->prepare($submitted_query);
        $submitted_stmt->bind_param("ss", $month_start, $month_end);
        $submitted_stmt->execute();
        $submitted_result = $submitted_stmt->get_result();
        $submitted_projects_data[] = $submitted_result->fetch_assoc()['count'];

        // Approved projects from admin_approved_projects table
        $approved_query = "SELECT COUNT(*) as count FROM admin_approved_projects WHERE DATE(submission_date) BETWEEN ? AND ?";
        $approved_stmt = $conn->prepare($approved_query);
        $approved_stmt->bind_param("ss", $month_start, $month_end);
        $approved_stmt->execute();
        $approved_result = $approved_stmt->get_result();
        $approved_projects_data[] = $approved_result->fetch_assoc()['count'];

        // Rejected projects
        $rejected_query = "SELECT COUNT(*) as count FROM denial_projects WHERE DATE(rejection_date) BETWEEN ? AND ?";
        $rejected_stmt = $conn->prepare($rejected_query);
        $rejected_stmt->bind_param("ss", $month_start, $month_end);
        $rejected_stmt->execute();
        $rejected_result = $rejected_stmt->get_result();
        $rejected_projects_data[] = $rejected_result->fetch_assoc()['count'];
        
        // Total projects from projects table
        $total_query = "SELECT COUNT(*) as count FROM projects WHERE DATE(submission_date) BETWEEN ? AND ?";
        $total_stmt = $conn->prepare($total_query);
        $total_stmt->bind_param("ss", $month_start, $month_end);
        $total_stmt->execute();
        $total_result = $total_stmt->get_result();
        $total_projects_data[] = $total_result->fetch_assoc()['count'];
    }
}

// Enhanced category data with multiple grouping options
$category_query = "SELECT classification, COUNT(*) as count FROM admin_approved_projects" . $category_where_clause . " GROUP BY classification";
$category_result = $conn->query($category_query);
if (!$category_result) {
    die("Error in category query: " . $conn->error);
}

$category_labels = [];
$category_data = [];
$category_colors = ['#4361ee', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#8b5cf6', '#06b6d4', '#84cc16'];

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

// Enhanced difficulty level statistics
$difficulty_query = "SELECT difficulty_level, COUNT(*) as count FROM projects WHERE difficulty_level IS NOT NULL GROUP BY difficulty_level";
$difficulty_result = $conn->query($difficulty_query);
$difficulty_stats = [];
if ($difficulty_result) {
    while ($row = $difficulty_result->fetch_assoc()) {
        $difficulty_stats[$row['difficulty_level']] = $row['count'];
    }
}

// Recent activities
$recent_activities = [];
$all_activities = [];

// Get recent project submissions with enhanced details
$recent_submissions_query = "SELECT id, project_name, user_id, submission_date, difficulty_level, project_category FROM projects ORDER BY submission_date DESC LIMIT 3";
$recent_submissions_result = $conn->query($recent_submissions_query);
if (!$recent_submissions_result) {
    die("Error in recent submissions query: " . $conn->error);
}

while ($row = $recent_submissions_result->fetch_assoc()) {
    $time_ago = time_elapsed_string($row['submission_date']);
    $difficulty = $row['difficulty_level'] ? ' (' . ucfirst($row['difficulty_level']) . ')' : '';
    $category = $row['project_category'] ? ' - ' . $row['project_category'] : '';
    $activity = [
            'type' => 'primary',
            'title' => 'New Project Submitted',
            'description' => 'Project "' . $row['project_name'] . '"' . $difficulty . $category . ' was submitted by User #' . $row['user_id'],
            'time_ago' => $time_ago,
            'date' => $row['submission_date']
    ];
    $recent_activities[] = $activity;
    $all_activities[] = $activity;
}

// Get recent approvals with enhanced details
$recent_approvals_query = "SELECT project_name, submission_date, difficulty_level, project_category FROM admin_approved_projects ORDER BY submission_date DESC LIMIT 2";
$recent_approvals_result = $conn->query($recent_approvals_query);
if (!$recent_approvals_result) {
    die("Error in recent approvals query: " . $conn->error);
}

while ($row = $recent_approvals_result->fetch_assoc()) {
    $time_ago = time_elapsed_string($row['submission_date']);
    $difficulty = $row['difficulty_level'] ? ' (' . ucfirst($row['difficulty_level']) . ')' : '';
    $category = $row['project_category'] ? ' - ' . $row['project_category'] : '';
    $activity = [
            'type' => 'success',
            'title' => 'Project Approved',
            'description' => 'Project "' . $row['project_name'] . '"' . $difficulty . $category . ' was approved',
            'time_ago' => $time_ago,
            'date' => $row['submission_date']
    ];
    $recent_activities[] = $activity;
    $all_activities[] = $activity;
}

// Get recent rejections
$recent_rejections_query = "SELECT project_name, rejection_date, rejection_reason FROM denial_projects ORDER BY rejection_date DESC LIMIT 2";
$recent_rejections_result = $conn->query($recent_rejections_query);
if (!$recent_rejections_result) {
    die("Error in recent rejections query: " . $conn->error);
}

while ($row = $recent_rejections_result->fetch_assoc()) {
    $time_ago = time_elapsed_string($row['rejection_date']);
    $activity = [
            'type' => 'danger',
            'title' => 'Project Rejected',
            'description' => 'Project "' . $row['project_name'] . '" was rejected. Reason: ' . $row['rejection_reason'],
            'time_ago' => $time_ago,
            'date' => $row['rejection_date']
    ];
    $recent_activities[] = $activity;
    $all_activities[] = $activity;
}

// Get all activities for "Show More" functionality (enhanced)
$all_submissions_query = "SELECT id, project_name, user_id, submission_date, difficulty_level, project_category FROM projects ORDER BY submission_date DESC";
$all_submissions_result = $conn->query($all_submissions_query);
if ($all_submissions_result) {
    while ($row = $all_submissions_result->fetch_assoc()) {
        $time_ago = time_elapsed_string($row['submission_date']);
        $difficulty = $row['difficulty_level'] ? ' (' . ucfirst($row['difficulty_level']) . ')' : '';
        $category = $row['project_category'] ? ' - ' . $row['project_category'] : '';
        $all_activities[] = [
                'type' => 'primary',
                'title' => 'New Project Submitted',
                'description' => 'Project "' . $row['project_name'] . '"' . $difficulty . $category . ' was submitted by User #' . $row['user_id'],
                'time_ago' => $time_ago,
                'date' => $row['submission_date']
        ];
    }
}

$all_approvals_query = "SELECT project_name, submission_date, difficulty_level, project_category FROM admin_approved_projects ORDER BY submission_date DESC";
$all_approvals_result = $conn->query($all_approvals_query);
if ($all_approvals_result) {
    while ($row = $all_approvals_result->fetch_assoc()) {
        $time_ago = time_elapsed_string($row['submission_date']);
        $difficulty = $row['difficulty_level'] ? ' (' . ucfirst($row['difficulty_level']) . ')' : '';
        $category = $row['project_category'] ? ' - ' . $row['project_category'] : '';
        $all_activities[] = [
                'type' => 'success',
                'title' => 'Project Approved',
                'description' => 'Project "' . $row['project_name'] . '"' . $difficulty . $category . ' was approved',
                'time_ago' => $time_ago,
                'date' => $row['submission_date']
        ];
    }
}

$all_rejections_query = "SELECT project_name, rejection_date, rejection_reason FROM denial_projects ORDER BY rejection_date DESC";
$all_rejections_result = $conn->query($all_rejections_query);
if ($all_rejections_result) {
    while ($row = $all_rejections_result->fetch_assoc()) {
        $time_ago = time_elapsed_string($row['rejection_date']);
        $all_activities[] = [
                'type' => 'danger',
                'title' => 'Project Rejected',
                'description' => 'Project "' . $row['project_name'] . '" was rejected. Reason: ' . $row['rejection_reason'],
                'time_ago' => $time_ago,
                'date' => $row['rejection_date']
        ];
    }
}

// Sort activities by time
usort($recent_activities, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

usort($all_activities, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Check if show more is requested
$show_all_activities = isset($_GET['show_all_activities']) && $_GET['show_all_activities'] == '1';
$activities_to_show = $show_all_activities ? $all_activities : $recent_activities;

// Enhanced pending projects list with more details
$pending_projects_list = [];
$pending_projects_query = "SELECT p.id, p.project_name, p.project_type, p.classification, p.user_id, p.status, 
                          p.difficulty_level, p.project_category, p.development_time, p.team_size
                          FROM projects p 
                          WHERE p.status = 'pending' 
                          ORDER BY p.submission_date DESC 
                          LIMIT 5";
$pending_projects_result = $conn->query($pending_projects_query);
if (!$pending_projects_result) {
    die("Error in pending projects query: " . $conn->error);
}

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
            'icon' => $icon,
            'difficulty' => $row['difficulty_level'],
            'category' => $row['project_category'],
            'dev_time' => $row['development_time'],
            'team_size' => $row['team_size']
    ];
}

$rejected_projects_query = "SELECT COUNT(*) as count FROM denial_projects";
$rejected_projects_result = $conn->query($rejected_projects_query);
if (!$rejected_projects_result) {
    die("Error in rejected projects query: " . $conn->error);
}
$rejected_projects = $rejected_projects_result->fetch_assoc()['count'];

// Initialize variables to prevent undefined variable errors
$show_rejection_form = false;
$reject_project_id = null;

// Helper function to convert MySQL datetime to "time ago" format
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
    );

    $result = [];
    foreach ($string as $k => $v) {
        if ($k == 'w' && $weeks) {
            $result[] = $weeks . ' week' . ($weeks > 1 ? 's' : '');
        } elseif ($k != 'w' && $diff->$k) {
            $result[] = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        }
    }

    if (!$full) {
        $result = array_slice($result, 0, 1);
    }
    return $result ? implode(', ', $result) . ' ago' : 'just now';
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
    <title>IdeaNest Admin - Dashboard</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        /* Enhanced Stats Card Styles */
        .stats-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 25px rgba(0,0,0,0.1);
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

        /* Enhanced Timeline Styles */
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

        /* Enhanced Project Details Styles */
        .project-details {
            margin-bottom: 20px;
        }

        .project-detail-label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
        }

        .project-detail-value {
            margin-bottom: 15px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #4361ee;
        }

        .project-files {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .file-link {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
        }

        .file-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Enhanced Difficulty Badge Styles */
        .difficulty-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .difficulty-beginner { background-color: #d1f2eb; color: #0e6655; }
        .difficulty-intermediate { background-color: #fef3cd; color: #856404; }
        .difficulty-advanced { background-color: #f8d7da; color: #721c24; }
        .difficulty-expert { background-color: #d1ecf1; color: #0c5460; }

        /* Modal styles */
        .modal-backdrop {
            z-index: 1040;
        }

        .modal {
            z-index: 1050;
        }

        /* Enhanced Timeline Styles */
        .activity-timeline {
            position: relative;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }

        .activity-timeline::-webkit-scrollbar {
            width: 6px;
        }

        .activity-timeline::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .activity-timeline::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .activity-timeline::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .activity-item {
            display: flex;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .activity-item:last-child {
            margin-bottom: 0;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .activity-content {
            flex-grow: 1;
        }

        .activity-title {
            font-size: 0.9375rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .activity-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .activity-time {
            font-size: 0.75rem;
            color: #adb5bd;
            display: block;
        }

        /* Read More Button */
        .activity-read-more {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #f1f1f1;
            margin-top: 0.5rem;
        }

        .activity-read-more .btn {
            font-size: 0.875rem;
        }

        /* Additional required styles */
        .stat-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-icon-container {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon {
            font-size: 1.5rem;
        }

        .bg-primary-light {
            background-color: rgba(67, 97, 238, 0.1);
        }

        .bg-success-light {
            background-color: rgba(16, 185, 129, 0.1);
        }

        .bg-warning-light {
            background-color: rgba(245, 158, 11, 0.1);
        }

        .bg-danger-light {
            background-color: rgba(239, 68, 68, 0.1);
        }

        .stat-progress-text {
            font-size: 0.75rem;
            color: #28a745;
            margin-top: 0.25rem;
        }

        /* Enhanced Project Card Styles */
        .project-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #e3e6f0;
        }

        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 25px rgba(0,0,0,0.1);
        }

        .project-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .project-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 8px;
        }

        .project-tag {
            background-color: #f8f9fa;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            color: #495057;
        }

        /* Enhanced Table Styles */
        .enhanced-table {
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        .enhanced-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            border: none;
            padding: 12px 15px;
        }

        .enhanced-table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-top: 1px solid #f1f1f1;
        }

        .enhanced-table tbody tr:hover {
            background-color: #f8f9fa;
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
    <link rel="stylesheet" href="assets/css/loader.css">
    <link rel="stylesheet" href="assets/css/loading.css">
</head>
<body>
<!-- Sidebar -->
<?php include 'sidebar_admin.php'; ?>

<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <button class="btn d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title">Enhanced Dashboard</h1>
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
    <?php if ($message) : ?>
        <div class="alert alert-success alert-banner alert-dismissible fade show" role="alert">
            <?php echo safe_html($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error) : ?>
        <div class="alert alert-danger alert-banner alert-dismissible fade show" role="alert">
            <?php echo safe_html($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($action) && $action == 'view' && isset($project)) : ?>
        <!-- Enhanced Project View Section -->
        <div class="card mb-4 project-card">
            <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-eye me-2"></i>Project Details: <?php echo safe_html($project['project_name']); ?>
                </h5>
                <a href="admin.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="bi bi-info-circle me-2"></i>Basic Information</h6>

                        <div class="project-detail-label">Project Name:</div>
                        <div class="project-detail-value"><?php echo safe_html($project['project_name']); ?></div>

                        <div class="project-detail-label">Project Type:</div>
                        <div class="project-detail-value">
                            <span class="badge bg-primary"><?php echo safe_html($project['project_type']); ?></span>
                        </div>

                        <div class="project-detail-label">Classification:</div>
                        <div class="project-detail-value"><?php echo safe_html($project['classification']); ?></div>

                        <?php if (!empty($project['project_category'])) : ?>
                            <div class="project-detail-label">Project Category:</div>
                            <div class="project-detail-value"><?php echo safe_html($project['project_category']); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($project['difficulty_level'])) : ?>
                            <div class="project-detail-label">Difficulty Level:</div>
                            <div class="project-detail-value">
                                <span class="difficulty-badge difficulty-<?php echo safe_html($project['difficulty_level']); ?>">
                                    <?php echo ucfirst(safe_html($project['difficulty_level'])); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="project-detail-label">Language/Technology:</div>
                        <div class="project-detail-value"><?php echo safe_html($project['language']); ?></div>

                        <?php if (!empty($project['development_time'])) : ?>
                            <div class="project-detail-label">Development Time:</div>
                            <div class="project-detail-value"><?php echo safe_html($project['development_time']); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($project['team_size'])) : ?>
                            <div class="project-detail-label">Team Size:</div>
                            <div class="project-detail-value"><?php echo safe_html($project['team_size']); ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Additional Details -->
                    <div class="col-md-6">
                        <h6 class="text-success mb-3"><i class="bi bi-person-circle me-2"></i>Submission Details</h6>

                        <div class="project-detail-label">Submitted By:</div>
                        <div class="project-detail-value">
                            <?php 
                            // Get user name from register table
                            $user_query = "SELECT name FROM register WHERE id = ?";
                            $user_stmt = $conn->prepare($user_query);
                            $user_stmt->bind_param("i", $project['user_id']);
                            $user_stmt->execute();
                            $user_result = $user_stmt->get_result();
                            $user_data = $user_result->fetch_assoc();
                            $user_stmt->close();
                            echo htmlspecialchars($user_data['name'] ?? 'User #' . $project['user_id']);
                            ?>
                        </div>

                        <div class="project-detail-label">Submission Date:</div>
                        <div class="project-detail-value"><?php echo date('F j, Y, g:i a', strtotime($project['submission_date'])); ?></div>

                        <div class="project-detail-label">Status:</div>
                        <div class="project-detail-value">
                                <span class="badge bg-<?php echo $project['status'] == 'pending' ? 'warning' : ($project['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                    <?php echo ucfirst(safe_html($project['status'])); ?>
                                </span>
                        </div>

                        <?php if (!empty($project['contact_email'])) : ?>
                            <div class="project-detail-label">Contact Email:</div>
                            <div class="project-detail-value">
                                <a href="mailto:<?php echo safe_html($project['contact_email']); ?>" class="text-primary">
                                    <?php echo safe_html($project['contact_email']); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($project['project_license'])) : ?>
                            <div class="project-detail-label">License:</div>
                            <div class="project-detail-value"><?php echo safe_html($project['project_license']); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($project['keywords'])) : ?>
                            <div class="project-detail-label">Keywords:</div>
                            <div class="project-detail-value">
                                <div class="project-tags">
                                    <?php
                                    $keywords = explode(',', $project['keywords']);
                                    foreach ($keywords as $keyword) :
                                        $keyword = trim($keyword);
                                        if (!empty($keyword)) :
                                            ?>
                                            <span class="project-tag"><?php echo safe_html($keyword); ?></span>
                                        <?php endif;
                                    endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Project Description and Goals -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="text-info mb-3"><i class="bi bi-card-text me-2"></i>Project Description & Goals</h6>

                        <div class="project-detail-label">Description:</div>
                        <div class="project-detail-value"><?php echo nl2br(safe_html($project['description'])); ?></div>

                        <?php if (!empty($project['target_audience'])) : ?>
                            <div class="project-detail-label">Target Audience:</div>
                            <div class="project-detail-value"><?php echo nl2br(safe_html($project['target_audience'])); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($project['project_goals'])) : ?>
                            <div class="project-detail-label">Project Goals:</div>
                            <div class="project-detail-value"><?php echo nl2br(safe_html($project['project_goals'])); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($project['challenges_faced'])) : ?>
                            <div class="project-detail-label">Challenges Faced:</div>
                            <div class="project-detail-value"><?php echo nl2br(safe_html($project['challenges_faced'])); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($project['future_enhancements'])) : ?>
                            <div class="project-detail-label">Future Enhancements:</div>
                            <div class="project-detail-value"><?php echo nl2br(safe_html($project['future_enhancements'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Files and Links -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="text-warning mb-3"><i class="bi bi-folder me-2"></i>Files & Resources</h6>

                        <?php if (!empty($project['image_path'])) : ?>
                            <div class="project-detail-label">Project Image:</div>
                            <div class="project-detail-value">
                                <img src="<?php echo safe_html($project['image_path']); ?>" alt="Project Image" class="img-fluid mb-3" style="max-height: 300px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                            </div>
                        <?php endif; ?>

                        <div class="project-files">
                            <?php if (!empty($project['video_path'])) : ?>
                                <a href="<?php echo safe_html($project['video_path']); ?>" class="file-link btn btn-outline-primary" target="_blank">
                                    <i class="bi bi-play-circle"></i> View Video
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($project['code_file_path'])) : ?>
                                <a href="<?php echo safe_html($project['code_file_path']); ?>" class="file-link btn btn-outline-success" target="_blank">
                                    <i class="bi bi-code-slash"></i> Download Code
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($project['instruction_file_path'])) : ?>
                                <a href="<?php echo safe_html($project['instruction_file_path']); ?>" class="file-link btn btn-outline-info" target="_blank">
                                    <i class="bi bi-file-text"></i> View Instructions
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($project['presentation_file_path'])) : ?>
                                <a href="<?php echo safe_html($project['presentation_file_path']); ?>" class="file-link btn btn-outline-warning" target="_blank">
                                    <i class="bi bi-file-earmark-slides"></i> View Presentation
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($project['additional_files_path'])) : ?>
                                <a href="<?php echo safe_html($project['additional_files_path']); ?>" class="file-link btn btn-outline-secondary" target="_blank">
                                    <i class="bi bi-file-earmark-zip"></i> Additional Files
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($project['github_repo'])) : ?>
                                <a href="<?php echo safe_html($project['github_repo']); ?>" class="file-link btn btn-outline-dark" target="_blank">
                                    <i class="bi bi-github"></i> GitHub Repository
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($project['live_demo_url'])) : ?>
                                <a href="<?php echo safe_html($project['live_demo_url']); ?>" class="file-link btn btn-outline-primary" target="_blank">
                                    <i class="bi bi-globe"></i> Live Demo
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($project['social_links'])) : ?>
                            <div class="project-detail-label mt-3">Social Links:</div>
                            <div class="project-detail-value"><?php echo nl2br(safe_html($project['social_links'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-5 gap-2">
                    <a href="admin.php?action=approve&id=<?php echo $project['id']; ?>" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to approve this project?')">
                        <i class="bi bi-check-circle me-2"></i> Approve Project
                    </a>
                    <button type="button" class="btn btn-danger btn-lg" id="rejectBtn" onclick="openRejectModal()">
                        <i class="bi bi-x-circle me-2"></i> Reject Project
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject Project Modal -->
        <div class="modal fade" id="rejectProjectModal" tabindex="-1" aria-labelledby="rejectProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectProjectModalLabel">Reject Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="admin.php" method="post" id="rejectForm">
                        <div class="modal-body">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <div class="mb-3">
                                <label for="rejection_reason" class="form-label">Rejection Reason</label>
                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required placeholder="Please provide a detailed reason for rejection..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelReject">Cancel</button>
                            <button type="submit" name="reject_submit" class="btn btn-danger" id="confirmReject">Reject Project</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php else : ?>
        <!-- Enhanced Dashboard Content -->
        <div class="dashboard-content">
            <!-- Enhanced Statistics Cards -->
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

            <!-- Additional Statistics Row -->
            <?php if (!empty($difficulty_stats)) : ?>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-bar-chart me-2"></i>Project Difficulty Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($difficulty_stats as $level => $count) : ?>
                                        <div class="col-md-3 col-sm-6 mb-3">
                                            <div class="text-center">
                                                <div class="difficulty-badge difficulty-<?php echo $level; ?> d-inline-block mb-2 px-3 py-2">
                                                    <?php echo ucfirst($level); ?>
                                                </div>
                                                <div class="h4 mb-0"><?php echo $count; ?></div>
                                                <small class="text-muted">projects</small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <!-- Project Activity Chart -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="bi bi-graph-up me-2"></i>Project Activity</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo safe_html($selected_time_range); ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach ($time_ranges as $range) : ?>
                                        <li><a class="dropdown-item <?php echo $range == $selected_time_range ? 'active' : ''; ?>" href="?time_range=<?php echo urlencode($range); ?>"><?php echo safe_html($range); ?></a></li>
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
                            <h5 class="card-title mb-0"><i class="bi bi-pie-chart me-2"></i>Project Categories</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <?php echo safe_html($selected_category_range); ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach ($category_ranges as $range) : ?>
                                        <li><a class="dropdown-item <?php echo $range == $selected_category_range ? 'active' : ''; ?>" href="?category_range=<?php echo urlencode($range); ?>"><?php echo safe_html($range); ?></a></li>
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

            <!-- Recent Activity and Pending Projects Row -->
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="bi bi-activity me-2"></i>Recent Activity</h5>
                            <?php if (!$show_all_activities) : ?>
                                <a href="?show_all_activities=1" class="btn btn-sm btn-outline-primary">Show More</a>
                            <?php else : ?>
                                <a href="admin.php" class="btn btn-sm btn-outline-secondary">Show Less</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <div class="activity-timeline">
                                <?php foreach ($activities_to_show as $activity) : ?>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-<?php echo $activity['type']; ?>-light text-<?php echo $activity['type']; ?>">
                                            <i class="bi bi-<?php echo $activity['type'] == 'primary' ? 'plus-circle' : ($activity['type'] == 'success' ? 'check-circle' : 'x-circle-fill'); ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <h6 class="activity-title"><?php echo safe_html($activity['title']); ?></h6>
                                            <p class="activity-text"><?php echo safe_html($activity['description']); ?></p>
                                            <span class="activity-time"><?php echo safe_html($activity['time_ago']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (empty($activities_to_show)) : ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-activity" style="font-size: 2rem; color: #dee2e6;"></i>
                                    <p class="text-muted mt-2">No recent activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Pending Projects List -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="bi bi-clock me-2"></i>Pending Projects</h5>
                            <a href="admin_view_project.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pending_projects_list)) : ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <p class="text-muted mt-2">No pending projects at the moment</p>
                                </div>
                            <?php else : ?>
                                <div class="table-responsive">
                                    <table class="table table-hover enhanced-table">
                                        <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Type</th>
                                            <th>Details</th>
                                            <th>Difficulty</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($pending_projects_list as $project) : ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="project-icon me-2">
                                                            <i class="bi bi-<?php echo $project['icon']; ?>" style="font-size: 1.25rem; color: #4361ee;"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0"><?php echo safe_html($project['name']); ?></h6>
                                                            <small class="text-muted"><?php echo safe_html($project['submitted_by']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark"><?php echo safe_html($project['type']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="project-meta">
                                                        <?php if ($project['category']) : ?>
                                                            <div><strong>Category:</strong> <?php echo safe_html($project['category']); ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($project['dev_time']) : ?>
                                                            <div><strong>Dev Time:</strong> <?php echo safe_html($project['dev_time']); ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($project['team_size']) : ?>
                                                            <div><strong>Team:</strong> <?php echo safe_html($project['team_size']); ?></div>
                                                        <?php endif; ?>
                                                        <div><strong>Tech:</strong> <?php echo safe_html($project['technologies']); ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($project['difficulty']) : ?>
                                                        <span class="difficulty-badge difficulty-<?php echo $project['difficulty']; ?>">
                                                                    <?php echo ucfirst($project['difficulty']); ?>
                                                                </span>
                                                    <?php else : ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                            <span class="badge bg-<?php echo $project['status_class']; ?>">
                                                                <?php echo safe_html($project['status']); ?>
                                                            </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="admin.php?action=view&id=<?php echo $project['id']; ?>" class="btn btn-outline-primary btn-sm" title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="admin.php?action=approve&id=<?php echo $project['id']; ?>" class="btn btn-outline-success btn-sm" onclick="return confirm('Are you sure you want to approve this project?')" title="Approve">
                                                            <i class="bi bi-check"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger btn-sm reject-btn" data-project-id="<?php echo $project['id']; ?>" title="Reject">
                                                            <i class="bi bi-x"></i>
                                                        </button>
                                                    </div>
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
        
        <!-- Reject Project Modal for Dashboard -->
        <div class="modal fade" id="dashboardRejectModal" tabindex="-1" aria-labelledby="dashboardRejectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dashboardRejectModalLabel">Reject Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="admin.php" method="post" id="dashboardRejectForm">
                        <div class="modal-body">
                            <input type="hidden" name="project_id" id="dashboardProjectId" value="">
                            <div class="mb-3">
                                <label for="dashboard_rejection_reason" class="form-label">Rejection Reason</label>
                                <textarea class="form-control" id="dashboard_rejection_reason" name="rejection_reason" rows="3" required placeholder="Please provide a detailed reason for rejection..."></textarea>
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
</div> <!-- Close main-content -->

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Enhanced Project chart
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
                        fill: true,
                        pointBackgroundColor: '#4361ee',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    },
                    {
                        label: 'Approved',
                        data: <?php echo json_encode($approved_projects_data); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    },
                    {
                        label: 'Rejected',
                        data: <?php echo json_encode($rejected_projects_data); ?>,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ef4444',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    },
                    {
                        label: 'Total Projects',
                        data: <?php echo json_encode($total_projects_data); ?>,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        borderWidth: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    // Enhanced Categories chart
    var categoryCtx = document.getElementById('categoriesChart');
    if (categoryCtx) {
        var categoriesChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_data); ?>,
                    backgroundColor: <?php echo json_encode(array_slice($category_colors, 0, count($category_labels))); ?>,
                    borderWidth: 0,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = Math.round((context.parsed * 100) / total);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '60%',
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        });
    }

    // Enhanced sidebar toggle functionality - handled by sidebar_admin.js
    document.addEventListener('DOMContentLoaded', function() {

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert-banner');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });

        // Handle rejection modal
        const rejectModal = document.getElementById('rejectProjectModal');
        const rejectForm = document.getElementById('rejectForm');
        const cancelRejectBtn = document.getElementById('cancelReject');
        
        if (rejectModal && rejectForm) {
            // Reset form when modal is hidden
            rejectModal.addEventListener('hidden.bs.modal', function () {
                rejectForm.reset();
            });
            
            // Handle cancel button click
            if (cancelRejectBtn) {
                cancelRejectBtn.addEventListener('click', function() {
                    const modal = bootstrap.Modal.getInstance(rejectModal);
                    if (modal) {
                        modal.hide();
                    }
                });
            }
            
            // Handle form submission confirmation
            rejectForm.addEventListener('submit', function(e) {
                const reason = document.getElementById('rejection_reason').value.trim();
                if (!reason) {
                    e.preventDefault();
                    alert('Please provide a rejection reason.');
                    return false;
                }
                
                if (!confirm('Are you sure you want to reject this project?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
        
        // Function to open reject modal
        window.openRejectModal = function() {
            const modal = new bootstrap.Modal(document.getElementById('rejectProjectModal'));
            modal.show();
        };
        
        // Handle dashboard reject buttons
        document.querySelectorAll('.reject-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const projectId = this.getAttribute('data-project-id');
                document.getElementById('dashboardProjectId').value = projectId;
                const modal = new bootstrap.Modal(document.getElementById('dashboardRejectModal'));
                modal.show();
            });
        });
        
        // Handle dashboard rejection form
        const dashboardRejectForm = document.getElementById('dashboardRejectForm');
        if (dashboardRejectForm) {
            dashboardRejectForm.addEventListener('submit', function(e) {
                const reason = document.getElementById('dashboard_rejection_reason').value.trim();
                if (!reason) {
                    e.preventDefault();
                    alert('Please provide a rejection reason.');
                    return false;
                }
                
                if (!confirm('Are you sure you want to reject this project?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
</script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="assets/js/loader.js"></script>
<script src="assets/js/loading.js"></script>
</body>
</html>