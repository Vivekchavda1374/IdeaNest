<?php
require_once '../config/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../Login/Login/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idea_id = (int)($_POST['idea_id'] ?? 0);
    $report_id = (int)($_POST['report_id'] ?? 0);

    if ($action === 'send_warning' && $idea_id > 0) {
        $warning_reason = $conn->real_escape_string($_POST['warning_reason'] ?? '');

        // Get idea and user details
        $idea_query = "SELECT b.*, r.name, r.email FROM blog b 
                      JOIN register r ON b.user_id = r.id 
                      WHERE b.id = $idea_id";
        $idea_result = $conn->query($idea_query);

        if ($idea_result && $idea_row = $idea_result->fetch_assoc()) {
            // Send warning email
            $to = $idea_row['email'];
            $subject = "Warning: Content Review Required - IdeaNest";
            $message = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2 style='color: #dc3545;'>Content Warning</h2>
                <p>Dear {$idea_row['name']},</p>
                <p>We have received reports about your idea: <strong>{$idea_row['project_name']}</strong></p>
                <p><strong>Reason:</strong> $warning_reason</p>
                <p>Please review and modify your content to comply with our community guidelines. Failure to address this issue may result in content removal.</p>
                <p>Best regards,<br>IdeaNest Admin Team</p>
            </body>
            </html>";

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: ideanest.ict@gmail.com\r\n";

            $email_sent = mail($to, $subject, $message, $headers);

            // Create warnings table if not exists
            $conn->query("CREATE TABLE IF NOT EXISTS idea_warnings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                idea_id INT NOT NULL,
                user_id INT NOT NULL,
                warning_reason TEXT,
                admin_id INT,
                status VARCHAR(20) DEFAULT 'sent',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // Log warning
            $admin_id = $_SESSION['admin_id'] ?? 1;
            $status = $email_sent ? 'sent' : 'failed';
            $conn->query("INSERT INTO idea_warnings (idea_id, user_id, warning_reason, admin_id, status) 
                         VALUES ($idea_id, {$idea_row['user_id']}, '$warning_reason', $admin_id, '$status')");

            // Update report status
            if ($report_id > 0) {
                $conn->query("UPDATE idea_reports SET status = 'reviewed' WHERE id = $report_id");
            }

            $success_msg = $email_sent ? "Warning email sent successfully!" : "Warning logged but email failed to send.";
        }
    }

    if ($action === 'delete_idea' && $idea_id > 0) {
        $deletion_reason = $conn->real_escape_string($_POST['deletion_reason'] ?? '');

        // Get idea details before deletion
        $idea_query = "SELECT * FROM blog WHERE id = $idea_id";
        $idea_result = $conn->query($idea_query);

        if ($idea_result && $idea_row = $idea_result->fetch_assoc()) {
            // Create deleted ideas table if not exists
            $conn->query("CREATE TABLE IF NOT EXISTS deleted_ideas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                original_idea_id INT,
                user_id INT,
                project_name VARCHAR(255),
                description TEXT,
                deletion_reason TEXT,
                deleted_by_admin INT,
                deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // Archive deleted idea
            $admin_id = $_SESSION['admin_id'] ?? 1;
            $conn->query("INSERT INTO deleted_ideas (original_idea_id, user_id, project_name, description, deletion_reason, deleted_by_admin) 
                         VALUES ($idea_id, {$idea_row['user_id']}, '{$idea_row['project_name']}', '{$idea_row['description']}', '$deletion_reason', $admin_id)");

            // Delete the idea
            $conn->query("DELETE FROM blog WHERE id = $idea_id");

            // Update all related reports
            $conn->query("UPDATE idea_reports SET status = 'resolved' WHERE idea_id = $idea_id");

            $success_msg = "Idea deleted successfully!";
        }
    }

    if ($action === 'dismiss_report' && $report_id > 0) {
        $conn->query("UPDATE idea_reports SET status = 'resolved' WHERE id = $report_id");
        $success_msg = "Report dismissed successfully!";
    }
}

// Get reported ideas with details
$reported_ideas_query = "
    SELECT 
        ir.id as report_id,
        ir.report_type as report_reason,
        ir.description as report_details,
        ir.status as report_status,
        ir.created_at as reported_at,
        b.id as idea_id,
        b.project_name,
        b.description,
        b.submission_datetime,
        r.name as user_name,
        r.email as user_email,
        reporter.name as reporter_name
    FROM idea_reports ir
    JOIN blog b ON ir.idea_id = b.id
    JOIN register r ON b.user_id = r.id
    JOIN register reporter ON ir.reporter_id = reporter.id
    WHERE ir.status IN ('pending', 'reviewed')
    ORDER BY ir.created_at DESC
";

$reported_ideas = $conn->query($reported_ideas_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reported Ideas - IdeaNest Admin</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
</head>
<body>
    <!-- Simple Admin Header -->
    <?php  include 'sidebar_admin.php';?>
    
    <div class="main-content">
        <button class="btn d-lg-none mb-3" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="bi bi-flag text-danger me-2"></i>Manage Reported Ideas
                        </h1>
                    </div>

                    <?php if (isset($success_msg)) : ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?php echo $success_msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Reported Ideas</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($reported_ideas && $reported_ideas->num_rows > 0) : ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Idea Details</th>
                                                <th>User</th>
                                                <th>Report Info</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $reported_ideas->fetch_assoc()) : ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($row['project_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo substr(htmlspecialchars($row['description']), 0, 100) . '...'; ?></small><br>
                                                        <small class="text-info">Posted: <?php echo date('M j, Y', strtotime($row['submission_datetime'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($row['user_name']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($row['user_email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning"><?php echo ucfirst($row['report_reason']); ?></span><br>
                                                        <small><?php echo htmlspecialchars($row['report_details'] ?? 'No details'); ?></small><br>
                                                        <small class="text-muted">By: <?php echo htmlspecialchars($row['reporter_name']); ?></small><br>
                                                        <small class="text-muted">Reported at: <?php echo date('M j, Y', strtotime($row['reported_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $row['report_status'] === 'pending' ? 'danger' : 'warning'; ?>">
                                                            <?php echo ucfirst($row['report_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group-vertical btn-group-sm">
                                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                                                    data-bs-target="#warningModal" 
                                                                    data-idea-id="<?php echo $row['idea_id']; ?>"
                                                                    data-report-id="<?php echo $row['report_id']; ?>"
                                                                    data-user-name="<?php echo htmlspecialchars($row['user_name']); ?>"
                                                                    data-idea-name="<?php echo htmlspecialchars($row['project_name']); ?>">
                                                                <i class="bi bi-exclamation-triangle"></i> Send Warning
                                                            </button>
                                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" 
                                                                    data-bs-target="#deleteModal" 
                                                                    data-idea-id="<?php echo $row['idea_id']; ?>"
                                                                    data-idea-name="<?php echo htmlspecialchars($row['project_name']); ?>">
                                                                <i class="bi bi-trash"></i> Delete Idea
                                                            </button>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="dismiss_report">
                                                                <input type="hidden" name="report_id" value="<?php echo $row['report_id']; ?>">
                                                                <button type="submit" class="btn btn-secondary btn-sm" 
                                                                        onclick="return confirm('Dismiss this report?')">
                                                                    <i class="bi bi-x-circle"></i> Dismiss
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else : ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                    <h4 class="mt-3">No Reported Ideas</h4>
                                    <p class="text-muted">All ideas are currently in good standing.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Modal -->
    <div class="modal fade" id="warningModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Send Warning</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="send_warning">
                        <input type="hidden" name="idea_id" id="warning_idea_id">
                        <input type="hidden" name="report_id" id="warning_report_id">
                        
                        <p>Send warning to <strong id="warning_user_name"></strong> for idea: <strong id="warning_idea_name"></strong></p>
                        
                        <div class="mb-3">
                            <label for="warning_reason" class="form-label">Warning Reason</label>
                            <textarea class="form-control" name="warning_reason" id="warning_reason" rows="3" required 
                                      placeholder="Explain why this content needs attention..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Send Warning</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Delete Idea</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_idea">
                        <input type="hidden" name="idea_id" id="delete_idea_id">
                        
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Warning!</strong> This action cannot be undone.
                        </div>
                        
                        <p>Are you sure you want to delete the idea: <strong id="delete_idea_name"></strong>?</p>
                        
                        <div class="mb-3">
                            <label for="deletion_reason" class="form-label">Deletion Reason</label>
                            <textarea class="form-control" name="deletion_reason" id="deletion_reason" rows="3" required 
                                      placeholder="Explain why this idea is being deleted..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Idea</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle warning modal
        document.getElementById('warningModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('warning_idea_id').value = button.getAttribute('data-idea-id');
            document.getElementById('warning_report_id').value = button.getAttribute('data-report-id');
            document.getElementById('warning_user_name').textContent = button.getAttribute('data-user-name');
            document.getElementById('warning_idea_name').textContent = button.getAttribute('data-idea-name');
        });

        // Handle delete modal
        document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('delete_idea_id').value = button.getAttribute('data-idea-id');
            document.getElementById('delete_idea_name').textContent = button.getAttribute('data-idea-name');
        });
    </script>
</body>
</html>