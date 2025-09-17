<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Get active students from accepted requests
$students_query = "SELECT mr.id, mr.student_id, mr.project_id, mr.created_at as paired_at,
                   r.name, r.email, r.department, r.enrollment_number,
                   p.project_name, p.classification, p.description,
                   NULL as rating, NULL as feedback
                   FROM mentor_requests mr
                   JOIN register r ON mr.student_id = r.id 
                   LEFT JOIN projects p ON mr.project_id = p.id 
                   WHERE mr.mentor_id = ? AND mr.status = 'accepted'
                   ORDER BY mr.created_at DESC";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get completed students
$completed_query = "SELECT msp.*, r.name, r.email, r.department,
                    p.project_name, msp.completed_at, msp.rating, msp.feedback
                    FROM mentor_student_pairs msp 
                    JOIN register r ON msp.student_id = r.id 
                    LEFT JOIN projects p ON msp.project_id = p.id 
                    WHERE msp.mentor_id = ? AND msp.status = 'completed'
                    ORDER BY msp.completed_at DESC";
$stmt = $conn->prepare($completed_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$completed = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <h2><i class="fas fa-users text-primary me-2"></i>My Students</h2>
            <p class="text-muted">Manage your active and completed mentorships</p>
        </div>
    </div>
</div>

<!-- Active Students -->
<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Active Students (<?= count($students) ?>)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($students)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No active students</h6>
                        <p class="text-muted">Accept pairing suggestions from the dashboard</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($students as $student): ?>
                            <div class="col-md-6 mb-3">
                                <div class="glass-card p-3">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-primary rounded-circle p-2 me-3">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($student['name']) ?></h6>
                                            <small class="text-muted d-block"><?= htmlspecialchars($student['department']) ?></small>
                                            <small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                            
                                            <?php if ($student['project_name']): ?>
                                                <div class="mt-2">
                                                    <span class="badge bg-info"><?= htmlspecialchars($student['project_name']) ?></span>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($student['classification']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <small class="text-muted d-block mt-2">
                                                Paired: <?= date('M j, Y', strtotime($student['paired_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-success" onclick="completePairing(<?= $student['id'] ?>)">
                                            <i class="fas fa-check me-1"></i>Complete
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewProgress(<?= $student['id'] ?>)">
                                            <i class="fas fa-chart-line me-1"></i>Progress
                                        </button>
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

<!-- Completed Students -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Completed Mentorships (<?= count($completed) ?>)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($completed)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-trophy fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No completed mentorships yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Project</th>
                                    <th>Completed</th>
                                    <th>Rating</th>
                                    <th>Feedback</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed as $student): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($student['name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($student['project_name'] ?? 'N/A') ?></td>
                                        <td><?= date('M j, Y', strtotime($student['completed_at'])) ?></td>
                                        <td>
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= ($student['rating'] ?? 0) ? 'text-warning' : 'text-muted' ?>"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td><?= htmlspecialchars(substr($student['feedback'] ?? '', 0, 50)) ?>...</td>
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

<script>
function completePairing(pairId) {
    if (confirm('Complete this mentorship?')) {
        window.location.href = `complete_pairing.php?pair_id=${pairId}`;
    }
}

function viewProgress(pairId) {
    window.location.href = `student_progress.php?pair_id=${pairId}`;
}
</script>

<?php
$content = ob_get_clean();
renderLayout('My Students', $content);
?>