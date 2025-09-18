<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Get mentor info - simplified for existing database
try {
    $mentor_query = "SELECT r.*, 
                     COALESCE(m.specialization, 'General') as specialization,
                     COALESCE(m.max_students, 10) as max_students
                     FROM register r 
                     LEFT JOIN mentors m ON r.id = m.user_id 
                     WHERE r.id = ? AND r.role = 'mentor'";
    $stmt = $conn->prepare($mentor_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $mentor = $stmt->get_result()->fetch_assoc();
    
    if (!$mentor) {
        die("Mentor not found or invalid role");
    }
    
    // Get stats from mentor requests
    $stats_query = "SELECT COUNT(*) as active_students FROM mentor_requests WHERE mentor_id = ? AND status = 'accepted'";
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $mentor['active_students'] = $result['active_students'] ?? 0;
    
    $stats_query = "SELECT COUNT(*) as completed_students FROM mentor_student_pairs WHERE mentor_id = ? AND status = 'completed'";
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $mentor['completed_students'] = $result['completed_students'] ?? 0;
    
    $stats_query = "SELECT AVG(rating) as avg_rating FROM mentor_student_pairs WHERE mentor_id = ? AND rating IS NOT NULL";
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $mentor['avg_rating'] = $result['avg_rating'] ?? 0;
    
    $mentor['upcoming_sessions'] = 0; // Default value
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    die("Database error occurred");
}

// Get active students from accepted requests
$active_pairs = [];
try {
    $pairs_query = "SELECT mr.id, mr.student_id, mr.project_id, mr.created_at as paired_at,
                    r.name as student_name, r.email as student_email, r.department,
                    p.project_name, p.classification, p.description,
                    0 as total_sessions,
                    NULL as next_session
                    FROM mentor_requests mr
                    JOIN register r ON mr.student_id = r.id 
                    LEFT JOIN projects p ON mr.project_id = p.id 
                    WHERE mr.mentor_id = ? AND mr.status = 'accepted'
                    ORDER BY mr.created_at DESC";
    $stmt = $conn->prepare($pairs_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $active_pairs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // If no students from mentor_requests, try mentor_student_pairs
    if (empty($active_pairs)) {
        $pairs_query = "SELECT msp.id, msp.student_id, msp.project_id, msp.paired_at,
                        r.name as student_name, r.email as student_email, r.department,
                        p.project_name, p.classification, p.description,
                        0 as total_sessions,
                        NULL as next_session
                        FROM mentor_student_pairs msp
                        JOIN register r ON msp.student_id = r.id 
                        LEFT JOIN projects p ON msp.project_id = p.id 
                        WHERE msp.mentor_id = ? AND msp.status = 'active'
                        ORDER BY msp.paired_at DESC";
        $stmt = $conn->prepare($pairs_query);
        $stmt->bind_param("i", $mentor_id);
        $stmt->execute();
        $active_pairs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Active pairs error: " . $e->getMessage());
    $active_pairs = [];
}

// Get smart pairing suggestions - using existing database
$suggested_students = [];
try {
    $suggestions_query = "SELECT r.id, r.name, r.email, r.department,
                          p.project_name, p.classification, p.description, 
                          'Not specified' as expected_duration,
                          2 as match_score
                          FROM register r 
                          JOIN projects p ON r.id = p.user_id 
                          WHERE r.role = 'student' 
                          AND p.status = 'approved' 
                          AND r.id NOT IN (SELECT student_id FROM mentor_student_pairs WHERE status = 'active')
                          ORDER BY p.submission_date DESC
                          LIMIT 6";
    $stmt = $conn->prepare($suggestions_query);
    $stmt->execute();
    $suggested_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Suggestions error: " . $e->getMessage());
    $suggested_students = [];
}

// Get upcoming sessions - using existing table
$upcoming_sessions = [];
try {
    // Try with mentor_student_pairs first
    $upcoming_sessions_query = "SELECT ms.*, r.name as student_name, p.project_name,
                               ms.session_date, ms.duration_minutes, ms.notes, ms.meeting_link,
                               TIMESTAMPDIFF(HOUR, NOW(), ms.session_date) as hours_until
                               FROM mentoring_sessions ms
                               JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
                               JOIN register r ON msp.student_id = r.id
                               LEFT JOIN projects p ON msp.project_id = p.id
                               WHERE msp.mentor_id = ? 
                               AND ms.status = 'scheduled' 
                               AND ms.session_date >= NOW()
                               ORDER BY ms.session_date ASC
                               LIMIT 5";
    $stmt = $conn->prepare($upcoming_sessions_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $upcoming_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // If no sessions found, try with mentor_requests
    if (empty($upcoming_sessions)) {
        $upcoming_sessions_query = "SELECT ms.*, r.name as student_name, p.project_name,
                                   ms.session_date, ms.duration_minutes, ms.notes, ms.meeting_link,
                                   TIMESTAMPDIFF(HOUR, NOW(), ms.session_date) as hours_until
                                   FROM mentoring_sessions ms
                                   JOIN mentor_requests mr ON ms.pair_id = mr.id
                                   JOIN register r ON mr.student_id = r.id
                                   LEFT JOIN projects p ON mr.project_id = p.id
                                   WHERE mr.mentor_id = ? AND mr.status = 'accepted'
                                   AND ms.status = 'scheduled' 
                                   AND ms.session_date >= NOW()
                                   ORDER BY ms.session_date ASC
                                   LIMIT 5";
        $stmt = $conn->prepare($upcoming_sessions_query);
        $stmt->bind_param("i", $mentor_id);
        $stmt->execute();
        $upcoming_sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Upcoming sessions error: " . $e->getMessage());
    $upcoming_sessions = [];
}

// Get recent notifications - using existing table
$notifications = [];
try {
    $notifications_query = "SELECT * FROM realtime_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->prepare($notifications_query);
    $stmt->bind_param("i", $mentor_id);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Notifications error: " . $e->getMessage());
    $notifications = [];
}

// Calculate capacity percentage - with safety check
$capacity_percentage = $mentor['max_students'] > 0 ? ($mentor['active_students'] / $mentor['max_students']) * 100 : 0;

// Dashboard content
ob_start();
?>

    <!-- Mobile Menu Toggle -->
    <div class="d-md-none mb-3">
        <button class="btn btn-outline-primary" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i> Menu
        </button>
    </div>

    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">Welcome back, <?= htmlspecialchars($mentor['name']) ?>! 🎓</h1>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar-day me-2"></i>
                            <?= date('l, F j, Y') ?>
                        </p>
                        <small class="text-muted">
                            Specialization: <span class="badge bg-primary"><?= htmlspecialchars($mentor['specialization'] ?? 'General') ?></span>
                        </small>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="position-relative d-inline-block">
                            <div class="bg-primary rounded-circle p-3">
                                <i class="fas fa-user-graduate text-white fa-2x"></i>
                            </div>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                            <?= $mentor['active_students'] ?>
                        </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="animate-counter mb-1"><?= $mentor['active_students'] ?></h3>
                        <p class="mb-0">Active Students</p>
                        <small class="opacity-75"><?= $mentor['active_students'] ?>/<?= $mentor['max_students'] ?> capacity</small>
                    </div>
                    <i class="fas fa-users fa-2x opacity-25"></i>
                </div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-light" style="width: <?= $capacity_percentage ?>%"></div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="animate-counter mb-1"><?= $mentor['completed_students'] ?></h3>
                        <p class="mb-0">Completed</p>
                        <small class="opacity-75">Total mentorships</small>
                    </div>
                    <i class="fas fa-trophy fa-2x opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="animate-counter mb-1"><?= number_format($mentor['avg_rating'] ?? 0, 1) ?></h3>
                        <p class="mb-0">Rating</p>
                        <div>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= ($mentor['avg_rating'] ?? 0) ? 'text-warning' : 'opacity-25' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <i class="fas fa-star fa-2x opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="animate-counter mb-1"><?= $mentor['upcoming_sessions'] ?></h3>
                        <p class="mb-0">Upcoming Sessions</p>
                        <small class="opacity-75">This week</small>
                    </div>
                    <i class="fas fa-calendar-check fa-2x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Active Students Column -->
        <div class="col-lg-8 mb-4">
            <div class="glass-card">
                <div class="card-header bg-transparent border-0 p-4 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-users text-primary me-2"></i>
                            My Students (<?= count($active_pairs) ?>)
                        </h5>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkActionsModal">
                            <i class="fas fa-cogs me-1"></i> Bulk Actions
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($active_pairs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No active students yet</h6>
                            <p class="text-muted">Start by accepting pairing suggestions below!</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($active_pairs as $pair): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="student-card glass-card p-3">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-primary rounded-circle p-2 me-3">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= htmlspecialchars($pair['student_name']) ?></h6>
                                                <small class="text-muted d-block"><?= htmlspecialchars($pair['department']) ?></small>
                                                <small class="text-muted">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?= htmlspecialchars($pair['student_email']) ?>
                                                </small>

                                                <?php if ($pair['project_name']): ?>
                                                    <div class="mt-2">
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-project-diagram me-1"></i>
                                                <?= htmlspecialchars($pair['project_name']) ?>
                                            </span>
                                                        <span class="badge bg-info">
                                                <?= htmlspecialchars($pair['classification']) ?>
                                            </span>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Sessions: <?= $pair['total_sessions'] ?>
                                                        <?php if ($pair['next_session']): ?>
                                                            | Next: <?= date('M j, g:i A', strtotime($pair['next_session'])) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3 d-flex gap-2 flex-wrap">
                                            <button class="btn btn-sm btn-gradient" onclick="scheduleSession(<?= $pair['id'] ?>)">
                                                <i class="fas fa-calendar-plus me-1"></i>Schedule
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewProgress(<?= $pair['id'] ?>)">
                                                <i class="fas fa-chart-line me-1"></i>Progress
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="completePairing(<?= $pair['id'] ?>)">
                                                <i class="fas fa-check me-1"></i>Complete
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

        <!-- Sidebar Content -->
        <div class="col-lg-4">
            <!-- Upcoming Sessions -->
            <div class="glass-card mb-4">
                <div class="card-header bg-transparent border-0 p-3 pb-0">
                    <h6 class="mb-0">
                        <i class="fas fa-clock text-warning me-2"></i>
                        Upcoming Sessions
                    </h6>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($upcoming_sessions)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-calendar text-muted mb-2"></i>
                            <p class="small text-muted mb-0">No upcoming sessions</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($upcoming_sessions, 0, 3) as $session): ?>
                            <div class="glass-card p-3 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="bg-warning rounded-circle p-2 me-3">
                                        <i class="fas fa-video text-white"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($session['student_name']) ?></h6>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <strong>Date & Time:</strong><br>
                                                <?= date('m/d/Y, H:i', strtotime($session['session_date'])) ?>
                                            </small>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <strong>Duration:</strong><br>
                                                <?= $session['duration_minutes'] ?? 60 ?> minutes
                                            </small>
                                        </div>
                                        <?php if (!empty($session['notes'])): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-sticky-note me-1"></i>
                                                <strong>Notes:</strong><br>
                                                <?= htmlspecialchars(substr($session['notes'], 0, 50)) ?>...
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($session['meeting_link'])): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-link me-1"></i>
                                                <strong>Meeting Link:</strong><br>
                                                <a href="<?= htmlspecialchars($session['meeting_link']) ?>" target="_blank" class="text-primary">Join Meeting</a>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        <span class="badge bg-warning text-dark">
                                            <?= abs($session['hours_until']) ?>h <?= $session['hours_until'] < 0 ? 'overdue' : 'remaining' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="sessions.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email System Quick Access -->
            <div class="glass-card mb-4">
                <div class="card-header bg-transparent border-0 p-3 pb-0">
                    <h6 class="mb-0">
                        <i class="fas fa-envelope text-primary me-2"></i>
                        Email System
                    </h6>
                </div>
                <div class="card-body p-3">
                    <?php
                    // Get email stats for quick view
                    $email_stats_query = "SELECT 
                                        COUNT(*) as total_emails,
                                        SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_emails
                                        FROM mentor_email_logs 
                                        WHERE mentor_id = ? 
                                        AND sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    $stmt = $conn->prepare($email_stats_query);
                    $stmt->bind_param("i", $mentor_id);
                    $stmt->execute();
                    $email_stats = $stmt->get_result()->fetch_assoc();
                    ?>
                    
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <h5 class="text-primary mb-0"><?= $email_stats['total_emails'] ?></h5>
                            <small class="text-muted">This Week</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-success mb-0"><?= $email_stats['sent_emails'] ?></h5>
                            <small class="text-muted">Delivered</small>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="send_email.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-envelope me-1"></i>Send Email
                        </a>
                        <a href="email_dashboard.php" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-chart-bar me-1"></i>Email Analytics
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass-card mb-4">
                <div class="card-header bg-transparent border-0 p-3 pb-0">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt text-primary me-2"></i>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="createSession()">
                            <i class="fas fa-plus me-1"></i>Create Session
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="viewAnalytics()">
                            <i class="fas fa-chart-bar me-1"></i>View Analytics
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="exportData()">
                            <i class="fas fa-download me-1"></i>Export Data
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div class="glass-card">
                <div class="card-header bg-transparent border-0 p-3 pb-0">
                    <h6 class="mb-0">
                        <i class="fas fa-bell text-info me-2"></i>
                        Recent Notifications
                    </h6>
                </div>
                <div class="card-body p-3">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-bell-slash text-muted mb-2"></i>
                            <p class="small text-muted mb-0">No new notifications</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                            <div class="d-flex align-items-start mb-3">
                                <div class="bg-info rounded-circle p-1 me-2 mt-1">
                                    <i class="fas fa-dot-circle text-white small"></i>
                                </div>
                                <div>
                                    <p class="small mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                    <small class="text-muted"><?= date('M j, g:i A', strtotime($notification['created_at'])) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Smart Pairing Suggestions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="glass-card">
                <div class="card-header bg-transparent border-0 p-4 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-magic text-success me-2"></i>
                            Smart Pairing Suggestions
                        </h5>
                        <button class="btn btn-sm btn-outline-success" onclick="refreshSuggestions()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($suggested_students)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-search fa-2x text-muted mb-3"></i>
                            <h6 class="text-muted">No suggestions available</h6>
                            <p class="text-muted">All available students have been paired or none match your expertise.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($suggested_students as $student): ?>
                                <div class="col-lg-6 mb-3">
                                    <div class="glass-card p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-success rounded-circle p-2 me-3">
                                                    <i class="fas fa-user-plus text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($student['name']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($student['department']) ?></small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                        <span class="badge bg-<?= $student['match_score'] >= 3 ? 'success' : ($student['match_score'] == 2 ? 'warning' : 'secondary') ?>">
                                            <?= $student['match_score'] >= 3 ? 'Perfect Match' : ($student['match_score'] == 2 ? 'Good Match' : 'Basic Match') ?>
                                        </span>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <h6 class="small mb-1">
                                                <i class="fas fa-project-diagram text-primary me-1"></i>
                                                <?= htmlspecialchars($student['project_name']) ?>
                                            </h6>
                                            <span class="badge bg-info me-2"><?= htmlspecialchars($student['classification']) ?></span>
                                            <?php if ($student['expected_duration']): ?>
                                                <span class="badge bg-light text-dark">
                                        <i class="fas fa-clock me-1"></i><?= $student['expected_duration'] ?> weeks
                                    </span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="small text-muted mb-3">
                                            <?= substr(htmlspecialchars($student['description']), 0, 100) ?>...
                                        </p>

                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-success flex-grow-1" onclick="acceptStudent(<?= $student['id'] ?>, '<?= htmlspecialchars($student['name']) ?>')">
                                                <i class="fas fa-handshake me-1"></i>Accept as Mentee
                                            </button>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewStudentDetails(<?= $student['id'] ?>)">
                                                <i class="fas fa-eye me-1"></i>Details
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

    <!-- Enhanced JavaScript -->
    <script>
        // Enhanced accept student function with confirmation
        function acceptStudent(studentId, studentName) {
            if (confirm(`Accept ${studentName} as your mentee? This will create an active pairing.`)) {
                fetch('pair_student.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({student_id: studentId})
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(`Successfully paired with ${studentName}!`, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showNotification(data.error || 'Failed to pair student', 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Network error occurred', 'error');
                    });
            }
        }

        // Enhanced schedule session with modal
        function scheduleSession(pairId) {
            const modal = `
        <div class="modal fade" id="scheduleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content glass-card">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">Schedule Session</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="sessionForm">
                            <div class="mb-3">
                                <label class="form-label">Student *</label>
                                <select class="form-select" id="studentSelect" disabled>
                                    <option value="${pairId}">Selected Student</option>
                                </select>
                                <small class="text-muted">Student is pre-selected for this session</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date & Time *</label>
                                <input type="datetime-local" class="form-control" id="sessionDate" required>
                                <small class="text-muted">Format: mm/dd/yyyy, HH:MM</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Duration</label>
                                <select class="form-select" id="duration">
                                    <option value="30">30 minutes</option>
                                    <option value="60" selected>1 hour</option>
                                    <option value="90">1.5 hours</option>
                                    <option value="120">2 hours</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" rows="3" placeholder="Session agenda, topics to discuss..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meeting Link</label>
                                <input type="url" class="form-control" id="meetingLink" placeholder="https://meet.google.com/... or https://zoom.us/...">
                                <small class="text-muted">Optional: Add Google Meet, Zoom, or other meeting link</small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-gradient" onclick="submitSession(${pairId})">Schedule Session</button>
                    </div>
                </div>
            </div>
        </div>
    `;

            document.body.insertAdjacentHTML('beforeend', modal);
            const bootstrapModal = new bootstrap.Modal(document.getElementById('scheduleModal'));

            // Set minimum date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('sessionDate').min = tomorrow.toISOString().slice(0, 16);

            bootstrapModal.show();

            // Clean up modal after hiding
            document.getElementById('scheduleModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }

        function submitSession(pairId) {
            const sessionData = {
                pair_id: pairId,
                session_date: document.getElementById('sessionDate').value,
                duration: document.getElementById('duration').value,
                notes: document.getElementById('notes').value,
                meeting_link: document.getElementById('meetingLink').value
            };

            if (!sessionData.session_date) {
                alert('Please select a date and time');
                return;
            }

            fetch('schedule_session.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(sessionData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Session scheduled successfully!');
                        bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert(data.error || 'Failed to schedule session');
                    }
                })
                .catch(error => {
                    alert('Network error occurred');
                });
        }

        // Enhanced complete pairing function
        function completePairing(pairId) {
            const modal = `
        <div class="modal fade" id="completeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content glass-card">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">Complete Mentorship</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Rate Student (1-5) *</label>
                            <div class="rating-stars">
                                ${[1,2,3,4,5].map(i => `<i class="fas fa-star star-rating" data-rating="${i}"></i>`).join('')}
                            </div>
                            <input type="hidden" id="rating" value="5">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Feedback *</label>
                            <textarea class="form-control" id="feedback" rows="4" placeholder="Share your experience mentoring this student..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="submitCompletion(${pairId})">Complete Mentorship</button>
                    </div>
                </div>
            </div>
        </div>
    `;

            document.body.insertAdjacentHTML('beforeend', modal);
            const bootstrapModal = new bootstrap.Modal(document.getElementById('completeModal'));

            // Star rating functionality
            document.querySelectorAll('.star-rating').forEach(star => {
                star.addEventListener('click', function() {
                    const rating = this.dataset.rating;
                    document.getElementById('rating').value = rating;
                    document.querySelectorAll('.star-rating').forEach((s, index) => {
                        s.classList.toggle('text-warning', index < rating);
                    });
                });
            });

            // Set default 5 stars
            document.querySelectorAll('.star-rating').forEach(star => {
                star.classList.add('text-warning');
            });

            bootstrapModal.show();

            // Clean up modal after hiding
            document.getElementById('completeModal').addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        }

        function submitCompletion(pairId) {
            const rating = document.getElementById('rating').value;
            const feedback = document.getElementById('feedback').value;

            if (!rating || rating < 1 || rating > 5) {
                alert('Please select a rating');
                return;
            }

            if (!feedback.trim()) {
                alert('Please provide feedback');
                return;
            }

            fetch('complete_pairing.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    pair_id: pairId,
                    rating: parseInt(rating),
                    feedback: feedback.trim()
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Mentorship completed successfully!');
                        bootstrap.Modal.getInstance(document.getElementById('completeModal')).hide();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert(data.error || 'Failed to complete mentorship');
                    }
                })
                .catch(error => {
                    alert('Network error occurred');
                });
        }

        // Additional utility functions
        function viewProgress(pairId) {
            window.location.href = `student_progress.php?pair_id=${pairId}`;
        }

        function viewStudentDetails(studentId) {
            window.location.href = `student_details.php?student_id=${studentId}`;
        }

        function refreshSuggestions() {
            showNotification('Refreshing suggestions...', 'info');
            setTimeout(() => location.reload(), 1000);
        }

        function createSession() {
            window.location.href = 'create_session.php';
        }

        function viewAnalytics() {
            window.location.href = 'analytics.php';
        }

        function exportData() {
            window.location.href = 'export_data.php';
        }

        // Counter animation
        function animateCounters() {
            document.querySelectorAll('.animate-counter').forEach(counter => {
                const target = parseInt(counter.textContent);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = target;
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current);
                    }
                }, 20);
            });
        }

        // Initialize animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            animateCounters();

            // Auto-refresh notifications every 5 minutes
            setInterval(() => {
                fetch('get_notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.new_count > 0) {
                            document.getElementById('studentsBadge').textContent = data.new_count;
                        }
                    })
                    .catch(error => console.log('Notification check failed'));
            }, 300000);
        });

        // Custom CSS for star ratings
        const style = document.createElement('style');
        style.textContent = `
    .rating-stars {
        font-size: 1.5rem;
        margin: 10px 0;
    }
    .star-rating {
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
        margin: 0 2px;
    }
    .star-rating:hover,
    .star-rating.text-warning {
        color: #ffc107 !important;
    }

    .notification-badge {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .glass-card {
        transition: all 0.3s ease;
    }

    .glass-card:hover {
        transform: translateY(-5px);
    }

    .student-card {
        position: relative;
        overflow: hidden;
    }

    .student-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .student-card:hover::before {
        left: 100%;
    }
`;
        document.head.appendChild(style);
    </script>

