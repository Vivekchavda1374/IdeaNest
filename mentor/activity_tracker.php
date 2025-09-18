<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Log mentor activity
function logMentorActivity($conn, $mentor_id, $activity_type, $description, $student_id = null) {
    $query = "INSERT INTO mentor_activity_logs (mentor_id, activity_type, description, student_id, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issi", $mentor_id, $activity_type, $description, $student_id);
    return $stmt->execute();
}

// Handle activity logging for different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'schedule_session':
            $student_id = $_POST['student_id'];
            $session_date = $_POST['session_date'];
            logMentorActivity($conn, $mentor_id, 'session_scheduled', "Scheduled session for " . date('M j, Y g:i A', strtotime($session_date)), $student_id);
            break;
            
        case 'send_email':
            $student_id = $_POST['student_id'];
            $subject = $_POST['subject'];
            logMentorActivity($conn, $mentor_id, 'email_sent', "Sent email: " . $subject, $student_id);
            break;
            
        case 'accept_request':
            $student_id = $_POST['student_id'];
            logMentorActivity($conn, $mentor_id, 'request_accepted', "Accepted mentorship request", $student_id);
            break;
    }
    
    echo json_encode(['success' => true]);
    exit;
}

// Get mentor's activity history
$activities_query = "
    SELECT 
        mal.activity_type,
        mal.description,
        mal.created_at,
        r.name as student_name,
        mal.student_id
    FROM mentor_activity_logs mal
    LEFT JOIN register r ON mal.student_id = r.id
    WHERE mal.mentor_id = ?
    ORDER BY mal.created_at DESC
    LIMIT 50
";

$stmt = $conn->prepare($activities_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="text-primary mb-0">
            <i class="fas fa-chart-line me-2"></i>Activity Tracker
        </h2>
        <p class="text-muted">Monitor your mentoring activities and interactions</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="mb-0">
                    <i class="fas fa-history text-primary me-2"></i>
                    Recent Activities
                </h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($activities)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No Activities Yet</h6>
                        <p class="text-muted">Your mentoring activities will appear here</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item d-flex mb-4">
                                <div class="activity-icon me-3">
                                    <div class="bg-<?= $activity['activity_type'] === 'session_scheduled' ? 'success' : ($activity['activity_type'] === 'email_sent' ? 'warning' : 'primary') ?> rounded-circle p-2">
                                        <i class="fas fa-<?= $activity['activity_type'] === 'session_scheduled' ? 'calendar-check' : ($activity['activity_type'] === 'email_sent' ? 'envelope' : 'handshake') ?> text-white"></i>
                                    </div>
                                </div>
                                <div class="activity-content flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($activity['description']) ?></h6>
                                            <?php if ($activity['student_name']): ?>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-user me-1"></i>
                                                    Student: <?= htmlspecialchars($activity['student_name']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?= $activity['activity_type'] === 'session_scheduled' ? 'success' : ($activity['activity_type'] === 'email_sent' ? 'warning' : 'primary') ?>">
                                            <?= ucwords(str_replace('_', ' ', $activity['activity_type'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="glass-card mb-4">
            <div class="card-header bg-transparent border-0 p-3 pb-0">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar text-info me-2"></i>
                    Activity Summary
                </h6>
            </div>
            <div class="card-body p-3">
                <?php
                // Calculate activity stats
                $session_count = count(array_filter($activities, fn($a) => $a['activity_type'] === 'session_scheduled'));
                $email_count = count(array_filter($activities, fn($a) => $a['activity_type'] === 'email_sent'));
                $request_count = count(array_filter($activities, fn($a) => $a['activity_type'] === 'request_accepted'));
                ?>
                
                <div class="row text-center">
                    <div class="col-4">
                        <h4 class="text-success mb-0"><?= $session_count ?></h4>
                        <small class="text-muted">Sessions</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-warning mb-0"><?= $email_count ?></h4>
                        <small class="text-muted">Emails</small>
                    </div>
                    <div class="col-4">
                        <h4 class="text-primary mb-0"><?= $request_count ?></h4>
                        <small class="text-muted">Requests</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-3 pb-0">
                <h6 class="mb-0">
                    <i class="fas fa-bolt text-warning me-2"></i>
                    Quick Actions
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success btn-sm" onclick="quickSchedule()">
                        <i class="fas fa-calendar-plus me-1"></i>Quick Schedule
                    </button>
                    <button class="btn btn-outline-warning btn-sm" onclick="quickEmail()">
                        <i class="fas fa-envelope me-1"></i>Send Email
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="viewStudents()">
                        <i class="fas fa-users me-1"></i>View Students
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickSchedule() {
    window.location.href = 'schedule_session.php';
}

function quickEmail() {
    window.location.href = 'send_email.php';
}

function viewStudents() {
    window.location.href = 'students.php';
}

// Auto-refresh activities every 30 seconds
setInterval(() => {
    fetch('activity_tracker.php')
        .then(response => response.text())
        .then(data => {
            // Update activity count in navigation if needed
            console.log('Activities refreshed');
        })
        .catch(error => console.log('Refresh failed'));
}, 30000);
</script>

<style>
.timeline {
    position: relative;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #667eea, #764ba2);
}

.activity-item {
    position: relative;
}

.activity-icon {
    position: relative;
    z-index: 2;
}

.activity-content {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 10px;
    padding: 15px;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.activity-content:hover {
    background: rgba(255, 255, 255, 0.95);
    transform: translateX(5px);
    transition: all 0.3s ease;
}
</style>

<?php
$content = ob_get_clean();
renderLayout('Activity Tracker', $content);
?>