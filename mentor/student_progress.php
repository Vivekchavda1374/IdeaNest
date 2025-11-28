<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];
$pair_id = $_GET['pair_id'] ?? null;

if (!$pair_id) {
    header('Location: students.php');
    exit;
}

// Get student and pairing details
$pair_query = "SELECT msp.*, r.name as student_name, r.email as student_email, 
               r.enrollment_number, r.department, p.project_name
               FROM mentor_student_pairs msp
               JOIN register r ON msp.student_id = r.id
               LEFT JOIN projects p ON msp.project_id = p.id
               WHERE msp.id = ? AND msp.mentor_id = ?";
$stmt = $conn->prepare($pair_query);
$stmt->bind_param("ii", $pair_id, $mentor_id);
$stmt->execute();
$pair = $stmt->get_result()->fetch_assoc();

if (!$pair) {
    header('Location: students.php');
    exit;
}

// Get sessions for this pair
$sessions_query = "SELECT * FROM mentoring_sessions 
                   WHERE pair_id = ? 
                   ORDER BY session_date DESC";
$stmt = $conn->prepare($sessions_query);
$stmt->bind_param("i", $pair_id);
$stmt->execute();
$sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get student's projects
$projects_query = "SELECT * FROM projects 
                   WHERE user_id = ? 
                   ORDER BY submission_date DESC";
$stmt = $conn->prepare($projects_query);
$stmt->bind_param("i", $pair['student_id']);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate progress metrics
$total_sessions = count($sessions);
$completed_sessions = count(array_filter($sessions, fn($s) => $s['status'] === 'completed'));
$total_projects = count($projects);
$approved_projects = count(array_filter($projects, fn($p) => $p['status'] === 'approved'));

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-chart-line text-primary me-2"></i>Student Progress</h2>
                    <p class="text-muted mb-0"><?= htmlspecialchars($pair['student_name']) ?> - Progress Tracking</p>
                </div>
                <a href="students.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Students
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Student Info -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="glass-card p-4">
            <h5><i class="fas fa-user text-primary me-2"></i>Student Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?= htmlspecialchars($pair['student_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($pair['student_email']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Enrollment:</strong> <?= htmlspecialchars($pair['enrollment_number']) ?></p>
                    <p><strong>Department:</strong> <?= htmlspecialchars($pair['department']) ?></p>
                </div>
            </div>
            <p><strong>Paired Since:</strong> <?= date('M j, Y', strtotime($pair['paired_at'])) ?></p>
            <?php if ($pair['project_name']) : ?>
                <p><strong>Current Project:</strong> <?= htmlspecialchars($pair['project_name']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-4 text-center">
            <h5>Pairing Status</h5>
            <span class="badge bg-<?= $pair['status'] === 'active' ? 'success' : 'secondary' ?> fs-6">
                <?= ucfirst($pair['status']) ?>
            </span>
        </div>
    </div>
</div>

<!-- Progress Metrics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="glass-card p-4 text-center">
            <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
            <h4><?= $completed_sessions ?>/<?= $total_sessions ?></h4>
            <p class="text-muted mb-0">Sessions Completed</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-card p-4 text-center">
            <i class="fas fa-project-diagram fa-2x text-success mb-2"></i>
            <h4><?= $approved_projects ?>/<?= $total_projects ?></h4>
            <p class="text-muted mb-0">Projects Approved</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-card p-4 text-center">
            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
            <h4><?= array_sum(array_column($sessions, 'duration_minutes')) ?></h4>
            <p class="text-muted mb-0">Total Minutes</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-card p-4 text-center">
            <i class="fas fa-percentage fa-2x text-info mb-2"></i>
            <h4><?= $total_sessions > 0 ? round(($completed_sessions / $total_sessions) * 100) : 0 ?>%</h4>
            <p class="text-muted mb-0">Completion Rate</p>
        </div>
    </div>
</div>

<!-- Sessions History -->
<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Sessions History (<?= count($sessions) ?>)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($sessions)) : ?>
                    <div class="text-center py-3">
                        <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No sessions recorded yet</p>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Meeting</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $session) : ?>
                                    <tr>
                                        <td><?= date('M j, Y g:i A', strtotime($session['session_date'])) ?></td>
                                        <td><?= $session['duration_minutes'] ?> min</td>
                                        <td>
                                            <span class="badge bg-<?= $session['status'] === 'completed' ? 'success' : ($session['status'] === 'scheduled' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($session['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars(substr($session['notes'] ?? '', 0, 50)) ?><?= strlen($session['notes'] ?? '') > 50 ? '...' : '' ?></td>
                                        <td>
                                            <?php if ($session['meeting_link']) : ?>
                                                <a href="<?= htmlspecialchars($session['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-video"></i>
                                                </a>
                                            <?php else : ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Projects Progress -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Projects Progress (<?= count($projects) ?>)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($projects)) : ?>
                    <div class="text-center py-3">
                        <i class="fas fa-folder-open fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No projects submitted yet</p>
                    </div>
                <?php else : ?>
                    <div class="row">
                        <?php foreach ($projects as $project) : ?>
                            <div class="col-md-6 mb-3">
                                <div class="glass-card p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-1"><?= htmlspecialchars($project['project_name']) ?></h6>
                                        <span class="badge bg-<?= $project['status'] === 'approved' ? 'success' : ($project['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($project['status']) ?>
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars(substr($project['description'], 0, 100)) ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('M j, Y', strtotime($project['submission_date'])) ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-code me-1"></i>
                                            <?= htmlspecialchars($project['language']) ?>
                                        </small>
                                    </div>
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
renderLayout('Student Progress - ' . $pair['student_name'], $content);
?>