<?php
$content = ob_get_clean();

$additionalCSS = '
    .rating-stars .fas {
        font-size: 1.5rem;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
        margin: 0 2px;
    }
    
    .rating-stars .fas:hover,
    .rating-stars .fas.text-warning {
        color: #ffc107 !important;
    }
    
    .modal-content {
        border: none;
        border-radius: 16px;
    }
    
    .progress {
        border-radius: 10px;
        overflow: hidden;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 4px 8px;
    }
    
    .student-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    .student-card:hover {
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .btn-gradient:hover {
        transform: translateY(-1px);
    }
    
    .notification-badge {
        animation: pulse 2s infinite;
        font-size: 10px;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .stat-card {
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::after {
        content: "";
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: rotate(45deg);
        transition: all 0.6s;
    }
    
    .stat-card:hover::after {
        animation: shine 0.6s ease-in-out;
    }
    
    @keyframes shine {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    
    .card-header h5, .card-header h6 {
        font-weight: 600;
    }
    
    .nav-link {
        font-weight: 500;
    }
    
    .nav-link i {
        width: 20px;
        text-align: center;
    }
    
    @media (max-width: 768px) {
        .stat-card {
            margin-bottom: 1rem;
        }
        
        .student-card {
            margin-bottom: 1rem;
        }
        
        .glass-card {
            margin-bottom: 1rem;
        }
    }
';

$additionalJS = '
    // Mobile responsiveness
    function checkMobile() {
        if (window.innerWidth < 768) {
            document.querySelectorAll(".btn-sm").forEach(btn => {
                btn.classList.add("btn-xs");
            });
        }
    }
    
    window.addEventListener("resize", checkMobile);
    checkMobile();
    
    // Smooth scrolling for anchor links
    document.querySelectorAll("a[href^=\"#\"]").forEach(anchor => {
        anchor.addEventListener("click", function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute("href")).scrollIntoView({
                behavior: "smooth"
            });
        });
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll(".alert").forEach(alert => {
            if (alert.classList.contains("alert-dismissible")) {
                new bootstrap.Alert(alert).close();
            }
        });
    }, 5000);
    
    // Enhanced notification system
    function showNotification(message, type = "success") {
        const colors = {
            success: "bg-success",
            error: "bg-danger", 
            warning: "bg-warning",
            info: "bg-info"
        };
        
        const toast = document.createElement("div");
        toast.className = `toast align-items-center text-white ${colors[type]} border-0`;
        toast.setAttribute("role", "alert");
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === "success" ? "check-circle" : type === "error" ? "exclamation-circle" : type === "warning" ? "exclamation-triangle" : "info-circle"} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        const toastContainer = document.getElementById("toastContainer") || createToastContainer();
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        setTimeout(() => toast.remove(), 5000);
    }
    
    function createToastContainer() {
        const container = document.createElement("div");
        container.id = "toastContainer";
        container.className = "toast-container position-fixed top-0 end-0 p-3";
        container.style.zIndex = "9999";
        document.body.appendChild(container);
        return container;
    }
';

renderLayout('Dashboard', $content, $additionalCSS, $additionalJS);
?>