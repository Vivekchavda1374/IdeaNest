<?php
session_start();
if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}

// Use local XAMPP database connection
$conn = new mysqli("localhost", "root", "", "ictmu6ya_ideanest", 3306, "/opt/lampp/var/mysql/mysql.sock");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
include_once "sidebar_subadmin.php";

$subadmin_id = $_SESSION['subadmin_id'];

// Fetch subadmin info
$stmt = $conn->prepare("SELECT email, name, software_classification, hardware_classification FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($email, $name, $software_classification, $hardware_classification);
$stmt->fetch();
$stmt->close();

// Get project counts
$total_projects = $conn->query("SELECT COUNT(*) FROM projects")->fetch_row()[0];
$pending_projects = $conn->query("SELECT COUNT(*) FROM projects WHERE status = 'pending'")->fetch_row()[0];
$approved_projects = $conn->query("SELECT COUNT(*) FROM admin_approved_projects")->fetch_row()[0];

// Get recent projects
$recent_projects = $conn->query("SELECT project_name, project_type, status FROM projects ORDER BY id DESC LIMIT 5");

ob_start();
?>


<!-- Welcome Section -->
<div class="mb-4 fade-in-up">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($name ?: 'Subadmin'); ?>! 👋</h2>
            <p class="text-muted mb-0">Here's your project overview and recent activity</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
        </div>
    </div>
</div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo $total_projects; ?></h3>
                        <p class="mb-0">Total Projects</p>
                        <small class="opacity-75">All submissions</small>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-kanban" style="font-size: 2.5rem; opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card warning" data-aos="fade-up" data-aos-delay="200">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo $pending_projects; ?></h3>
                        <p class="mb-0">Pending Review</p>
                        <small class="opacity-75">Awaiting approval</small>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-clock-history" style="font-size: 2.5rem; opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card success" data-aos="fade-up" data-aos-delay="300">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo $approved_projects; ?></h3>
                        <p class="mb-0">Approved</p>
                        <small class="opacity-75">Successfully reviewed</small>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stat-card info" data-aos="fade-up" data-aos-delay="400">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1"><?php echo (!empty($software_classification) ? 1 : 0) + (!empty($hardware_classification) ? 1 : 0); ?></h3>
                        <p class="mb-0">Classifications</p>
                        <small class="opacity-75">Active specializations</small>
                    </div>
                    <div class="stat-icon">
                        <i class="bi bi-tags" style="font-size: 2.5rem; opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Classifications & Quick Actions -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-gear me-2 text-primary"></i>Your Classifications</h5>
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="d-flex align-items-center justify-content-between p-3 rounded" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white;">
                                <div>
                                    <strong>Software</strong>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($software_classification ?: 'Not Set'); ?></span>
                                    </div>
                                </div>
                                <i class="bi bi-code-slash" style="font-size: 1.5rem; opacity: 0.8;"></i>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between p-3 rounded" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                                <div>
                                    <strong>Hardware</strong>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($hardware_classification ?: 'Not Set'); ?></span>
                                    </div>
                                </div>
                                <i class="bi bi-cpu" style="font-size: 1.5rem; opacity: 0.8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning me-2 text-warning"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="assigned_projects.php" class="btn btn-primary d-flex align-items-center justify-content-center" data-loading="Loading projects...">
                            <i class="bi bi-kanban me-2"></i> View Assigned Projects
                        </a>
                        <a href="profile.php" class="btn btn-outline-primary d-flex align-items-center justify-content-center" data-loading="Loading profile...">
                            <i class="bi bi-person me-2"></i> Edit Profile Settings
                        </a>
                        <a href="support.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" data-loading="Loading support...">
                            <i class="bi bi-headset me-2"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Projects -->
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-info"></i>Recent Projects</h5>
            <a href="assigned_projects.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-right me-1"></i> View All
            </a>
        </div>
        <div class="card-body">
            <?php if ($recent_projects->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0"><i class="bi bi-folder me-1"></i> Project Name</th>
                                <th class="border-0"><i class="bi bi-tag me-1"></i> Type</th>
                                <th class="border-0"><i class="bi bi-flag me-1"></i> Status</th>
                                <th class="border-0 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($project = $recent_projects->fetch_assoc()): ?>
                            <tr class="border-bottom">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="project-icon me-3">
                                            <i class="bi bi-<?php echo $project['project_type'] == 'Software' ? 'code-slash' : 'cpu'; ?> text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($project['project_name']); ?></h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $project['project_type'] == 'Software' ? 'primary' : 'success'; ?> bg-opacity-10 text-<?php echo $project['project_type'] == 'Software' ? 'primary' : 'success'; ?>">
                                        <?php echo htmlspecialchars($project['project_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $project['status'] == 'approved' ? 'success' : 
                                            ($project['status'] == 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <i class="bi bi-<?php 
                                            echo $project['status'] == 'approved' ? 'check-circle' : 
                                                ($project['status'] == 'pending' ? 'clock' : 'x-circle'); 
                                        ?> me-1"></i>
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm" onclick="viewProject('<?php echo $project['project_name']; ?>')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="empty-state">
                        <div class="empty-icon mb-3">
                            <i class="bi bi-inbox" style="font-size: 4rem; color: #e2e8f0;"></i>
                        </div>
                        <h5 class="text-muted mb-2">No Projects Yet</h5>
                        <p class="text-muted mb-3">Projects will appear here once they are assigned to you for review.</p>
                        <a href="assigned_projects.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Check Assignments
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function viewProject(projectName) {
        showNotification('Opening project: ' + projectName, 'info');
        // Add your project viewing logic here
        setTimeout(() => {
            window.location.href = 'assigned_projects.php';
        }, 1000);
    }
    </script>
<?php
$content = ob_get_clean();
renderLayout("Dashboard", $content, "dashboard");
?>