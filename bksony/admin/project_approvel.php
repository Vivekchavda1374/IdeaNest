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

// Initialize variables
$message = "";
$pending_projects = [];

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Approve project
    if (isset($_POST['approve_project'])) {
        $project_id = $_POST['project_id'];
        
        $sql = "UPDATE projects SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        
        if ($stmt->execute()) {
            $message = "Project approved successfully";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    
    // Reject project
    if (isset($_POST['reject_project'])) {
        $project_id = $_POST['project_id'];
        
        $sql = "UPDATE projects SET status = 'rejected' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $project_id);
        
        if ($stmt->execute()) {
            $message = "Project rejected successfully";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Create the projects table if it doesn't exist with the new structure
$create_table_sql = "CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  `project_type` varchar(50) NOT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `language` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `code_file_path` varchar(255) DEFAULT NULL,
  `instruction_file_path` varchar(255) DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($create_table_sql) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// Make sure the status column exists in the projects table
$check_status_column = "SHOW COLUMNS FROM `projects` LIKE 'status'";
$result = $conn->query($check_status_column);
if ($result->num_rows == 0) {
    // Add status column if it doesn't exist
    $add_status_column = "ALTER TABLE `projects` ADD COLUMN `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'";
    if ($conn->query($add_status_column) === FALSE) {
        die("Error adding status column: " . $conn->error);
    }
}

// Fetch pending projects with user information
$sql = "SELECT p.id, p.project_name, p.project_type, p.classification, p.description, 
        p.language, p.image_path, p.video_path, p.code_file_path, p.instruction_file_path, 
        p.submission_date, u.name as submitter_name, u.email as submitter_email 
        FROM projects p
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'pending'
        ORDER BY p.submission_date DESC";
        
try {
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $pending_projects[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    // If error is due to missing status column, try alternative query
    $sql_alt = "SELECT p.id, p.project_name, p.project_type, p.classification, p.description, 
            p.language, p.image_path, p.video_path, p.code_file_path, p.instruction_file_path, 
            p.submission_date, u.name as submitter_name, u.email as submitter_email 
            FROM projects p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.submission_date DESC";
    
    $result = $conn->query($sql_alt);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $pending_projects[] = $row;
        }
    }
}

// Get project counts
$pending_count = count($pending_projects);

// Try to get approved count, handle potential errors
try {
    $sql = "SELECT COUNT(*) as approved_count FROM projects WHERE status = 'approved'";
    $result = $conn->query($sql);
    $approved_count = ($result && $row = $result->fetch_assoc()) ? $row['approved_count'] : 0;
} catch (mysqli_sql_exception $e) {
    $approved_count = 0;
}

// Try to get rejected count, handle potential errors
try {
    $sql = "SELECT COUNT(*) as rejected_count FROM projects WHERE status = 'rejected'";
    $result = $conn->query($sql);
    $rejected_count = ($result && $row = $result->fetch_assoc()) ? $row['rejected_count'] : 0;
} catch (mysqli_sql_exception $e) {
    $rejected_count = 0;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest Admin - Project Approval</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
    .sidebar {
        min-height: 100vh;
        background-color: #212529;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
    }

    .sidebar .nav-link:hover {
        color: #fff;
    }

    .sidebar .nav-link.active {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .main-content {
        padding: 20px;
    }

    .alert-dismissible {
        cursor: pointer;
    }

    .project-card {
        transition: all 0.3s ease;
    }

    .project-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .truncate-text {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .file-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="d-flex align-items-center justify-content-center mb-4">
                        <h3 class="text-white">IdeaNest Admin</h3>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="bi bi-people me-2"></i>Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-lightbulb me-2"></i>Ideas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="bi bi-check2-square me-2"></i>Project Approval
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-file-text me-2"></i>Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item mt-5">
                            <a class="nav-link" href="#">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Project Approval</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-sort-down"></i> Sort
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Pending Projects</h6>
                                        <h3 class="card-text"><?php echo $pending_count; ?></h3>
                                    </div>
                                    <i class="bi bi-hourglass-split fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Approved Projects</h6>
                                        <h3 class="card-text"><?php echo $approved_count; ?></h3>
                                    </div>
                                    <i class="bi bi-check-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Rejected Projects</h6>
                                        <h3 class="card-text"><?php echo $rejected_count; ?></h3>
                                    </div>
                                    <i class="bi bi-x-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Projects -->
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Pending Project Approvals</h5>
                        <span class="badge bg-warning"><?php echo $pending_count; ?> Pending</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_projects)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">No pending projects to approve</h4>
                            <p class="text-muted">All caught up! Check back later for new submissions.</p>
                        </div>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($pending_projects as $project): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 project-card">
                                    <div class="card-header d-flex justify-content-between">
                                        <h5 class="card-title"><?php echo htmlspecialchars($project['project_name']); ?>
                                        </h5>
                                        <span
                                            class="badge bg-info"><?php echo htmlspecialchars($project['project_type']); ?></span>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text truncate-text">
                                            <?php echo htmlspecialchars($project['description']); ?></p>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-person-circle me-2"></i>
                                            <small class="text-muted">Submitted by:
                                                <strong><?php echo htmlspecialchars($project['submitter_name']); ?></strong></small>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-envelope me-2"></i>
                                            <small class="text-muted">Contact:
                                                <?php echo htmlspecialchars($project['submitter_email']); ?></small>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-tag me-2"></i>
                                            <small class="text-muted">Language:
                                                <?php echo htmlspecialchars($project['language']); ?></small>
                                        </div>
                                        <?php if (!empty($project['classification'])): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="bi bi-bookmark me-2"></i>
                                            <small class="text-muted">Classification:
                                                <?php echo htmlspecialchars($project['classification']); ?></small>
                                        </div>
                                        <?php endif; ?>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar me-2"></i>
                                            <small class="text-muted">Submitted on:
                                                <?php echo date("M d, Y", strtotime($project['submission_date'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent d-flex justify-content-between">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?php echo $project['id']; ?>">
                                            <i class="bi bi-eye me-1"></i> View Details
                                        </button>
                                        <div>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="project_id"
                                                    value="<?php echo $project['id']; ?>">
                                                <button type="submit" name="approve_project"
                                                    class="btn btn-success me-1">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="project_id"
                                                    value="<?php echo $project['id']; ?>">
                                                <button type="submit" name="reject_project" class="btn btn-danger">
                                                    <i class="bi bi-x-lg"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Details Modal -->
                            <div class="modal fade" id="viewModal<?php echo $project['id']; ?>" tabindex="-1"
                                aria-labelledby="viewModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewModalLabel<?php echo $project['id']; ?>">
                                                Project Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <h4><?php echo htmlspecialchars($project['project_name']); ?></h4>
                                            <div class="mb-3">
                                                <span class="badge bg-primary">Type:
                                                    <?php echo htmlspecialchars($project['project_type']); ?></span>
                                                <span class="badge bg-secondary">Language:
                                                    <?php echo htmlspecialchars($project['language']); ?></span>
                                                <?php if (!empty($project['classification'])): ?>
                                                <span class="badge bg-info">Classification:
                                                    <?php echo htmlspecialchars($project['classification']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <h6 class="mb-0">Project Description</h6>
                                                </div>
                                                <div class="card-body">
                                                    <p><?php echo nl2br(htmlspecialchars($project['description'])); ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Project Files -->
                                            <div class="card mb-3">
                                                <div class="card-header">
                                                    <h6 class="mb-0">Project Files</h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <?php if (!empty($project['image_path'])): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="file-badge bg-light">
                                                                <i class="bi bi-image text-primary me-1"></i>
                                                                <a href="<?php echo htmlspecialchars($project['image_path']); ?>"
                                                                    target="_blank">View Image</a>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>

                                                        <?php if (!empty($project['video_path'])): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="file-badge bg-light">
                                                                <i class="bi bi-film text-danger me-1"></i>
                                                                <a href="<?php echo htmlspecialchars($project['video_path']); ?>"
                                                                    target="_blank">View Video</a>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>

                                                        <?php if (!empty($project['code_file_path'])): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="file-badge bg-light">
                                                                <i class="bi bi-code-square text-success me-1"></i>
                                                                <a href="<?php echo htmlspecialchars($project['code_file_path']); ?>"
                                                                    target="_blank">View Code</a>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>

                                                        <?php if (!empty($project['instruction_file_path'])): ?>
                                                        <div class="col-md-6 mb-2">
                                                            <div class="file-badge bg-light">
                                                                <i class="bi bi-file-text text-info me-1"></i>
                                                                <a href="<?php echo htmlspecialchars($project['instruction_file_path']); ?>"
                                                                    target="_blank">View Instructions</a>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>

                                                        <?php if (empty($project['image_path']) && empty($project['video_path']) && empty($project['code_file_path']) && empty($project['instruction_file_path'])): ?>
                                                        <div class="col-12">
                                                            <p class="text-muted">No files attached to this project.</p>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card mb-3">
                                                        <div class="card-header">
                                                            <h6 class="mb-0">Submitter Information</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p><strong>Name:</strong>
                                                                <?php echo htmlspecialchars($project['submitter_name']); ?>
                                                            </p>
                                                            <p><strong>Email:</strong>
                                                                <?php echo htmlspecialchars($project['submitter_email']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card mb-3">
                                                        <div class="card-header">
                                                            <h6 class="mb-0">Submission Details</h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <p><strong>Submission Date:</strong>
                                                                <?php echo date("F d, Y", strtotime($project['submission_date'])); ?>
                                                            </p>
                                                            <p><strong>Submission Time:</strong>
                                                                <?php echo date("h:i A", strtotime($project['submission_date'])); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="project_id"
                                                    value="<?php echo $project['id']; ?>">
                                                <button type="submit" name="approve_project"
                                                    class="btn btn-success me-1">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="project_id"
                                                    value="<?php echo $project['id']; ?>">
                                                <button type="submit" name="reject_project" class="btn btn-danger">
                                                    <i class="bi bi-x-lg"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-close alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        var alertList = document.querySelectorAll('.alert-dismissible');
        alertList.forEach(function(alert) {
            setTimeout(function() {
                var closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            }, 5000);
        });
    });
    </script>
</body>

</html>