<?php
require_once __DIR__ . '/../../includes/security_init.php';
// Configure session settings
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// Debug logging (remove after testing)
error_log("SubAdmin Dashboard Access - Session ID: " . session_id());
error_log("SubAdmin Session Data: " . print_r($_SESSION, true));

if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    error_log("SubAdmin NOT logged in - redirecting to login");
    header("Location: ../../Login/Login/login.php");
    exit();
}

// Include database connection and layout
include_once "../../Login/Login/db.php";
require_once "../../includes/notification_helper.php"; // Notification system
include_once "sidebar_subadmin.php"; // Include the layout file

$subadmin_id = $_SESSION['subadmin_id'];

// Handle approve/reject actions
$action_message = '';
if (isset($_POST['action']) && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    $action = $_POST['action'];
    $rejection_reason = $action === 'reject' ? trim($_POST['rejection_reason'] ?? '') : '';

    try {
        $conn->begin_transaction();
        
        // Get project and user data
        $stmt = $conn->prepare("SELECT p.*, r.email, r.name FROM projects p JOIN register r ON p.user_id = r.id WHERE p.id=?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $project_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$project_data) {
            throw new Exception("Project not found");
        }
        
        if ($action === 'approve') {
            // Move to admin_approved_projects
            $stmt = $conn->prepare("INSERT INTO admin_approved_projects (user_id, project_name, project_type, classification, project_category, difficulty_level, development_time, team_size, target_audience, project_goals, challenges_faced, future_enhancements, github_repo, live_demo_url, project_license, keywords, contact_email, social_links, description, language, image_path, video_path, code_file_path, instruction_file_path, presentation_file_path, additional_files_path, submission_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
            $stmt->bind_param("issssssssssssssssssssssssss", 
                $project_data['user_id'], $project_data['project_name'], $project_data['project_type'], 
                $project_data['classification'], $project_data['project_category'], $project_data['difficulty_level'],
                $project_data['development_time'], $project_data['team_size'], $project_data['target_audience'],
                $project_data['project_goals'], $project_data['challenges_faced'], $project_data['future_enhancements'],
                $project_data['github_repo'], $project_data['live_demo_url'], $project_data['project_license'],
                $project_data['keywords'], $project_data['contact_email'], $project_data['social_links'],
                $project_data['description'], $project_data['language'], $project_data['image_path'],
                $project_data['video_path'], $project_data['code_file_path'], $project_data['instruction_file_path'],
                $project_data['presentation_file_path'], $project_data['additional_files_path'], 
                $project_data['submission_date']
            );
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert into admin_approved_projects: " . $stmt->error);
            }
            $stmt->close();
            
            // Update status in projects table
            $stmt = $conn->prepare("UPDATE projects SET status='approved' WHERE id=?");
            $stmt->bind_param("i", $project_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update project status: " . $stmt->error);
            }
            $stmt->close();
            
            // Create notification for user
            $notifier = new NotificationHelper($conn);
            $notifier->notifyProjectApproved($project_data['user_id'], $project_id, $project_data['project_name']);
            
            $action_message = "Project approved successfully!";
        } else {
            // Move to denial_projects
            $stmt = $conn->prepare("INSERT INTO denial_projects (user_id, project_name, project_type, classification, project_category, difficulty_level, development_time, team_size, target_audience, project_goals, challenges_faced, future_enhancements, github_repo, live_demo_url, project_license, keywords, contact_email, social_links, description, language, image_path, video_path, code_file_path, instruction_file_path, presentation_file_path, additional_files_path, submission_date, status, rejection_date, rejection_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rejected', NOW(), ?)");
            $stmt->bind_param("isssssssssssssssssssssssssss", 
                $project_data['user_id'], $project_data['project_name'], $project_data['project_type'], 
                $project_data['classification'], $project_data['project_category'], $project_data['difficulty_level'],
                $project_data['development_time'], $project_data['team_size'], $project_data['target_audience'],
                $project_data['project_goals'], $project_data['challenges_faced'], $project_data['future_enhancements'],
                $project_data['github_repo'], $project_data['live_demo_url'], $project_data['project_license'],
                $project_data['keywords'], $project_data['contact_email'], $project_data['social_links'],
                $project_data['description'], $project_data['language'], $project_data['image_path'],
                $project_data['video_path'], $project_data['code_file_path'], $project_data['instruction_file_path'],
                $project_data['presentation_file_path'], $project_data['additional_files_path'], 
                $project_data['submission_date'], $rejection_reason
            );
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert into denial_projects: " . $stmt->error);
            }
            $stmt->close();
            
            // Update status in projects table
            $stmt = $conn->prepare("UPDATE projects SET status='rejected' WHERE id=?");
            $stmt->bind_param("i", $project_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update project status: " . $stmt->error);
            }
            $stmt->close();
            
            // Create notification for user
            $notifier = new NotificationHelper($conn);
            $notifier->notifyProjectRejected($project_data['user_id'], $project_id, $project_data['project_name'], $rejection_reason);
            
            $action_message = "Project rejected successfully!";
        }
        
        $conn->commit();
        
    } catch (Exception $e) {
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        $action_message = "Error: " . $e->getMessage();
        error_log("Error in dashboard.php: " . $e->getMessage());
    }

    header("Location: dashboard.php?msg=" . urlencode($action_message));
    exit();
}

