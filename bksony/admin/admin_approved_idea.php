<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set site name
$site_name = "IdeaNest";

// Handle approval action
if(isset($_POST['approve']) && isset($_POST['id'])) {
    $id = $_POST['id'];

    // Begin transaction
    $conn->begin_transaction();

    try {
        // First, get the idea details from blog table
        $stmt = $conn->prepare("SELECT * FROM blog WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($row = $result->fetch_assoc()) {
            // Insert into admin_approved_idea table
            $insertStmt = $conn->prepare("INSERT INTO admin_approved_idea 
                (er_number, project_name, project_type, classification, description, 
                submission_datetime, status, priority1, assigned_to, completion_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $insertStmt->bind_param(
                "ssssssssss",
                $row['er_number'],
                $row['project_name'],
                $row['project_type'],
                $row['classification'],
                $row['description'],
                $row['submission_datetime'],
                $row['status'],
                $row['priority1'],
                $row['assigned_to'],
                $row['completion_date']
            );

            $insertStmt->execute();

            // Delete from blog table
            $deleteStmt = $conn->prepare("DELETE FROM blog WHERE id = ?");
            $deleteStmt->bind_param("i", $id);
            $deleteStmt->execute();

            // Commit the transaction
            $conn->commit();

            $message = "Idea successfully approved and moved!";
            $alertType = "success";
        } else {
            throw new Exception("Idea not found!");
        }
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Handle reject action
if(isset($_POST['reject']) && isset($_POST['id'])) {
    $id = $_POST['id'];

    try {
        // Simply delete the idea from blog table
        $deleteStmt = $conn->prepare("DELETE FROM blog WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();

        if($deleteStmt->affected_rows > 0) {
            $message = "Idea has been rejected and removed from the system.";
            $alertType = "warning";
        } else {
            throw new Exception("Idea not found or already processed!");
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
        $alertType = "danger";
    }
}

// Fetch all ideas from blog table
$sql = "SELECT * FROM blog ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Idea Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Custom styles -->
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #f50057;
            --success-color: #10b981;
            --light-bg: #f8f9fa;
            --dark-text: #333;
            --light-text: #6c757d;
            --card-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            z-index: 1000;
            transition: var(--transition);
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
            color: var(--primary-color);
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
            color: var(--light-text);
            text-decoration: none;
            border-radius: 0.25rem;
            transition: var(--transition);
        }

        .sidebar-link i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        .sidebar-link.active {
            background-color: var(--primary-color);
            color: #fff;
        }

        .sidebar-link:hover:not(.active) {
            background-color: var(--light-bg);
            color: var(--primary-color);
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

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 1rem;
            transition: var(--transition);
        }

        /* Topbar Styles */
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
            color: var(--dark-text);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            margin-left: 1rem;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--primary-color);
        }

        .card {
            border: none;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin-bottom: 20px;
            height: 100%;
            border-radius: 0.5rem;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            font-size: 0.8rem;
            font-weight: 500;
        }

        .priority-high {
            color: var(--secondary-color);
        }

        .priority-medium {
            color: #ff9800;
        }

        .priority-low {
            color: #2196f3;
        }

        .action-buttons .btn {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            margin-right: 5px;
        }

        .idea-title {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .badge {
            font-weight: 500;
        }

        .modal-content {
            border: none;
            border-radius: 0.75rem;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 0.75rem 0.75rem 0 0;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #5c6bc0 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .stats-card {
            border-left: 4px solid var(--primary-color);
            background-color: white;
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

        .stats-icon.primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .stats-icon.success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .stats-icon.warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stats-icon.danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 0;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
        }

        .idea-card {
            position: relative;
        }

        .idea-description {
            height: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .idea-meta {
            font-size: 0.85rem;
            color: var(--light-text);
        }

        .card-footer {
            background-color: transparent;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem;
        }

        .search-box {
            max-width: 500px;
            margin: 0 auto 20px;
        }

        .dropdown-menu {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
        }

        .card-icon {
            font-size: 1.5rem;
            margin-right: 10px;
            color: var(--primary-color);
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }

        /* Animation for cards */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .idea-item {
            animation: fadeIn 0.5s ease-out forwards;
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
            <a href="admin.php" class="sidebar-link active">
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

        <hr class="sidebar-divider">
        <li class="sidebar-item">
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
    <div class="sidebar-footer">
        <a href="../Login/Login/logout.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
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
        <h1 class="page-title">Idea Management Dashboard</h1>
        <div class="topbar-actions">
            <div class="dropdown">
                <a href="#" class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../Login/Login/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Dashboard Header -->
    <div class="row dashboard-header align-items-center">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Ideas </h1>
            <p class="lead mb-0">Manage and approve submitted project ideas</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="bi bi-funnel me-2"></i>Filter Ideas
            </button>
            <button class="btn btn-outline-light ms-2">
                <i class="bi bi-download me-2"></i>Export
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stats-icon primary">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stats-info">
                        <div class="stats-label">Pending Ideas</div>
                        <div class="stats-value"><?php echo $result->num_rows; ?></div>
                    </div>
                    <div class="progress stats-progress" style="height: 4px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo min(100, ($result->num_rows/10) * 100); ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <span class="text-muted">Total pending review</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stats-icon warning">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stats-info">
                        <?php
                        $highPriorityCount = 0;
                        if($result->num_rows > 0) {
                            $result->data_seek(0); // Reset result pointer
                            while($row = $result->fetch_assoc()) {
                                if(strtolower($row['priority1']) == 'high') {
                                    $highPriorityCount++;
                                }
                            }
                            $result->data_seek(0); // Reset result pointer again
                        }
                        ?>
                        <div class="stats-label">High Priority</div>
                        <div class="stats-value"><?php echo $highPriorityCount; ?></div>
                    </div>
                    <div class="progress stats-progress" style="height: 4px;">
                        <div class="progress-bar bg-warning" style="width: <?php echo $result->num_rows > 0 ? ($highPriorityCount / $result->num_rows) * 100 : 0; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <span class="text-muted"><?php echo $result->num_rows > 0 ? round(($highPriorityCount / $result->num_rows) * 100) : 0; ?>% of total</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="stats-icon success">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stats-info">
                        <?php
                        $todayCount = 0;
                        if($result->num_rows > 0) {
                            $result->data_seek(0); // Reset result pointer
                            $today = date('Y-m-d');
                            while($row = $result->fetch_assoc()) {
                                if(date('Y-m-d', strtotime($row['created_at'])) == $today) {
                                    $todayCount++;
                                }
                            }
                            $result->data_seek(0); // Reset result pointer again
                        }
                        ?>
                        <div class="stats-label">Today's Submissions</div>
                        <div class="stats-value"><?php echo $todayCount; ?></div>
                    </div>
                    <div class="progress stats-progress" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $result->num_rows > 0 ? ($todayCount / $result->num_rows) * 100 : 0; ?>%"></div>
                    </div>
                    <div class="stats-percentage">
                        <span class="text-muted"><?php echo date('F j, Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(isset($message) || isset($error)): ?>
        <div class="alert alert-<?php echo isset($message) ? $alertType : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo isset($message) ? $message : $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Search Box -->
    <div class="search-box mb-4">
        <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search"></i>
                </span>
            <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Search ideas by name, type, or assigned person...">
        </div>
    </div>

    <?php if($result->num_rows > 0): ?>
        <div class="row" id="ideasContainer">
            <?php while($row = $result->fetch_assoc()): ?>
                <?php
                $statusClass = 'secondary';
                switch(strtolower($row['status'])) {
                    case 'active':
                        $statusClass = 'success';
                        break;
                    case 'pending':
                        $statusClass = 'warning';
                        break;
                    case 'blocked':
                        $statusClass = 'danger';
                        break;
                }

                $priorityClass = 'primary';
                $priorityIcon = 'arrow-down';
                switch(strtolower($row['priority1'])) {
                    case 'high':
                        $priorityClass = 'danger';
                        $priorityIcon = 'arrow-up';
                        break;
                    case 'medium':
                        $priorityClass = 'warning';
                        $priorityIcon = 'arrow-right';
                        break;
                }
                ?>
                <div class="col-lg-4 col-md-6 mb-4 idea-item"
                     data-type="<?php echo strtolower(htmlspecialchars($row['project_type'])); ?>"
                     data-priority="<?php echo strtolower(htmlspecialchars($row['priority1'])); ?>"
                     data-status="<?php echo strtolower(htmlspecialchars($row['status'])); ?>"
                     data-date="<?php echo date('Y-m-d', strtotime($row['submission_datetime'])); ?>">
                    <div class="card idea-card h-100">
                        <div class="card-header d-flex align-items-center">
                            <i class="bi bi-lightbulb-fill card-icon"></i>
                            <div>
                                <h5 class="idea-title mb-0"><?php echo htmlspecialchars($row['project_name']); ?></h5>
                                <small class="text-muted">ER: <?php echo htmlspecialchars($row['er_number']); ?></small>
                            </div>
                            <span class="badge bg-<?php echo $priorityClass; ?> ms-auto">
                            <i class="bi bi-<?php echo $priorityIcon; ?>"></i>
                            <?php echo htmlspecialchars($row['priority1']); ?>
                        </span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="badge rounded-pill bg-info"><?php echo htmlspecialchars($row['classification']); ?></span>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                            </div>

                            <p class="idea-description"><?php echo htmlspecialchars($row['description']); ?></p>

                            <div class="idea-meta mt-3">
                                <div class="row">
                                    <div class="col-6">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($row['assigned_to']); ?>
                                    </div>
                                    <div class="col-6 text-end">
                                        <i class="bi bi-calendar"></i>
                                        <?php echo date('M d, Y', strtotime($row['submission_datetime'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-sm btn-primary view-details"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-project="<?php echo htmlspecialchars($row['project_name']); ?>"
                                        data-er="<?php echo htmlspecialchars($row['er_number']); ?>"
                                        data-type="<?php echo htmlspecialchars($row['project_type']); ?>"
                                        data-classification="<?php echo htmlspecialchars($row['classification']); ?>"
                                        data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                        data-priority="<?php echo htmlspecialchars($row['priority1']); ?>"
                                        data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                        data-assigned="<?php echo htmlspecialchars($row['assigned_to']); ?>"
                                        data-submitted="<?php echo date('Y-m-d H:i', strtotime($row['submission_datetime'])); ?>"
                                        data-completion="<?php echo $row['completion_date']; ?>" data-bs-toggle="modal"
                                        data-bs-target="#detailsModal">
                                    <i class="bi bi-eye"></i> View Details
                                </button>
                                <div class="btn-group">
                                    <form method="post" class="d-inline-block" onsubmit="return confirm('Are you sure you want to approve this idea?');">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="approve" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline-block ms-1" onsubmit="return confirm('Are you sure you want to reject this idea? This will permanently delete it.');">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="reject" class="btn btn-sm btn-danger">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="no-results" id="noResults">
            <div class="card">
                <div class="card-body empty-state">
                    <i class="bi bi-search mb-3"></i>
                    <h4>No Matching Ideas</h4>
                    <p class="text-muted">No ideas match your current search or filter criteria.</p>
                    <button class="btn btn-outline-primary mt-2" id="clearFilters">
                        <i class="bi bi-arrow-repeat me-2"></i>Clear All Filters
                    </button>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="card">
            <div class="card-body empty-state">
                <i class="bi bi-inbox mb-3"></i>
                <h4>No Ideas Found</h4>
                <p class="text-muted">There are currently no pending ideas to review.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Idea Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <h3 id="modalProjectTitle"></h3>
                        <p class="badge bg-info" id="modalClassification"></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge text-bg-primary fs-6" id="modalER"></span>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <small class="text-muted">Status</small>
                                <h5 class="mt-1 mb-0"><span class="badge" id="modalStatus"></span></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <small class="text-muted">Priority</small>
                                <h5 class="mt-1 mb-0" id="modalPriority"></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <small class="text-muted">Type</small>
                                <h5 class="mt-1 mb-0" id="modalType"></h5>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Description</h6>
                    </div>
                    <div class="card-body">
                        <p id="modalDescription"></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6>Assigned To</h6>
                                <p class="mb-0" id="modalAssigned"></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6>Submission Date</h6>
                                <p class="mb-0" id="modalSubmitted"></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6>Completion Date</h6>
                                <p class="mb-0" id="modalCompletion"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <form method="post" class="me-auto">
                    <input type="hidden" name="id" id="rejectIdInput">
                    <button type="submit" name="reject" class="btn btn-danger">
                        <i class="bi bi-x-lg me-1"></i> Reject Idea
                    </button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form method="post">
                    <input type="hidden" name="id" id="approveIdInput">
                    <button type="submit" name="approve" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Approve Idea
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Ideas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="priorityFilter" id="priorityAll" value="all" checked>
                        <label class="btn btn-outline-secondary" for="priorityAll">All</label>

                        <input type="radio" class="btn-check" name="priorityFilter" id="priorityHigh" value="high">
                        <label class="btn btn-outline-danger" for="priorityHigh">High</label>

                        <input type="radio" class="btn-check" name="priorityFilter" id="priorityMedium" value="medium">
                        <label class="btn btn-outline-warning" for="priorityMedium">Medium</label>

                        <input type="radio" class="btn-check" name="priorityFilter" id="priorityLow" value="low">
                        <label class="btn btn-outline-primary" for="priorityLow">Low</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="statusFilter" id="statusAll" value="all" checked>
                        <label class="btn btn-outline-secondary" for="statusAll">All</label>

                        <input type="radio" class="btn-check" name="statusFilter" id="statusActive" value="active">
                        <label class="btn btn-outline-success" for="statusActive">Active</label>

                        <input type="radio" class="btn-check" name="statusFilter" id="statusPending" value="pending">
                        <label class="btn btn-outline-warning" for="statusPending">Pending</label>

                        <input type="radio" class="btn-check" name="statusFilter" id="statusBlocked" value="blocked">
                        <label class="btn btn-outline-danger" for="statusBlocked">Blocked</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Date Range</label>
                    <div class="row g-2">
                        <div class="col">
                            <input type="date" class="form-control" id="dateFrom">
                            <div class="form-text">From</div>
                        </div>
                        <div class="col">
                            <input type="date" class="form-control" id="dateTo">
                            <div class="form-text">To</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Project Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="all">All Types</option>
                        <option value="website">Website</option>
                        <option value="mobile app">Mobile App</option>
                        <option value="feature request">Feature Request</option>
                        <option value="bug fix">Bug Fix</option>
                        <option value="enhancement">Enhancement</option>
                        <option value="integration">Integration</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="resetFilters">Reset Filters</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Apply Filters</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                mainContent.classList.toggle('pushed');
            });
        }

        // Details Modal Functionality
        const detailsButtons = document.querySelectorAll('.view-details');
        detailsButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const project = this.getAttribute('data-project');
                const er = this.getAttribute('data-er');
                const type = this.getAttribute('data-type');
                const classification = this.getAttribute('data-classification');
                const status = this.getAttribute('data-status');
                const priority = this.getAttribute('data-priority');
                const description = this.getAttribute('data-description');
                const assigned = this.getAttribute('data-assigned');
                const submitted = this.getAttribute('data-submitted');
                const completion = this.getAttribute('data-completion');

                document.getElementById('modalProjectTitle').textContent = project;
                document.getElementById('modalER').textContent = 'ER: ' + er;
                document.getElementById('modalType').textContent = type;
                document.getElementById('modalClassification').textContent = classification;

                const statusElem = document.getElementById('modalStatus');
                statusElem.textContent = status;
                statusElem.className = 'badge';

                switch(status.toLowerCase()) {
                    case 'active':
                        statusElem.classList.add('bg-success');
                        break;
                    case 'pending':
                        statusElem.classList.add('bg-warning');
                        break;
                    case 'blocked':
                        statusElem.classList.add('bg-danger');
                        break;
                    default:
                        statusElem.classList.add('bg-secondary');
                }

                document.getElementById('modalPriority').textContent = priority;
                document.getElementById('modalDescription').textContent = description;
                document.getElementById('modalAssigned').textContent = assigned;
                document.getElementById('modalSubmitted').textContent = formatDateTime(submitted);
                document.getElementById('modalCompletion').textContent = completion ? formatDate(completion) : 'Not set';

                document.getElementById('approveIdInput').value = id;
                document.getElementById('rejectIdInput').value = id;
            });
        });

        // Format date and time
        function formatDateTime(dateTimeStr) {
            const dt = new Date(dateTimeStr);
            return dt.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatDate(dateStr) {
            const dt = new Date(dateStr);
            return dt.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        // Search Functionality
        const searchInput = document.getElementById('searchInput');
        const ideasContainer = document.getElementById('ideasContainer');
        const ideaItems = document.querySelectorAll('.idea-item');
        const noResults = document.getElementById('noResults');

        searchInput.addEventListener('input', filterIdeas);

        // Filter Functionality
        const priorityFilters = document.querySelectorAll('input[name="priorityFilter"]');
        const statusFilters = document.querySelectorAll('input[name="statusFilter"]');
        const typeFilter = document.getElementById('typeFilter');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const resetFiltersBtn = document.getElementById('resetFilters');
        const clearFiltersBtn = document.getElementById('clearFilters');

        priorityFilters.forEach(filter => {
            filter.addEventListener('change', filterIdeas);
        });

        statusFilters.forEach(filter => {
            filter.addEventListener('change', filterIdeas);
        });

        typeFilter.addEventListener('change', filterIdeas);
        dateFrom.addEventListener('change', filterIdeas);
        dateTo.addEventListener('change', filterIdeas);

        resetFiltersBtn.addEventListener('click', resetFilters);
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', resetFilters);
        }

        function resetFilters() {
            document.getElementById('priorityAll').checked = true;
            document.getElementById('statusAll').checked = true;
            typeFilter.value = 'all';
            dateFrom.value = '';
            dateTo.value = '';
            searchInput.value = '';
            filterIdeas();
        }

        function filterIdeas() {
            const searchTerm = searchInput.value.toLowerCase();
            const priorityValue = document.querySelector('input[name="priorityFilter"]:checked').value;
            const statusValue = document.querySelector('input[name="statusFilter"]:checked').value;
            const typeValue = typeFilter.value;
            const fromDate = dateFrom.value ? new Date(dateFrom.value) : null;
            const toDate = dateTo.value ? new Date(dateTo.value) : null;

            let visibleCount = 0;

            ideaItems.forEach(item => {
                const itemType = item.getAttribute('data-type');
                const itemPriority = item.getAttribute('data-priority');
                const itemStatus = item.getAttribute('data-status');
                const itemDate = new Date(item.getAttribute('data-date'));

                const projectName = item.querySelector('.idea-title').textContent.toLowerCase();
                const assignedTo = item.querySelector('.idea-meta').textContent.toLowerCase();

                // Search term match
                const matchesSearch = searchTerm === '' ||
                    projectName.includes(searchTerm) ||
                    itemType.includes(searchTerm) ||
                    assignedTo.includes(searchTerm);

                // Filter matches
                const matchesPriority = priorityValue === 'all' || itemPriority === priorityValue;
                const matchesStatus = statusValue === 'all' || itemStatus === statusValue;
                const matchesType = typeValue === 'all' || itemType === typeValue;

                // Date range match
                let matchesDate = true;
                if (fromDate && toDate) {
                    matchesDate = itemDate >= fromDate && itemDate <= toDate;
                } else if (fromDate) {
                    matchesDate = itemDate >= fromDate;
                } else if (toDate) {
                    matchesDate = itemDate <= toDate;
                }

                const visible = matchesSearch && matchesPriority && matchesStatus && matchesType && matchesDate;

                if (visible) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Show "no results" message if needed
            if (visibleCount === 0 && ideaItems.length > 0) {
                noResults.style.display = 'block';
            } else {
                noResults.style.display = 'none';
            }
        }
    });
</script>
</body>
</html>