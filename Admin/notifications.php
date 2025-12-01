<?php
require_once __DIR__ . '/../includes/security_init.php';
require_once '../config/config.php';
// Production-safe error reporting
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

// Get comprehensive notification statistics from all sources
$stats = [
    'total_notifications' => 0,
    'email_sent' => 0,
    'email_failed' => 0,
    'user_notifications' => 0,
    'realtime_notifications' => 0
];

// Count from notification_logs (email notifications)
$email_stats = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
FROM notification_logs");
if ($email_stats && $row = $email_stats->fetch_assoc()) {
    $stats['email_sent'] = $row['sent'] ?? 0;
    $stats['email_failed'] = $row['failed'] ?? 0;
}

// Count from notifications table
$user_notif = $conn->query("SELECT COUNT(*) as count FROM notifications");
if ($user_notif && $row = $user_notif->fetch_assoc()) {
    $stats['user_notifications'] = $row['count'];
}

// Count from realtime_notifications table
$realtime_notif = $conn->query("SELECT COUNT(*) as count FROM realtime_notifications");
if ($realtime_notif && $row = $realtime_notif->fetch_assoc()) {
    $stats['realtime_notifications'] = $row['count'];
}

$stats['total_notifications'] = $stats['email_sent'] + $stats['email_failed'] + $stats['user_notifications'] + $stats['realtime_notifications'];

// --- FILTERS ---
$source_filter = isset($_GET['source']) ? $_GET['source'] : 'all'; // all, email, user, realtime
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build unified query from all notification sources with explicit collation
$union_parts = [];

