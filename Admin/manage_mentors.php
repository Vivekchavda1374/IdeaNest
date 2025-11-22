<?php
session_start();
require_once '../config/config.php';
require_once '../Login/Login/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

// Get all mentors
$query = "SELECT r.id, r.name, r.email, m.specialization, m.experience_years, m.current_students, m.max_students 
          FROM register r 
          JOIN mentors m ON r.id = m.user_id 
          WHERE r.role = 'mentor'";
$mentors = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Mentors - IdeaNest Admin</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/loader.css">
    <link rel="stylesheet" href="assets/css/loading.css">
</head>
<body>
<?php include 'sidebar_admin.php'; ?>

<div class="main-content">
    <div class="topbar">
        <button class="btn d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title">Manage Mentors</h1>
    </div>

    <div class="dashboard-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Mentors</h2>
        <a href="add_mentor.php" class="btn btn-primary">Add New Mentor</a>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Specialization</th>
                    <th>Experience</th>
                    <th>Students</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mentors as $mentor) : ?>
                <tr>
                    <td><?= htmlspecialchars($mentor['name']) ?></td>
                    <td><?= htmlspecialchars($mentor['email']) ?></td>
                    <td><?= htmlspecialchars($mentor['specialization']) ?></td>
                    <td><?= $mentor['experience_years'] ?> years</td>
                    <td><?= $mentor['current_students'] ?>/<?= $mentor['max_students'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="resetPassword(<?= $mentor['id'] ?>, '<?= $mentor['email'] ?>')">Reset Password</button>
                        <button class="btn btn-sm btn-danger ms-1" onclick="removeMentor(<?= $mentor['id'] ?>, '<?= $mentor['name'] ?>')">Remove</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div>
</div>

<script>
function resetPassword(mentorId, email) {
    if (confirm('Reset password for ' + email + '?')) {
        fetch('reset_mentor_password.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({mentor_id: mentorId})
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('New password sent to email');
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}

function removeMentor(mentorId, mentorName) {
    if (confirm('Are you sure you want to remove mentor "' + mentorName + '"? This action cannot be undone.')) {
        fetch('remove_mentor.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({mentor_id: mentorId})
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Mentor removed successfully');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="assets/js/loader.js"></script>
<script src="assets/js/loading.js"></script>
</body>
</html>