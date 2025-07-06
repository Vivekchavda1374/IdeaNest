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
/* Modern Bookmark Page Styles */
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.bookmark-container {
    padding: 2rem 0;
    min-height: 100vh;
}

.bookmark-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 3rem 2rem;
    margin-bottom: 3rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.bookmark-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border-radius: 24px;
}

.bookmark-header h1 {
    font-size: 3rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

.bookmark-header p {
    font-size: 1.2rem;
    color: #6b7280;
    font-weight: 500;
    position: relative;
    z-index: 1;
}

.bookmark-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 2rem;
    position: relative;
    z-index: 1;
}

.stat-item {
    text-align: center;
    padding: 1rem 2rem;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 16px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #667eea;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
}

.project-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    position: relative;
}

.project-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.project-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.project-type-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bookmark-icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.project-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.75rem;
    line-height: 1.4;
}

.project-description {
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.project-meta {
    background: rgba(102, 126, 234, 0.05);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.meta-item:last-child {
    margin-bottom: 0;
}

.meta-icon {
    width: 20px;
    color: #667eea;
    margin-right: 0.5rem;
}

.meta-label {
    font-weight: 600;
    color: #374151;
    margin-right: 0.5rem;
}

.meta-value {
    color: #6b7280;
}

.card-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: auto;
}

.btn-modern {
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    color: white;
}

.btn-danger-modern {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.btn-danger-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(240, 147, 251, 0.3);
    color: white;
}

.empty-state {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 4rem 2rem;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.empty-state-icon {
    font-size: 5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1.5rem;
    display: block;
}

.empty-state h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 1rem;
}

.empty-state p {
    font-size: 1.1rem;
    color: #6b7280;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.btn-explore {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 2rem;
    border-radius: 16px;
    font-weight: 600;
    font-size: 1.1rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    border: none;
}

.btn-explore:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px rgba(102, 126, 234, 0.3);
    color: white;
    text-decoration: none;
}

.alert-modern {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .bookmark-header h1 {
        font-size: 2.5rem;
    }
    
    .bookmark-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .stat-item {
        padding: 0.75rem 1.5rem;
    }
    
    .project-card {
        margin-bottom: 1.5rem;
    }
}

/* Animation for cards */
.project-card {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Stagger animation for multiple cards */
.project-card:nth-child(1) { animation-delay: 0.1s; }
.project-card:nth-child(2) { animation-delay: 0.2s; }
.project-card:nth-child(3) { animation-delay: 0.3s; }
.project-card:nth-child(4) { animation-delay: 0.4s; }
.project-card:nth-child(5) { animation-delay: 0.5s; }
.project-card:nth-child(6) { animation-delay: 0.6s; }
</style>

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

<?php include $basePath . 'layout_footer.php'; ?>