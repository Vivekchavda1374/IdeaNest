<?php
session_start();
if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}

include_once "../../Login/Login/db.php";
require_once "sidebar_subadmin.php"; // Include the layout file

$subadmin_id = $_SESSION['subadmin_id'];

// Fetch subadmin's classification
$stmt = $conn->prepare("SELECT software_classification, hardware_classification FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($software_classification, $hardware_classification);
$stmt->fetch();
$stmt->close();

// Fetch projects matching either classification
$stmt = $conn->prepare("SELECT id, project_name, project_type, classification, description, status FROM admin_approved_projects WHERE classification = ? OR classification = ?");
$stmt->bind_param("ss", $software_classification, $hardware_classification);
$stmt->execute();
$result = $stmt->get_result();

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
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
        $result_email = sendProjectStatusEmail($project_id, $status, $rejection_reason);
        if ($result_email['success']) {
            $action_message = "Project status updated and email sent to user.";
        } else {
            $action_message = "Project status updated, but email could not be sent. " . $result_email['message'];
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

// Re-fetch projects after potential updates
$stmt = $conn->prepare("SELECT id, project_name, project_type, classification, description, status FROM admin_approved_projects WHERE classification = ? OR classification = ?");
$stmt->bind_param("ss", $software_classification, $hardware_classification);
$stmt->execute();
$result = $stmt->get_result();

// Start output buffering to capture the content
ob_start();
?>

    <!-- Page specific styles -->
    <link rel="stylesheet" href="../../assets/css/assigned_projects.css">

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

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="glass-card stats-card text-center">
                <span class="stats-number"><?php echo $total_projects; ?></span>
                <span class="stats-label">Total Projects</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3 text-center" style="background: linear-gradient(135deg, var(--success-color) 0%, #10b981 100%); color: white;">
                <span class="stats-number"><?php echo $approved_count; ?></span>
                <span class="stats-label">Approved</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3 text-center" style="background: linear-gradient(135deg, var(--warning-color) 0%, #f59e0b 100%); color: white;">
                <span class="stats-number"><?php echo $pending_count; ?></span>
                <span class="stats-label">Pending</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="glass-card p-3 text-center" style="background: linear-gradient(135deg, var(--danger-color) 0%, #ef4444 100%); color: white;">
                <span class="stats-number"><?php echo $rejected_count; ?></span>
                <span class="stats-label">Rejected</span>
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
                        Classifications: <?php echo htmlspecialchars($software_classification . ', ' . $hardware_classification); ?>
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
                                        <form method="post" style="display: inline-block;">
                                            <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-action btn-approve" onclick="return confirm('Are you sure you want to approve this project?')">
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
                                                <form method="post">
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
                    There are currently no projects assigned to your classification (<?php echo htmlspecialchars($software_classification . ', ' . $hardware_classification); ?>).
                    New projects will appear here when they are submitted.
                </p>
            </div>
        <?php endif; ?>
    </div>

<?php
// Capture the content
$content = ob_get_clean();

// Render the page using the layout
renderLayout('Assigned Projects', $content, 'projects');
?>