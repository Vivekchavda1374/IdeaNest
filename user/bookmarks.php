<?php
include '../Login/Login/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User";
$user_initial = !empty($user_name) ? substr($user_name, 0, 1) : "U";

// Get user email
$emailSql = "SELECT email FROM register WHERE id = ?";
$stmt = $conn->prepare($emailSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$emailResult = $stmt->get_result();

if ($emailResult->num_rows > 0) {
    $emailRow = $emailResult->fetch_assoc();
    $user_email = $emailRow['email'];
} else {
    $user_email = "user@example.com"; // Default email if not found
}
$stmt->close();

// Fetch bookmarked projects for the current user
$bookmarkSql = "SELECT b.id, b.bookmarked_at, p.* 
                FROM user_bookmarks b
                JOIN admin_approved_projects p ON b.project_id = p.id
                WHERE b.user_id = ?
                ORDER BY b.bookmarked_at DESC";
                
$stmt = $conn->prepare($bookmarkSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookmarkedProjects = [];

while ($row = $result->fetch_assoc()) {
    $bookmarkedProjects[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookmarks - IdeaNest</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>

<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-cubes"></i>
        <div class="logo">Dashboard</div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="user_project_search.php">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
        </li>
        <li>
            <a href="Blog/idea_dashboard.php">
                <i class="fas fa-file-alt"></i>
                <span>Idea Posts</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-user-graduate"></i>
                <span>Mentorship</span>
            </a>
        </li>
        <li>
            <a href="bookmarks.php" class="active">
                <i class="fas fa-bookmark"></i>
                <span>Bookmarks</span>
            </a>
        </li>
        <li>
            <a href="user_profile_setting.php">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        <li>
            <a href="Blog/idea_bookmarks.php">
                <i class="fas fa-bookmark"></i>
                <span>Idea Bookmarks</span>
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container-fluid">
            <button id="sidebarToggle" class="btn btn-light d-lg-none me-3">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Search Form -->
            <div class="d-none d-md-block ms-lg-4 flex-grow-1 me-auto">
                <div class="position-relative">
                    <input type="text" id="search" class="form-control" placeholder="Search projects, ideas, mentors..." onkeyup="fetchResults()">
                    <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                    <div id="searchResults" class="position-absolute start-0 bg-white shadow-lg rounded-3 mt-1 w-100 p-2 d-none" style="z-index: 1000;"></div>
                </div>
            </div>

            <!-- Right-side menu items -->
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown ms-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                            <span class="text-white fw-medium"><?php echo htmlspecialchars($user_initial); ?></span>
                        </div>
                        <span class="d-none d-lg-inline"><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                        <li>
                            <div class="dropdown-item text-center">
                                <div class="user-avatar rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 60px; height: 60px;">
                                    <span class="text-white fw-bold"><?php echo htmlspecialchars($user_initial); ?></span>
                                </div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($user_name); ?></h6>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($user_email); ?></p>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="user_profile_setting.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../Login/Login/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">My Bookmarks</h2>
        </div>

        <?php if (empty($bookmarkedProjects)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> You haven't bookmarked any projects yet.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($bookmarkedProjects as $project): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card project-card h-100">
                            <?php if (!empty($project['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($project['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($project['project_name']); ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                                    <i class="fas fa-project-diagram fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($project['project_name']); ?></h5>
                                    <span class="badge bg-<?php 
                                        echo ($project['project_type'] === 'software') ? 'primary' : 'success'; 
                                    ?>"><?php echo htmlspecialchars(ucfirst($project['project_type'])); ?></span>
                                </div>
                                
                                <?php if (!empty($project['classification'])): ?>
                                    <span class="badge bg-secondary mb-2"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($project['classification']))); ?></span>
                                <?php endif; ?>
                                
                                <p class="card-text text-muted small mb-3">
                                    <?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>
                                    <?php if (strlen($project['description']) > 100): ?>...<?php endif; ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                    <a href="#" class="btn btn-sm btn-danger remove-bookmark" data-id="<?php echo $project['id']; ?>">
                                        <i class="fas fa-trash me-1"></i> Remove
                                    </a>
                                </div>
                                
                                <div class="mt-2 small text-muted">
                                    <i class="far fa-clock me-1"></i> Bookmarked: <?php echo date('M d, Y', strtotime($project['bookmarked_at'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle sidebar
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const overlay = document.getElementById('overlay');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
        
        if (overlay) {
            overlay.addEventListener('click', toggleSidebar);
        }
    });

    // Handle remove bookmark functionality
    $(document).ready(function() {
        $('.remove-bookmark').click(function(e) {
            e.preventDefault();
            const projectId = $(this).data('id');
            const card = $(this).closest('.col-md-6');
            
            if (confirm('Are you sure you want to remove this bookmark?')) {
                $.ajax({
                    url: 'forms/remove_bookmark.php',
                    type: 'POST',
                    data: { project_id: projectId },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // Remove the card with animation
                            card.fadeOut(300, function() {
                                $(this).remove();
                                // If no more bookmarks, show the empty message
                                if ($('.project-card').length === 0) {
                                    $('.row.g-4').html('<div class="alert alert-info w-100"><i class="fas fa-info-circle me-2"></i> You haven\'t bookmarked any projects yet.</div>');
                                }
                            });
                        } else {
                            alert('Error removing bookmark: ' + result.message);
                        }
                    },
                    error: function() {
                        alert('Error communicating with the server');
                    }
                });
            }
        });
    });
</script>
</body>
</html> 