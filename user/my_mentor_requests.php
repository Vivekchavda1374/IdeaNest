<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's mentor requests
$requests_query = "SELECT mr.id, mr.mentor_id, mr.project_id, mr.message, mr.status, mr.created_at, mr.updated_at,
                          r.name as mentor_name, r.email as mentor_email, r.department as mentor_department,
                          m.specialization, m.experience_years,
                          p.project_name
                   FROM mentor_requests mr
                   JOIN register r ON mr.mentor_id = r.id
                   LEFT JOIN mentors m ON r.id = m.user_id
                   LEFT JOIN projects p ON mr.project_id = p.id
                   WHERE mr.student_id = ?
                   ORDER BY mr.created_at DESC";
$requests_stmt = $conn->prepare($requests_query);
$requests_stmt->bind_param("i", $user_id);
$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();

include 'layout.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Mentor Requests - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background:  min-height: 100vh; }
        .main-content { margin-left: 280px; padding: 20px; }
        .request-card { background: rgba(255, 255, 255, 0.95); border-radius: 15px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
        .request-card:hover { transform: translateY(-2px); }
        .mentor-avatar { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; font-weight: bold; }
        .status-pending { color: #f59e0b; }
        .status-accepted { color: #10b981; }
        .status-rejected { color: #ef4444; }
        @media (max-width: 768px) { .main-content { margin-left: 0; } }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-white mb-0"><i class="fas fa-paper-plane me-2"></i>My Mentor Requests</h2>
                    <p class="text-white-50">Track the status of your mentorship requests</p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <?php if ($requests_result->num_rows > 0) : ?>
                        <?php while ($request = $requests_result->fetch_assoc()) : ?>
                            <div class="request-card p-4 mb-4">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center mb-3 mb-md-0">
                                        <div class="mentor-avatar mx-auto">
                                            <?= strtoupper(substr($request['mentor_name'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="mb-1"><?= htmlspecialchars($request['mentor_name']) ?></h5>
                                        <p class="text-muted mb-1"><?= htmlspecialchars($request['mentor_department']) ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-star me-1"></i><?= htmlspecialchars($request['specialization']) ?>
                                            <span class="ms-3">
                                                <i class="fas fa-clock me-1"></i><?= $request['experience_years'] ?> years exp.
                                            </span>
                                        </small>
                                        <?php if ($request['project_name']) : ?>
                                            <div class="mt-2">
                                                <span class="badge bg-info">Project: <?= htmlspecialchars($request['project_name']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <?php
                                        $status_icons = [
                                            'pending' => 'fas fa-clock',
                                            'accepted' => 'fas fa-check-circle',
                                            'rejected' => 'fas fa-times-circle'
                                        ];
                                        $status_class = 'status-' . $request['status'];
                                        ?>
                                        <div class="<?= $status_class ?>">
                                            <i class="<?= $status_icons[$request['status']] ?> fa-2x mb-2"></i>
                                            <div class="fw-bold"><?= ucfirst($request['status']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <small class="text-muted d-block">Sent:</small>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($request['created_at'])) ?></small>
                                        <?php if ($request['status'] !== 'pending') : ?>
                                            <small class="text-muted d-block mt-1">Updated:</small>
                                            <small class="text-muted"><?= date('M j, Y', strtotime($request['updated_at'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="bg-light p-3 rounded">
                                            <strong>Your Message:</strong>
                                            <p class="mb-0 mt-2"><?= htmlspecialchars($request['message']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($request['status'] === 'accepted') : ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="alert alert-success mb-0">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Great! Your mentorship request has been accepted. You can now collaborate with your mentor.
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($request['status'] === 'rejected') : ?>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="alert alert-warning mb-0">
                                                <i class="fas fa-info-circle me-2"></i>
                                                This request was not accepted. You can try requesting other mentors or improve your project proposal.
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <div class="request-card p-5 text-center">
                            <i class="fas fa-paper-plane fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Mentor Requests Yet</h4>
                            <p class="text-muted mb-4">You haven't sent any mentorship requests yet.</p>
                            <a href="select_mentor.php" class="btn btn-primary">
                                <i class="fas fa-user-graduate me-2"></i>Find a Mentor
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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