<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ideanest";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark'])) {
    $project_id = $_POST['project_id'];
    $session_id = session_id();
    
    // Check if bookmark already exists for this project
    $check_sql = "SELECT * FROM bookmark WHERE project_id = $project_id AND user_id = '$session_id'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        // Bookmark exists, so remove it
        $delete_sql = "DELETE FROM bookmark WHERE project_id = $project_id AND user_id = '$session_id'";
        if ($conn->query($delete_sql) === TRUE) {
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
        if ($conn->query($insert_sql) === TRUE) {
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

// Handle project approval
if (isset($_POST['approve_project'])) {
    $project_id = $_POST['project_id'];
    
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
        
        if ($conn->query($insert_sql) === TRUE) {
            // Delete from projects table
            $delete_sql = "DELETE FROM projects WHERE id = $project_id";
            if ($conn->query($delete_sql) === TRUE) {
                echo "<div class='alert alert-success shadow-sm'>
                        <div class='d-flex align-items-center'>
                            <i class='bi bi-check-circle-fill me-2'></i>
                            <strong>Success!</strong> Project approved and moved to approved projects!
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
    $project_id = $_POST['project_id'];
    $rejection_reason = isset($_POST['rejection_reason']) ? $_POST['rejection_reason'] : "No reason provided";
    
    // Get the project data
    $get_project_sql = "SELECT * FROM projects WHERE id = $project_id";
    $project_result = $conn->query($get_project_sql);
    
    if ($project_result->num_rows > 0) {
        $project = $project_result->fetch_assoc();
        
        // Insert into denial_projects
      // Insert into denial_projects
    $insert_sql = "INSERT INTO denial_projects 
(project_name, project_type, classification, description, language, 
 image_path, video_path, code_file_path, instruction_file_path, 
 submission_date, rejection_reason) 
VALUES 
('{$project['project_name']}', '{$project['project_type']}', '{$project['classification']}', 
 '{$project['description']}', '{$project['language']}', '{$project['image_path']}', 
 '{$project['video_path']}', '{$project['code_file_path']}', '{$project['instruction_file_path']}', 
 '{$project['submission_date']}', '$rejection_reason')";  
        if ($conn->query($insert_sql) === TRUE) {
            // Delete from projects table
            $delete_sql = "DELETE FROM projects WHERE id = $project_id";
            if ($conn->query($delete_sql) === TRUE) {
                echo "<div class='alert alert-warning shadow-sm'>
                        <div class='d-flex align-items-center'>
                            <i class='bi bi-x-circle-fill me-2'></i>
                            <strong>Success!</strong> Project rejected and moved to denied projects!
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
                        <strong>Error!</strong> Failed to reject project: " . $conn->error . "
                    </div>
                  </div>";
        }
    }
}

// Get pending projects
$pending_sql = "SELECT * FROM projects ORDER BY submission_date DESC";
$pending_result = $conn->query($pending_sql);

// Get approved projects with bookmark status for current user
$approved_sql = "SELECT admin_approved_projects.*, 
                CASE WHEN bookmark.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
                FROM admin_approved_projects 
                LEFT JOIN bookmark ON admin_approved_projects.id = bookmark.project_id AND bookmark.user_id = '" . session_id() . "'
                ORDER BY admin_approved_projects.submission_date DESC";
$approved_result = $conn->query($approved_sql);

// Get rejected projects
$rejected_sql = "SELECT * FROM denial_projects ORDER BY submission_date DESC"; // Changed from rejection_date to submission_date
$rejected_result = $conn->query($rejected_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --success-color: #4cc9f0;
        --info-color: #4895ef;
        --warning-color: #f72585;
        --danger-color: #ff5a5f;
        --light-color: #f8f9fa;
        --dark-color: #212529;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fa;
        color: #333;
    }

    .dashboard-header {
        background-color: var(--primary-color);
        color: white;
        padding: 1.5rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .dashboard-title {
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .dashboard-title i {
        margin-right: 10px;
        font-size: 1.8rem;
    }

    .project-card {
        margin-bottom: 1.5rem;
        border-radius: 12px;
        border: none;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .project-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1.25rem 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .badge {
        padding: 0.5rem 0.8rem;
        font-weight: 600;
        font-size: 0.75rem;
        border-radius: 50px;
    }

    .badge-approved {
        background-color: #10b981;
        color: white;
    }

    .badge-pending {
        background-color: #f59e0b;
        color: white;
    }

    .badge-rejected {
        background-color: #ef4444;
        color: white;
    }

    .file-link {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        padding: 8px 12px;
        border-radius: 8px;
        background-color: #f8f9fa;
        transition: all 0.2s ease;
        text-decoration: none;
        color: #333;
    }

    .file-link:hover {
        background-color: #e9ecef;
    }

    .file-link i {
        margin-right: 8px;
        font-size: 1.1rem;
        color: var(--primary-color);
    }

    .project-detail {
        margin-bottom: 10px;
    }

    .project-detail strong {
        color: var(--primary-color);
        font-weight: 600;
    }

    .empty-projects {
        text-align: center;
        padding: 3rem;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
    }

    .empty-projects i {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    .bookmark-btn {
        background: none;
        border: none;
        color: var(--primary-color);
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-left: 10px;
    }

    .bookmark-btn:hover {
        transform: scale(1.2);
    }

    .bookmark-btn.active {
        color: #f59e0b;
    }

    .section-title {
        margin: 2rem 0 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary-color);
        color: var(--primary-color);
        font-weight: 600;
    }

    .btn-approve {
        background-color: #10b981;
        color: white;
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-approve:hover {
        background-color: #059669;
        color: white;
        transform: translateY(-2px);
    }

    .btn-reject {
        background-color: #ef4444;
        color: white;
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-left: 10px;
    }

    .btn-reject:hover {
        background-color: #dc2626;
        color: white;
        transform: translateY(-2px);
    }

    .nav-tabs {
        border-bottom: 2px solid var(--primary-color);
        margin-bottom: 2rem;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6b7280;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        margin-right: 0.5rem;
        border-radius: 8px 8px 0 0;
    }

    .nav-tabs .nav-link.active {
        color: var(--primary-color);
        background-color: transparent;
        border-bottom: 3px solid var(--primary-color);
    }

    .nav-tabs .nav-link:hover:not(.active) {
        background-color: #f8f9fa;
    }

    .action-buttons {
        display: flex;
    }

    .rejection-reason {
        margin-top: 1rem;
        padding: 1rem;
        background-color: #fee2e2;
        border-radius: 8px;
        border-left: 4px solid #ef4444;
    }

    /* Add responsive styling */
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
            width: 100%;
        }

        .btn-action {
            width: 100%;
            margin-bottom: 8px;
        }

        .btn-reject {
            margin-left: 0;
            margin-top: 8px;
        }
    }
    </style>
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
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved-projects"
                    type="button" role="tab" aria-controls="approved-projects" aria-selected="false">
                    <i class="bi bi-check-circle me-1"></i>Approved Projects
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected-projects"
                    type="button" role="tab" aria-controls="rejected-projects" aria-selected="false">
                    <i class="bi bi-x-circle me-1"></i>Rejected Projects
                </button>
            </li>
        </ul>

        <div class="tab-content" id="projectTabsContent">
            <!-- Pending Projects Tab -->
            <div class="tab-pane fade show active" id="pending-projects" role="tabpanel" aria-labelledby="pending-tab">
                <h2 class="section-title">
                    <i class="bi bi-hourglass me-2"></i>Pending Projects
                </h2>

                <?php
                if ($pending_result && $pending_result->num_rows > 0) {
                    while($row = $pending_result->fetch_assoc()) {
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
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                                            <button type="submit" name="approve_project" class="btn btn-approve">
                                                <i class="bi bi-check-lg me-1"></i> Approve Project
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-reject" data-bs-toggle="modal"
                                            data-bs-target="#rejectModal<?php echo $row["id"]; ?>">
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

                                        <?php if(!empty($row["image_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["image_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-image"></i> View Image
                                        </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["video_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["video_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-play"></i> View Video
                                        </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["code_file_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["code_file_path"]); ?>"
                                            target="_blank" class="file-link">
                                            <i class="bi bi-file-earmark-code"></i> View Code
                                        </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["instruction_file_path"])): ?>
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

                <!-- Reject Modal for each project -->
                <div class="modal fade" id="rejectModal<?php echo $row["id"]; ?>" tabindex="-1"
                    aria-labelledby="rejectModalLabel<?php echo $row["id"]; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="rejectModalLabel<?php echo $row["id"]; ?>">
                                    <i class="bi bi-x-circle me-2"></i>Reject Project
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form method="post">
                                <div class="modal-body">
                                    <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                                    <p>You are about to reject the project
                                        <strong><?php echo htmlspecialchars($row["project_name"]); ?></strong>.
                                    </p>
                                    <div class="mb-3">
                                        <label for="rejection_reason" class="form-label">Rejection Reason:</label>
                                        <textarea class="form-control" name="rejection_reason" id="rejection_reason"
                                            rows="4" placeholder="Please provide a reason for rejecting this project..."
                                            required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="reject_project" class="btn btn-danger">
                                        <i class="bi bi-x-lg me-1"></i> Reject Project
                                    </button>
                                </div>
                            </form>
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
                </h2>

                <?php
                if ($approved_result && $approved_result->num_rows > 0) {
                    while($row = $approved_result->fetch_assoc()) {
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

                                        <?php if(!empty($row["image_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["image_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-image"></i> View Image
                                        </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["video_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["video_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-play"></i> View Video
                                        </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["code_file_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["code_file_path"]); ?>"
                                            target="_blank" class="file-link">
                                            <i class="bi bi-file-earmark-code"></i> View Code
                                        </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["instruction_file_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["instruction_file_path"]);
                                        ?>" target="_blank" class="file-link">
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
                    <i class="bi bi-check-circle"></i>
                    <h3>No Approved Projects</h3>
                    <p class="text-muted">There are currently no approved projects to display.</p>
                </div>
                <?php
            }
            ?>
            </div>

            <!-- Rejected Projects Tab -->
            <div class="tab-pane fade" id="rejected-projects" role="tabpanel" aria-labelledby="rejected-tab">
                <h2 class="section-title">
                    <i class="bi bi-x-circle me-2"></i>Rejected Projects
                </h2>

                <?php
            if ($rejected_result && $rejected_result->num_rows > 0) {
                while($row = $rejected_result->fetch_assoc()) {
            ?>
                <div class="project-card card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($row["project_name"]); ?></h5>
                        <span class="badge badge-rejected">
                            <i class="bi bi-x-circle me-1"></i>
                            Rejected
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
                                        <div class="project-detail">
                                            <strong><i class="bi bi-calendar-x me-1"></i> Rejected:</strong>
                                            <?php echo date("F j, Y, g:i a", strtotime($row["rejection_date"])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="project-detail mt-3">
                                    <strong><i class="bi bi-text-paragraph me-1"></i> Description:</strong>
                                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($row["description"])); ?></p>
                                </div>

                                <div class="rejection-reason">
                                    <strong><i class="bi bi-exclamation-triangle me-1"></i> Rejection Reason:</strong>
                                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($row["rejection_reason"])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title fw-bold mb-3"><i class="bi bi-file-earmark me-1"></i>
                                            Project Files</h6>

                                        <?php if(!empty($row["image_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["image_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-image"></i> View Image
                                        </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["video_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["video_path"]); ?>" target="_blank"
                                            class="file-link">
                                            <i class="bi bi-file-earmark-play"></i> View Video
                                        </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["code_file_path"])): ?>
                                        <a href="<?php echo htmlspecialchars($row["code_file_path"]); ?>"
                                            target="_blank" class="file-link">
                                            <i class="bi bi-file-earmark-code"></i> View Code
                                        </a>
                                        <?php endif; ?>

                                        <?php if(!empty($row["instruction_file_path"])): ?>
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
                    <i class="bi bi-x-circle"></i>
                    <h3>No Rejected Projects</h3>
                    <p class="text-muted">There are currently no rejected projects to display.</p>
                </div>
                <?php
            }
            ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Show the rejection modal when reject button is clicked
    const rejectBtn = document.querySelectorAll('.btn-reject');
    rejectBtn.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const projectId = e.target.closest('form').querySelector('input[name="project_id"]').value;
            const modalId = `#rejectModal${projectId}`;
            const modal = new bootstrap.Modal(document.querySelector(modalId));
            modal.show();
        });
    });
    </script>
</body>

</html>