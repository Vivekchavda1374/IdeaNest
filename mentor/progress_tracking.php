<?php
require_once __DIR__ . '/includes/security_init.php';
/**
 * Student Progress Tracking System
 * Complete implementation with milestones and metrics
 */

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

// Get student and project details
$query = "SELECT msp.*, r.name as student_name, r.email as student_email,
          p.project_name, p.classification
          FROM mentor_student_pairs msp
          JOIN register r ON msp.student_id = r.id
          LEFT JOIN projects p ON msp.project_id = p.id
          WHERE msp.id = ? AND msp.mentor_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $pair_id, $mentor_id);
$stmt->execute();
$pair = $stmt->get_result()->fetch_assoc();

if (!$pair) {
    die('Invalid pair ID or access denied');
}

// Handle milestone creation
if (isset($_POST['add_milestone'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $target_date = $_POST['target_date'];
    $priority = $_POST['priority'];
    
    $insertQuery = "INSERT INTO progress_milestones (pair_id, title, description, target_date, priority, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("issss", $pair_id, $title, $description, $target_date, $priority);
    $stmt->execute();
    
    header('Location: progress_tracking.php?pair_id=' . $pair_id . '&success=milestone_added');
    exit;
}

// Handle milestone update
if (isset($_POST['update_milestone'])) {
    $milestone_id = intval($_POST['milestone_id']);
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    
    $updateQuery = "UPDATE progress_milestones 
                    SET status = ?, completion_notes = ?, 
                        completed_date = IF(? = 'completed', NOW(), NULL)
                    WHERE id = ? AND pair_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sssii", $status, $notes, $status, $milestone_id, $pair_id);
    $stmt->execute();
    
    header('Location: progress_tracking.php?pair_id=' . $pair_id . '&success=milestone_updated');
    exit;
}

// Handle progress note
if (isset($_POST['add_note'])) {
    $note = trim($_POST['note']);
    $category = $_POST['category'];
    
    $insertQuery = "INSERT INTO progress_notes (pair_id, mentor_id, note, category)
                    VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iiss", $pair_id, $mentor_id, $note, $category);
    $stmt->execute();
    
    header('Location: progress_tracking.php?pair_id=' . $pair_id . '&success=note_added');
    exit;
}

// Get milestones
$milestonesQuery = "SELECT * FROM progress_milestones WHERE pair_id = ? ORDER BY target_date ASC";
$stmt = $conn->prepare($milestonesQuery);
$stmt->bind_param("i", $pair_id);
$stmt->execute();
$milestones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get progress notes
$notesQuery = "SELECT * FROM progress_notes WHERE pair_id = ? ORDER BY created_at DESC LIMIT 20";
$stmt = $conn->prepare($notesQuery);
$stmt->bind_param("i", $pair_id);
$stmt->execute();
$notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate progress statistics
$totalMilestones = count($milestones);
$completedMilestones = count(array_filter($milestones, fn($m) => $m['status'] === 'completed'));
$progressPercentage = $totalMilestones > 0 ? ($completedMilestones / $totalMilestones) * 100 : 0;

// Get session count
$sessionQuery = "SELECT COUNT(*) as count FROM mentoring_sessions WHERE pair_id = ? AND status = 'completed'";
$stmt = $conn->prepare($sessionQuery);
$stmt->bind_param("i", $pair_id);
$stmt->execute();
$sessionCount = $stmt->get_result()->fetch_assoc()['count'];

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-chart-line text-primary me-2"></i>Progress Tracking</h2>
                    <p class="text-muted mb-0">
                        Student: <strong><?= htmlspecialchars($pair['student_name']) ?></strong>
                        <?php if ($pair['project_name']): ?>
                            | Project: <strong><?= htmlspecialchars($pair['project_name']) ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
                <a href="students.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Students
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Progress Overview -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <h3><?= $totalMilestones ?></h3>
            <p>Total Milestones</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <h3><?= $completedMilestones ?></h3>
            <p>Completed</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <h3><?= $sessionCount ?></h3>
            <p>Sessions Held</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <h3><?= number_format($progressPercentage, 1) ?>%</h3>
            <p>Overall Progress</p>
        </div>
    </div>
</div>

<!-- Progress Bar -->
<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <h5 class="mb-3">Overall Progress</h5>
            <div class="progress" style="height: 30px;">
                <div class="progress-bar bg-success" style="width: <?= $progressPercentage ?>%">
                    <?= number_format($progressPercentage, 1) ?>%
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Milestones -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Milestones</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMilestoneModal">
                        <i class="fas fa-plus me-1"></i>Add Milestone
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if (empty($milestones)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-flag fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No milestones yet. Add your first milestone to track progress.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($milestones as $milestone): ?>
                        <div class="glass-card p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-2">
                                        <?= htmlspecialchars($milestone['title']) ?>
                                        <span class="badge bg-<?= $milestone['status'] === 'completed' ? 'success' : ($milestone['status'] === 'in_progress' ? 'warning' : 'secondary') ?> ms-2">
                                            <?= ucfirst($milestone['status']) ?>
                                        </span>
                                        <span class="badge bg-<?= $milestone['priority'] === 'high' ? 'danger' : ($milestone['priority'] === 'medium' ? 'warning' : 'info') ?>">
                                            <?= ucfirst($milestone['priority']) ?> Priority
                                        </span>
                                    </h6>
                                    <p class="text-muted mb-2"><?= htmlspecialchars($milestone['description']) ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>Target: <?= date('M d, Y', strtotime($milestone['target_date'])) ?>
                                        <?php if ($milestone['completed_date']): ?>
                                            | <i class="fas fa-check-circle me-1 text-success"></i>Completed: <?= date('M d, Y', strtotime($milestone['completed_date'])) ?>
                                        <?php endif; ?>
                                    </small>
                                    <?php if ($milestone['completion_notes']): ?>
                                        <div class="mt-2 p-2 bg-light rounded">
                                            <small><strong>Notes:</strong> <?= htmlspecialchars($milestone['completion_notes']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="editMilestone(<?= $milestone['id'] ?>, '<?= htmlspecialchars($milestone['status']) ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Progress Notes -->
    <div class="col-lg-4">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Progress Notes</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-4" style="max-height: 600px; overflow-y: auto;">
                <?php if (empty($notes)): ?>
                    <p class="text-muted text-center">No notes yet</p>
                <?php else: ?>
                    <?php foreach ($notes as $note): ?>
                        <div class="glass-card p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-<?= $note['category'] === 'achievement' ? 'success' : ($note['category'] === 'concern' ? 'danger' : 'info') ?>">
                                    <?= ucfirst($note['category']) ?>
                                </span>
                                <small class="text-muted"><?= date('M d, g:i A', strtotime($note['created_at'])) ?></small>
                            </div>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($note['note'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Milestone Modal -->
<div class="modal fade" id="addMilestoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-0">
                <h5 class="modal-title">Add New Milestone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Date *</label>
                        <input type="date" name="target_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority *</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_milestone" class="btn btn-primary">Add Milestone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-0">
                <h5 class="modal-title">Add Progress Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-select" required>
                            <option value="general">General</option>
                            <option value="achievement">Achievement</option>
                            <option value="concern">Concern</option>
                            <option value="feedback">Feedback</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note *</label>
                        <textarea name="note" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_note" class="btn btn-primary">Add Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Milestone Modal -->
<div class="modal fade" id="editMilestoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-0">
                <h5 class="modal-title">Update Milestone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="milestone_id" id="edit_milestone_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Completion Notes</label>
                        <textarea name="notes" class="form-control" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_milestone" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editMilestone(id, status) {
    document.getElementById('edit_milestone_id').value = id;
    document.getElementById('edit_status').value = status;
    new bootstrap.Modal(document.getElementById('editMilestoneModal')).show();
}
</script>

<?php
$content = ob_get_clean();
renderLayout('Progress Tracking', $content);
?>