if (isset($_GET['msg'])) {
    $action_message = htmlspecialchars($_GET['msg']);
}

$subadmin_id = $_SESSION['subadmin_id'];

// Fetch subadmin basic info
$stmt = $conn->prepare("SELECT email, first_name, last_name FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($email, $first_name, $last_name);
$stmt->fetch();
$stmt->close();
$name = $first_name . ' ' . $last_name;

// Fetch subadmin's domains
$stmt = $conn->prepare("SELECT domains FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($domains);
$stmt->fetch();
$stmt->close();

// Build classification matching using LIKE for flexible matching with keyword mapping
$where_conditions = [];
$params = [];
$types = '';
$search_keywords = [];

if (!empty($domains)) {
    $domain_list = array_map('trim', explode(',', $domains));
    
    // Map domain names to search keywords for better matching
    $domain_keyword_mapping = [
        'Web Development' => ['web'],
        'Web Application' => ['web'],
        'Web' => ['web'],
        'Mobile Development' => ['mobile'],
        'Mobile Application' => ['mobile'],
        'Mobile' => ['mobile'],
        'AI/ML' => ['ai', 'ml', 'machine learning', 'artificial intelligence'],
        'AI & Machine Learning' => ['ai', 'ml', 'machine learning'],
        'Data Science' => ['data science', 'analytics'],
        'Desktop Application' => ['desktop'],
        'Desktop' => ['desktop'],
        'System Software' => ['system'],
        'Cybersecurity' => ['cybersecurity', 'security'],
        'Game Development' => ['game'],
        'IoT' => ['iot', 'embedded'],
        'IoT Projects' => ['iot', 'embedded'],
        'Embedded Systems' => ['embedded', 'iot'],
        'Embedded' => ['embedded', 'iot'],
        'Robotics' => ['robotics', 'robot'],
        'Automation' => ['automation'],
        'Cloud-Based Applications' => ['cloud']
    ];
    
    foreach ($domain_list as $domain) {
        $domain_trimmed = trim($domain);
        if (isset($domain_keyword_mapping[$domain_trimmed])) {
            $search_keywords = array_merge($search_keywords, $domain_keyword_mapping[$domain_trimmed]);
        }
    }
    $search_keywords = array_unique($search_keywords);
    
    // Build WHERE conditions using keywords
    if (!empty($search_keywords)) {
        foreach ($search_keywords as $keyword) {
            $keyword_clean = strtolower(trim($keyword));
            $where_conditions[] = "LOWER(classification) LIKE ?";
            $params[] = "%$keyword_clean%";
            $types .= 's';
        }
    } else {
        // Fallback to original domain names if no mapping found
        foreach ($domain_list as $domain) {
            $domain_clean = strtolower(trim($domain));
            $where_conditions[] = "LOWER(classification) LIKE ?";
            $params[] = "%$domain_clean%";
            $types .= 's';
        }
    }
}

if (!empty($where_conditions)) {
    $where_clause = implode(' OR ', $where_conditions);
    
    // Total Projects count (all statuses)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM projects WHERE ($where_clause)");
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $stmt->bind_result($assigned_projects_count);
    $stmt->fetch();
    $stmt->close();
    
    // Pending Tasks count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM projects WHERE status = 'pending' AND ($where_clause)");
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $stmt->bind_result($pending_tasks_count);
    $stmt->fetch();
    $stmt->close();
    
    // Approved Projects count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM projects WHERE status = 'approved' AND ($where_clause)");
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $stmt->bind_result($approved_projects_count);
    $stmt->fetch();
    $stmt->close();
    
    // Fetch recent projects (all statuses, prioritize pending)
    $stmt = $conn->prepare("SELECT id, project_name, project_type, classification, description, status FROM projects WHERE ($where_clause) ORDER BY CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 END, submission_date DESC LIMIT 5");
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $assigned_projects_count = 0;
    $pending_tasks_count = 0;
    $approved_projects_count = 0;
    $result = $conn->query("SELECT id, project_name, project_type, classification, description, status FROM projects WHERE 1=0 LIMIT 5");
}

// Notifications and messages count (dummy data)
$notifications_count = 3;
$messages_count = 2;

// Build the dashboard content
ob_start();
?>

  <link rel="stylesheet" href="../../assets/css/subadmin_dashboard.css">

    <!-- Action Message Alert -->
    <?php if ($action_message) : ?>
        <?php 
        $isError = strpos($action_message, 'Error') !== false;
        $alertClass = $isError ? 'alert-danger' : 'alert-success';
        $icon = $isError ? 'bi-exclamation-triangle' : 'bi-check-circle';
        ?>
        <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
            <i class="<?php echo $icon; ?> me-2"></i>
            <?php echo $action_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

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

        <?php if ($result->num_rows > 0) : ?>
            <?php 
            // Store results in array for reuse
            $projects_data = [];
            while ($row = $result->fetch_assoc()) {
                $projects_data[] = $row;
            }
            ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Type</th>
                        <th>Classification</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projects_data as $row) : ?>
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
                            <td>
                                <?php if ($row['status'] == 'pending') : ?>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <form method="POST" action="dashboard.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to approve this project?');">
                                            <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $row['id']; ?>" title="Reject">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                <?php else : ?>
                                    <span class="text-muted small">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Reject Modals -->
            <?php foreach ($projects_data as $row) : ?>
                <?php if ($row['status'] == 'pending') : ?>
                    <div class="modal fade" id="rejectModal<?php echo $row['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                                        Reject Project
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="dashboard.php">
                                    <div class="modal-body">
                                        <div class="alert alert-warning">
                                            <i class="bi bi-info-circle me-2"></i>
                                            You are about to reject "<strong><?php echo htmlspecialchars($row['project_name']); ?></strong>". Please provide a reason.
                                        </div>
                                        <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <div class="mb-3">
                                            <label for="rejection_reason<?php echo $row['id']; ?>" class="form-label">
                                                <i class="bi bi-chat-square-text me-1"></i>
                                                Reason for Rejection <span class="text-danger">*</span>
                                            </label>
                                            <textarea class="form-control" name="rejection_reason" id="rejection_reason<?php echo $row['id']; ?>" rows="4" placeholder="Please provide a detailed reason..." required></textarea>
                                            <div class="form-text">This will be sent to the project owner.</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x-circle me-1"></i> Cancel
                                        </button>
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-exclamation-triangle me-1"></i> Reject Project
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>No Projects Found</h5>
                <p class="mb-0">You don't have any projects assigned yet for your domains: <?php echo htmlspecialchars($domains ?: 'No domains assigned'); ?></p>
                <?php if (!empty($where_conditions)): ?>
                <small class="text-muted">Searching for projects with classifications matching: <?php echo htmlspecialchars($domains); ?></small>
                <?php endif; ?>
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
            <a href="support.php" class="action-button action-outline">
                <i class="bi bi-envelope-fill"></i>
                Contact Support
            </a>
        </div>
    </div>

<?php
$content = ob_get_clean();

// Render the layout with the dashboard content
renderLayout("Dashboard", $content, "dashboard");
?>