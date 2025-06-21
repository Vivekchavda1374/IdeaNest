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
$sql = "SELECT COUNT(*) AS project_count FROM admin_approved_projects"; // Replace 'projects' with your actual table name
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$projectCount = $row['project_count'];

$blogSql = "SELECT COUNT(*) AS blog_count FROM blog";
$blogResult = $conn->query($blogSql);
$blogRow = $blogResult->fetch_assoc();
$blogCount = $blogRow['blog_count'];

$bookMarkSql = "SELECT COUNT(*) AS bookmark_count FROM user_bookmarks";
$bookMarkResult = $conn->query($bookMarkSql);
$bookMarkRow = $bookMarkResult->fetch_assoc();
$bookMarkCount = $bookMarkRow['bookmark_count'];


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
    $user_email = "admin@ICT.com"; // Default email if not found
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

    <link href="user/style.css" rel="stylesheet">
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
            <a href="user/user_project_search.php">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
        </li>
        <li>
            <a href="user/Blog/idea_dashboard.php">
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
            <a href="user/bookmarks.php">
                <i class="fas fa-bookmark"></i>
                <span>Bookmarks</span>
            </a>
        </li>

        <li>
            <a href="user/user_profile_setting.php">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        
        <li>
            <a href="user/Blog/idea_bookmarks.php">
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
             <!-- Message Notification -->
                <li class="nav-item ms-2 position-relative">
                    <a href="user/messages.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span id="unreadMessageBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display:none;">
                            0
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    </a>
                </li>

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
                        <li><a class="dropdown-item" href="user/user_profile_setting.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="Login/Login/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>


    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Welcome, <?php echo htmlspecialchars($user_name); ?></h2>
            <a href="user/forms/new_project_add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> New Project</a>
        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-primary">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 text-white-50">Projects</p>
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
                                <p class="mb-0 text-white-50">Ideas</p>
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
                                <p class="mb-0 text-white-50">Upcoming Feature </p>
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
                                <h3 class="mt-2 mb-0 text-white"><?php echo $bookMarkCount; ?></h3>
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
include './forms/progressbar.php';
include './forms/progressbar_idea.php';
?>


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

        // Function to check unread messages
        function checkUnreadMessages() {
            fetch('get_unread_messages.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('unreadMessageBadge');
                    if (data.success && data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Check unread messages on page load and every 5 minutes
        document.addEventListener('DOMContentLoaded', checkUnreadMessages);
        setInterval(checkUnreadMessages, 5 * 60 * 1000);
    </script>
</body>
</html>