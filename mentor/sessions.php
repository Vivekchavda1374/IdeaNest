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

// Get upcoming sessions - use UNION to check both mentor_student_pairs and mentor_requests
$upcoming_query = "SELECT ms.*, r.name as student_name, p.project_name,
                   TIMESTAMPDIFF(HOUR, NOW(), ms.session_date) as hours_until,
                   'pair' as source_type
                   FROM mentoring_sessions ms
                   JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
                   JOIN register r ON msp.student_id = r.id
                   LEFT JOIN projects p ON msp.project_id = p.id
                   WHERE msp.mentor_id = ? AND ms.status = 'scheduled' AND ms.session_date >= NOW()
                   
                   UNION
                   
                   SELECT ms.*, r.name as student_name, p.project_name,
                   TIMESTAMPDIFF(HOUR, NOW(), ms.session_date) as hours_until,
                   'request' as source_type
                   FROM mentoring_sessions ms
                   JOIN mentor_requests mr ON ms.pair_id = mr.id
                   JOIN register r ON mr.student_id = r.id
                   LEFT JOIN projects p ON mr.project_id = p.id
                   WHERE mr.mentor_id = ? AND mr.status = 'accepted' AND ms.status = 'scheduled' AND ms.session_date >= NOW()
                   
                   ORDER BY session_date ASC";

$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("ii", $mentor_id, $mentor_id);
$stmt->execute();
$upcoming = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get completed sessions - use UNION to check both sources
$completed_query = "SELECT ms.*, r.name as student_name, p.project_name,
                    'pair' as source_type
                    FROM mentoring_sessions ms
                    JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
                    JOIN register r ON msp.student_id = r.id
                    LEFT JOIN projects p ON msp.project_id = p.id
                    WHERE msp.mentor_id = ? AND ms.status = 'completed'
                    
                    UNION
                    
                    SELECT ms.*, r.name as student_name, p.project_name,
                    'request' as source_type
                    FROM mentoring_sessions ms
                    JOIN mentor_requests mr ON ms.pair_id = mr.id
                    JOIN register r ON mr.student_id = r.id
                    LEFT JOIN projects p ON mr.project_id = p.id
                    WHERE mr.mentor_id = ? AND mr.status = 'accepted' AND ms.status = 'completed'
                    
                    ORDER BY session_date DESC LIMIT 10";

$stmt = $conn->prepare($completed_query);
$stmt->bind_param("ii", $mentor_id, $mentor_id);
$stmt->execute();
$completed = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all sessions for debugging (can be removed later)
$all_sessions_query = "SELECT ms.*, r.name as student_name
                       FROM mentoring_sessions ms
                       JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
                       JOIN register r ON msp.student_id = r.id
                       WHERE msp.mentor_id = ?
                       ORDER BY ms.created_at DESC";
