<?php
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Get mentor info
$mentor_query = "SELECT r.*, m.* FROM register r JOIN mentors m ON r.id = m.user_id WHERE r.id = ?";
$stmt = $conn->prepare($mentor_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$mentor = $stmt->get_result()->fetch_assoc();

// Get active pairings
$pairs_query = "SELECT msp.*, r.name as student_name, r.email as student_email, p.project_name 
                FROM mentor_student_pairs msp 
                JOIN register r ON msp.student_id = r.id 
                LEFT JOIN projects p ON msp.project_id = p.id 
                WHERE msp.mentor_id = ? AND msp.status = 'active'";
$stmt = $conn->prepare($pairs_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$active_pairs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get pairing requests
$requests_query = "SELECT r.id, r.name, r.email, r.department, p.project_name, p.classification 
                   FROM register r 
                   JOIN projects p ON r.id = p.user_id 
                   WHERE r.role = 'student' AND p.status = 'approved' 
                   AND r.id NOT IN (SELECT student_id FROM mentor_student_pairs WHERE status = 'active')
                   LIMIT 10";
$stmt = $conn->prepare($requests_query);
$stmt->execute();
$suggested_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mentor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Mentor Dashboard</h2>
            <p>Welcome, <?= htmlspecialchars($mentor['name']) ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Active Students (<?= count($active_pairs) ?>/<?= $mentor['max_students'] ?>)</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($active_pairs as $pair): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <strong><?= htmlspecialchars($pair['student_name']) ?></strong><br>
                        <small><?= htmlspecialchars($pair['student_email']) ?></small><br>
                        <small>Project: <?= htmlspecialchars($pair['project_name'] ?? 'No project') ?></small>
                        <div class="mt-1">
                            <button class="btn btn-sm btn-primary" onclick="scheduleSession(<?= $pair['id'] ?>)">Schedule Session</button>
                            <button class="btn btn-sm btn-success" onclick="completePairing(<?= $pair['id'] ?>)">Complete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Smart Pairing Suggestions</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($suggested_students as $student): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <strong><?= htmlspecialchars($student['name']) ?></strong><br>
                        <small><?= htmlspecialchars($student['department']) ?></small><br>
                        <small>Project: <?= htmlspecialchars($student['project_name']) ?> (<?= htmlspecialchars($student['classification']) ?>)</small>
                        <div class="mt-1">
                            <button class="btn btn-sm btn-success" onclick="acceptStudent(<?= $student['id'] ?>)">Accept as Mentee</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function acceptStudent(studentId) {
    fetch('pair_student.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({student_id: studentId})
    }).then(() => location.reload());
}

function scheduleSession(pairId) {
    const date = prompt('Enter session date (YYYY-MM-DD HH:MM):');
    if (date) {
        fetch('schedule_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({pair_id: pairId, session_date: date})
        }).then(() => location.reload());
    }
}

function completePairing(pairId) {
    const rating = prompt('Rate student (1-5):');
    const feedback = prompt('Feedback:');
    if (rating) {
        fetch('complete_pairing.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({pair_id: pairId, rating: rating, feedback: feedback})
        }).then(() => location.reload());
    }
}
</script>
</body>
</html>