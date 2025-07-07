<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include "../Login/Login/db.php";

// Site name
$site_name = "IdeaNest Admin";

// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to admin login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_name = "Admin";

// Create notification_logs table if not exists
$create_logs_table = "CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    user_id INT,
    project_id INT NULL,
    status VARCHAR(50) NOT NULL,
    email_to VARCHAR(255),
    email_subject VARCHAR(255),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_user_id (user_id),
    INDEX idx_project_id (project_id)
)";
$conn->query($create_logs_table);

// Add missing columns if they don't exist
$alter_queries = [
    "ALTER TABLE notification_logs ADD COLUMN IF NOT EXISTS email_to VARCHAR(255)",
    "ALTER TABLE notification_logs ADD COLUMN IF NOT EXISTS email_subject VARCHAR(255)",
    "ALTER TABLE notification_logs ADD COLUMN IF NOT EXISTS error_message TEXT"
];

foreach ($alter_queries as $query) {
    $conn->query($query);
}

// Get notification statistics
$stats_query = "SELECT 
    type,
    status,
    COUNT(*) as count
FROM notification_logs 
GROUP BY type, status";
$stats_result = $conn->query($stats_query);

$stats = [];
while($row = $stats_result->fetch_assoc()) {
    $stats[$row['type']][$row['status']] = $row['count'];
}

// Get recent notifications
$recent_query = "SELECT 
    nl.*,
    r.name as user_name,
    r.email as user_email,
    p.project_name
FROM notification_logs nl
LEFT JOIN register r ON nl.user_id = r.id
LEFT JOIN admin_approved_projects p ON nl.project_id = p.id
ORDER BY nl.created_at DESC
LIMIT 50";
$recent_result = $conn->query($recent_query);

// --- FILTERS ---
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];
$types = '';