$stmt = $conn->prepare($all_sessions_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$all_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<!-- Debug Info (Remove after testing) -->
<?php if (!empty($all_sessions)) : ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <strong>Debug:</strong> Found <?= count($all_sessions) ?> total session(s) in database for mentor ID <?= $mentor_id ?>
            <br>Upcoming: <?= count($upcoming) ?>, Completed: <?= count($completed) ?>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- End Debug Info -->


<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-calendar-alt text-primary me-2"></i>Sessions</h2>
                    <p class="text-muted mb-0">Manage your mentoring sessions</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSessionModal">
                        <i class="fas fa-plus me-1"></i>New Session
                    </button>
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
                <?php if (empty($upcoming)) : ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No upcoming sessions</h6>
                        <p class="text-muted">Schedule a session with your students</p>
                    </div>
                <?php else : ?>
                    <div class="row">
                        <?php foreach ($upcoming as $session) : ?>
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
                                    
                                    <?php if ($session['notes']) : ?>
                                        <div class="mb-2">
                                            <small class="text-muted"><?= htmlspecialchars($session['notes']) ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($session['meeting_link']) : ?>
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
                <?php if (empty($completed)) : ?>
                    <div class="text-center py-3">
                        <i class="fas fa-history fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No completed sessions yet</p>
                    </div>
                <?php else : ?>
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
                                <?php foreach ($completed as $session) : ?>
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
            <form id="scheduleSessionForm">
                <div class="modal-body">
                    <div id="sessionAlert" class="alert d-none" role="alert"></div>
                    
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select class="form-select" id="pair_id" name="pair_id" required>
                            <option value="">Select Student</option>
                            <?php
                            // Try mentor_requests first
                            $students_query = "SELECT mr.id, r.name FROM mentor_requests mr
                                             JOIN register r ON mr.student_id = r.id 
                                             WHERE mr.mentor_id = ? AND mr.status = 'accepted'";
                            $stmt = $conn->prepare($students_query);
                            $stmt->bind_param("i", $mentor_id);
                            $stmt->execute();
                            $modal_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                            // If no students from mentor_requests, try mentor_student_pairs
                            if (empty($modal_students)) {
                                $students_query = "SELECT msp.id, r.name FROM mentor_student_pairs msp
                                                 JOIN register r ON msp.student_id = r.id 
                                                 WHERE msp.mentor_id = ? AND msp.status = 'active'";
                                $stmt = $conn->prepare($students_query);
                                $stmt->bind_param("i", $mentor_id);
                                $stmt->execute();
                                $modal_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            }

                            if (empty($modal_students)) : ?>
                                <option value="" disabled>No students assigned yet. Please contact admin to assign students to your mentorship.</option>
                            <?php else :
                                foreach ($modal_students as $student) : ?>
                                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                                <?php endforeach;
                            endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date & Time</label>
                        <input type="datetime-local" class="form-control" id="session_date" name="session_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <select class="form-select" id="duration" name="duration">
                            <option value="30">30 minutes</option>
                            <option value="60" selected>1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add any notes or agenda for this session..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meeting Link</label>
                        <input type="url" class="form-control" id="meeting_link" name="meeting_link" placeholder="https://meet.google.com/...">
                        <small class="text-muted">Optional: Add a video call link for the session</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-calendar-plus me-1"></i>Schedule Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle session scheduling form
document.getElementById('scheduleSessionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const alertDiv = document.getElementById('sessionAlert');
    const originalBtnText = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Scheduling...';
    alertDiv.classList.add('d-none');
    
    // Get form data
    const formData = {
        pair_id: document.getElementById('pair_id').value,
        session_date: document.getElementById('session_date').value.replace('T', ' ') + ':00',
        duration: document.getElementById('duration').value,
        notes: document.getElementById('notes').value,
        meeting_link: document.getElementById('meeting_link').value
    };
    
    try {
        const response = await fetch('schedule_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = `<i class="fas fa-check-circle me-2"></i>${data.message || 'Session scheduled successfully!'}`;
            alertDiv.classList.remove('d-none');
            
            // Reset form
            document.getElementById('scheduleSessionForm').reset();
            
            // Reload page after 1.5 seconds
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            // Show error message
            alertDiv.className = 'alert alert-danger';
            alertDiv.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${data.error || 'Failed to schedule session'}`;
            if (data.debug) {
                alertDiv.innerHTML += `<br><small>${data.debug}</small>`;
            }
            alertDiv.classList.remove('d-none');
            
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    } catch (error) {
        console.error('Error:', error);
        alertDiv.className = 'alert alert-danger';
        alertDiv.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>Network error. Please try again.`;
        alertDiv.classList.remove('d-none');
        
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
});

function markCompleted(sessionId) {
    if (confirm('Mark this session as completed?')) {
        fetch('update_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({session_id: sessionId, status: 'completed'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to update session'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        });
    }
}

function cancelSession(sessionId) {
    if (confirm('Cancel this session?')) {
        fetch('update_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({session_id: sessionId, status: 'cancelled'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to cancel session'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error. Please try again.');
        });
    }
}

// Set minimum datetime to now
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('session_date');
    if (dateInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        dateInput.min = now.toISOString().slice(0, 16);
    }
});
</script>

<?php
$content = ob_get_clean();
renderLayout('Sessions', $content);
?>