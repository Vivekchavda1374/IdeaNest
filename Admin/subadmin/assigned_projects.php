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
require_once "sidebar_subadmin.php"; // Include the layout file

$subadmin_id = $_SESSION['subadmin_id'];

// Fetch subadmin's classifications
$stmt = $conn->prepare("SELECT domains FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($domains);
$stmt->fetch();
$stmt->close();

// Build classification array from domains with proper mapping
$classifications = [];
if (!empty($domains)) {
    $domain_list = array_map('trim', explode(',', $domains));
    
    // Map domain names to classification values
    $domain_mapping = [
        'Web Development' => 'web',
        'Web Application' => 'web', 
        'Mobile Development' => 'mobile',
        'AI/ML' => 'ai_ml',
        'Data Science' => 'ai_ml',
        'Cybersecurity' => 'system',
        'IoT' => 'iot',
        'Internet of Things (IoT)' => 'iot',
        'Blockchain' => 'system',
        'Game Development' => 'system',
        'Desktop Application' => 'system',
        'Embedded' => 'embedded',
        'Wearable' => 'wearable'
    ];
    
    foreach ($domain_list as $domain) {
        if (isset($domain_mapping[$domain])) {
            $classifications[] = $domain_mapping[$domain];
        }
    }
    $classifications = array_unique($classifications);
}

// Build dynamic query for multiple classifications
if (!empty($classifications)) {
    $placeholders = str_repeat('?,', count($classifications) - 1) . '?';
    $stmt = $conn->prepare("SELECT id, project_name, project_type, classification, description, status FROM admin_approved_projects WHERE classification IN ($placeholders)");
    $stmt->bind_param(str_repeat('s', count($classifications)), ...$classifications);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id, project_name, project_type, classification, description, status FROM admin_approved_projects WHERE 1=0");
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__FILE__) . '/../../Admin/project_notification.php';

// Handle approve/reject actions
$action_message = '';
if (isset($_POST['action']) && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    $action = $_POST['action'];
    $rejection_reason = $action === 'reject' ? trim($_POST['rejection_reason'] ?? '') : '';

    try {
        $conn->begin_transaction();
        
        // Get project data first
        $stmt = $conn->prepare("SELECT * FROM admin_approved_projects WHERE id=?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $project_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$project_data) {
            throw new Exception("Project not found");
        }
        
        if ($action === 'approve') {
            // Keep in admin_approved_projects but update status
            $stmt = $conn->prepare("UPDATE admin_approved_projects SET status='approved' WHERE id=?");
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $stmt->close();
            
            $status = 'approved';
            $action_message = "Project approved successfully";
        } else {
            // Move to denial_projects table
            $stmt = $conn->prepare("INSERT INTO denial_projects (user_id, project_name, project_type, classification, project_category, difficulty_level, development_time, team_size, target_audience, project_goals, challenges_faced, future_enhancements, github_repo, live_demo_url, project_license, keywords, contact_email, social_links, description, language, image_path, video_path, code_file_path, instruction_file_path, presentation_file_path, additional_files_path, submission_date, status, rejection_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssssssssssssssssssssssssss", 
                $project_data['user_id'], $project_data['project_name'], $project_data['project_type'], 
                $project_data['classification'], $project_data['project_category'], $project_data['difficulty_level'],
                $project_data['development_time'], $project_data['team_size'], $project_data['target_audience'],
                $project_data['project_goals'], $project_data['challenges_faced'], $project_data['future_enhancements'],
                $project_data['github_repo'], $project_data['live_demo_url'], $project_data['project_license'],
                $project_data['keywords'], $project_data['contact_email'], $project_data['social_links'],
                $project_data['description'], $project_data['language'], $project_data['image_path'],
                $project_data['video_path'], $project_data['code_file_path'], $project_data['instruction_file_path'],
                $project_data['presentation_file_path'], $project_data['additional_files_path'], 
                $project_data['submission_date'], 'rejected', $rejection_reason
            );
            $stmt->execute();
            $stmt->close();
            
            // Remove from admin_approved_projects
            $stmt = $conn->prepare("DELETE FROM admin_approved_projects WHERE id=?");
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $stmt->close();
            
            $status = 'rejected';
            $action_message = "Project rejected and moved to denial table";
        }
        
        $conn->commit();
        
        // Get subadmin details for email
        $stmt = $conn->prepare("SELECT name, email FROM subadmins WHERE id=?");
        $stmt->bind_param("i", $subadmin_id);
        $stmt->execute();
        $subadmin_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Send email notification with subadmin details
        $result_email = sendProjectStatusEmail($project_id, $status, $rejection_reason, $subadmin_result);
        if (!$result_email['success']) {
            $action_message .= ", but email could not be sent: " . $result_email['message'];
        } else {
            $action_message .= " and email sent to user";
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $action_message = "Error processing request: " . $e->getMessage();
        error_log("Error in assigned_projects.php: " . $e->getMessage());
    }

    header("Location: assigned_projects.php?msg=" . urlencode($action_message));
    exit();
}

if (isset($_GET['msg'])) {
    $action_message = htmlspecialchars($_GET['msg']);
}

// Re-fetch projects after potential updates
if (!empty($classifications)) {
    $placeholders = str_repeat('?,', count($classifications) - 1) . '?';
    $stmt = $conn->prepare("SELECT id, project_name, project_type, classification, description, status FROM admin_approved_projects WHERE classification IN ($placeholders)");
    $stmt->bind_param(str_repeat('s', count($classifications)), ...$classifications);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT id, project_name, project_type, classification, description, status FROM admin_approved_projects WHERE 1=0");
}

// Start output buffering to capture the content
ob_start();
?>

    <!-- Page specific styles -->
    <link rel="stylesheet" href="../../assets/css/assigned_projects.css">
    <style>
        /* Fix modal backdrop issues */
        .modal-backdrop {
            display: none !important;
        }
        
        .modal.show {
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>

    <!-- Action Message Alert -->
<?php if ($action_message) : ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $action_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

    <!-- Projects Statistics -->
<?php
$total_projects = $result->num_rows;
$approved_count = 0;
$pending_count = 0;
$rejected_count = 0;

// Count projects by status
$projects_data = [];
while ($row = $result->fetch_assoc()) {
    $projects_data[] = $row;
    switch ($row['status']) {
        case 'approved':
            $approved_count++;
            break;
        case 'pending':
            $pending_count++;
            break;
        case 'rejected':
            $rejected_count++;
            break;
    }
}
?>

    <!-- Project Statistics -->
    <div class="row mb-4 fade-in-up">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="glass-card stats-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="stats-number"><?php echo $total_projects; ?></span>
                        <span class="stats-label">Total Projects</span>
                    </div>
                    <i class="bi bi-kanban" style="font-size: 2.5rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="glass-card stats-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="stats-number"><?php echo $approved_count; ?></span>
                        <span class="stats-label">Approved</span>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="glass-card stats-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="stats-number"><?php echo $pending_count; ?></span>
                        <span class="stats-label">Pending Review</span>
                    </div>
                    <i class="bi bi-clock-history" style="font-size: 2.5rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="glass-card stats-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="stats-number"><?php echo $rejected_count; ?></span>
                        <span class="stats-label">Rejected</span>
                    </div>
                    <i class="bi bi-x-circle" style="font-size: 2.5rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="glass-card">
        <div class="p-4 border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 fw-bold">
                        <i class="bi bi-kanban-fill me-2 text-primary"></i>
                        Assigned Projects
                    </h5>
                    <p class="text-muted mb-0">Projects assigned based on your classification</p>
                </div>
                <div class="d-flex align-items-center gap-2 text-muted">
                    <small>
                        <i class="bi bi-tags-fill me-1"></i>
                        Classifications: <?php echo htmlspecialchars(implode(', ', array_filter($classifications))); ?>
                    </small>
                </div>
            </div>
        </div>

        <?php if (count($projects_data) > 0) : ?>
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                    <tr>
                        <th><i class="bi bi-folder me-1"></i>Project Name</th>
                        <th><i class="bi bi-tag me-1"></i>Type</th>
                        <th><i class="bi bi-bookmark me-1"></i>Classification</th>
                        <th><i class="bi bi-file-text me-1"></i>Description</th>
                        <th><i class="bi bi-flag me-1"></i>Status</th>
                        <th><i class="bi bi-gear me-1"></i>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($projects_data as $row) : ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-primary">
                                    <?php echo htmlspecialchars($row['project_name']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($row['project_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($row['classification']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($row['description']); ?>">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'pending') : ?>
                                    <div class="action-buttons">
                                        <form method="post" style="display: inline-block;" data-loading-message="Approving project...">
                                            <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-action btn-approve">
                                                <i class="bi bi-check-lg"></i>
                                                Approve
                                            </button>
                                        </form>

                                        <button class="btn btn-action btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $row['id']; ?>">
                                            <i class="bi bi-x-lg"></i>
                                            Reject
                                        </button>
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="rejectModalLabel<?php echo $row['id']; ?>">
                                                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                                                        Reject Project
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="post" data-loading-message="Rejecting project...">
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning">
                                                            <i class="bi bi-info-circle me-2"></i>
                                                            You are about to reject the project "<strong><?php echo htmlspecialchars($row['project_name']); ?></strong>". Please provide a reason for rejection.
                                                        </div>
                                                        <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                                        <div class="mb-3">
                                                            <label for="rejection_reason<?php echo $row['id']; ?>" class="form-label">
                                                                <i class="bi bi-chat-square-text me-1"></i>
                                                                Reason for Rejection <span class="text-danger">*</span>
                                                            </label>
                                                            <textarea class="form-control" name="rejection_reason" id="rejection_reason<?php echo $row['id']; ?>" rows="4" placeholder="Please provide a detailed reason for rejecting this project..." required></textarea>
                                                            <div class="form-text">This reason will be sent to the project submitter via email.</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                            <i class="bi bi-x-circle me-1"></i>
                                                            Cancel
                                                        </button>
                                                        <button type="submit" name="action" value="reject" class="btn btn-danger">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            Reject Project
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <span class="text-muted fst-italic">
                                        <i class="bi bi-check-circle me-1"></i>
                                        No actions available
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-inbox"></i>
                </div>
                <h3 class="empty-state-title">No Projects Found</h3>
                <p class="empty-state-desc">
                    There are currently no projects assigned to your domains.<br>
                    <small class="text-muted">Your domains: <?php echo htmlspecialchars($domains ?: 'None assigned'); ?></small><br>
                    <small class="text-muted">Mapped classifications: <?php echo htmlspecialchars(implode(', ', array_filter($classifications)) ?: 'None'); ?></small>
                    New projects will appear here when they are submitted.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Remove any existing modal backdrops
            document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
                backdrop.remove();
            });
            
            // Handle approve buttons with loading
            document.querySelectorAll('button[value="approve"]').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to approve this project?')) {
                        e.preventDefault();
                    } else {
                        setButtonLoading(this, true, 'Approving...');
                    }
                });
            });
            
            // Handle form submissions with custom loading messages
            document.querySelectorAll('form[data-loading-message]').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const message = this.getAttribute('data-loading-message');
                    window.loadingManager.show(message);
                });
            });
            
            // Handle modal cleanup on hide
            document.querySelectorAll('.modal').forEach(function(modal) {
                modal.addEventListener('hidden.bs.modal', function() {
                    document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
                        backdrop.remove();
                    });
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('padding-right');
                });
            });
        });
    </script>

<?php
// Capture the content
$content = ob_get_clean();

// Render the page using the layout
renderLayout('Assigned Projects', $content, 'projects');
?>