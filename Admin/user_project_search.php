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

if (isset($_POST['toggle_bookmark'])) {
    $project_id = $_POST['project_id'];
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
            echo "<div class='alert alert-info shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark me-2'></i>
                        <strong>Success!</strong> Project removed from bookmarks!
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
        $delete_stmt->close();
    } else {
        // Add new bookmark
        $idea_id = 0; // Default value for idea_id or you could make this field nullable

        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isi", $project_id, $session_id, $idea_id);

        if ($insert_stmt->execute()) {
            echo "<div class='alert alert-success shadow-sm'>
                    <div class='d-flex align-items-center'>
                        <i class='bi bi-bookmark-fill me-2'></i>
                        <strong>Success!</strong> Project added to bookmarks!
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
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Get approved projects with bookmark status for current user
$sql = "SELECT admin_approved_projects.*, 
        CASE WHEN bookmark.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
        FROM admin_approved_projects 
        LEFT JOIN bookmark ON admin_approved_projects.id = bookmark.project_id AND bookmark.user_id = ? 
        ORDER BY admin_approved_projects.submission_date DESC";

$stmt = $conn->prepare($sql);
$session_id = session_id();
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die('Query failed: ' . $conn->error);
}
?>

<div class="container-fluid px-0">
    <h2>Approved Projects</h2>
    <div class="row g-4">
    <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card project-card shadow-sm h-100 border-0">
                        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                            <span class="badge rounded-pill bg-gradient-primary text-uppercase">
                                <?php echo htmlspecialchars($row['project_type']); ?>
                            </span>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="toggle_bookmark" class="btn btn-link p-0" style="color:<?php echo $row['is_bookmarked'] ? '#f72585' : '#aaa'; ?>;" title="Bookmark">
                                    <i class="fas fa-bookmark<?php echo $row['is_bookmarked'] ? '' : '-o'; ?> fa-lg"></i>
                                    </button>
                                </form>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary mb-2">
                                <?php echo htmlspecialchars($row['project_name']); ?>
                            </h5>
                            <p class="card-text text-muted mb-3" style="min-height: 60px;">
                                <?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 100, '...')); ?>
                            </p>
                            <ul class="list-unstyled small mb-3">
                                <li><i class="fas fa-tags me-1 text-secondary"></i> <strong>Classification:</strong> <?php echo htmlspecialchars($row['classification']); ?></li>
                                <li><i class="fas fa-code me-1 text-secondary"></i> <strong>Language:</strong> <?php echo htmlspecialchars($row['language']); ?></li>
                                <li><i class="fas fa-calendar-alt me-1 text-secondary"></i> <strong>Submitted:</strong> <?php echo htmlspecialchars($row['submission_date']); ?></li>
                            </ul>
                            </div>
                        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
                            <a href="../user/project_details.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                            <?php if (!empty($row['project_file_path'])): ?>
                                <a href="<?php echo htmlspecialchars($row['project_file_path']); ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3" target="_blank">
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
    <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">No projects found.</div>
        </div>
    <?php endif; ?>
        </div>
</div>
<?php include $basePath . 'layout_footer.php'; ?>

<!-- Scripts for sidebar and search -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
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
    function fetchResults() {
        const searchInput = document.getElementById('search');
        const searchResults = document.getElementById('searchResults');
        const searchTerm = searchInput.value.trim();
        if (searchTerm.length > 0) {
            searchResults.classList.remove('d-none');
            searchResults.innerHTML = '<p class="text-center py-2"><i class="fas fa-spinner fa-spin me-2"></i>Searching...</p>';
            fetch('../../user/search.php?query=' + encodeURIComponent(searchTerm))
                .then(response => response.text())
                .then(data => {
                    searchResults.innerHTML = data;
                })
                .catch(error => {
                    searchResults.innerHTML = '<p class="text-center py-2 text-danger">Error fetching results</p>';
                    console.error('Search error:', error);
                });
        } else {
            searchResults.classList.add('d-none');
        }
    }
    document.addEventListener('click', function(event) {
        const searchResults = document.getElementById('searchResults');
        const searchInput = document.getElementById('search');
        if (event.target !== searchInput && !searchResults.contains(event.target)) {
            searchResults.classList.add('d-none');
        }
    });
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                document.getElementById('searchResults').classList.remove('d-none');
                fetchResults();
            }
        });
    }
</script>

</div>

<style>
.project-card {
    border-radius: 18px;
    transition: box-shadow 0.2s, transform 0.2s;
    box-shadow: 0 4px 24px rgba(67, 97, 238, 0.07);
    background: #fff;
}
.project-card:hover {
    box-shadow: 0 8px 32px rgba(67, 97, 238, 0.15);
    transform: translateY(-4px) scale(1.01);
}
.bg-gradient-primary {
    background: linear-gradient(90deg, #4361ee 0%, #4cc9f0 100%);
    color: #fff !important;
}
.btn-outline-primary, .btn-outline-secondary {
    border-radius: 20px;
}
.btn-outline-primary:hover, .btn-outline-secondary:hover {
    box-shadow: 0 2px 8px rgba(67, 97, 238, 0.10);
}
</style>