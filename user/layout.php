<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($basePath)) { $basePath = './'; }
// Get user info from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User";
$user_initial = !empty($user_name) ? substr($user_name, 0, 1) : "U";
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : "admin@ICT.com";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest - Innovation Platform</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo $basePath; ?>style.css" rel="stylesheet">
</head>
<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-lightbulb"></i>
        <div class="logo">IdeaNest</div>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="<?php echo $basePath; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $basePath; ?>../Admin/user_project_search.php">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $basePath; ?>Blog/list-project.php">
                <i class="fas fa-lightbulb"></i>
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
            <a href="<?php echo $basePath; ?>bookmark.php">
                <i class="fas fa-bookmark"></i>
                <span>Bookmarks</span>
            </a>
        </li>
        <li>
            <a href="<?php echo $basePath; ?>user_profile_setting.php">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
</div>

<!-- Main Content Start -->
<div class="main-content" id="mainContent">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <button id="sidebarToggle" class="btn btn-light d-lg-none me-3">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Search Form -->
            <div class="d-none d-md-block ms-lg-4 flex-grow-1 me-auto">
                <div class="search-bar">
                    <input type="text" id="search" class="form-control" placeholder="Search projects, ideas, mentors..." onkeyup="fetchResults()">
                    <i class="fas fa-search"></i>
                    <div id="searchResults" class="position-absolute start-0 bg-white shadow-lg rounded-3 mt-1 w-100 p-2 d-none" style="z-index: 1000; max-height: 300px; overflow-y: auto;"></div>
                </div>
            </div>
            
            <!-- Right-side menu items -->
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- User Profile Dropdown -->
                <li class="nav-item dropdown ms-2">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                            <span class="text-white fw-medium"><?php echo htmlspecialchars($user_initial); ?></span>
                        </div>
                        <span class="d-none d-lg-inline fw-medium"><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <div class="dropdown-item text-center">
                                <div class="user-avatar rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 60px; height: 60px;">
                                    <span class="text-white fw-bold"><?php echo htmlspecialchars($user_initial); ?></span>
                                </div>
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($user_name); ?></h6>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($user_email); ?></p>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $basePath; ?>user_profile_setting.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo $basePath; ?>../Login/Login/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <!-- Page content should start after this include -->
</body>
</html> 