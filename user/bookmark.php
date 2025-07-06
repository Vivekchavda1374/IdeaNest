<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session before any output or includes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = './';
include $basePath . 'layout.php';
include '../Login/Login/db.php';

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

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : session_id();

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark'])) {
    $project_id = $_POST['project_id'];
    $session_id = session_id();

    // Check if bookmark already exists for this project
    $check_sql = "SELECT id FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $project_id, $session_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Bookmark exists, so remove it
        $bookmark_data = $check_result->fetch_assoc();
        $delete_sql = "DELETE FROM bookmark WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $bookmark_data['id']);

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
                        <strong>Error!</strong> Failed to remove bookmark: " . $conn->error . "
                    </div>
                  </div>";
        }
        $delete_stmt->close();
    } else {
        // Add new bookmark
        $idea_id = 0; // Default value for idea_id
        $current_timestamp = date('Y-m-d H:i:s');

        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id, bookmarked_at) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isis", $project_id, $session_id, $idea_id, $current_timestamp);

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
                        <strong>Error!</strong> Failed to add bookmark: " . $conn->error . "
                    </div>
                  </div>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Get bookmarked projects for current user
$sql = "SELECT admin_approved_projects.*, 
        bookmark.bookmarked_at,
        bookmark.id as bookmark_id
        FROM admin_approved_projects 
        INNER JOIN bookmark ON admin_approved_projects.id = bookmark.project_id 
        WHERE bookmark.user_id = ? 
        ORDER BY bookmark.bookmarked_at DESC";

$stmt = $conn->prepare($sql);
$session_id = session_id();
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die('Query failed: ' . $conn->error);
}
?>

<style>
.bookmark-header {
    background: linear-gradient(120deg, #3a86ff 0%, #8338ec 100%);
    border-radius: 2rem;
    padding: 2.5rem 2rem;
    margin-bottom: 2.5rem;
    box-shadow: 0 8px 32px 0 rgba(58,134,255,0.15);
    color: #fff;
    text-align: center;
}
.bookmark-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}
.bookmark-header p {
    font-size: 1.1rem;
    opacity: 0.9;
}
.project-card {
    border-radius: 18px;
    transition: box-shadow 0.2s, transform 0.2s;
    box-shadow: 0 4px 24px rgba(67, 97, 238, 0.07);
    background: rgba(255,255,255,0.8);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.18);
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
.bookmark-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: linear-gradient(135deg, #f72585 0%, #7209b7 100%);
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(247, 37, 133, 0.3);
}
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: rgba(255,255,255,0.7);
    border-radius: 2rem;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.18);
}
.empty-state i {
    font-size: 4rem;
    color: #6c757d;
    margin-bottom: 1rem;
}
</style>

<div class="container-fluid px-0">
    <!-- Bookmark Header -->
    <div class="bookmark-header">
        <h1><i class="fas fa-bookmark me-3"></i>My Bookmarks</h1>
        <p>Your saved projects and ideas</p>
    </div>

    <!-- Display bookmark message if set -->
    <?php if (isset($bookmark_message)): ?>
        <div class="mb-4">
            <?php echo $bookmark_message; ?>
        </div>
    <?php endif; ?>

    <!-- Bookmarked Projects -->
    <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card project-card shadow-sm h-100 border-0 position-relative">
                        <div class="bookmark-badge">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                            <span class="badge rounded-pill bg-gradient-primary text-uppercase">
                                <?php echo htmlspecialchars($row['project_type']); ?>
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo date('M d, Y', strtotime($row['bookmarked_at'])); ?>
                            </small>
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
                            <a href="project_details.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="toggle_bookmark" class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Remove Bookmark">
                                    <i class="fas fa-bookmark me-1"></i> Remove
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-bookmark"></i>
                    <h3>No Bookmarks Yet</h3>
                    <p class="text-muted">You haven't bookmarked any projects yet. Start exploring and save your favorite projects!</p>
                    <a href="all_projects.php" class="btn btn-primary btn-lg rounded-pill px-4">
                        <i class="fas fa-search me-2"></i>Explore Projects
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include $basePath . 'layout_footer.php'; ?>