<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Set admin_id for compatibility
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;
}

// Get comprehensive statistics with error handling
try {
    // Test connection first
    $test_query = "SHOW TABLES";
    $test_result = $conn->query($test_query);
    
    $stats_query = "SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN role = 'student' OR role IS NULL THEN 1 END) as students,
        COUNT(CASE WHEN role = 'mentor' THEN 1 END) as mentors
        FROM register";
    $result = $conn->query($stats_query);
    $stats = $result ? $result->fetch_assoc() : ['total_users' => 0, 'students' => 0, 'mentors' => 0];

    $subadmin_result = $conn->query("SELECT COUNT(*) as count FROM subadmins");
    $subadmin_count = $subadmin_result ? $subadmin_result->fetch_assoc() : ['count' => 0];
    $stats['subadmins'] = $subadmin_count['count'];

    // Project statistics from both tables
    $project_result = $conn->query("SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
        FROM projects");
    $project_stats = $project_result ? $project_result->fetch_assoc() : ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];

    $admin_project_result = $conn->query("SELECT 
        COUNT(*) as admin_approved,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as admin_pending,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as admin_approved_count,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as admin_rejected
        FROM admin_approved_projects");
    $admin_project_stats = $admin_project_result ? $admin_project_result->fetch_assoc() : ['admin_approved' => 0, 'admin_pending' => 0, 'admin_approved_count' => 0, 'admin_rejected' => 0];

    // Ideas statistics
    $idea_result = $conn->query("SELECT 
        COUNT(*) as total_ideas,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_ideas,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_ideas,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_ideas
        FROM blog");
    $idea_stats = $idea_result ? $idea_result->fetch_assoc() : ['total_ideas' => 0, 'pending_ideas' => 0, 'in_progress_ideas' => 0, 'completed_ideas' => 0];

    // Mentor activity statistics
    $mentor_result = $conn->query("SELECT 
        COUNT(DISTINCT mentor_id) as active_mentors,
        COUNT(*) as total_sessions,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions
        FROM mentoring_sessions");
    $mentor_stats = $mentor_result ? $mentor_result->fetch_assoc() : ['active_mentors' => 0, 'total_sessions' => 0, 'completed_sessions' => 0];

    // Subadmin activity statistics
    $subadmin_activity_result = $conn->query("SELECT 
        COUNT(*) as total_requests,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests
        FROM subadmin_classification_requests");
    $subadmin_activity = $subadmin_activity_result ? $subadmin_activity_result->fetch_assoc() : ['total_requests' => 0, 'approved_requests' => 0];

    // Additional statistics from all tables with safe queries
    $additional_stats = [];
    $tables_to_check = [
        'bookmarks' => 'bookmark',
        'project_likes' => 'project_likes', 
        'project_comments' => 'project_comments',
        'idea_likes' => 'idea_likes',
        'idea_comments' => 'idea_comments', 
        'idea_reports' => 'idea_reports',
        'support_tickets' => 'support_tickets',
        'mentor_requests' => 'mentor_requests',
        'mentor_pairs' => 'mentor_student_pairs',
        'notifications' => 'notification_logs',
        'denied_projects' => 'denial_projects'
    ];
    
    foreach ($tables_to_check as $key => $table) {
        $check_result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $additional_stats[$key] = $check_result ? $check_result->fetch_assoc()['count'] : 0;
    }

} catch (Exception $e) {
    // Debug: Show error
    error_log("Export Overview Error: " . $e->getMessage());
    
    // Set default values if queries fail
    $stats = ['total_users' => 0, 'students' => 0, 'mentors' => 0, 'subadmins' => 0];
    $project_stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    $admin_project_stats = ['admin_approved' => 0];
    $idea_stats = ['total_ideas' => 0, 'pending_ideas' => 0, 'in_progress_ideas' => 0, 'completed_ideas' => 0];
    $mentor_stats = ['active_mentors' => 0, 'total_sessions' => 0, 'completed_sessions' => 0];
    $subadmin_activity = ['total_requests' => 0, 'approved_requests' => 0];
    $additional_stats = ['bookmarks' => 0, 'project_likes' => 0, 'project_comments' => 0, 'idea_likes' => 0, 'idea_comments' => 0, 'idea_reports' => 0, 'support_tickets' => 0, 'mentor_requests' => 0, 'mentor_pairs' => 0, 'notifications' => 0, 'denied_projects' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Overview - IdeaNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/sidebar_admin.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 250px; padding: 20px; }
        .export-card { border-left: 4px solid #28a745; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-download"></i> Export Overview</h1>
        </div>


        <!-- Export Options -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card export-card">
                    <div class="card-header">
                        <h5><i class="bi bi-file-earmark-spreadsheet"></i> Comprehensive Data Export</h5>
                    </div>
                    <div class="card-body">
                        <p>Export all system data including user activities, project details, subadmin approvals, and mentor activities.</p>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Export Options:</h6>
                                <div class="btn-group-vertical w-100" role="group">
                                    <a href="export_comprehensive_data.php?type=csv" class="btn btn-success mb-2">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> Complete CSV Export
                                    </a>
                                    <a href="export_comprehensive_data.php?type=users" class="btn btn-outline-primary mb-2">
                                        <i class="bi bi-people"></i> Users & Activities Only
                                    </a>
                                    <a href="export_comprehensive_data.php?type=projects" class="btn btn-outline-success mb-2">
                                        <i class="bi bi-kanban"></i> Projects & Ideas Only
                                    </a>
                                    <a href="export_comprehensive_data.php?type=mentors" class="btn btn-outline-info mb-2">
                                        <i class="bi bi-person-workspace"></i> Mentor Activities Only
                                    </a>
                                    <a href="export_comprehensive_data.php?type=all" class="btn btn-outline-dark mb-2">
                                        <i class="bi bi-database"></i> Complete Database Export
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Data Summary:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $stats['total_users'] ?? 0; ?> User Profiles</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $project_stats['total'] ?? 0; ?> Project Submissions</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $admin_project_stats['admin_approved'] ?? 0; ?> Admin Projects</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $idea_stats['total_ideas'] ?? 0; ?> Project Ideas</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $stats['subadmins'] ?? 0; ?> Subadmin Records</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $subadmin_activity['total_requests'] ?? 0; ?> Subadmin Requests</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $mentor_stats['total_sessions'] ?? 0; ?> Mentor Sessions</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $additional_stats['bookmarks']; ?> Bookmarks</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $additional_stats['project_likes']; ?> Project Likes</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $additional_stats['project_comments']; ?> Project Comments</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $additional_stats['support_tickets']; ?> Support Tickets</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $additional_stats['notifications']; ?> Notifications</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-info-circle"></i> Export Details</h5>
                    </div>
                    <div class="card-body">
                        <h6>Comprehensive Data Includes:</h6>
                        <ul class="small">
                            <li><strong>User Management:</strong> All user profiles, roles, and activity logs</li>
                            <li><strong>Project Lifecycle:</strong> Submissions, approvals, rejections, and admin decisions</li>
                            <li><strong>Ideas & Innovation:</strong> All project ideas with status tracking</li>
                            <li><strong>Subadmin Activities:</strong> Classification requests, approvals, and project assignments</li>
                            <li><strong>Mentor System:</strong> Sessions, student pairings, and activity logs</li>
                            <li><strong>System Analytics:</strong> Engagement metrics and performance data</li>
                        </ul>
                        <hr>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> Last updated: <?php echo date('Y-m-d H:i:s'); ?><br>
                            <i class="bi bi-shield-check"></i> Data exported securely with admin privileges
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Activity Summary -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>