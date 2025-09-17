<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Get projects from mentored students
$projects_query = "SELECT p.*, r.name as student_name, r.email, msp.paired_at
                   FROM projects p
                   JOIN register r ON p.user_id = r.id
                   JOIN mentor_student_pairs msp ON msp.student_id = r.id
                   WHERE msp.mentor_id = ? AND p.status = 'approved'
                   ORDER BY p.submission_date DESC";
$stmt = $conn->prepare($projects_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get project statistics
$stats_query = "SELECT 
                COUNT(*) as total_projects,
                SUM(CASE WHEN p.project_type = 'software' THEN 1 ELSE 0 END) as software_count,
                SUM(CASE WHEN p.project_type = 'hardware' THEN 1 ELSE 0 END) as hardware_count
                FROM projects p
                JOIN mentor_student_pairs msp ON msp.student_id = p.user_id
                WHERE msp.mentor_id = ? AND p.status = 'approved'";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <h2><i class="fas fa-project-diagram text-primary me-2"></i>Projects</h2>
            <p class="text-muted">Projects from your mentored students</p>
        </div>
    </div>
</div>

<!-- Project Statistics -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-folder fa-2x text-primary mb-2"></i>
            <h4><?= $stats['total_projects'] ?? 0 ?></h4>
            <p class="mb-0">Total Projects</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-code fa-2x text-info mb-2"></i>
            <h4><?= $stats['software_count'] ?? 0 ?></h4>
            <p class="mb-0">Software Projects</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-microchip fa-2x text-warning mb-2"></i>
            <h4><?= $stats['hardware_count'] ?? 0 ?></h4>
            <p class="mb-0">Hardware Projects</p>
        </div>
    </div>
</div>

<!-- Projects List -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Student Projects (<?= count($projects) ?>)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($projects)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No projects yet</h6>
                        <p class="text-muted">Projects from your mentored students will appear here</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($projects as $project): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="glass-card p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($project['project_name']) ?></h6>
                                            <small class="text-muted">by <?= htmlspecialchars($project['student_name']) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?= $project['project_type'] == 'software' ? 'info' : 'warning' ?>">
                                                <?= ucfirst($project['project_type']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="badge bg-secondary me-1"><?= htmlspecialchars($project['classification']) ?></span>
                                        <?php if ($project['difficulty_level']): ?>
                                            <span class="badge bg-light text-dark"><?= ucfirst($project['difficulty_level']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="text-muted small mb-3">
                                        <?= htmlspecialchars(substr($project['description'], 0, 150)) ?>...
                                    </p>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-code me-1"></i><?= htmlspecialchars($project['language']) ?>
                                        </small>
                                        <?php if ($project['team_size']): ?>
                                            <small class="text-muted ms-3">
                                                <i class="fas fa-users me-1"></i><?= htmlspecialchars($project['team_size']) ?> members
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Submitted: <?= date('M j, Y', strtotime($project['submission_date'])) ?>
                                        </small>
                                        <div>
                                            <?php if ($project['github_repo']): ?>
                                                <a href="<?= htmlspecialchars($project['github_repo']) ?>" target="_blank" class="btn btn-sm btn-outline-dark">
                                                    <i class="fab fa-github me-1"></i>Code
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($project['live_demo_url']): ?>
                                                <a href="<?= htmlspecialchars($project['live_demo_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-1"></i>Demo
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($project['image_path']): ?>
                                        <div class="mt-3">
                                            <img src="../user/<?= htmlspecialchars($project['image_path']) ?>" 
                                                 class="img-fluid rounded" style="max-height: 200px; width: 100%; object-fit: cover;">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderLayout('Projects', $content);
?>