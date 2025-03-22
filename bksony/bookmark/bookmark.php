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

// Handle bookmark removal if requested
if (isset($_POST['remove_bookmark'])) {
    $project_id = $_POST['project_id'];
    $session_id = session_id();
    
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
}

// Fetch all bookmarked projects for current session
$sql = "SELECT admin_approved_projects.* 
        FROM bookmark
        JOIN admin_approved_projects ON bookmark.project_id = admin_approved_projects.id
        WHERE bookmark.user_id = '" . session_id() . "'
        ORDER BY admin_approved_projects.submission_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookmarked Projects</title>
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

    .bookmark-btn {
        background: none;
        border: none;
        color: #f59e0b;
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-left: 10px;
    }

    .bookmark-btn:hover {
        transform: scale(1.2);
    }

    .section-title {
        margin: 2rem 0 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary-color);
        color: var(--primary-color);
        font-weight: 600;
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
                <i class="bi bi-bookmark-heart-fill"></i>
                My Bookmarked Projects
            </h1>
        </div>
    </div>

    <div class="container">
        <!-- Bookmarked Projects Section -->
        <h2 class="section-title">
            <i class="bi bi-bookmark-fill me-2"></i>My Bookmarks
        </h2>

        <?php
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
        ?>
        <div class="project-card card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($row["project_name"]); ?></h5>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                        <button type="submit" name="remove_bookmark" class="bookmark-btn">
                            <i class="bi bi-bookmark-fill"></i>
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
            <i class="bi bi-bookmark"></i>
            <h3>No Bookmarked Projects</h3>
            <p class="text-muted">You haven't bookmarked any projects yet. Browse approved projects and click the
                bookmark icon to add them here.</p>
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