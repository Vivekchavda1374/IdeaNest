<?php
session_start();
require_once '../config/config.php';
require_once '../Login/Login/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: overview.php");
    exit();
}

$user_id = (int)$_GET['id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: overview.php");
    exit();
}

// Get user's projects
$projects_query = "SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($projects_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result();

// Get user's activities (if mentor)
$mentor_stats = [];
if ($user['role'] === 'mentor') {
    // Get mentor sessions
    $sessions_query = "SELECT COUNT(*) as total_sessions FROM mentor_sessions WHERE mentor_id = ?";
    $stmt = $conn->prepare($sessions_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $mentor_stats['sessions'] = $stmt->get_result()->fetch_assoc()['total_sessions'];

    // Get paired students
    $students_query = "SELECT COUNT(*) as total_students FROM mentor_student_pairs WHERE mentor_id = ?";
    $stmt = $conn->prepare($students_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $mentor_stats['students'] = $stmt->get_result()->fetch_assoc()['total_students'];
}

// Handle status update
if ($_POST['action'] ?? '' == 'update_status') {
    $new_status = $_POST['status'];
    $update_query = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $user_id);
    $stmt->execute();
    header("Location: user_details.php?id=$user_id&updated=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo htmlspecialchars($user['name']); ?></title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/sidebar_admin.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 250px; padding: 20px; }
        .user-avatar { width: 80px; height: 80px; border-radius: 50%; background: #007bff; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 2rem; }
        .stat-card { border-left: 4px solid #007bff; }
        .activity-timeline { border-left: 2px solid #dee2e6; padding-left: 20px; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: -25px; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: #007bff; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="overview.php" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <h1><i class="bi bi-person"></i> User Details</h1>
            </div>
            <?php if (isset($_GET['updated'])) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                User status updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
        </div>

        <!-- User Profile Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="user-avatar mx-auto mb-3">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                        <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                        <span class="badge bg-<?php
                            echo $user['role'] === 'admin' ? 'danger' :
                                ($user['role'] === 'mentor' ? 'success' :
                                ($user['role'] === 'subadmin' ? 'warning' : 'primary'));
                            ?> fs-6">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Contact Information</h6>
                                <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><i class="bi bi-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                                <p><i class="bi bi-clock"></i> Last Login: 
                                    <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Account Status</h6>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <select name="status" class="form-select mb-2" onchange="this.form.submit()">
                                        <option value="active" <?php echo ($user['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($user['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="suspended" <?php echo ($user['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </form>
                                <?php if ($user['github_username']) : ?>
                                <p><i class="bi bi-github"></i> GitHub: 
                                    <a href="https://github.com/<?php echo htmlspecialchars($user['github_username']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($user['github_username']); ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <?php if ($user['role'] === 'mentor') : ?>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-people fs-1 text-primary mb-2"></i>
                        <h3><?php echo $mentor_stats['students']; ?></h3>
                        <p class="mb-0">Students Mentored</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-event fs-1 text-success mb-2"></i>
                        <h3><?php echo $mentor_stats['sessions']; ?></h3>
                        <p class="mb-0">Sessions Conducted</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-kanban fs-1 text-info mb-2"></i>
                        <h3><?php echo $projects->num_rows; ?></h3>
                        <p class="mb-0">Projects Created</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- User's Projects -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-kanban"></i> User's Projects (<?php echo $projects->num_rows; ?>)</h5>
                <a href="../Admin/admin_view_project.php?user_id=<?php echo $user_id; ?>" class="btn btn-sm btn-outline-primary">
                    View All Projects
                </a>
            </div>
            <div class="card-body">
                <?php if ($projects->num_rows > 0) : ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Project Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($project = $projects->fetch_assoc()) : ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($project['title']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . '...'; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($project['category'] ?? 'General'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php
                                        echo $project['status'] === 'approved' ? 'success' :
                                            ($project['status'] === 'rejected' ? 'danger' : 'warning');
                                    ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                <td>
                                    <a href="../Admin/admin_view_project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else : ?>
                <div class="text-center py-4">
                    <i class="bi bi-kanban fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No projects found for this user.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="btn btn-outline-primary w-100">
                            <i class="bi bi-envelope"></i> Send Email
                        </a>
                    </div>
                    <?php if ($user['role'] === 'mentor') : ?>
                    <div class="col-md-3 mb-2">
                        <a href="mentor_details.php?id=<?php echo $user_id; ?>" class="btn btn-outline-success w-100">
                            <i class="bi bi-person-workspace"></i> Mentor Details
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3 mb-2">
                        <a href="../Admin/admin_view_project.php?user_id=<?php echo $user_id; ?>" class="btn btn-outline-info w-100">
                            <i class="bi bi-kanban"></i> View Projects
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-danger w-100" onclick="confirmAction('suspend')">
                            <i class="bi bi-person-x"></i> Suspend User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmAction(action) {
            if (confirm(`Are you sure you want to ${action} this user?`)) {
                // Handle action
                console.log(`${action} action confirmed`);
            }
        }
    </script>
</body>
</html>