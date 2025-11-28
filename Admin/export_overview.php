<?php
require_once __DIR__ . '/../includes/security_init.php';
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

    // Ideas statistics with comprehensive details
    $idea_result = $conn->query("SELECT 
        COUNT(*) as total_ideas,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_ideas,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_ideas,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_ideas,
        COUNT(CASE WHEN category = 'Software' THEN 1 END) as software_ideas,
        COUNT(CASE WHEN category = 'Hardware' THEN 1 END) as hardware_ideas,
        COUNT(CASE WHEN difficulty = 'Easy' THEN 1 END) as easy_ideas,
        COUNT(CASE WHEN difficulty = 'Medium' THEN 1 END) as medium_ideas,
        COUNT(CASE WHEN difficulty = 'Hard' THEN 1 END) as hard_ideas,
        AVG(CHAR_LENGTH(description)) as avg_description_length,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_ideas
        FROM blog");
    $idea_stats = $idea_result ? $idea_result->fetch_assoc() : [
        'total_ideas' => 0, 'pending_ideas' => 0, 'in_progress_ideas' => 0, 'completed_ideas' => 0,
        'software_ideas' => 0, 'hardware_ideas' => 0, 'easy_ideas' => 0, 'medium_ideas' => 0, 'hard_ideas' => 0,
        'avg_description_length' => 0, 'recent_ideas' => 0
    ];

    // Ideas engagement statistics
    $idea_engagement_result = $conn->query("SELECT 
        COUNT(DISTINCT il.idea_id) as liked_ideas,
        COUNT(il.id) as total_idea_likes,
        COUNT(DISTINCT ic.idea_id) as commented_ideas,
        COUNT(ic.id) as total_idea_comments,
        COUNT(DISTINCT ir.idea_id) as reported_ideas,
        COUNT(ir.id) as total_reports
        FROM blog b
        LEFT JOIN idea_likes il ON b.id = il.idea_id
        LEFT JOIN idea_comments ic ON b.id = ic.idea_id
        LEFT JOIN idea_reports ir ON b.id = ir.idea_id");
    $idea_engagement = $idea_engagement_result ? $idea_engagement_result->fetch_assoc() : [
        'liked_ideas' => 0, 'total_idea_likes' => 0, 'commented_ideas' => 0, 
        'total_idea_comments' => 0, 'reported_ideas' => 0, 'total_reports' => 0
    ];

    // Top idea contributors
    $top_contributors_result = $conn->query("SELECT 
        r.name, COUNT(b.id) as idea_count
        FROM blog b
        JOIN register r ON b.user_id = r.id
        GROUP BY b.user_id, r.name
        ORDER BY idea_count DESC
        LIMIT 5");
    $top_contributors = [];
    if ($top_contributors_result) {
        while ($row = $top_contributors_result->fetch_assoc()) {
            $top_contributors[] = $row;
        }
    }

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
        // Validate table name against whitelist
$allowed_tables = ['register', 'projects', 'admin_approved_projects', 'blog', 'mentors', 'subadmins'];
if (!in_array($table, $allowed_tables)) {
    continue; // Skip invalid table names
}
$check_result = $conn->query("SELECT COUNT(*) as count FROM `" . $conn->real_escape_string($table) . "`");
        $additional_stats[$key] = $check_result ? $check_result->fetch_assoc()['count'] : 0;
    }
} catch (Exception $e) {
    // Debug: Show error
    error_log("Export Overview Error: " . $e->getMessage());

    // Set default values if queries fail
    $stats = ['total_users' => 0, 'students' => 0, 'mentors' => 0, 'subadmins' => 0];
    $project_stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    $admin_project_stats = ['admin_approved' => 0];
    $idea_stats = [
        'total_ideas' => 0, 'pending_ideas' => 0, 'in_progress_ideas' => 0, 'completed_ideas' => 0,
        'software_ideas' => 0, 'hardware_ideas' => 0, 'easy_ideas' => 0, 'medium_ideas' => 0, 'hard_ideas' => 0,
        'avg_description_length' => 0, 'recent_ideas' => 0
    ];
    $idea_engagement = [
        'liked_ideas' => 0, 'total_idea_likes' => 0, 'commented_ideas' => 0, 
        'total_idea_comments' => 0, 'reported_ideas' => 0, 'total_reports' => 0
    ];
    $top_contributors = [];
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
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/sidebar_admin.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 250px; padding: 20px; }
        .export-card { border-left: 4px solid #28a745; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
    <link rel="stylesheet" href="../assets/css/loading.css">
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <button class="btn d-lg-none mb-3" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        
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
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $idea_engagement['total_idea_likes'] ?? 0; ?> Idea Likes</li>
                                    <li><i class="bi bi-check-circle text-success"></i> <?php echo $idea_engagement['total_idea_comments'] ?? 0; ?> Idea Comments</li>
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
        <!-- Ideas Details Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-lightbulb"></i> Ideas & Innovation Analytics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Status Distribution</h6>
                                <ul class="list-unstyled">
                                    <li><span class="badge bg-warning"><?php echo $idea_stats['pending_ideas']; ?></span> Pending Ideas</li>
                                    <li><span class="badge bg-info"><?php echo $idea_stats['in_progress_ideas']; ?></span> In Progress</li>
                                    <li><span class="badge bg-success"><?php echo $idea_stats['completed_ideas']; ?></span> Completed</li>
                                </ul>
                                
                                <h6 class="mt-3">Category Breakdown</h6>
                                <ul class="list-unstyled">
                                    <li><i class="bi bi-laptop text-primary"></i> <?php echo $idea_stats['software_ideas']; ?> Software Ideas</li>
                                    <li><i class="bi bi-cpu text-success"></i> <?php echo $idea_stats['hardware_ideas']; ?> Hardware Ideas</li>
                                </ul>
                            </div>
                            
                            <div class="col-md-4">
                                <h6>Difficulty Levels</h6>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-success" style="width: <?php echo $idea_stats['total_ideas'] > 0 ? ($idea_stats['easy_ideas'] / $idea_stats['total_ideas']) * 100 : 0; ?>%"></div>
                                </div>
                                <small>Easy: <?php echo $idea_stats['easy_ideas']; ?> ideas</small>
                                
                                <div class="progress mb-2 mt-2">
                                    <div class="progress-bar bg-warning" style="width: <?php echo $idea_stats['total_ideas'] > 0 ? ($idea_stats['medium_ideas'] / $idea_stats['total_ideas']) * 100 : 0; ?>%"></div>
                                </div>
                                <small>Medium: <?php echo $idea_stats['medium_ideas']; ?> ideas</small>
                                
                                <div class="progress mb-2 mt-2">
                                    <div class="progress-bar bg-danger" style="width: <?php echo $idea_stats['total_ideas'] > 0 ? ($idea_stats['hard_ideas'] / $idea_stats['total_ideas']) * 100 : 0; ?>%"></div>
                                </div>
                                <small>Hard: <?php echo $idea_stats['hard_ideas']; ?> ideas</small>
                                
                                <h6 class="mt-3">Engagement Metrics</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="bi bi-heart-fill text-danger"></i> <?php echo $idea_engagement['total_idea_likes']; ?> Total Likes</li>
                                    <li><i class="bi bi-chat-fill text-primary"></i> <?php echo $idea_engagement['total_idea_comments']; ?> Total Comments</li>
                                    <li><i class="bi bi-flag-fill text-warning"></i> <?php echo $idea_engagement['total_reports']; ?> Reports</li>
                                </ul>
                            </div>
                            
                            <div class="col-md-4">
                                <h6>Top Contributors</h6>
                                <?php if (!empty($top_contributors)): ?>
                                    <ol class="list-group list-group-numbered">
                                        <?php foreach ($top_contributors as $contributor): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold"><?php echo htmlspecialchars($contributor['name']); ?></div>
                                                </div>
                                                <span class="badge bg-primary rounded-pill"><?php echo $contributor['idea_count']; ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php else: ?>
                                    <p class="text-muted">No contributors found</p>
                                <?php endif; ?>
                                
                                <h6 class="mt-3">Activity Summary</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="bi bi-calendar-check"></i> <?php echo $idea_stats['recent_ideas']; ?> ideas in last 30 days</li>
                                    <li><i class="bi bi-file-text"></i> Avg description: <?php echo round($idea_stats['avg_description_length']); ?> chars</li>
                                    <li><i class="bi bi-graph-up"></i> <?php echo $idea_engagement['liked_ideas']; ?> ideas have likes</li>
                                    <li><i class="bi bi-chat-dots"></i> <?php echo $idea_engagement['commented_ideas']; ?> ideas have comments</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
<script src="../assets/js/loading.js"></script>
</body>
</html>