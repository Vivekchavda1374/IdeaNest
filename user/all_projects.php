<?php
// user/all_projects.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$basePath = './';
include $basePath . 'layout.php';
include '../Login/Login/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark']) && isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
    $session_id = session_id();
    // Check if bookmark already exists
    $check_sql = "SELECT * FROM bookmark WHERE project_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $project_id, $session_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        // Remove bookmark
        $delete_sql = "DELETE FROM bookmark WHERE project_id = ? AND user_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("is", $project_id, $session_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        $bookmark_message = '<div class="alert alert-info">Bookmark removed!</div>';
    } else {
        // Add bookmark
        $idea_id = 0;
        $insert_sql = "INSERT INTO bookmark (project_id, user_id, idea_id) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isi", $project_id, $session_id, $idea_id);
        $insert_stmt->execute();
        $insert_stmt->close();
        $bookmark_message = '<div class="alert alert-success">Project bookmarked!</div>';
    }
    $check_stmt->close();
}

// Fetch all approved projects with bookmark status
$session_id = session_id();
$sql = "SELECT ap.*, CASE WHEN b.project_id IS NOT NULL THEN 1 ELSE 0 END AS is_bookmarked
        FROM admin_approved_projects ap
        LEFT JOIN bookmark b ON ap.id = b.project_id AND b.user_id = ?
        ORDER BY ap.submission_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<div class="container py-5">
    <h2 class="fw-bold mb-4 text-primary"><i class="fas fa-project-diagram me-2"></i>All Approved Projects</h2>
    <?php if (isset($bookmark_message)) echo $bookmark_message; ?>
    <div class="row g-4">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $project): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm project-card">
                        <form method="post" class="bookmark-float">
                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <button type="submit" name="toggle_bookmark" class="btn btn-link p-0" style="color:<?php echo $project['is_bookmarked'] ? '#f72585' : '#aaa'; ?>; font-size: 1.5rem;" title="<?php echo $project['is_bookmarked'] ? 'Remove from bookmarks' : 'Add to bookmarks'; ?>">
                                <i class="fas fa-bookmark<?php echo $project['is_bookmarked'] ? '' : '-o'; ?>"></i>
                            </button>
                        </form>
                        <div class="card-body" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $project['id']; ?>">
                            <h5 class="card-title mb-2"><?php echo htmlspecialchars($project['project_name']); ?></h5>
                            <p class="card-text text-muted mb-2" style="min-height: 48px;">
                                <?php echo htmlspecialchars(mb_strimwidth($project['description'], 0, 80, '...')); ?>
                            </p>
                            <div class="mb-2">
                                <span class="badge bg-info text-dark me-2"><?php echo htmlspecialchars($project['classification']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($project['project_type'] ?? ''); ?></span>
                            </div>
                            <div class="project-date">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : ''); ?>
                            </div>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                <button type="submit" name="toggle_bookmark" class="bookmark-inline<?php echo $project['is_bookmarked'] ? ' bookmarked' : ''; ?>">
                                    <i class="fas fa-bookmark<?php echo $project['is_bookmarked'] ? '' : '-o'; ?>"></i>
                                    <?php echo $project['is_bookmarked'] ? ' Bookmarked' : ' Add Bookmark'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal for project details -->
                <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="projectModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content project-modal-glass">
                      <div class="modal-header project-modal-header">
                        <h5 class="modal-title fw-bold" id="projectModalLabel<?php echo $project['id']; ?>">
                          <i class="fas fa-project-diagram me-2"></i><?php echo htmlspecialchars($project['project_name']); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row g-3 mb-3">
                          <div class="col-md-6">
                            <div class="mb-2"><span class="fw-bold text-secondary">Classification:</span> <?php echo htmlspecialchars($project['classification']); ?></div>
                            <div class="mb-2"><span class="fw-bold text-secondary">Type:</span> <?php echo htmlspecialchars($project['project_type'] ?? ''); ?></div>
                            <div class="mb-2"><span class="fw-bold text-secondary">Submitted:</span> <?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : ''); ?></div>
                          </div>
                          <div class="col-md-6">
                            <div class="mb-2"><span class="fw-bold text-secondary">ID:</span> <?php echo $project['id']; ?></div>
                            <?php if (!empty($project['language'])): ?>
                              <div class="mb-2"><span class="fw-bold text-secondary">Language:</span> <?php echo htmlspecialchars($project['language']); ?></div>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="mb-3">
                          <h6 class="fw-bold mb-2">Description</h6>
                          <div class="p-3 project-modal-desc">
                            <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                          </div>
                        </div>
                        <?php if (!empty($project['project_file_path'])): ?>
                          <div class="mb-3">
                            <a href="<?php echo htmlspecialchars($project['project_file_path']); ?>" class="btn btn-outline-primary" target="_blank">
                              <i class="fas fa-download me-1"></i> Download Project File
                            </a>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      </div>
                    </div>
                  </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">No approved projects found.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle (for modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
.project-card {
    border-radius: 1.5rem;
    background: rgba(255,255,255,0.85);
    box-shadow: 0 8px 32px 0 rgba(58,134,255,0.10);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.18);
    transition: box-shadow 0.2s, transform 0.2s;
    position: relative;
    overflow: hidden;
    cursor: pointer;
}
.project-card:hover {
    box-shadow: 0 16px 48px 0 rgba(58,134,255,0.18);
    transform: translateY(-4px) scale(1.01);
}
.project-card .bookmark-float {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 2;
}
.project-card .card-body {
    padding-top: 2.5rem;
    padding-bottom: 1.5rem;
}
.project-card .card-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #3a86ff;
}
.project-card .badge {
    font-size: 0.95rem;
    border-radius: 0.7rem;
    margin-bottom: 0.2rem;
}
.project-card .project-date {
    font-size: 0.95rem;
    color: #6c757d;
    margin-bottom: 0.7rem;
}
.project-card .bookmark-inline {
    display: inline-block;
    margin-top: 0.5rem;
    font-size: 1.1rem;
    color: #8338ec;
    background: none;
    border: none;
    cursor: pointer;
    transition: color 0.2s;
}
.project-card .bookmark-inline.bookmarked {
    color: #f72585;
}
.project-card .bookmark-inline:hover {
    color: #3a86ff;
}
.project-modal-glass {
    background: rgba(255,255,255,0.92);
    border-radius: 1.5rem;
    box-shadow: 0 8px 32px 0 rgba(58,134,255,0.10);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,0.18);
    overflow: hidden;
}
.project-modal-header {
    background: linear-gradient(90deg, #3a86ff 0%, #8338ec 100%);
    color: #fff;
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
    box-shadow: 0 2px 8px rgba(58,134,255,0.10);
}
.project-modal-header .modal-title {
    color: #fff;
}
.project-modal-desc {
    background: rgba(130, 130, 255, 0.07);
    border-radius: 0.7rem;
    font-size: 1.08rem;
    color: #333;
    box-shadow: 0 2px 8px rgba(58,134,255,0.05);
}
</style>

<script>
  // Wait for the DOM to be ready
  document.addEventListener("DOMContentLoaded", function() {
    // Select all Bootstrap alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
      setTimeout(function() {
        // Fade out
        alert.style.transition = "opacity 0.5s";
        alert.style.opacity = 0;
        // Remove from DOM after fade
        setTimeout(function() {
          alert.remove();
        }, 500);
      }, 2000); // 2 seconds
    });
  });
</script>

<?php include $basePath . 'layout_footer.php'; ?> 