if ($type_filter) {
    $where[] = 'nl.type = ?';
    $params[] = $type_filter;
    $types .= 's';
}
if ($status_filter) {
    $where[] = 'nl.status = ?';
    $params[] = $status_filter;
    $types .= 's';
}
if ($date_filter) {
    $where[] = 'DATE(nl.created_at) = ?';
    $params[] = $date_filter;
    $types .= 's';
}
if ($search) {
    $where[] = '(r.name LIKE ? OR r.email LIKE ? OR nl.email_to LIKE ? OR nl.email_subject LIKE ? OR p.project_name LIKE ? OR nl.type LIKE ? OR nl.status LIKE ? OR nl.error_message LIKE ?)';
    for ($i = 0; $i < 8; $i++) {
        $params[] = "%$search%";
        $types .= 's';
    }
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// --- STATISTICS ---
$stats_sql = "SELECT 
    type,
    status,
    COUNT(*) as count
FROM notification_logs 
GROUP BY type, status";
$stats_result = $conn->query($stats_sql);
$stats = [];
while($row = $stats_result->fetch_assoc()) {
    $stats[$row['type']][$row['status']] = $row['count'];
}

// --- NOTIFICATION TYPES ---
$types_sql = "SELECT DISTINCT type FROM notification_logs ORDER BY type";
$types_result = $conn->query($types_sql);
$type_options = [];
while($row = $types_result->fetch_assoc()) {
    $type_options[] = $row['type'];
}

// --- STATUS OPTIONS ---
$status_sql = "SELECT DISTINCT status FROM notification_logs ORDER BY status";
$status_result = $conn->query($status_sql);
$status_options = [];
while($row = $status_result->fetch_assoc()) {
    $status_options[] = $row['status'];
}

// --- DATE OPTIONS ---
$date_sql = "SELECT DISTINCT DATE(created_at) as date FROM notification_logs ORDER BY date DESC LIMIT 30";
$date_result = $conn->query($date_sql);
$date_options = [];
while($row = $date_result->fetch_assoc()) {
    $date_options[] = $row['date'];
}

// --- PAGINATED NOTIFICATIONS ---
$count_sql = "SELECT COUNT(*) as total FROM notification_logs nl
LEFT JOIN register r ON nl.user_id = r.id
LEFT JOIN admin_approved_projects p ON nl.project_id = p.id
$where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total = $count_result->fetch_assoc()['total'];
$count_stmt->close();

$log_sql = "SELECT nl.*, r.name as user_name, r.email as user_email, p.project_name
FROM notification_logs nl
LEFT JOIN register r ON nl.user_id = r.id
LEFT JOIN admin_approved_projects p ON nl.project_id = p.id
$where_sql
ORDER BY nl.created_at DESC
LIMIT $per_page OFFSET $offset";
$log_stmt = $conn->prepare($log_sql);
if ($types) $log_stmt->bind_param($types, ...$params);
$log_stmt->execute();
$log_result = $log_stmt->get_result();

$total_pages = ceil($total / $per_page);

$message = isset($_GET['message']) ? $_GET['message'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo $site_name; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
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
        .sidebar-link.active, .sidebar-link:focus {
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
        .main-content {
            margin-left: 250px;
            padding: 1rem;
            transition: all 0.3s;
        }
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
        .chart-container {
            position: relative;
            height: 300px;
        }
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
        /* Timeline Styles */
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
        .activity-read-more {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #f1f1f1;
            margin-top: 0.5rem;
        }
        .activity-read-more .btn {
            font-size: 0.875rem;
        }
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
        /* Notification-specific styles (keep these) */
        .notification-table {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        .notification-table th, .notification-table td {
            vertical-align: middle;
            padding: 0.75rem 1rem;
        }
        .notification-table th {
            background: #f8fafc;
            font-weight: 600;
        }
        .notification-table tr {
            transition: background 0.2s;
        }
        .notification-table tr:hover {
            background: #f1f5f9;
        }
        .status-badge {
            display: inline-block;
            padding: 0.35em 0.85em;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-sent { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-pending { background: #fef9c3; color: #92400e; }
        .table-responsive { border-radius: 12px; overflow: hidden; }
        .pagination { justify-content: flex-end; }
        .glass-card {
            background: rgba(255,255,255,0.7);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08);
            backdrop-filter: blur(6px);
            border-radius: 1.25rem;
            border: 1px solid rgba(255,255,255,0.18);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .glass-card:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 16px 32px 0 rgba(67, 97, 238, 0.12);
            z-index: 2;
        }
        .accent-bar {
            border-radius: 1.25rem 1.25rem 0 0;
        }
        .icon-bg {
            box-shadow: 0 2px 8px 0 rgba(67, 97, 238, 0.07);
        }
        .stats-row { margin-left: 0; margin-right: 0; }
        .stats-card { min-width: 0; }
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
                <a href="admin_view_project.php" class="sidebar-link">
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
            <li class="sidebar-item">
                <a href="notifications.php" class="sidebar-link active">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
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
            <h1 class="page-title">Notification Dashboard</h1>
            <div class="topbar-actions">
                <div class="dropdown">
                    <a href="#" class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
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

        <!-- Statistics Row -->
        <div class="row stats-row mb-4 gx-4 gy-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stats-card success glass-card position-relative overflow-hidden h-100">
                    <div class="accent-bar bg-success position-absolute top-0 start-0 w-100" style="height: 6px;"></div>
                    <div class="icon-bg bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                        <i class="bi bi-check-circle text-success" style="font-size:2rem;"></i>
                    </div>
                    <div class="stats-number">
                        <?php echo ($stats['project_approval']['sent'] ?? 0) + ($stats['project_rejection']['sent'] ?? 0) + ($stats['new_user_notification']['sent'] ?? 0); ?>
                    </div>
                    <div class="stats-label">
                        Successful Notifications
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stats-card danger glass-card position-relative overflow-hidden h-100">
                    <div class="accent-bar bg-danger position-absolute top-0 start-0 w-100" style="height: 6px;"></div>
                    <div class="icon-bg bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                        <i class="bi bi-exclamation-triangle text-danger" style="font-size:2rem;"></i>
                    </div>
                    <div class="stats-number">
                        <?php echo ($stats['project_approval']['failed'] ?? 0) + ($stats['project_rejection']['failed'] ?? 0) + ($stats['new_user_notification']['failed'] ?? 0); ?>
                    </div>
                    <div class="stats-label">
                        Failed Notifications
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stats-card info glass-card position-relative overflow-hidden h-100">
                    <div class="accent-bar bg-info position-absolute top-0 start-0 w-100" style="height: 6px;"></div>
                    <div class="icon-bg bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                        <i class="bi bi-person-plus text-info" style="font-size:2rem;"></i>
                    </div>
                    <div class="stats-number">
                        <?php echo $stats['new_user_notification']['sent'] ?? 0; ?>
                    </div>
                    <div class="stats-label">
                        New User Notifications
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stats-card warning glass-card position-relative overflow-hidden h-100">
                    <div class="accent-bar bg-warning position-absolute top-0 start-0 w-100" style="height: 6px;"></div>
                    <div class="icon-bg bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                        <i class="bi bi-envelope text-warning" style="font-size:2rem;"></i>
                    </div>
                    <div class="stats-number">
                        <?php echo ($stats['project_approval']['sent'] ?? 0) + ($stats['project_rejection']['sent'] ?? 0); ?>
                    </div>
                    <div class="stats-label">
                        Project Notifications
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter/Search Bar -->
        <form class="filter-bar row g-2 mb-4" method="get" action="">
            <div class="col-auto">
                <select class="form-select" name="type">
                    <option value="">All Types</option>
                    <?php foreach($type_options as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php if($type_filter == $type) echo 'selected'; ?>><?php echo ucwords(str_replace('_', ' ', $type)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <?php foreach($status_options as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>" <?php if($status_filter == $status) echo 'selected'; ?>><?php echo ucfirst($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select class="form-select" name="date">
                    <option value="">All Dates</option>
                    <?php foreach($date_options as $date): ?>
                        <option value="<?php echo htmlspecialchars($date); ?>" <?php if($date_filter == $date) echo 'selected'; ?>><?php echo htmlspecialchars($date); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto flex-grow-1">
                <input type="text" class="form-control" name="search" placeholder="Search notifications..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
        </form>

        <!-- Notification Table -->
        <div class="table-responsive notification-table mb-4">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>User</th>
                        <th>Email To</th>
                        <th>Project</th>
                        <th>Subject</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($log_result && $log_result->num_rows > 0): ?>
                        <?php while($n = $log_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M j, Y g:i A', strtotime($n['created_at'])); ?></td>
                                <td><span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $n['type'])); ?></span></td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($n['status']); ?>">
                                        <?php echo ucfirst($n['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $n['user_name'] ? htmlspecialchars($n['user_name']) : 'N/A'; ?>
                                    <?php if($n['user_email']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($n['user_email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($n['email_to'] ?? ''); ?></td>
                                <td><?php echo $n['project_name'] ? htmlspecialchars($n['project_name']) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($n['email_subject'] ?? ''); ?></td>
                                <td style="max-width:200px; white-space:pre-wrap; word-break:break-all;"><span class="text-danger"><?php echo htmlspecialchars($n['error_message'] ?? ''); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-bell-slash" style="font-size: 2rem;"></i><br>No notifications found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <li class="page-item <?php if($i == $page) echo 'active'; ?>">
                        <a class="page-link" href="?<?php 
                            $q = $_GET; $q['page'] = $i; echo http_build_query($q); 
                        ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-content').classList.toggle('pushed');
        });
    </script>
</body>
</html> 