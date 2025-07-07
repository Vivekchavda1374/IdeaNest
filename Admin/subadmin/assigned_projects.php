<?php
session_start();
if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}
include_once "../../Login/Login/db.php";
$subadmin_id = $_SESSION['subadmin_id'];
// Fetch subadmin's classification
$stmt = $conn->prepare("SELECT software_classification, hardware_classification FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($software_classification, $hardware_classification);
$stmt->fetch();
$stmt->close();
// Fetch projects matching either classification
$stmt = $conn->prepare("SELECT id, project_name, project_type, classification, description, status FROM projects WHERE classification = ? OR classification = ?");
$stmt->bind_param("ss", $software_classification, $hardware_classification);
$stmt->execute();
$result = $stmt->get_result();

require_once dirname(__FILE__) . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
require_once dirname(__FILE__) . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once dirname(__FILE__) . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__FILE__) . '/../../Admin/project_notification.php';

// Handle approve/reject actions
$action_message = '';
if (isset($_POST['action']) && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    $status = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $rejection_reason = $_POST['action'] === 'reject' ? trim($_POST['rejection_reason'] ?? '') : '';
    // Update project status
    $stmt = $conn->prepare("UPDATE projects SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $project_id);
    if ($stmt->execute()) {
        // Use the shared notification function for email
        $result = sendProjectStatusEmail($project_id, $status, $rejection_reason);
        if ($result['success']) {
            $action_message = "Project status updated and email sent to user.";
        } else {
            $action_message = "Project status updated, but email could not be sent. " . $result['message'];
        }
    } else {
        $action_message = "Failed to update project status.";
    }
    $stmt->close();
    // Refresh project list after action
    header("Location: assigned_projects.php?msg=" . urlencode($action_message));
    exit();
}
if (isset($_GET['msg'])) {
    $action_message = htmlspecialchars($_GET['msg']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 250px; background: rgba(255,255,255,0.95); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08); border-radius: 0 2rem 2rem 0; z-index: 1000; transition: all 0.3s; overflow-y: auto; padding: 1.5rem 1rem 1rem 1.5rem; }
        .sidebar-header { padding: 1rem 0; text-align: center; border-bottom: 1px solid #f1f1f1; margin-bottom: 1.5rem; }
        .sidebar-brand { font-size: 1.7rem; font-weight: 700; color: #4f46e5; text-decoration: none; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; letter-spacing: 1px; }
        .sidebar-brand i { margin-right: 0.6rem; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-item { margin-bottom: 0.7rem; }
        .sidebar-link { display: flex; align-items: center; padding: 0.85rem 1.1rem; color: #6366f1; text-decoration: none; border-radius: 0.5rem; font-weight: 500; font-size: 1.08rem; transition: all 0.2s; background: transparent; }
        .sidebar-link i { margin-right: 0.85rem; font-size: 1.3rem; }
        .sidebar-link.active, .sidebar-link:focus { background: linear-gradient(90deg, #6366f1 0%, #a5b4fc 100%); color: #fff; box-shadow: 0 2px 8px rgba(99,102,241,0.08); }
        .sidebar-link:hover:not(.active) { background: #f1f5f9; color: #4f46e5; }
        .sidebar-divider { margin: 1.2rem 0; border-top: 1.5px solid #e5e7eb; }
        .sidebar-footer { padding: 1.2rem 0 0.5rem 0; border-top: 1px solid #f1f1f1; margin-top: 1.5rem; }
        .main-content { margin-left: 250px; padding: 2.5rem 2rem 2rem 2rem; transition: all 0.3s; max-width: 100vw; width: 100%; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 1.2rem 0 1.5rem 0; margin-bottom: 2.5rem; }
        .page-title { font-size: 2rem; font-weight: 700; margin: 0; color: #4f46e5; letter-spacing: 1px; }
        .topbar-actions { display: flex; align-items: center; }
        .user-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #6366f1 0%, #a5b4fc 100%); display: flex; align-items: center; justify-content: center; color: #fff; margin-left: 1.2rem; font-size: 1.5rem; box-shadow: 0 2px 8px rgba(99,102,241,0.08); }
        .glass-card { background: rgba(255,255,255,0.85); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.10); backdrop-filter: blur(8px); border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.18); transition: transform 0.15s, box-shadow 0.15s; }
        .glass-card:hover { transform: translateY(-4px) scale(1.03); box-shadow: 0 16px 32px 0 rgba(99,102,241,0.13); z-index: 2; }
        .alert { border-radius: 0.75rem; }
        .card { border: none; }
        @media (max-width: 991.98px) { .sidebar { transform: translateX(-100%); border-radius: 0 0 2rem 2rem; } .sidebar.show { transform: translateX(0); } .main-content { margin-left: 0; padding: 1rem; } .main-content.pushed { margin-left: 250px; } }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="bi bi-lightbulb"></i>
                <span>IdeaNest Subadmin</span>
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="dashboard.php" class="sidebar-link">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="profile.php" class="sidebar-link">
                    <i class="bi bi-person-circle"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="assigned_projects.php" class="sidebar-link active">
                    <i class="bi bi-kanban"></i>
                    <span>Assigned Projects</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#notifications" class="sidebar-link">
                    <i class="bi bi-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a href="#support" class="sidebar-link">
                    <i class="bi bi-envelope"></i>
                    <span>Support</span>
                </a>
            </li>
            <hr class="sidebar-divider">
        </ul>
        <div class="sidebar-footer">
            <a href="../../Login/Login/logout.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
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
            <h1 class="page-title">Assigned Projects</h1>
            <div class="topbar-actions">
                <div class="dropdown">
                    <a href="#" class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../../Login/Login/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Assigned Projects Table -->
        <div class="row mb-4">
            <div class="col-12 col-lg-11">
                <div class="glass-card card p-4 w-100">
                    <h5 class="mb-3"><i class="bi bi-kanban me-2"></i>Assigned Projects (by Classification)</h5>
                    <?php if ($action_message): ?>
                        <div class="alert alert-info"> <?php echo $action_message; ?> </div>
                    <?php endif; ?>
                    <?php if ($result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Type</th>
                                        <th>Classification</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['project_type']); ?></td>
                                            <td><?php echo htmlspecialchars($row['classification']); ?></td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td><span class="badge bg-<?php echo $row['status']=='approved'?'success':($row['status']=='pending'?'warning text-dark':'danger'); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                            <td>
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <form method="post" style="display:inline-block;">
                                                        <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                                    </form>
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $row['id']; ?>">Reject</button>
                                                    <!-- Reject Modal -->
                                                    <div class="modal fade" id="rejectModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                      <div class="modal-dialog">
                                                        <div class="modal-content">
                                                          <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel<?php echo $row['id']; ?>">Reject Project</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                          </div>
                                                          <form method="post">
                                                            <div class="modal-body">
                                                              <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                                              <div class="mb-3">
                                                                <label for="rejection_reason<?php echo $row['id']; ?>" class="form-label">Reason for rejection</label>
                                                                <textarea class="form-control" name="rejection_reason" id="rejection_reason<?php echo $row['id']; ?>" required></textarea>
                                                              </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                              <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                                                            </div>
                                                          </form>
                                                        </div>
                                                      </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No projects found for your classification.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
            document.querySelector('.main-content').classList.toggle('pushed');
        });
    </script>
</body>
</html> 