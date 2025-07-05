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

// Fetch all approved projects
$sql = "SELECT * FROM admin_approved_projects ORDER BY submission_date DESC";
$result = $conn->query($sql);
$projects = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}
$conn->close();
?>

<div class="container py-5">
    <h2 class="fw-bold mb-4 text-primary"><i class="fas fa-project-diagram me-2"></i>All Approved Projects</h2>
    <div class="row g-4">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $project): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm project-card" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#projectModal<?php echo $project['id']; ?>">
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-primary mb-2"><?php echo htmlspecialchars($project['project_name']); ?></h5>
                            <p class="card-text text-muted mb-2" style="min-height: 48px;">
                                <?php echo htmlspecialchars(mb_strimwidth($project['description'], 0, 80, '...')); ?>
                            </p>
                            <div class="mb-2">
                                <span class="badge bg-info text-dark me-2"><?php echo htmlspecialchars($project['classification']); ?></span>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($project['project_type'] ?? ''); ?></span>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : ''); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal for project details -->
                <div class="modal fade" id="projectModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="projectModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="projectModalLabel<?php echo $project['id']; ?>">
                          <?php echo htmlspecialchars($project['project_name']); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row mb-3">
                          <div class="col-md-6">
                            <p><strong>Classification:</strong> <?php echo htmlspecialchars($project['classification']); ?></p>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($project['project_type'] ?? ''); ?></p>
                            <p><strong>Submitted:</strong> <?php echo isset($project['submission_date']) ? htmlspecialchars($project['submission_date']) : (isset($project['created_at']) ? htmlspecialchars($project['created_at']) : ''); ?></p>
                          </div>
                          <div class="col-md-6">
                            <p><strong>ID:</strong> <?php echo $project['id']; ?></p>
                            <?php if (!empty($project['language'])): ?>
                              <p><strong>Language:</strong> <?php echo htmlspecialchars($project['language']); ?></p>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="mb-3">
                          <h6>Description</h6>
                          <div class="p-3 bg-light rounded">
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
    border-radius: 1.2rem;
    transition: box-shadow 0.2s, transform 0.2s;
    box-shadow: 0 4px 24px rgba(67, 97, 238, 0.07);
    background: #fff;
}
.project-card:hover {
    box-shadow: 0 8px 32px rgba(67, 97, 238, 0.15);
    transform: translateY(-4px) scale(1.01);
}
</style>

<?php include $basePath . 'layout_footer.php'; ?> 