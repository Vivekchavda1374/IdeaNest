<?php
/**
 * IdeaNest Production Status Dashboard
 * Quick overview of system status
 */

// Load configuration
require_once 'Login/Login/db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>IdeaNest Production Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .card h3 { color: #333; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .status { display: flex; align-items: center; gap: 10px; margin: 10px 0; }
        .status-icon { width: 20px; height: 20px; border-radius: 50%; }
        .online { background: #10b981; }
        .offline { background: #ef4444; }
        .warning { background: #f59e0b; }
        .metric { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .metric:last-child { border-bottom: none; }
        .value { font-weight: bold; color: #667eea; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .refresh { float: right; font-size: 14px; color: #6b7280; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🚀 IdeaNest Production Status</h1>
        <p>Domain: https://ictmu.in/hcd/IdeaNest/</p>
        <p>Last Updated: <?php echo date('Y-m-d H:i:s'); ?> <a href="?" class="refresh">🔄 Refresh</a></p>
    </div>

    <div class="grid">
        <!-- System Status -->
        <div class="card">
            <h3>🖥️ System Status</h3>
            <?php
            $db_status = $conn && !$conn->connect_error;
            $upload_status = is_writable('user/uploads/') && is_writable('user/forms/uploads/');
            $email_config = file_exists('config/email_config.php');
            ?>
            <div class="status">
                <div class="status-icon <?php echo $db_status ? 'online' : 'offline'; ?>"></div>
                Database: <?php echo $db_status ? 'Connected' : 'Disconnected'; ?>
            </div>
            <div class="status">
                <div class="status-icon <?php echo $upload_status ? 'online' : 'offline'; ?>"></div>
                File Uploads: <?php echo $upload_status ? 'Working' : 'Error'; ?>
            </div>
            <div class="status">
                <div class="status-icon <?php echo $email_config ? 'online' : 'offline'; ?>"></div>
                Email Config: <?php echo $email_config ? 'Configured' : 'Missing'; ?>
            </div>
        </div>

        <!-- Database Metrics -->
        <div class="card">
            <h3>📊 Database Metrics</h3>
            <?php if ($db_status): ?>
                <?php
                $users = $conn->query("SELECT COUNT(*) as count FROM register")->fetch_assoc()['count'];
                $projects = $conn->query("SELECT COUNT(*) as count FROM projects")->fetch_assoc()['count'];
                $approved = $conn->query("SELECT COUNT(*) as count FROM admin_approved_projects")->fetch_assoc()['count'];
                $ideas = $conn->query("SELECT COUNT(*) as count FROM blog")->fetch_assoc()['count'];
                ?>
                <div class="metric"><span>Total Users</span><span class="value"><?php echo $users; ?></span></div>
                <div class="metric"><span>Total Projects</span><span class="value"><?php echo $projects; ?></span></div>
                <div class="metric"><span>Approved Projects</span><span class="value"><?php echo $approved; ?></span></div>
                <div class="metric"><span>Ideas Shared</span><span class="value"><?php echo $ideas; ?></span></div>
            <?php else: ?>
                <p style="color: #ef4444;">Database connection failed</p>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <h3>📈 Recent Activity (24h)</h3>
            <?php if ($db_status): ?>
                <?php
                $new_users = $conn->query("SELECT COUNT(*) as count FROM register WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'];
                $new_projects = $conn->query("SELECT COUNT(*) as count FROM projects WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'];
                $new_ideas = $conn->query("SELECT COUNT(*) as count FROM blog WHERE submission_datetime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['count'];
                ?>
                <div class="metric"><span>New Users</span><span class="value"><?php echo $new_users; ?></span></div>
                <div class="metric"><span>New Projects</span><span class="value"><?php echo $new_projects; ?></span></div>
                <div class="metric"><span>New Ideas</span><span class="value"><?php echo $new_ideas; ?></span></div>
            <?php else: ?>
                <p style="color: #ef4444;">Database connection failed</p>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h3>⚡ Quick Actions</h3>
            <div class="actions">
                <a href="production_system_test.php" class="btn btn-primary">🧪 Full System Test</a>
                <a href="test_email.php" class="btn btn-success">📧 Test Email</a>
                <a href="Login/Login/login.php" class="btn btn-warning">🔐 Test Login</a>
                <a href="Admin/admin.php" class="btn btn-danger">👨💼 Admin Panel</a>
            </div>
        </div>

        <!-- File System -->
        <div class="card">
            <h3>📁 File System</h3>
            <?php
            $upload_size = 0;
            $upload_files = 0;
            if (is_dir('user/uploads/')) {
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('user/uploads/'));
                foreach ($files as $file) {
                    if ($file->isFile()) {
                        $upload_size += $file->getSize();
                        $upload_files++;
                    }
                }
            }
            ?>
            <div class="metric"><span>Upload Files</span><span class="value"><?php echo $upload_files; ?></span></div>
            <div class="metric"><span>Upload Size</span><span class="value"><?php echo round($upload_size / 1024 / 1024, 2); ?> MB</span></div>
            <div class="metric"><span>Disk Space</span><span class="value"><?php echo round(disk_free_space('.') / 1024 / 1024 / 1024, 2); ?> GB Free</span></div>
        </div>

        <!-- Environment Info -->
        <div class="card">
            <h3>🔧 Environment</h3>
            <div class="metric"><span>PHP Version</span><span class="value"><?php echo PHP_VERSION; ?></span></div>
            <div class="metric"><span>Server</span><span class="value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span></div>
            <div class="metric"><span>Environment</span><span class="value"><?php echo $_ENV['APP_ENV'] ?? 'development'; ?></span></div>
            <div class="metric"><span>Memory Limit</span><span class="value"><?php echo ini_get('memory_limit'); ?></span></div>
        </div>
    </div>

    <!-- Logs Section -->
    <div class="card">
        <h3>📋 Recent Logs</h3>
        <?php
        $log_files = ['logs/error.log', 'logs/weekly_notifications.log', 'logs/mentor_emails.log'];
        foreach ($log_files as $log_file) {
            if (file_exists($log_file) && is_readable($log_file)) {
                echo "<h4>" . basename($log_file) . "</h4>";
                $lines = file($log_file);
                $recent_lines = array_slice($lines, -5);
                echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px; overflow-x: auto;'>";
                echo htmlspecialchars(implode('', $recent_lines));
                echo "</pre>";
            }
        }
        ?>
    </div>

    <div style="text-align: center; margin-top: 30px; color: #6b7280;">
        <p>IdeaNest Production Dashboard | <a href="https://ictmu.in/hcd/IdeaNest/">Visit Site</a></p>
    </div>
</div>
</body>
</html>