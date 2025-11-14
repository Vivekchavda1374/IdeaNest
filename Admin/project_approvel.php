<?php
require_once '../config/config.php';
// Database connection
include "../Login/Login/db.php";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();

// Check if admin is logged in
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        // Redirect to admin login page if not logged in
        header("Location: ../Login/Login/login.php");
        exit();
    }
}

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark'])) {
    $project_id = (int)$_POST['project_id'];
    $session_id = session_id();

    // Check if bookmark already exists for this project
    $check_sql = "SELECT * FROM bookmark WHERE project_id = $project_id AND user_id = '$session_id'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        // Bookmark exists, so remove it
        $delete_sql = "DELETE FROM bookmark WHERE project_id = $project_id AND user_id = '$session_id'";
        if ($conn->query($delete_sql) === true) {
            echo "<div class='alert alert-info shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark me-2'></i>
                        <strong>Success!</strong> Bookmark removed!
                    </div>  
                  </div>";
        } else {
            echo "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> " . $conn->error . "
                    </div>
                  </div>";
        }
    } else {
        // First, remove any existing bookmarks for this user
        $delete_all_sql = "DELETE FROM bookmark WHERE user_id = '$session_id'";
        $conn->query($delete_all_sql);

        // Then add the new bookmark
        $insert_sql = "INSERT INTO bookmark (project_id, user_id) VALUES ($project_id, '$session_id')";
        if ($conn->query($insert_sql) === true) {
            echo "<div class='alert alert-success shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark-fill me-2'></i>
                        <strong>Success!</strong> Project bookmarked!
                    </div>
                  </div>";
        } else {
            echo "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> " . $conn->error . "
                    </div>
                  </div>";
        }
    }
}

// Include notification backend
include "notification_backend.php";

// Handle project approval
if (isset($_POST['approve_project'])) {
    $project_id = (int)$_POST['project_id'];

    // Get the project data
    $get_project_sql = "SELECT * FROM projects WHERE id = $project_id";
    $project_result = $conn->query($get_project_sql);

    if ($project_result->num_rows > 0) {
        $project = $project_result->fetch_assoc();

        // Insert into admin_approved_projects
        $insert_sql = "INSERT INTO admin_approved_projects 
                      (project_name, project_type, classification, description, language, 
                       image_path, video_path, code_file_path, instruction_file_path, submission_date) 
                      VALUES 
                      ('{$project['project_name']}', '{$project['project_type']}', '{$project['classification']}', 
                       '{$project['description']}', '{$project['language']}', '{$project['image_path']}', 
                       '{$project['video_path']}', '{$project['code_file_path']}', '{$project['instruction_file_path']}', 
                       '{$project['submission_date']}')";

        if ($conn->query($insert_sql) === true) {
            // Delete from projects table
            $delete_sql = "DELETE FROM projects WHERE id = $project_id";
            if ($conn->query($delete_sql) === true) {
                // Send approval email notification
                $email_result = sendProjectApprovalEmail($project_id, $conn);


                // Log the notification
                $email_to = $project['email'] ?? '';
                $email_subject = "Congratulations! Your Project \"{$project['project_name']}\" Has Been Approved";
                $error_message = $email_result['success'] ? null : $email_result['message'];
                logNotification(
                    'project_approval',
                    $project['user_id'],
                    $conn,
                    $email_result['success'] ? 'sent' : 'failed',
                    $project_id,
                    $email_to,
                    $email_subject,
                    $error_message
                );

                $email_message = '';
                if ($email_result['success']) {
                    $email_message = " Email notification sent to user.";
                } else {
                    $email_message = " Email notification failed: " . $email_result['message'];
                }

                echo "<div class='alert alert-success shadow-sm'>
                        <div class='d-flex align-items-center'>
                            <i class='bi bi-check-circle-fill me-2'></i>
                            <strong>Success!</strong> Project approved and moved to approved projects!" . $email_message . "
                        </div>
                      </div>";
            } else {
                echo "<div class='alert alert-danger shadow-sm'>
                        <div class='d-flex align-items-center'>
                            <i class='bi bi-exclamation-triangle-fill me-2'></i>
                            <strong>Error!</strong> Failed to remove from pending projects: " . $conn->error . "
                        </div>
                      </div>";
            }
        } else {
            echo "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> Failed to approve project: " . $conn->error . "
                    </div>
                  </div>";
        }
    }
}

