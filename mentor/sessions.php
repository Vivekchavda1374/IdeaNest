<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Get upcoming sessions using mentor requests
$upcoming_query = "SELECT ms.*, r.name as student_name, p.project_name,
                   TIMESTAMPDIFF(HOUR, NOW(), ms.session_date) as hours_until
                   FROM mentoring_sessions ms
                   JOIN mentor_requests mr ON ms.pair_id = mr.id
                   JOIN register r ON mr.student_id = r.id
                   LEFT JOIN projects p ON mr.project_id = p.id
                   WHERE mr.mentor_id = ? AND mr.status = 'accepted' AND ms.status = 'scheduled' AND ms.session_date >= NOW()
                   ORDER BY ms.session_date ASC";
$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$upcoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get completed sessions using mentor requests
$completed_query = "SELECT ms.*, r.name as student_name, p.project_name
                    FROM mentoring_sessions ms
                    JOIN mentor_requests mr ON ms.pair_id = mr.id
                    JOIN register r ON mr.student_id = r.id
                    LEFT JOIN projects p ON mr.project_id = p.id
                    WHERE mr.mentor_id = ? AND mr.status = 'accepted' AND ms.status = 'completed'
                    ORDER BY ms.session_date DESC LIMIT 10";
$stmt = $conn->prepare($completed_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$completed = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-calendar-alt text-primary me-2"></i>Sessions</h2>
                    <p class="text-muted mb-0">Manage your mentoring sessions</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="create_session.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>New Session
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="export_sessions.php?format=csv"><i class="fas fa-file-csv me-1"></i>CSV</a></li>
                            <li><a class="dropdown-item" href="export_sessions.php?format=json"><i class="fas fa-file-code me-1"></i>JSON</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Sessions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Upcoming Sessions (<?= count($upcoming) ?>)</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($upcoming)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No upcoming sessions</h6>
                        <p class="text-muted">Schedule a session with your students</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($upcoming as $session): ?>
                            <div class="col-md-6 mb-3">
                                <div class="glass-card p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($session['student_name']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($session['project_name'] ?? 'General Session') ?></small>
                                        </div>
                                        <span class="badge bg-warning"><?= $session['hours_until'] ?>h</span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('M j, Y g:i A', strtotime($session['session_date'])) ?>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <i class="fas fa-hourglass-half me-1"></i>
                                        <?= $session['duration_minutes'] ?> minutes
                                    </div>
                                    
                                    <?php if ($session['notes']): ?>
                                        <div class="mb-2">
                                            <small class="text-muted"><?= htmlspecialchars($session['notes']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($session['meeting_link']): ?>
                                        <div class="mb-2">
                                            <a href="<?= htmlspecialchars($session['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-video me-1"></i>Join Meeting
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-success" onclick="markCompleted(<?= $session['id'] ?>)">
                                            <i class="fas fa-check me-1"></i>Complete
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelSession(<?= $session['id'] ?>)">
                                            <i class="fas fa-times me-1"></i>Cancel
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

<!-- Recent Sessions -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Recent Sessions</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($completed)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-history fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No completed sessions yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Project</th>
                                    <th>Date</th>
                                    <th>Duration</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed as $session): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($session['student_name']) ?></td>
                                        <td><?= htmlspecialchars($session['project_name'] ?? 'General') ?></td>
                                        <td><?= date('M j, Y', strtotime($session['session_date'])) ?></td>
                                        <td><?= $session['duration_minutes'] ?> min</td>
                                        <td><?= htmlspecialchars(substr($session['notes'] ?? '', 0, 50)) ?>...</td>
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

<!-- New Session Modal -->
<div class="modal fade" id="newSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-card">
            <div class="modal-header border-0">
                <h5 class="modal-title">Schedule New Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="schedule_session.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select class="form-select" name="pair_id" required>
                            <option value="">Select Student</option>
                            <?php
                            $students_query = "SELECT mr.id, r.name FROM mentor_requests mr
                                             JOIN register r ON mr.student_id = r.id 
                                             WHERE mr.mentor_id = ? AND mr.status = 'accepted'";
                            $stmt = $conn->prepare($students_query);
                            $stmt->bind_param("i", $mentor_id);
                            $stmt->execute();
                            $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date & Time</label>
                        <input type="datetime-local" class="form-control" name="session_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <select class="form-select" name="duration">
                            <option value="30">30 minutes</option>
                            <option value="60" selected>1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meeting Link</label>
                        <input type="url" class="form-control" name="meeting_link" placeholder="https://meet.google.com/...">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function markCompleted(sessionId) {
    if (confirm('Mark this session as completed?')) {
        fetch('update_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({session_id: sessionId, status: 'completed'})
        }).then(() => location.reload());
    }
}

function cancelSession(sessionId) {
    if (confirm('Cancel this session?')) {
        fetch('update_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({session_id: sessionId, status: 'cancelled'})
        }).then(() => location.reload());
    }
}
</script>

<?php
$content = ob_get_clean();
renderLayout('Sessions', $content);
?>