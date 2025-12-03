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

// Get all mentor activities
$activities = [];

try {
    // Get scheduled sessions
    $sessions_query = "SELECT 
        'session' as type,
        ms.session_date as date,
        ms.status,
        r.name as student_name,
        ms.notes,
        ms.duration_minutes,
        ms.meeting_link,
        'Session scheduled' as title
        FROM mentoring_sessions ms
        JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
        JOIN register r ON msp.student_id = r.id
        WHERE msp.mentor_id = ?
        ORDER BY ms.session_date DESC
        LIMIT 20";

    $stmt = $conn->prepare($sessions_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $activities = array_merge($activities, $sessions);

    // Get mentor requests
    $requests_query = "SELECT 
        'request' as type,
        mr.created_at as date,
        mr.status,
        r.name as student_name,
        mr.message as notes,
        NULL as duration_minutes,
        NULL as meeting_link,
        'Student request' as title
        FROM mentor_requests mr
        JOIN register r ON mr.student_id = r.id
        WHERE mr.mentor_id = ?
        ORDER BY mr.created_at DESC
        LIMIT 20";

    $stmt = $conn->prepare($requests_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $activities = array_merge($activities, $requests);

    // Sort by date
    usort($activities, function ($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
} catch (Exception $e) {
    error_log("Activity error: " . $e->getMessage());
}

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-history me-2"></i>My Activity Dashboard</h2>
        <p class="text-muted">Track all your mentoring activities, sessions, and updates</p>
    </div>
</div>

<!-- Activity Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
            <h4><?= count(array_filter($activities, fn($a) => $a['type'] === 'session')) ?></h4>
            <small>Total Sessions</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
            <h4><?= count(array_filter($activities, fn($a) => $a['status'] === 'scheduled')) ?></h4>
            <small>Upcoming</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
            <h4><?= count(array_filter($activities, fn($a) => $a['status'] === 'completed')) ?></h4>
            <small>Completed</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-users fa-2x text-info mb-2"></i>
            <h4><?= count(array_filter($activities, fn($a) => $a['type'] === 'request' && $a['status'] === 'accepted')) ?></h4>
            <small>Active Students</small>
        </div>
    </div>
</div>

<!-- Activity Timeline -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5><i class="fas fa-timeline me-2"></i>Activity Timeline</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($activities)) : ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Activities Yet</h5>
                        <p class="text-muted">Your mentoring activities will appear here</p>
                    </div>
                <?php else : ?>
                    <div class="timeline">
                        <?php foreach ($activities as $activity) : ?>
                            <div class="timeline-item mb-4">
                                <div class="row">
                                    <div class="col-auto">
                                        <div class="timeline-icon bg-<?= $activity['type'] === 'session' ? 'primary' : 'info' ?> rounded-circle p-3">
                                            <i class="fas fa-<?= $activity['type'] === 'session' ? 'video' : 'handshake' ?> text-white"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="glass-card p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($activity['title']) ?></h6>
                                                    <p class="text-muted mb-0">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?= htmlspecialchars($activity['student_name']) ?>
                                                    </p>
                                                </div>
                                                <span class="badge bg-<?=
                                                    $activity['status'] === 'completed' ? 'success' :
                                                    ($activity['status'] === 'scheduled' ? 'warning' :
                                                    ($activity['status'] === 'accepted' ? 'info' : 'secondary'))
                                                                        ?>">
                                                    <?= ucfirst($activity['status']) ?>
                                                </span>
                                            </div>
                                            
                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('M j, Y g:i A', strtotime($activity['date'])) ?>
                                                    </small>
                                                </div>
                                                <?php if ($activity['duration_minutes']) : ?>
                                                <div class="col-md-6">
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?= $activity['duration_minutes'] ?> minutes
                                                    </small>
                                                </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($activity['notes']) : ?>
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <strong>Notes:</strong> <?= htmlspecialchars($activity['notes']) ?>
                                                </small>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($activity['meeting_link']) : ?>
                                            <div class="mb-2">
                                                <a href="<?= htmlspecialchars($activity['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-1"></i>Join Meeting
                                                </a>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($activity['type'] === 'session' && $activity['status'] === 'scheduled') : ?>
                                            <div class="mt-2">
                                                <button class="btn btn-sm btn-success me-2" onclick="markCompleted(<?= $activity['id'] ?? 0 ?>)">
                                                    <i class="fas fa-check me-1"></i>Mark Complete
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary" onclick="reschedule(<?= $activity['id'] ?? 0 ?>)">
                                                    <i class="fas fa-calendar-alt me-1"></i>Reschedule
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
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

<script>
function markCompleted(sessionId) {
    if (confirm('Mark this session as completed?')) {
        fetch('update_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: sessionId, status: 'completed'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating session');
            }
        });
    }
}

function reschedule(sessionId) {
    window.location.href = `schedule_session.php?edit=${sessionId}`;
}
</script>

<style>
.timeline-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 25px;
    top: 50px;
    width: 2px;
    height: calc(100% - 50px);
    background: linear-gradient(to bottom, #dee2e6, transparent);
}
</style>

<?php
$content = ob_get_clean();
renderLayout('Activity Dashboard', $content);
?>