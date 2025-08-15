<?php
session_start();
if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}

// Include database connection and layout
include_once "../../Login/Login/db.php";
include_once "sidebar_subadmin.php"; // Include the layout file

$subadmin_id = $_SESSION['subadmin_id'];

// Fetch subadmin basic info
$stmt = $conn->prepare("SELECT email, name FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($email, $name);
$stmt->fetch();
$stmt->close();

// Fetch subadmin's classification
$stmt = $conn->prepare("SELECT software_classification, hardware_classification FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($software_classification, $hardware_classification);
$stmt->fetch();
$stmt->close();

// Assigned Projects count
$stmt = $conn->prepare("SELECT COUNT(*) FROM projects WHERE classification = ? OR classification = ?");
$stmt->bind_param("ss", $software_classification, $hardware_classification);
$stmt->execute();
$stmt->bind_result($assigned_projects_count);
$stmt->fetch();
$stmt->close();

// Pending Tasks count
$stmt = $conn->prepare("SELECT COUNT(*) FROM projects WHERE (classification = ? OR classification = ?) AND status = 'pending'");
$stmt->bind_param("ss", $software_classification, $hardware_classification);
$stmt->execute();
$stmt->bind_result($pending_tasks_count);
$stmt->fetch();
$stmt->close();

// Approved Projects count
$stmt = $conn->prepare("SELECT COUNT(*) FROM projects WHERE (classification = ? OR classification = ?) AND status = 'approved'");
$stmt->bind_param("ss", $software_classification, $hardware_classification);
$stmt->execute();
$stmt->bind_result($approved_projects_count);
$stmt->fetch();
$stmt->close();

// Fetch recent projects
$stmt = $conn->prepare("SELECT id, project_name, project_type, classification, description, status FROM projects WHERE classification = ? OR classification = ? ORDER BY id DESC LIMIT 5");
$stmt->bind_param("ss", $software_classification, $hardware_classification);
$stmt->execute();
$result = $stmt->get_result();

// Notifications and messages count (dummy data)
$notifications_count = 3;
$messages_count = 2;

// Build the dashboard content
ob_start();
?>

  <link rel="stylesheet" href="../../assets/css/subadmin_dashboard.css">

    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="welcome-content">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="welcome-title">Welcome back, <?php echo htmlspecialchars($name); ?>!</h2>
                    <p class="welcome-subtitle mb-0">Here's what's happening with your projects today.</p>
                </div>
                <div class="d-none d-md-block">
                    <i class="bi bi-lightbulb-fill" style="font-size: 3rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon projects">
                <i class="bi bi-kanban-fill"></i>
            </div>
            <div class="stat-number"><?php echo $assigned_projects_count; ?></div>
            <div class="stat-label">Assigned Projects</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-number"><?php echo $pending_tasks_count; ?></div>
            <div class="stat-label">Pending Tasks</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon approved">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-number"><?php echo $approved_projects_count; ?></div>
            <div class="stat-label">Approved Projects</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon notifications">
                <i class="bi bi-bell-fill"></i>
            </div>
            <div class="stat-number"><?php echo $notifications_count; ?></div>
            <div class="stat-label">Notifications</div>
        </div>
    </div>

    <!-- Recent Projects -->
    <div class="projects-table-card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="bi bi-kanban-fill"></i>
                Recent Assigned Projects
            </h5>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Type</th>
                        <th>Classification</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['project_name']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($row['project_type']); ?></td>
                            <td>
                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($row['classification']); ?></span>
                            </td>
                            <td>
                                <div style="max-width: 200px;" class="text-truncate">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php
                                echo $row['status'] == 'approved' ? 'success' :
                                        ($row['status'] == 'pending' ? 'warning' : 'danger');
                                ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>No Projects Found</h5>
                <p class="mb-0">You don't have any projects assigned yet for your classification.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h5 class="mb-3" style="color: var(--text-primary); font-weight: 700;">Quick Actions</h5>
        <div class="d-flex flex-wrap">
            <a href="assigned_projects.php" class="action-button action-primary">
                <i class="bi bi-kanban-fill"></i>
                View All Projects
            </a>
            <a href="profile.php" class="action-button action-outline">
                <i class="bi bi-person-circle"></i>
                Edit Profile
            </a>
            <a href="notifications.php" class="action-button action-outline">
                <i class="bi bi-bell-fill"></i>
                View Notifications
            </a>
            <a href="support.php" class="action-button action-outline">
                <i class="bi bi-envelope-fill"></i>
                Contact Support
            </a>
        </div>
    </div>

<?php
$stmt->close();
$content = ob_get_clean();

// Render the layout with the dashboard content
renderLayout("Dashboard", $content, "dashboard");
?>