// Handle project rejection
if (isset($_POST['reject_project'])) {
    $project_id = (int)$_POST['project_id'];
    $rejection_reason = trim($_POST['rejection_reason'] ?? 'Project does not meet our criteria.');

    // Get the project data before deletion
    $get_project_sql = "SELECT * FROM projects WHERE id = $project_id";
    $project_result = $conn->query($get_project_sql);

    if ($project_result->num_rows > 0) {
        $project = $project_result->fetch_assoc();

        // Delete from projects table
        $delete_sql = "DELETE FROM projects WHERE id = $project_id";
        if ($conn->query($delete_sql) === true) {
            // Send rejection email notification
            $email_result = sendProjectRejectionEmail($project_id, $rejection_reason, $conn);


            // Log the notification
            $email_to = $project['email'] ?? '';
            $email_subject = "Important Update About Your Project \"{$project['project_name']}\"";
            $error_message = $email_result['success'] ? null : $email_result['message'];
            logNotification(
                'project_rejection',
                $project['user_id'],
                $conn,
                $email_result['success'] ? 'sent' : 'failed',
                $project_id,
                $email_to,
                $email_subject,
                $error_message
            );

            $email_message = '';
            if ($email_result['success']) {
                $email_message = " Email notification sent to user.";
            } else {
                $email_message = " Email notification failed: " . $email_result['message'];
            }

            echo "<div class='alert alert-warning shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Project Rejected!</strong> Project removed from pending list." . $email_message . "
                    </div>
                  </div>";
        } else {
            echo "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> Failed to reject project: " . $conn->error . "
                    </div>
                  </div>";
        }
    }
}

// Get pending projects
$pending_sql = "SELECT * FROM projects ORDER BY submission_date DESC";
$pending_result = $conn->query($pending_sql);
$pending_count = $pending_result ? $pending_result->num_rows : 0;

// Get approved projects with bookmark status for current user
$approved_sql = "SELECT admin_approved_projects.*, 
                CASE WHEN bookmark.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
                FROM admin_approved_projects 
                LEFT JOIN bookmark ON admin_approved_projects.id = bookmark.project_id AND bookmark.user_id = '" . session_id() . "'
                ORDER BY admin_approved_projects.submission_date DESC";
$approved_result = $conn->query($approved_sql);
$approved_count = $approved_result ? $approved_result->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/project_approvel.css">
</head>

