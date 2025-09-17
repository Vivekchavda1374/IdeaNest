<?php
session_start();
include "../Login/Login/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get basic statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM register")->fetch_assoc()['count'];
$total_projects = $conn->query("SELECT COUNT(*) as count FROM projects")->fetch_assoc()['count'];
$approved_projects = $conn->query("SELECT COUNT(*) as count FROM admin_approved_projects")->fetch_assoc()['count'];
$pending_projects = $conn->query("SELECT COUNT(*) as count FROM projects WHERE status = 'pending'")->fetch_assoc()['count'];
$total_mentors = $conn->query("SELECT COUNT(*) as count FROM register WHERE role = 'mentor'")->fetch_assoc()['count'];
$total_subadmins = $conn->query("SELECT COUNT(*) as count FROM subadmins")->fetch_assoc()['count'];

// Get recent users
$recent_users = $conn->query("SELECT id, name, email, department FROM register ORDER BY id DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Overview - IdeaNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar_admin.css">
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <div class="topbar">
            <h1 class="page-title">System Overview</h1>
        </div>

        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-people fs-1 text-primary"></i>
                            <h3 class="mt-2"><?php echo $total_users; ?></h3>
                            <p class="text-muted">Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-folder fs-1 text-info"></i>
                            <h3 class="mt-2"><?php echo $total_projects; ?></h3>
                            <p class="text-muted">Total Projects</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <h3 class="mt-2"><?php echo $approved_projects; ?></h3>
                            <p class="text-muted">Approved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-clock fs-1 text-warning"></i>
                            <h3 class="mt-2"><?php echo $pending_projects; ?></h3>
                            <p class="text-muted">Pending</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-workspace fs-1 text-info"></i>
                            <h3 class="mt-2"><?php echo $total_mentors; ?></h3>
                            <p class="text-muted">Mentors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-plus fs-1 text-secondary"></i>
                            <h3 class="mt-2"><?php echo $total_subadmins; ?></h3>
                            <p class="text-muted">Subadmins</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Users</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent_users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></td>
                                            <td>
                                                <a href="user_manage_by_admin.php?view=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>