<?php
session_start();
require_once '../config/config.php';
include "../Login/Login/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get subadmin statistics
$total_subadmins = $conn->query("SELECT COUNT(*) as count FROM subadmins")->fetch_assoc()['count'];
$active_subadmins = $conn->query("SELECT COUNT(*) as count FROM subadmins WHERE status = 'active'")->fetch_assoc()['count'];
$pending_requests = $conn->query("SELECT COUNT(*) as count FROM subadmin_classification_requests WHERE status = 'pending'")->fetch_assoc()['count'];
$open_tickets = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status IN ('open', 'in_progress')")->fetch_assoc()['count'];

// Get subadmin list with domain information
$subadmins = $conn->query("
    SELECT s.*, 
           COUNT(scr.id) as pending_requests,
           COUNT(st.id) as total_tickets
    FROM subadmins s
    LEFT JOIN subadmin_classification_requests scr ON s.id = scr.subadmin_id AND scr.status = 'pending'
    LEFT JOIN support_tickets st ON s.id = st.subadmin_id
    GROUP BY s.id
    ORDER BY s.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subadmin Overview - IdeaNest</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar_admin.css">
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <button class="btn d-lg-none mb-3" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar">
            <h1 class="page-title">Subadmin Overview</h1>
        </div>

        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-people fs-1 text-primary"></i>
                            <h3 class="mt-2"><?php echo $total_subadmins; ?></h3>
                            <p class="text-muted">Total Subadmins</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <h3 class="mt-2"><?php echo $active_subadmins; ?></h3>
                            <p class="text-muted">Active</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-clock fs-1 text-warning"></i>
                            <h3 class="mt-2"><?php echo $pending_requests; ?></h3>
                            <p class="text-muted">Pending Requests</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-ticket fs-1 text-info"></i>
                            <h3 class="mt-2"><?php echo $open_tickets; ?></h3>
                            <p class="text-muted">Open Tickets</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subadmin List -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Subadmin Management</h5>
                            <a href="subadmin/add_subadmin.php" class="btn btn-primary">
                                <i class="bi bi-plus"></i> Add Subadmin
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Domain</th>
                                            <th>Domains</th>
                                            <th>Status</th>
                                            <th>Requests</th>
                                            <th>Tickets</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subadmins as $subadmin) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(trim(($subadmin['first_name'] ?? '') . ' ' . ($subadmin['last_name'] ?? '')) ?: 'Not Set'); ?></td>
                                            <td><?php echo htmlspecialchars($subadmin['email']); ?></td>
                                            <td>
                                                <?php 
                                                $domains_display = $subadmin['domains'] ?? $subadmin['domain'] ?? 'Not Set';
                                                if (strlen($domains_display) > 30) {
                                                    echo '<span title="' . htmlspecialchars($domains_display) . '">' . htmlspecialchars(substr($domains_display, 0, 30)) . '...</span>';
                                                } else {
                                                    echo htmlspecialchars($domains_display);
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $domains_display = $subadmin['domains'] ?? 'Not Set';
                                                if ($domains_display !== 'Not Set' && strlen($domains_display) > 50) {
                                                    echo '<span title="' . htmlspecialchars($domains_display) . '">' . htmlspecialchars(substr($domains_display, 0, 50)) . '...</span>';
                                                } else {
                                                    echo htmlspecialchars($domains_display);
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $subadmin['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($subadmin['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($subadmin['pending_requests'] > 0) : ?>
                                                    <span class="badge bg-warning"><?php echo $subadmin['pending_requests']; ?></span>
                                                <?php else : ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($subadmin['total_tickets'] > 0) : ?>
                                                    <span class="badge bg-info"><?php echo $subadmin['total_tickets']; ?></span>
                                                <?php else : ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="subadmin/add_subadmin.php?tab=manage" class="btn btn-outline-primary" title="Manage">
                                                        <i class="bi bi-gear"></i>
                                                    </a>
                                                </div>
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