<body>
    <div class="dashboard-header">
        <div class="container">
            <h1 class="dashboard-title">
                <i class="bi bi-kanban"></i>
                Project Management
            </h1>
        </div>
    </div>

    <div class="container">
        <ul class="nav nav-tabs" id="projectTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-projects"
                    type="button" role="tab" aria-controls="pending-projects" aria-selected="true">
                    <i class="bi bi-hourglass me-1"></i>Pending Projects
                    <span class="project-count"><?php echo $pending_count; ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved-projects"
                    type="button" role="tab" aria-controls="approved-projects" aria-selected="false">
                    <i class="bi bi-check-circle me-1"></i>Approved Projects
                    <span class="project-count"><?php echo $approved_count; ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="projectTabsContent">
            <!-- Pending Projects Tab -->
            <div class="tab-pane fade show active" id="pending-projects" role="tabpanel" aria-labelledby="pending-tab">
                <h2 class="section-title">
                    <i class="bi bi-hourglass me-2"></i>Pending Projects
                    <small class="text-muted">(<?php echo $pending_count; ?> projects)</small>
                </h2>

                <?php
                if ($pending_result && $pending_result->num_rows > 0) {
                    while ($row = $pending_result->fetch_assoc()) {
                        ?>
                <div class="project-card card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($row["project_name"]); ?></h5>
                        <span class="badge badge-pending">
                            <i class="bi bi-hourglass me-1"></i>
                            Pending Approval
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="project-detail">
                                            <strong><i class="bi bi-tag me-1"></i> Type:</strong>
                                            <?php echo htmlspecialchars($row["project_type"]); ?>
                                        </div>
                                        <div class="project-detail">
                                            <strong><i class="bi bi-bookmark me-1"></i> Classification:</strong>
                                            <?php echo htmlspecialchars($row["classification"]); ?>
                                        </div>
                                        <div class="project-detail">
                                            <strong><i class="bi bi-code-slash me-1"></i> Language:</strong>
                                            <?php echo htmlspecialchars($row["language"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="project-detail">
                                            <strong><i class="bi bi-calendar-date me-1"></i> Submitted:</strong>
                                            <?php echo date("F j, Y, g:i a", strtotime($row["submission_date"])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="project-detail mt-3">
                                    <strong><i class="bi bi-text-paragraph me-1"></i> Description:</strong>
                                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($row["description"])); ?></p>
                                </div>
                                <div class="mt-4">
                                    <div class="action-buttons">
                                        <form method="post" class="d-inline me-2">
                                        <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                                        <button type="submit" name="approve_project" class="btn btn-approve">
                                            <i class="bi bi-check-lg me-1"></i> Approve Project
                                        </button>
                                    </form>
                                        <button type="button" class="btn btn-reject" 
                                                onclick="openRejectModal(<?php echo $row["id"]; ?>, '<?php echo htmlspecialchars($row["project_name"]); ?>')">
                                            <i class="bi bi-x-lg me-1"></i> Reject Project
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold mb-3"><i class="bi bi-file-earmark me-1"></i>
                                            Project Files</h6>

                                        <?php if (!empty($row["image_path"])) : ?>
                                        <a href="<?php echo htmlspecialchars($row["image_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-zip"></i> Download ZIP
                                        </a>
                                        <?php endif; ?>

                                        <?php if (!empty($row["video_path"])) : ?>
                                        <a href="<?php echo htmlspecialchars($row["video_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-play"></i> View Video
                                        </a>
                                        <?php endif; ?>

                                        <?php if (!empty($row["code_file_path"])) : ?>
                                        <a href="<?php echo htmlspecialchars($row["code_file_path"]); ?>"
                                            target="_blank" class="file-link">
                                            <i class="bi bi-file-earmark-code"></i> View Code
                                        </a>
                                        <?php endif; ?>

                                        <?php if (!empty($row["instruction_file_path"])) : ?>
                                        <a href="<?php echo htmlspecialchars($row["instruction_file_path"]); ?>"
                                            target="_blank" class="file-link">
                                            <i class="bi bi-file-earmark-text"></i> View Instructions
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                        <?php
                    }
                } else {
                    ?>
                <div class="empty-projects">
                    <i class="bi bi-hourglass"></i>
                    <h3>No Pending Projects</h3>
                    <p class="text-muted">There are currently no pending projects to review.</p>
                </div>
                    <?php
                }
                ?>
            </div>

            <!-- Approved Projects Tab -->
            <div class="tab-pane fade" id="approved-projects" role="tabpanel" aria-labelledby="approved-tab">
                <h2 class="section-title">
                    <i class="bi bi-check-circle me-2"></i>Approved Projects
                    <small class="text-muted">(<?php echo $approved_count; ?> projects)</small>
                </h2>

                <?php
                if ($approved_result && $approved_result->num_rows > 0) {
                    while ($row = $approved_result->fetch_assoc()) {
                        ?>
                <div class="project-card card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($row["project_name"]); ?></h5>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                                <button type="submit" name="toggle_bookmark"
                                    class="bookmark-btn <?php echo ($row["is_bookmarked"] ? 'active' : ''); ?>">
                                    <i
                                        class="bi <?php echo ($row["is_bookmarked"] ? 'bi-bookmark-fill' : 'bi-bookmark'); ?>"></i>
                                </button>
                            </form>
                        </div>
                        <span class="badge badge-approved">
                            <i class="bi bi-check-circle me-1"></i>
                            Approved
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="project-detail">
                                            <strong><i class="bi bi-tag me-1"></i> Type:</strong>
                                            <?php echo htmlspecialchars($row["project_type"]); ?>
                                        </div>
                                        <div class="project-detail">
                                            <strong><i class="bi bi-bookmark me-1"></i> Classification:</strong>
                                            <?php echo htmlspecialchars($row["classification"]); ?>
                                        </div>
                                        <div class="project-detail">
                                            <strong><i class="bi bi-code-slash me-1"></i> Language:</strong>
                                            <?php echo htmlspecialchars($row["language"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="project-detail">
                                            <strong><i class="bi bi-calendar-date me-1"></i> Submitted:</strong>
                                            <?php echo date("F j, Y, g:i a", strtotime($row["submission_date"])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="project-detail mt-3">
                                    <strong><i class="bi bi-text-paragraph me-1"></i> Description:</strong>
                                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($row["description"])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold mb-3"><i class="bi bi-file-earmark me-1"></i>
                                            Project
                                            Files</h6>

                                        <?php if (!empty($row["image_path"])) : ?>
                                        <a href="<?php echo htmlspecialchars($row["image_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-zip"></i> Download ZIP
                                        </a>
                                        <?php endif; ?>

                                        <?php if (!empty($row["video_path"])) : ?>
                                        <a href="<?php echo htmlspecialchars($row["video_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-play"></i> View Video
                                        </a>
                                        <?php endif; ?>

                                        <?php if (!empty($row["code_file_path"])) : ?>
                                        <a href="<?php echo htmlspecialchars($row["code_file_path"]); ?>"
                                            target="_blank" class="file-link">
                                            <i class="bi bi-file-earmark-code"></i> View Code
                                        </a>
                                        <?php endif; ?>

                                        <?php if (!empty($row["instruction_file_path"])) : ?>
                                        <a href="<?php echo htmlspecialchars($row["instruction_file_path"]); ?>"
                                            target="_blank" class="file-link">
                                            <i class="bi bi-file-earmark-text"></i> View Instructions
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                        <?php
                    }
                } else {
                    ?>
                <div class="empty-projects">
                    <i class="bi bi-check2-all"></i>
                    <h3>No Approved Projects</h3>
                    <p class="text-muted">There are currently no approved projects to display.</p>
                </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Reject Project
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="project_id" id="rejectProjectId">
                        <p>Are you sure you want to reject <strong id="rejectProjectName"></strong>?</p>
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Rejection Reason (Optional):</label>
                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4" 
                                      placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reject_project" class="btn btn-danger">
                            <i class="bi bi-x-lg me-1"></i> Reject Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function openRejectModal(projectId, projectName) {
            document.getElementById('rejectProjectId').value = projectId;
            document.getElementById('rejectProjectName').textContent = projectName;
            var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        }
    </script>
</body>

</html>

<?php
// Close connection
$conn->close();
?>