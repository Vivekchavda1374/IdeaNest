<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../config/config.php';
include '../Login/Login/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get notification statistics
$stats_query = "SELECT 
    COUNT(*) as total_users,
    SUM(email_notifications) as enabled_users,
    COUNT(*) - SUM(email_notifications) as disabled_users
    FROM register";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get recent notification logs
$logs_query = "SELECT nl.*, r.name, r.email 
               FROM notification_logs nl 
               JOIN register r ON nl.user_id = r.id 
               ORDER BY nl.sent_at DESC 
               LIMIT 50";
$logs_result = $conn->query($logs_query);

// Get weekly stats
$weekly_stats_query = "SELECT 
    DATE(sent_at) as date,
    COUNT(*) as notifications_sent,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
    FROM notification_logs 
    WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(sent_at)
    ORDER BY date DESC";
$weekly_stats_result = $conn->query($weekly_stats_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../assets/js/anti_injection.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Dashboard - IdeaNest Admin</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #6366f1; }
        .stat-label { color: #6b7280; margin-top: 5px; }
        .section { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section h2 { color: #1f2937; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .table th { background: #f9fafb; font-weight: 600; color: #374151; }
        .status-sent { color: #10b981; font-weight: bold; }
        .status-failed { color: #ef4444; font-weight: bold; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bell"></i> Email Notification Dashboard</h1>
            <p>Monitor and manage the weekly notification system</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['enabled_users']; ?></div>
                <div class="stat-label">Notifications Enabled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['disabled_users']; ?></div>
                <div class="stat-label">Notifications Disabled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo round(($stats['enabled_users'] / $stats['total_users']) * 100); ?>%</div>
                <div class="stat-label">Opt-in Rate</div>
            </div>
        </div>

        <div class="section">
            <h2>Quick Actions</h2>
            <a href="../cron/test_notifications.php" class="btn btn-warning" target="_blank">
                <i class="fas fa-flask"></i> Test Notifications
            </a>
            <a href="../cron/weekly_notifications.php" class="btn btn-primary" target="_blank">
                <i class="fas fa-paper-plane"></i> Send Weekly Notifications
            </a>
            <a href="../user/user_profile_setting.php" class="btn btn-success">
                <i class="fas fa-cog"></i> Notification Settings
            </a>
        </div>

        <div class="section">
            <h2>Daily Statistics (Last 30 Days)</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Notifications Sent</th>
                        <th>Successful</th>
                        <th>Failed</th>
                        <th>Success Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $weekly_stats_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['notifications_sent']; ?></td>
                        <td class="status-sent"><?php echo $row['successful']; ?></td>
                        <td class="status-failed"><?php echo $row['failed']; ?></td>
                        <td><?php echo $row['notifications_sent'] > 0 ? round(($row['successful'] / $row['notifications_sent']) * 100) : 0; ?>%</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Recent Notification Logs</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Projects</th>
                        <th>Ideas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $logs_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo date('M j, Y H:i', strtotime($log['sent_at'])); ?></td>
                        <td><?php echo htmlspecialchars($log['name']); ?></td>
                        <td><?php echo htmlspecialchars($log['email']); ?></td>
                        <td><?php echo ucfirst($log['notification_type']); ?></td>
                        <td><?php echo $log['projects_count']; ?></td>
                        <td><?php echo $log['ideas_count']; ?></td>
                        <td class="status-<?php echo $log['status']; ?>">
                            <?php echo ucfirst($log['status']); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
</body>
</html>