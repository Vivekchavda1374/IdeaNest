<?php
$site_name = "IdeaNest Admin"?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_name); ?> - Admin Dashboard</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar_admin.css">
</head>
<body>

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
            <a href="admin_view_project.php" class="sidebar-link active">
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
            <a href="notifications.php" class="sidebar-link">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </a>
        </li>
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
</body>
</html>