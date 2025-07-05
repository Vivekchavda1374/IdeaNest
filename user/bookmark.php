<?php
include '../Login/Login/db.php';
// Start session before any output or includes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in BEFORE including layout.php
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

$basePath = './';
include $basePath . 'layout.php';

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

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            <i class="bi bi-bookmark-heart-fill me-2"></i>
            My Bookmarked Projects
        </h2>
    </div>

    <!-- Bookmarked Projects Section -->
    <div class="row g-4 mb-4">
        <?php
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
        ?>
        <div class="col-12">
            <div class="card project-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($row["project_name"]); ?></h5>
                        <form method="post" class="d-inline ms-3">
                            <input type="hidden" name="project_id" value="<?php echo $row["id"]; ?>">
                            <button type="submit" name="remove_bookmark" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-bookmark-fill"></i> Remove
                            </button>
                        </form>
                    </div>
                    <span class="badge bg-success">
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
                                    <h6 class="card-title fw-bold mb-3"><i class="bi bi-file-earmark me-1"></i> Project Files</h6>

                                    <?php if(!empty($row["image_path"])): ?>
                                    <a href="<?php echo htmlspecialchars($row["image_path"]); ?>" target="_blank" class="btn btn-outline-primary btn-sm mb-2 w-100">
                                        <i class="bi bi-file-earmark-image me-1"></i> View Image
                                    </a>
                                    <?php endif; ?>

                                    <?php if(!empty($row["video_path"])): ?>
                                    <a href="<?php echo htmlspecialchars($row["video_path"]); ?>" target="_blank" class="btn btn-outline-primary btn-sm mb-2 w-100">
                                        <i class="bi bi-file-earmark-play me-1"></i> View Video
                                    </a>
                                    <?php endif; ?>

                                    <?php if(!empty($row["code_file_path"])): ?>
                                    <a href="<?php echo htmlspecialchars($row["code_file_path"]); ?>" target="_blank" class="btn btn-outline-primary btn-sm mb-2 w-100">
                                        <i class="bi bi-file-earmark-code me-1"></i> View Code
                                    </a>
                                    <?php endif; ?>

                                    <?php if(!empty($row["instruction_file_path"])): ?>
                                    <a href="<?php echo htmlspecialchars($row["instruction_file_path"]); ?>" target="_blank" class="btn btn-outline-primary btn-sm mb-2 w-100">
                                        <i class="bi bi-file-earmark-text me-1"></i> View Instructions
                                    </a>
                                    <?php endif; ?>
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
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="bi bi-bookmark display-1 text-muted mb-3"></i>
                    <h3>No Bookmarked Projects</h3>
                    <p class="text-muted">You haven't bookmarked any projects yet. Browse approved projects and click the bookmark icon to add them here.</p>
                </div>
            </div>
        </div>
        <?php
        }
        ?>
    </div>
</div>

<?php include $basePath . 'layout_footer.php'; ?>

<?php
// Close connection
$conn->close();
?>