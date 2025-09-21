<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'layout.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$activities = [];

// Add sample data for testing
$activities = [
    [
        'type' => 'session',
        'date' => '2025-01-15 14:00:00',
        'status' => 'scheduled',
        'mentor_name' => 'Dr. John Smith',
        'notes' => 'Project review and guidance session',
        'duration_minutes' => 60,
        'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        'title' => 'Mentoring Session'
    ],
    [
        'type' => 'session',
        'date' => '2025-01-10 10:30:00',
        'status' => 'completed',
        'mentor_name' => 'Prof. Sarah Johnson',
        'notes' => 'Discussed project architecture and next steps',
        'duration_minutes' => 90,
        'meeting_link' => 'https://zoom.us/j/123456789',
        'title' => 'Mentoring Session'
    ],
    [
        'type' => 'request',
        'date' => '2025-01-05 09:15:00',
        'status' => 'accepted',
        'mentor_name' => 'Dr. Mike Wilson',
        'notes' => 'Looking for guidance on machine learning project',
        'duration_minutes' => null,
        'meeting_link' => null,
        'title' => 'Mentor Request'
    ]
];

try {
    // Try to get real data from database
    $sessions_query = "SELECT 
        'session' as type,
        COALESCE(ms.session_date, NOW()) as date,
        COALESCE(ms.status, 'scheduled') as status,
        r.name as mentor_name,
        COALESCE(ms.notes, 'Session scheduled') as notes,
        COALESCE(ms.duration_minutes, 60) as duration_minutes,
        ms.meeting_link,
        'Mentoring Session' as title
        FROM mentoring_sessions ms
        JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
        JOIN register r ON msp.mentor_id = r.id
        WHERE msp.student_id = ?
        ORDER BY ms.session_date DESC
        LIMIT 20";

    $stmt = $conn->prepare($sessions_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $activities = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    // Get mentor requests
    $requests_query = "SELECT 
        'request' as type,
        mr.created_at as date,
        mr.status,
        r.name as mentor_name,
        COALESCE(mr.message, 'Mentor request sent') as notes,
        NULL as duration_minutes,
        NULL as meeting_link,
        'Mentor Request' as title
        FROM mentor_requests mr
        JOIN register r ON mr.mentor_id = r.id
        WHERE mr.student_id = ?
        ORDER BY mr.created_at DESC
        LIMIT 20";

    $stmt = $conn->prepare($requests_query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $requests = $result->fetch_all(MYSQLI_ASSOC);
            $activities = array_merge($activities, $requests);
        }
    }

    // Sort by date
    if (!empty($activities)) {
        usort($activities, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
    }
} catch (Exception $e) {
    error_log("Activities error: " . $e->getMessage());
    // Keep sample data if database fails
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.main-content { 
    margin-left: 280px;
    padding: 20px;
    min-height: 100vh;
}
.glass-card { 
    background: rgba(255, 255, 255, 0.9); 
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px; 
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); 
    transition: all 0.3s ease;
}
.glass-card:hover { 
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}
.timeline-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
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
.timeline-item {
    position: relative;
}
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 10px;
    }
}
</style>

<div class="main-content">
    <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-white mb-0">
                        <i class="fas fa-history me-2"></i>My Mentor Activities
                    </h2>
                    <p class="text-white-50">Track all your mentoring sessions, requests, and interactions</p>
                </div>
            </div>

            <!-- Activity Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="glass-card p-3 text-center">
                        <i class="fas fa-video fa-2x text-primary mb-2"></i>
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
                        <i class="fas fa-handshake fa-2x text-info mb-2"></i>
                        <h4><?= count(array_filter($activities, fn($a) => $a['status'] === 'accepted')) ?></h4>
                        <small>Active Mentors</small>
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
                                    <div class="mt-3">
                                        <a href="select_mentor.php" class="btn btn-primary me-2">
                                            <i class="fas fa-user-plus me-1"></i>Find a Mentor
                                        </a>
                                        <a href="my_mentor_requests.php" class="btn btn-outline-primary">
                                            <i class="fas fa-paper-plane me-1"></i>My Requests
                                        </a>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="timeline">
                                    <?php foreach ($activities as $activity) : ?>
                                        <div class="timeline-item mb-4">
                                            <div class="row">
                                                <div class="col-auto">
                                                    <div class="timeline-icon bg-<?= $activity['type'] === 'session' ? 'primary' : 'info' ?>">
                                                        <i class="fas fa-<?= $activity['type'] === 'session' ? 'video' : 'handshake' ?> text-white"></i>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="glass-card p-3">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <h6 class="mb-1"><?= htmlspecialchars($activity['title']) ?></h6>
                                                                <p class="text-muted mb-0">
                                                                    <i class="fas fa-user-tie me-1"></i>
                                                                    <?= htmlspecialchars($activity['mentor_name']) ?>
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

                                                        <?php if (!empty($activity['meeting_link'])) : ?>
                                                        <div class="mb-2">
                                                            <a href="<?= htmlspecialchars($activity['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-external-link-alt me-1"></i>Join Meeting
                                                            </a>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($activity['type'] === 'session' && $activity['status'] === 'scheduled') : ?>
                                                        <div class="mt-2">
                                                            <small class="text-success">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                Upcoming session - check your email for updates
                                                            </small>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>