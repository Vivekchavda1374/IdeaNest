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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookmarks - IdeaNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/bookmark.css">
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="bookmark-container">
            <!-- Bookmark Header -->
            <div class="bookmark-header">
                <h1><i class="fas fa-bookmark me-3"></i>My Bookmarks</h1>
                <p>Your curated collection of favorite projects and ideas</p>
                
                <div class="bookmark-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $result ? $result->num_rows : 0; ?></span>
                        <span class="stat-label">Bookmarked Projects</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo date('M'); ?></span>
                        <span class="stat-label">This Month</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo date('Y'); ?></span>
                        <span class="stat-label">This Year</span>
                    </div>
                </div>
            </div>

            <!-- Display bookmark message if set -->
            <?php if (isset($bookmark_message)): ?>
                <div class="alert alert-modern">
                    <?php echo $bookmark_message; ?>
                </div>
            <?php endif; ?>

            <!-- Bookmarked Projects -->
            <div class="row g-4">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card project-card h-100 border-0">
                                <div class="bookmark-icon">
                                    <i class="fas fa-bookmark"></i>
                                </div>
                                
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="project-type-badge">
                                            <?php echo htmlspecialchars($row['project_type']); ?>
                                        </span>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo date('M d, Y', strtotime($row['bookmarked_at'])); ?>
                                        </small>
                                    </div>
                                    
                                    <h5 class="project-title">
                                        <?php echo htmlspecialchars($row['project_name']); ?>
                                    </h5>
                                    
                                    <p class="project-description">
                                        <?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 120, '...')); ?>
                                    </p>
                                    
                                    <div class="project-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-tags meta-icon"></i>
                                            <span class="meta-label">Classification:</span>
                                            <span class="meta-value"><?php echo htmlspecialchars($row['classification']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-code meta-icon"></i>
                                            <span class="meta-label">Language:</span>
                                            <span class="meta-value"><?php echo htmlspecialchars($row['language']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-calendar-alt meta-icon"></i>
                                            <span class="meta-label">Submitted:</span>
                                            <span class="meta-value"><?php echo date('M d, Y', strtotime($row['submission_date'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-transparent border-0 p-4 pt-0">
                                    <div class="card-actions">
                                        <a href="project_details.php?id=<?php echo $row['id']; ?>" class="btn btn-modern btn-primary-modern flex-fill">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </a>
                                        <form method="post" style="display:inline; flex: 0 0 auto;">
                                            <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="toggle_bookmark" class="btn btn-modern btn-danger-modern" title="Remove Bookmark">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="fas fa-bookmark empty-state-icon"></i>
                            <h3>No Bookmarks Yet</h3>
                            <p>Start exploring amazing projects and save your favorites to this collection. Your bookmarks will appear here for easy access.</p>
                            <a href="all_projects.php" class="btn-explore">
                                <i class="fas fa-search"></i>
                                Explore Projects
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include $basePath . 'layout_footer.php'; ?>