// 1. Email Notifications (notification_logs)
if ($source_filter == 'all' || $source_filter == 'email') {
    $union_parts[] = "
        SELECT 
            'email' COLLATE utf8mb4_general_ci as source,
            nl.id,
            CAST(nl.type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as type,
            CAST(nl.status AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as status,
            nl.created_at,
            CAST(r.name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as user_name,
            CAST(r.email AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as user_email,
            CAST(nl.email_to AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as email_to,
            CAST(nl.email_subject AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as subject,
            CAST(COALESCE(p.project_name, ap.project_name) AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as project_name,
            CAST(nl.error_message AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as error_message,
            CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as message,
            NULL as is_read
        FROM notification_logs nl
        LEFT JOIN register r ON nl.user_id = r.id
        LEFT JOIN projects p ON nl.project_id = p.id
        LEFT JOIN admin_approved_projects ap ON nl.project_id = ap.id
    ";
}

// 2. User Notifications (notifications)
if ($source_filter == 'all' || $source_filter == 'user') {
    $union_parts[] = "
        SELECT 
            'user' COLLATE utf8mb4_general_ci as source,
            n.id,
            CAST(n.type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as type,
            CAST(CASE WHEN n.is_read = 1 THEN 'read' ELSE 'unread' END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as status,
            n.created_at,
            CAST(r.name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as user_name,
            CAST(r.email AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as user_email,
            CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as email_to,
            CAST(n.title AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as subject,
            CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as project_name,
            CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as error_message,
            CAST(n.message AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as message,
            n.is_read
        FROM notifications n
        LEFT JOIN register r ON n.user_id = r.id
    ";
}

// 3. Realtime Notifications (realtime_notifications)
if ($source_filter == 'all' || $source_filter == 'realtime') {
    $union_parts[] = "
        SELECT 
            'realtime' COLLATE utf8mb4_general_ci as source,
            rn.id,
            CAST(rn.type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as type,
            CAST(CASE WHEN rn.is_read = 1 THEN 'read' ELSE 'unread' END AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as status,
            rn.created_at,
            CAST(r.name AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as user_name,
            CAST(r.email AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as user_email,
            CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as email_to,
            CAST(rn.title AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as subject,
            CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as project_name,
            CAST(NULL AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as error_message,
            CAST(rn.message AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as message,
            rn.is_read
        FROM realtime_notifications rn
        LEFT JOIN register r ON rn.user_id = r.id
    ";
}

// Combine all sources
if (empty($union_parts)) {
    $union_parts[] = "SELECT 'email' as source, 0 as id, '' as type, '' as status, NOW() as created_at, '' as user_name, '' as user_email, '' as email_to, '' as subject, '' as project_name, '' as error_message, '' as message, 0 as is_read LIMIT 0";
}

$base_query = "SELECT * FROM (" . implode(" UNION ALL ", $union_parts) . ") as all_notifications";

// Apply filters
$where_conditions = [];
if ($type_filter) {
    $where_conditions[] = "type = " . $conn->real_escape_string($type_filter);
}
if ($status_filter) {
    $where_conditions[] = "status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($date_filter) {
    $where_conditions[] = "DATE(created_at) = '" . $conn->real_escape_string($date_filter) . "'";
}
if ($search) {
    $search_escaped = $conn->real_escape_string($search);
    $where_conditions[] = "(user_name LIKE '%$search_escaped%' OR user_email LIKE '%$search_escaped%' OR email_to LIKE '%$search_escaped%' OR subject LIKE '%$search_escaped%' OR project_name LIKE '%$search_escaped%' OR type LIKE '%$search_escaped%' OR message LIKE '%$search_escaped%')";
}

$where_sql = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM ($base_query $where_sql) as counted";
$count_result = $conn->query($count_query);
$total = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total / $per_page);

// Get paginated results
$final_query = "$base_query $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$log_result = $conn->query($final_query);

// Get filter options
$type_options = [];
$type_query = "SELECT DISTINCT type FROM (
    SELECT CAST(type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as type FROM notification_logs
    UNION
    SELECT CAST(type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as type FROM notifications
    UNION
    SELECT CAST(type AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci as type FROM realtime_notifications
) as types WHERE type IS NOT NULL AND type != '' ORDER BY type";
$type_result = $conn->query($type_query);
if ($type_result) {
    while ($row = $type_result->fetch_assoc()) {
        if ($row['type']) {
            $type_options[] = $row['type'];
        }
    }
}

$status_options = ['sent', 'failed', 'read', 'unread'];

// Get date options
$date_query = "SELECT DISTINCT DATE(created_at) as date FROM (
    SELECT created_at FROM notification_logs
    UNION
    SELECT created_at FROM notifications
    UNION
    SELECT created_at FROM realtime_notifications
) as dates ORDER BY date DESC LIMIT 30";
$date_result = $conn->query($date_query);
$date_options = [];
if ($date_result) {
    while ($row = $date_result->fetch_assoc()) {
        if ($row['date']) {
            $date_options[] = $row['date'];
        }
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
    <title>Notifications - <?php echo $site_name; ?></title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <link rel="stylesheet" href="../assets/css/loader.css">
    <link rel="stylesheet" href="../assets/css/loading.css">
</head>
<body>
    <!-- Sidebar -->
   <?php include "sidebar_admin.php"?>

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
        <?php if ($message) : ?>
            <div class="alert alert-success alert-banner alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error) : ?>
            <div class="alert alert-danger alert-banner alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Row -->
        <div class="row stats-row mb-4 gx-4 gy-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stats-card primary glass-card position-relative overflow-hidden h-100">
                    <div class="accent-bar bg-primary position-absolute top-0 start-0 w-100" style="height: 6px;"></div>
                    <div class="icon-bg bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                        <i class="bi bi-bell text-primary" style="font-size:2rem;"></i>
                    </div>
                    <div class="stats-number">
                        <?php echo $stats['total_notifications']; ?>
                    </div>
                    <div class="stats-label">
                        Total Notifications
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stats-card success glass-card position-relative overflow-hidden h-100">
                    <div class="accent-bar bg-success position-absolute top-0 start-0 w-100" style="height: 6px;"></div>
                    <div class="icon-bg bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                        <i class="bi bi-envelope-check text-success" style="font-size:2rem;"></i>
                    </div>
                    <div class="stats-number">
                        <?php echo $stats['email_sent']; ?>
                    </div>
                    <div class="stats-label">
                        Emails Sent
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stats-card danger glass-card position-relative overflow-hidden h-100">
                    <div class="accent-bar bg-danger position-absolute top-0 start-0 w-100" style="height: 6px;"></div>
                    <div class="icon-bg bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                        <i class="bi bi-envelope-x text-danger" style="font-size:2rem;"></i>
                    </div>
                    <div class="stats-number">
                        <?php echo $stats['email_failed']; ?>
                    </div>
                    <div class="stats-label">
                        Failed Emails
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stats-card info glass-card position-relative overflow-hidden h-100">
                    <div class="accent-bar bg-info position-absolute top-0 start-0 w-100" style="height: 6px;"></div>
                    <div class="icon-bg bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;">
                        <i class="bi bi-person-check text-info" style="font-size:2rem;"></i>
                    </div>
                    <div class="stats-number">
                        <?php echo $stats['user_notifications'] + $stats['realtime_notifications']; ?>
                    </div>
                    <div class="stats-label">
                        User Notifications
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter/Search Bar -->
        <form class="filter-bar row g-2 mb-4" method="get" action="">
            <div class="col-auto">
                <select class="form-select" name="source">
                    <option value="all" <?php if ($source_filter == 'all') echo 'selected'; ?>>All Sources</option>
                    <option value="email" <?php if ($source_filter == 'email') echo 'selected'; ?>>Email Notifications</option>
                    <option value="user" <?php if ($source_filter == 'user') echo 'selected'; ?>>User Notifications</option>
                    <option value="realtime" <?php if ($source_filter == 'realtime') echo 'selected'; ?>>Realtime Notifications</option>
                </select>
            </div>
            <div class="col-auto">
                <select class="form-select" name="type">
                    <option value="">All Types</option>
                    <?php foreach ($type_options as $type) : ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php if ($type_filter == $type) {
                            echo 'selected';
                                       } ?>><?php echo ucwords(str_replace('_', ' ', $type)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <?php foreach ($status_options as $status) : ?>
                        <option value="<?php echo htmlspecialchars($status); ?>" <?php if ($status_filter == $status) {
                            echo 'selected';
                                       } ?>><?php echo ucfirst($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select class="form-select" name="date">
                    <option value="">All Dates</option>
                    <?php foreach ($date_options as $date) : ?>
                        <option value="<?php echo htmlspecialchars($date); ?>" <?php if ($date_filter == $date) {
                            echo 'selected';
                                       } ?>><?php echo htmlspecialchars($date); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto flex-grow-1">
                <input type="text" class="form-control" name="search" placeholder="Search notifications..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
            <div class="col-auto">
                <a href="notifications.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Clear</a>
            </div>
        </form>

        <!-- Notification Table -->
        <div class="table-responsive notification-table mb-4">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Date/Time</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>User</th>
                        <th>Subject/Title</th>
                        <th>Message/Details</th>
                        <th>Project</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($log_result && $log_result->num_rows > 0) : ?>
                        <?php while ($n = $log_result->fetch_assoc()) : ?>
                            <tr>
                                <td>
                                    <?php 
                                    $source_badges = [
                                        'email' => '<span class="badge bg-primary"><i class="bi bi-envelope"></i> Email</span>',
                                        'user' => '<span class="badge bg-info"><i class="bi bi-person"></i> User</span>',
                                        'realtime' => '<span class="badge bg-success"><i class="bi bi-lightning"></i> Realtime</span>'
                                    ];
                                    echo $source_badges[$n['source']] ?? '<span class="badge bg-secondary">Unknown</span>';
                                    ?>
                                </td>
                                <td><?php echo date('M j, Y<\b\r>g:i A', strtotime($n['created_at'])); ?></td>
                                <td><span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $n['type'])); ?></span></td>
                                <td>
                                    <?php 
                                    $status_class = [
                                        'sent' => 'success',
                                        'failed' => 'danger',
                                        'read' => 'info',
                                        'unread' => 'warning'
                                    ];
                                    $class = $status_class[$n['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?>">
                                        <?php echo ucfirst($n['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $n['user_name'] ? htmlspecialchars($n['user_name']) : 'N/A'; ?>
                                    <?php if ($n['user_email']) : ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($n['user_email']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($n['email_to'] && $n['email_to'] != $n['user_email']) : ?>
                                        <br><small class="text-primary"><i class="bi bi-arrow-right"></i> <?php echo htmlspecialchars($n['email_to']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:250px;">
                                    <?php echo htmlspecialchars($n['subject'] ?? 'N/A'); ?>
                                </td>
                                <td style="max-width:300px;">
                                    <?php if ($n['message']) : ?>
                                        <small><?php echo htmlspecialchars(substr($n['message'], 0, 150)) . (strlen($n['message']) > 150 ? '...' : ''); ?></small>
                                    <?php elseif ($n['error_message']) : ?>
                                        <small class="text-danger"><i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars(substr($n['error_message'], 0, 150)); ?></small>
                                    <?php else : ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $n['project_name'] ? htmlspecialchars($n['project_name']) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-bell-slash" style="font-size: 2rem;"></i><br>No notifications found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
        <nav>
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                    <li class="page-item <?php if ($i == $page) {
                        echo 'active';
                                         } ?>">
                        <a class="page-link" href="?<?php
                            $q = $_GET;
                        $q['page'] = $i;
                        echo http_build_query($q);
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