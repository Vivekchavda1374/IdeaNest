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

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.projects { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.approved { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.notifications { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 1.25rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .projects-table-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 1rem;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .card-header {
            background: var(--light-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
        }

        .card-title i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        .table {
            margin: 0;
        }

        .table th {
            background: var(--light-bg);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%) !important;
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%) !important;
            color: white !important;
        }

        .badge.bg-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%) !important;
        }

        .quick-actions {
            margin-top: 2rem;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            margin: 0.5rem 0.5rem 0.5rem 0;
            border-radius: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .action-button i {
            margin-right: 0.5rem;
        }

        .action-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .action-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .action-outline {
            border: 2px solid var(--border-color);
            color: var(--text-secondary);
            background: white;
        }

        .action-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: var(--light-bg);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border-color);
            margin-bottom: 1rem;
        }

        .empty-state h5 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .welcome-card {
                padding: 1.5rem;
            }

            .welcome-title {
                font-size: 1.5rem;
            }

            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>

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