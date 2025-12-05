<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../config/config.php';
require_once '../Login/Login/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: overview.php");
    exit();
}

$mentor_id = (int)$_GET['id'];

// Get mentor details
$mentor_query = "SELECT * FROM users WHERE id = ? AND role = 'mentor'";
$stmt = $conn->prepare($mentor_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$mentor = $stmt->get_result()->fetch_assoc();

if (!$mentor) {
    header("Location: overview.php");
    exit();
}

// Get mentor's students
$students_query = "SELECT u.*, msp.created_at as paired_at FROM users u 
                   JOIN mentor_student_pairs msp ON u.id = msp.student_id 
                   WHERE msp.mentor_id = ? ORDER BY msp.created_at DESC";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$students = $stmt->get_result();

// Get mentor's sessions
$sessions_query = "SELECT ms.*, u.name as student_name FROM mentor_sessions ms 
                   LEFT JOIN users u ON ms.student_id = u.id 
                   WHERE ms.mentor_id = ? ORDER BY ms.created_at DESC LIMIT 10";
$stmt = $conn->prepare($sessions_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$sessions = $stmt->get_result();

// Get mentor statistics
$stats = [];

// Total students
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mentor_student_pairs WHERE mentor_id = ?");
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$stats['total_students'] = $stmt->get_result()->fetch_assoc()['count'];

// Total sessions
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mentor_sessions WHERE mentor_id = ?");
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$stats['total_sessions'] = $stmt->get_result()->fetch_assoc()['count'];

// Completed sessions
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mentor_sessions WHERE mentor_id = ? AND status = 'completed'");
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$stats['completed_sessions'] = $stmt->get_result()->fetch_assoc()['count'];

// Average rating (if exists)
$stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM mentor_sessions WHERE mentor_id = ? AND rating IS NOT NULL");
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stats['avg_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../assets/js/anti_injection.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Details - <?php echo htmlspecialchars($mentor['name']); ?></title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/sidebar_admin.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 250px; padding: 20px; }
        .mentor-avatar { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 2.5rem; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; }
        .stat-card.students { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.sessions { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-card.rating { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #333; }
        .student-card { border-left: 4px solid #28a745; }
        .session-card { border-left: 4px solid #007bff; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <button class="btn d-lg-none mb-3" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="overview.php" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <h1><i class="bi bi-person-workspace"></i> Mentor Details</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="manage_mentors.php" class="btn btn-outline-primary">
                    <i class="bi bi-people"></i> All Mentors
                </a>
                <button class="btn btn-primary" onclick="exportMentorData()">
                    <i class="bi bi-download"></i> Export Data
                </button>
            </div>
        </div>

        <!-- Mentor Profile Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="mentor-avatar mx-auto mb-3">
                            <?php echo strtoupper(substr($mentor['name'], 0, 1)); ?>
                        </div>
                        <h4><?php echo htmlspecialchars($mentor['name']); ?></h4>
                        <span class="badge bg-success fs-6">Mentor</span>
                        <div class="mt-3">
                            <?php if ($stats['avg_rating'] > 0) : ?>
                            <div class="d-flex justify-content-center align-items-center">
                                <span class="text-warning me-1">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <i class="bi bi-star<?php echo $i <= $stats['avg_rating'] ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </span>
                                <span class="fw-bold"><?php echo $stats['avg_rating']; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Contact Information</h6>
                                <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($mentor['email']); ?></p>
                                <p><i class="bi bi-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($mentor['created_at'])); ?></p>
                                <p><i class="bi bi-clock"></i> Last Login: 
                                    <?php echo $mentor['last_login'] ? date('M d, Y H:i', strtotime($mentor['last_login'])) : 'Never'; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Mentor Information</h6>
                                <p><i class="bi bi-mortarboard"></i> Expertise: 
                                    <?php echo htmlspecialchars($mentor['expertise'] ?? 'Not specified'); ?>
                                </p>
                                <p><i class="bi bi-briefcase"></i> Experience: 
                                    <?php echo htmlspecialchars($mentor['experience'] ?? 'Not specified'); ?>
                                </p>
                                <?php if ($mentor['github_username']) : ?>
                                <p><i class="bi bi-github"></i> GitHub: 
                                    <a href="https://github.com/<?php echo htmlspecialchars($mentor['github_username']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($mentor['github_username']); ?>
                                    </a>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card students">
                    <div class="card-body text-center">
                        <i class="bi bi-people fs-1 mb-2"></i>
                        <h3><?php echo $stats['total_students']; ?></h3>
                        <p class="mb-0">Students Mentored</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card sessions">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-event fs-1 mb-2"></i>
                        <h3><?php echo $stats['total_sessions']; ?></h3>
                        <p class="mb-0">Total Sessions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle fs-1 mb-2"></i>
                        <h3><?php echo $stats['completed_sessions']; ?></h3>
                        <p class="mb-0">Completed Sessions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card rating">
                    <div class="card-body text-center">
                        <i class="bi bi-star fs-1 mb-2"></i>
                        <h3><?php echo $stats['avg_rating']; ?></h3>
                        <p class="mb-0">Average Rating</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students and Sessions -->
        <div class="row">
            <div class="col-md-6">
                <div class="card student-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-people"></i> Mentored Students</h5>
                        <span class="badge bg-success"><?php echo $students->num_rows; ?> students</span>
                    </div>
                    <div class="card-body">
                        <?php if ($students->num_rows > 0) : ?>
                            <?php while ($student = $students->fetch_assoc()) : ?>
                            <div class="d-flex align-items-center justify-content-between mb-3 p-2 border rounded">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($student['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">Paired: <?php echo date('M d, Y', strtotime($student['paired_at'])); ?></small>
                                    <br>
                                    <a href="user_details.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <div class="text-center py-4">
                                <i class="bi bi-people fs-1 text-muted"></i>
                                <p class="text-muted mt-2">No students assigned yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card session-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-calendar-event"></i> Recent Sessions</h5>
                        <span class="badge bg-primary"><?php echo $sessions->num_rows; ?> sessions</span>
                    </div>
                    <div class="card-body">
                        <?php if ($sessions->num_rows > 0) : ?>
                            <?php while ($session = $sessions->fetch_assoc()) : ?>
                            <div class="d-flex align-items-center justify-content-between mb-3 p-2 border rounded">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($session['title'] ?? 'Session'); ?></div>
                                    <small class="text-muted">
                                        with <?php echo htmlspecialchars($session['student_name'] ?? 'Unknown'); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y H:i', strtotime($session['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php
                                        echo $session['status'] === 'completed' ? 'success' :
                                            ($session['status'] === 'cancelled' ? 'danger' : 'warning');
                                    ?>">
                                        <?php echo ucfirst($session['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-event fs-1 text-muted"></i>
                                <p class="text-muted mt-2">No sessions conducted yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="mailto:<?php echo htmlspecialchars($mentor['email']); ?>" class="btn btn-outline-primary w-100">
                            <i class="bi bi-envelope"></i> Send Email
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="../mentor/dashboard.php?mentor_id=<?php echo $mentor_id; ?>" class="btn btn-outline-success w-100">
                            <i class="bi bi-speedometer2"></i> View Dashboard
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-info w-100" onclick="assignStudent()">
                            <i class="bi bi-person-plus"></i> Assign Student
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-outline-warning w-100" onclick="suspendMentor()">
                            <i class="bi bi-pause-circle"></i> Suspend Mentor
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportMentorData() {
            window.open('export_mentor.php?id=<?php echo $mentor_id; ?>', '_blank');
        }

        function assignStudent() {
            // Redirect to student assignment page
            window.location.href = 'assign_student.php?mentor_id=<?php echo $mentor_id; ?>';
        }

        function suspendMentor() {
            if (confirm('Are you sure you want to suspend this mentor?')) {
                // Handle suspension
                console.log('Mentor suspension confirmed');
            }
        }
    </script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
</body>
</html>