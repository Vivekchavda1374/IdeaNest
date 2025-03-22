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

// Handle project approval
if (isset($_POST['approve'])) {
    $project_id = $_POST['project_id'];
    
    // First, get all the project data
    $sql = "SELECT * FROM projects WHERE id = $project_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();
        
        // Insert into admin_approved_projects
        $insert_sql = "INSERT INTO admin_approved_projects 
                       (project_name, project_type, classification, description, language, 
                       image_path, video_path, code_file_path, instruction_file_path, 
                       submission_date, status) 
                       VALUES 
                       ('{$project['project_name']}', '{$project['project_type']}', '{$project['classification']}', 
                       '{$project['description']}', '{$project['language']}', '{$project['image_path']}', 
                       '{$project['video_path']}', '{$project['code_file_path']}', '{$project['instruction_file_path']}', 
                       '{$project['submission_date']}', 'approved')";
        
        if ($conn->query($insert_sql) === TRUE) {
            // Delete from projects table
            $delete_sql = "DELETE FROM projects WHERE id = $project_id";
            if ($conn->query($delete_sql) === TRUE) {
                echo "<div class='alert alert-success shadow-sm'>
                        <div class='d-flex align-items-center'>
                            <i class='bi bi-check-circle-fill me-2'></i>
                            <strong>Success!</strong> Project approved and moved to approved projects successfully!
                        </div>
                      </div>";
            } else {
                echo "<div class='alert alert-danger shadow-sm'>
                        <div class='d-flex align-items-center'>
                            <i class='bi bi-exclamation-triangle-fill me-2'></i>
                            <strong>Error!</strong> Error deleting from projects table: " . $conn->error . "
                        </div>
                      </div>";
            }
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

// Handle project rejection
if (isset($_POST['reject'])) {
    $project_id = $_POST['project_id'];
    
    // Update status in projects table
    $update_sql = "UPDATE projects SET status = 'rejected' WHERE id = $project_id";
    
    if ($conn->query($update_sql) === TRUE) {
        echo "<div class='alert alert-warning shadow-sm'>
                <div class='d-flex align-items-center'>
                    <i class='bi bi-exclamation-circle-fill me-2'></i>
                    <strong>Notice:</strong> Project rejected!
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

// Fetch all projects
$sql = "SELECT * FROM projects ORDER BY submission_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Project Management</title>
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

    .badge-pending {
        background-color: #f59e0b;
        color: white;
    }

    .badge-approved {
        background-color: #10b981;
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

    .btn-action {
        border-radius: 50px;
        padding: 8px 20px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-approve {
        background-color: #10b981;
        border-color: #10b981;
    }

    .btn-approve:hover {
        background-color: #059669;
        border-color: #059669;
    }

    .btn-reject {
        background-color: #ef4444;
        border-color: #ef4444;
    }

    .btn-reject:hover {
        background-color: #dc2626;
        border-color: #dc2626;
    }

    .project-detail {
        margin-bottom: 10px;
    }

    .project-detail strong {
        color: var(--primary-color);
        font-weight: 600;
    }

    .action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
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
    }
    </style>
</head>

<body>
    <div class="dashboard-header">
        <div class="container">
            <h1 class="dashboard-title">
                <i class="bi bi-kanban"></i>
                Project Management Dashboard
            </h1>
        </div>
    </div>

    <div class="container">
        <?php
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
        ?>
        <div class="project-card card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($row["project_name"]); ?></h5>
                <span class="badge <?php echo 'badge-'.strtolower($row["status"]); ?>">
                    <?php 
                    $status_icon = "";
                    switch(strtolower($row["status"])) {
                        case "pending":
                            $status_icon = "bi-hourglass-split";
                            break;
                        case "approved":
                            $status_icon = "bi-check-circle";
                            break;
                        case "rejected":
                            $status_icon = "bi-x-circle";
                            break;
                        default:
                            $status_icon = "bi-circle";
                    }
                    ?>
                    <i class="bi <?php echo $status_icon; ?> me-1"></i>
                    <?php echo $row["status"]; ?>
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
                                    <strong><i class="bi bi-hash me-1"></i> ID:</strong>
                                    <?php echo $row["id"]; ?>
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
                                <h6 class="card-title fw-bold mb-3"><i class="bi bi-file-earmark me-1"></i> Project
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
                                <a href="<?php echo htmlspecialchars($row["code_file_path"]); ?>" target="_blank"
                                    class="file-link">
                                    <i class="bi bi-file-earmark-code"></i> View Code
                                </a>
                                <?php endif; ?>

                                <?php if(!empty($row["instruction_file_path"])): ?>
                                <a href="<?php echo htmlspecialchars($row["instruction_file_path"]); ?>" target="_blank"
                                    class="file-link">
                                    <i class="bi bi-file-earmark-text"></i> View Instructions
                                </a>
                                <?php endif; ?>

                                <div class="action-buttons">
                                    <form method="post">
                                        <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                                        <button type="submit" name="approve"
                                            class="btn btn-success btn-action btn-approve">
                                            <i class="bi bi-check-lg me-1"></i> Approve
                                        </button>
                                    </form>
                                    <form method="post">
                                        <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                                        <button type="submit" name="reject"
                                            class="btn btn-danger btn-action btn-reject">
                                            <i class="bi bi-x-lg me-1"></i> Reject
                                        </button>
                                    </form>
                                </div>
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
            <i class="bi bi-inbox"></i>
            <h3>No Projects Found</h3>
            <p class="text-muted">There are currently no projects available for review.</p>
        </div>
        <?php
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// Close connection
$conn->close();
?>