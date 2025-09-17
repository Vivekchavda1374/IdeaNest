<?php
session_start();
include "../Login/Login/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get quick stats
$stats = [
    'users' => $conn->query("SELECT COUNT(*) as count FROM register")->fetch_assoc()['count'],
    'projects' => $conn->query("SELECT COUNT(*) as count FROM projects")->fetch_assoc()['count'],
    'mentors' => $conn->query("SELECT COUNT(*) as count FROM register WHERE role = 'mentor'")->fetch_assoc()['count'],
    'subadmins' => $conn->query("SELECT COUNT(*) as count FROM subadmins")->fetch_assoc()['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IdeaNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar_admin.css">
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <div class="topbar">
            <h1 class="page-title">Admin Dashboard</h1>
        </div>

        <div class="container-fluid">
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h2>Welcome to IdeaNest Admin</h2>
                            <p class="mb-0">Manage your platform efficiently with comprehensive tools and insights.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-people fs-1 text-primary"></i>
                            <h3 class="mt-2"><?php echo $stats['users']; ?></h3>
                            <p class="text-muted">Total Users</p>
                            <a href="overview.php" class="btn btn-sm btn-outline-primary">View Details</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-folder fs-1 text-success"></i>
                            <h3 class="mt-2"><?php echo $stats['projects']; ?></h3>
                            <p class="text-muted">Total Projects</p>
                            <a href="admin_view_project.php" class="btn btn-sm btn-outline-success">Manage</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-workspace fs-1 text-info"></i>
                            <h3 class="mt-2"><?php echo $stats['mentors']; ?></h3>
                            <p class="text-muted">Mentors</p>
                            <a href="manage_mentors.php" class="btn btn-sm btn-outline-info">Manage</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-plus fs-1 text-warning"></i>
                            <h3 class="mt-2"><?php echo $stats['subadmins']; ?></h3>
                            <p class="text-muted">Subadmins</p>
                            <a href="subadmin_overview.php" class="btn btn-sm btn-outline-warning">Manage</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="admin_view_project.php" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> Review Projects
                                </a>
                                <a href="user_manage_by_admin.php" class="btn btn-outline-success">
                                    <i class="bi bi-people"></i> Manage Users
                                </a>
                                <a href="subadmin/add_subadmin.php" class="btn btn-outline-info">
                                    <i class="bi bi-person-plus"></i> Add Subadmin
                                </a>
                                <a href="system_analytics.php" class="btn btn-outline-warning">
                                    <i class="bi bi-graph-up"></i> View Analytics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-info-circle"></i> System Information</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li><strong>Platform:</strong> IdeaNest v3.0</li>
                                <li><strong>Database:</strong> MySQL/MariaDB</li>
                                <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                                <li><strong>Server:</strong> Apache</li>
                                <li><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>