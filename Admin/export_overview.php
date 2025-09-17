<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get statistics for display
$stats_query = "SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN role = 'student' THEN 1 END) as students,
    COUNT(CASE WHEN role = 'mentor' THEN 1 END) as mentors,
    0 as subadmins
    FROM register";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get subadmin count separately
$subadmin_count = $conn->query("SELECT COUNT(*) as count FROM subadmins")->fetch_assoc();
$stats['subadmins'] = $subadmin_count['count'];

$project_stats = $conn->query("SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
    FROM projects")->fetch_assoc();
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

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card export-card">
                    <div class="card-header">
                        <h5><i class="bi bi-file-earmark-spreadsheet"></i> System Data Export</h5>
                    </div>
                    <div class="card-body">
                        <p>Export comprehensive system data including users, projects, and statistics.</p>
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 class="text-primary"><?php echo $stats['total_users']; ?></h4>
                                    <small>Total Users</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 class="text-success"><?php echo $project_stats['total']; ?></h4>
                                    <small>Total Projects</small>
                                </div>
                            </div>
                        </div>
                        <a href="export_data.php" class="btn btn-success w-100">
                            <i class="bi bi-download"></i> Download CSV Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-info-circle"></i> Export Information</h5>
                    </div>
                    <div class="card-body">
                        <h6>Included Data:</h6>
                        <ul>
                            <li>System Statistics</li>
                            <li>User Details (<?php echo $stats['students']; ?> Students, <?php echo $stats['mentors']; ?> Mentors)</li>
                            <li>Project Information (<?php echo $project_stats['approved']; ?> Approved, <?php echo $project_stats['pending']; ?> Pending)</li>
                            <li>Activity Reports</li>
                        </ul>
                        <small class="text-muted">Last updated: <?php echo date('Y-m-d H:i:s'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>