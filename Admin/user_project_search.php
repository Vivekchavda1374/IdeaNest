<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session before any output or includes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = '../user/';
include $basePath . 'layout.php';

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

// Get project ID from URL
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($project_id <= 0) {
    echo "<div class='alert alert-warning'>No project selected or invalid project ID.</div>";
    echo "<div class='mb-3'><a href='../user/all_projects.php' class='btn btn-primary'>Back to Projects</a></div>";
    exit();
}

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark'])) {
    $session_id = session_id();

    // Check if bookmark already exists for this project
    $check_sql = "SELECT * FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $project_id, $session_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Bookmark exists, so remove it
        $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("is", $project_id, $session_id);

        if ($delete_stmt->execute()) {
            $bookmark_message = "<div class='alert alert-info shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark me-2'></i>
                        <strong>Success!</strong> Project removed from bookmarks!
                    </div>
                  </div>";
        } else {
            $bookmark_message = "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> " . $conn->error . "
                    </div>
                  </div>";
        }
        $delete_stmt->close();
    } else {
        // Add new bookmark
        $idea_id = 0; // Default value for idea_id

        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isi", $project_id, $session_id, $idea_id);

        if ($insert_stmt->execute()) {
            $bookmark_message = "<div class='alert alert-success shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark-fill me-2'></i>
                        <strong>Success!</strong> Project added to bookmarks!
                    </div>
                  </div>";
        } else {
            $bookmark_message = "<div class='alert alert-danger shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-exclamation-triangle-fill me-2'></i>
                        <strong>Error!</strong> " . $conn->error . "
                    </div>
                  </div>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Fetch project details with bookmark status
$sql = "SELECT admin_approved_projects.*, 
        CASE WHEN bookmark.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
        FROM admin_approved_projects 
        LEFT JOIN bookmark ON admin_approved_projects.id = bookmark.project_id AND bookmark.user_id = ? 
        WHERE admin_approved_projects.id = ?";

$stmt = $conn->prepare($sql);
$session_id = session_id();
$stmt->bind_param("si", $session_id, $project_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die('Query failed: ' . $conn->error);
}

$project = $result->fetch_assoc();

if (!$project) {
    echo "<div class='alert alert-warning'>Project not found.</div>";
    exit();
}

// Display bookmark message if set
if (isset($bookmark_message)) {
    echo $bookmark_message;
}
?>

<div class="container-fluid px-0">
    <!-- Back button -->
    <div class="mb-4">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fas fa-arrow-left me-2"></i>Back to Projects
        </a>
    </div>

    <!-- Project Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h1 class="h2 fw-bold text-primary mb-2"><?php echo htmlspecialchars($project['project_name']); ?></h1>
                            <span class="badge bg-gradient-primary text-uppercase fs-6 rounded-pill px-3 py-2">
                                <?php echo htmlspecialchars($project['project_type']); ?>
                            </span>
                        </div>
                        <form method="post" style="display:inline;">
                            <button type="submit" name="toggle_bookmark" class="btn btn-link p-0" style="color:<?php echo $project['is_bookmarked'] ? '#f72585' : '#aaa'; ?>;" title="<?php echo $project['is_bookmarked'] ? 'Remove from bookmarks' : 'Add to bookmarks'; ?>">
                                <i class="fas fa-bookmark<?php echo $project['is_bookmarked'] ? '' : '-o'; ?> fa-2x"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="h5 fw-bold mb-3">Project Description</h4>
                            <p class="text-muted mb-4" style="line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h5 class="card-title fw-bold mb-3">Project Info</h5>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="fas fa-tags me-2 text-secondary"></i>
                                            <strong>Classification:</strong><br>
                                            <span class="ms-4"><?php echo htmlspecialchars($project['classification'] ?? 'N/A'); ?></span>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-code me-2 text-secondary"></i>
                                            <strong>Language:</strong><br>
                                            <span class="ms-4"><?php echo htmlspecialchars($project['language']); ?></span>
                                        </li>
                                        <li class="mb-0">
                                            <i class="fas fa-calendar-alt me-2 text-secondary"></i>
                                            <strong>Submitted:</strong><br>
                                            <span class="ms-4"><?php echo date('F j, Y', strtotime($project['submission_date'])); ?></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Media Section -->
    <?php if (!empty($project['image_path']) || !empty($project['video_path'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h4 class="h5 fw-bold mb-3">Media</h4>
                    <div class="row">
                        <?php if (!empty($project['image_path'])): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Project Image</h6>
                                    <img src="<?php echo htmlspecialchars($project['image_path']); ?>" 
                                         alt="Project Image" 
                                         class="img-fluid rounded shadow-sm" 
                                         style="max-height: 300px; object-fit: cover;">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($project['video_path'])): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Project Video</h6>
                                    <video controls class="w-100 rounded shadow-sm" style="max-height: 300px;">
                                        <source src="<?php echo htmlspecialchars($project['video_path']); ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Downloads Section -->
    <?php if (!empty($project['code_file_path']) || !empty($project['instruction_file_path'])): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h4 class="h5 fw-bold mb-3">Downloads</h4>
                    <div class="row">
                        <?php if (!empty($project['code_file_path'])): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-file-code fa-3x text-primary"></i>
                                    </div>
                                    <h6 class="card-title">Source Code</h6>
                                    <p class="text-muted small">Download the complete source code for this project</p>
                                    <a href="<?php echo htmlspecialchars($project['code_file_path']); ?>" 
                                       class="btn btn-primary rounded-pill px-4" 
                                       target="_blank">
                                        <i class="fas fa-download me-2"></i>Download Code
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($project['instruction_file_path'])): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas fa-file-alt fa-3x text-secondary"></i>
                                    </div>
                                    <h6 class="card-title">Instructions</h6>
                                    <p class="text-muted small">Download setup and usage instructions</p>
                                    <a href="<?php echo htmlspecialchars($project['instruction_file_path']); ?>" 
                                       class="btn btn-secondary rounded-pill px-4" 
                                       target="_blank">
                                        <i class="fas fa-download me-2"></i>Download Instructions
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Project Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="h5 fw-bold mb-3">Project Actions</h4>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <form method="post" style="display:inline;">
                            <button type="submit" name="toggle_bookmark" class="btn btn-outline-primary rounded-pill px-4">
                                <i class="fas fa-bookmark<?php echo $project['is_bookmarked'] ? '' : '-o'; ?> me-2"></i>
                                <?php echo $project['is_bookmarked'] ? 'Remove Bookmark' : 'Add Bookmark'; ?>
                            </button>
                        </form>
                        
                        <a href="javascript:history.back()" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="fas fa-arrow-left me-2"></i>Back to Projects
                        </a>
                        
                        <?php if (!empty($project['code_file_path'])): ?>
                        <a href="<?php echo htmlspecialchars($project['code_file_path']); ?>" 
                           class="btn btn-success rounded-pill px-4" 
                           target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>View Project Files
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $basePath . 'layout_footer.php'; ?>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
    // Sidebar functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('overlay');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                overlay.classList.toggle('active');
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                overlay.classList.remove('active');
            });
        }
    });

    // Image modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img[src]');
        images.forEach(img => {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function() {
                const modal = document.createElement('div');
                modal.innerHTML = `
                    <div class="modal fade" id="imageModal" tabindex="-1">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Project Image</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img src="${this.src}" class="img-fluid" alt="Project Image">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                const bootstrapModal = new bootstrap.Modal(modal.querySelector('.modal'));
                bootstrapModal.show();
                
                modal.querySelector('.modal').addEventListener('hidden.bs.modal', function() {
                    document.body.removeChild(modal);
                });
            });
        });
    });
</script>

<style>
.bg-gradient-primary {
    background: linear-gradient(90deg, #4361ee 0%, #4cc9f0 100%);
    color: #fff !important;
}

.card {
    border-radius: 18px;
    transition: box-shadow 0.2s, transform 0.2s;
}

.card:hover {
    box-shadow: 0 8px 32px rgba(67, 97, 238, 0.15);
}

.btn {
    border-radius: 20px;
    transition: all 0.2s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.text-primary {
    color: #4361ee !important;
}

.btn-primary {
    background: linear-gradient(90deg, #4361ee 0%, #4cc9f0 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #3651d4 0%, #3bb5e0 100%);
}

.btn-outline-primary {
    border-color: #4361ee;
    color: #4361ee;
}

.btn-outline-primary:hover {
    background-color: #4361ee;
    border-color: #4361ee;
}

.shadow-sm {
    box-shadow: 0 4px 24px rgba(67, 97, 238, 0.07) !important;
}

img {
    transition: transform 0.2s;
}

img:hover {
    transform: scale(1.02);
}

.gap-3 {
    gap: 1rem !important;
}

@media (max-width: 768px) {
    .d-flex.gap-3 {
        flex-direction: column;
    }
    
    .d-flex.gap-3 > * {
        width: 100%;
    }
}
</style>

<?php
$stmt->close();
$conn->close();
?>