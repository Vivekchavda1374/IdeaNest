<?php
include '../Login/Login/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get the user's name from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User";
$user_initial = !empty($user_name) ? substr($user_name, 0, 1) : "U";

// Fetch project count
$sql = "SELECT COUNT(*) AS project_count FROM projects"; // Replace 'projects' with your actual table name
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$projectCount = $row['project_count'];

$blogSql = "SELECT COUNT(*) AS blog_count FROM blog";
$blogResult = $conn->query($blogSql);
$blogRow = $blogResult->fetch_assoc();
$blogCount = $blogRow['blog_count'];


// Fetch user email based on user_id from session
$user_id = $_SESSION['user_id'];
$emailSql = "SELECT email FROM register WHERE id = ?"; // Assuming 'users' is your users table and 'id' is the primary key
$stmt = $conn->prepare($emailSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$emailResult = $stmt->get_result();

if ($emailResult->num_rows > 0) {
    $emailRow = $emailResult->fetch_assoc();
    $user_email = $emailRow['email'];
    // Store in session for future use
    $_SESSION['email'] = $user_email;
} else {
    $user_email = "user@example.com"; // Default email if not found
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
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
            <a href="#" class="active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="projects_view.php">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
        </li>
        <li>
            <a href="./Blog/list-project.php">
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
            <a href="../bksony/bookmark/bookmark.php">
                <i class="fas fa-bookmark"></i>
                <span>Bookmarks</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Top Navigation -->
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container-fluid">
            <!-- Sidebar Toggle Button -->
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
                <!-- Notifications Dropdown -->



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
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Account Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../Login/Login/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>


    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Welcome, <?php echo htmlspecialchars($user_name); ?></h2>
            <a href="./forms/new_project_add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> New Project</a>
        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-primary">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 text-white-50">My Projects</p>
                                <h3 class="mt-2 mb-0 text-white"><?php echo $projectCount; ?></h3>
                            </div>
                            <div class="icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card" style="background-color: #4cc9f0;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 text-white-50"> My Published Ideas</p>
                                <h3 class="mt-2 mb-0 text-white"><?php echo $blogCount; ?></h3>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card" style="background-color: #f72585;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 text-white-50">Upcoming Featuer </p>
                                <h3 class="mt-2 mb-0 text-white"> Mentor Sessions</h3>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card" style="background-color: #3f37c9;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 text-white-50">Bookmarks</p>
                                <h3 class="mt-2 mb-0 text-white">17</h3>
                            </div>
                            <div class="icon">
                                <i class="fas fa-bookmark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Cards -->
        <div class="row g-4 mb-4">


           <?php
// Method 1: include
// If file is not found, PHP will issue a warning but continue execution
include './forms/progressbar.php';
include './forms/progressbar_idea.php';
?>

            <!-- Recent Activity and idea Posts -->
            <div class="row g-4">
                <!-- Recent Activity -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Activity</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" id="activityDropdown"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="activityDropdown">
                                    <li><a class="dropdown-item" href="#">All Activities</a></li>
                                    <li><a class="dropdown-item" href="#">Filter by Type</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="recent-activity">
                                <div class="item p-3">
                                    <div class="avatar" style="background-color: #4cc9f0;">
                                        <i class="fas fa-comment-alt"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1">You commented on <span class="fw-medium">Mobile App
                                                    Design</span></p>
                                        <p class="text-muted small mb-0">2 hours ago</p>
                                    </div>
                                </div>
                                <div class="item p-3">
                                    <div class="avatar" style="background-color: #f72585;">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1">You published a new idea <span class="fw-medium">UX Design
                                                    Principles</span></p>
                                        <p class="text-muted small mb-0">Yesterday at 3:45 PM</p>
                                    </div>
                                </div>
                                <div class="item p-3">
                                    <div class="avatar" style="background-color: #4361ee;">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1">You completed <span class="fw-medium">Frontend
                                                    Development</span> milestone</p>
                                        <p class="text-muted small mb-0">Feb 28, 2025</p>
                                    </div>
                                </div>
                                <div class="item p-3">
                                    <div class="avatar" style="background-color: #3f37c9;">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1">Mentor session with <span class="fw-medium">Sarah
                                                    Johnson</span>
                                            completed</p>
                                        <p class="text-muted small mb-0">Feb 27, 2025</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 text-center">
                            <button class="btn btn-outline-primary btn-sm">View All Activity</button>
                        </div>
                    </div>
                </div>

                <!-- Latest idea Posts -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Latest Idea Posts</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" id="blogDropdown"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="blogDropdown">
                                    <li><a class="dropdown-item" href="./Blog/list-project.php">My Posts</a></li>
                                    <li><a class="dropdown-item" href="#>Bookmarked Posts</a></li>
                                    <li><a class="dropdown-item" href="#">Popular Posts</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action p-3">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1">Best Practices for React Development</h6>
                                        <span class="badge bg-primary badge-custom">New</span>
                                    </div>
                                    <p class="text-muted small mb-2">Tips and tricks for optimizing React
                                        applications
                                        and improving performance.</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            <i class="far fa-eye me-1"></i> 245 views
                                            <i class="far fa-comment ms-3 me-1"></i> 18 comments
                                        </div>
                                        <span class="text-muted small">Feb 28, 2025</span>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action p-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">UI Design Principles for Developers</h6>
                                    </div>
                                    <p class="text-muted small mb-2">Essential design concepts every developer
                                        should
                                        know about.</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            <i class="far fa-eye me-1"></i> 187 views
                                            <i class="far fa-comment ms-3 me-1"></i> 12 comments
                                        </div>
                                        <span class="text-muted small">Feb 25, 2025</span>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action p-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Getting Started with Backend Development</h6>
                                    </div>
                                    <p class="text-muted small mb-2">A comprehensive guide to building robust
                                        server-side applications.</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            <i class="far fa-eye me-1"></i> 324 views
                                            <i class="far fa-comment ms-3 me-1"></i> 24 comments
                                        </div>
                                        <span class="text-muted small">Feb 20, 2025</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 text-center">
                            <button class="btn btn-outline-primary btn-sm"> <a href="./Blog/form.php" >Create New Idea</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
        // Add any JavaScript you need for the dashboard here
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle functionality
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('overlay');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    overlay.classList.toggle('active');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                    overlay.classList.remove('active');
                });
            }
        });

        // Search functionality (placeholder - you'll need to implement the actual search)
        function fetchResults() {
            const searchInput = document.getElementById('search');
            const searchResults = document.getElementById('searchResults');
            const searchTerm = searchInput.value.trim();

            if (searchTerm.length > 0) {
                // Show the results container
                searchResults.classList.remove('d-none');
                searchResults.innerHTML = '<p class="text-center py-2"><i class="fas fa-spinner fa-spin me-2"></i>Searching...</p>';

                // Fetch results from the server
                fetch('search_results.php?query=' + encodeURIComponent(searchTerm))
                    .then(response => response.text())
                    .then(data => {
                        searchResults.innerHTML = data;
                    })
                    .catch(error => {
                        searchResults.innerHTML = '<p class="text-center py-2 text-danger">Error fetching results</p>';
                        console.error('Search error:', error);
                    });
            } else {
                // Hide the results container when search is empty
                searchResults.classList.add('d-none');
            }
        }

        // Add event listener to close search results when clicking outside
        document.addEventListener('click', function(event) {
            const searchResults = document.getElementById('searchResults');
            const searchInput = document.getElementById('search');

            if (event.target !== searchInput && !searchResults.contains(event.target)) {
                searchResults.classList.add('d-none');
            }
        });

        // Add event listener to show results when focusing on search input
        document.getElementById('search').addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                document.getElementById('searchResults').classList.remove('d-none');
                fetchResults();
            }
        });
    </script>
</body>
</html>