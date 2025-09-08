<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($basePath)) { $basePath = './'; }

// Get user info from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "vivek";
$user_initial = !empty($user_name) ? strtoupper(substr($user_name, 0, 1)) : "V";

// Get current page to set active state
$current_page = basename($_SERVER['PHP_SELF']);

// Check if we're in a subdirectory (like Blog)
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$is_in_subdirectory = ($current_dir !== 'user' && $current_dir !== 'IdeaNest');

// Adjust base path for subdirectories
if ($is_in_subdirectory) {
    $basePath = '../';
} else {
    $basePath = './';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest</title>
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>../assets/css/layout_user.css">
    <!-- JavaScript -->
    <script src="<?php echo $basePath; ?>../assets/js/layout_user.js" defer></script>
</head>
<body>
<!-- Mobile Menu Toggle Button -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation menu">
    <i class="fas fa-bars"></i>
</button>

<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <i class="fas fa-lightbulb"></i>
            </div>
            <div class="sidebar-logo-text">IdeaNest</div>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><?php echo htmlspecialchars($user_initial); ?></div>
            <div class="sidebar-user-info">
                <h4><?php echo htmlspecialchars($user_name); ?></h4>
                <p>Innovator</p>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="<?php echo $basePath; ?>index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="<?php echo $basePath; ?>all_projects.php" class="nav-item <?php echo ($current_page == 'all_projects.php') ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram nav-icon"></i>
                <span class="nav-text">My Projects</span>
            </a>
            <a href="<?php echo $basePath; ?>Blog/form.php" class="nav-item <?php echo ($current_page == 'form.php' && $current_dir == 'Blog') ? 'active' : ''; ?>">
                <i class="fas fa-lightbulb nav-icon"></i>
                <span class="nav-text">Ideas</span>
            </a>
            <a href="<?php echo $basePath; ?>bookmark.php" class="nav-item <?php echo ($current_page == 'bookmark.php') ? 'active' : ''; ?>">
                <i class="fas fa-bookmark nav-icon"></i>
                <span class="nav-text">Bookmarks</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Create</div>
            <a href="<?php echo $basePath; ?>forms/new_project_add.php" class="nav-item <?php echo ($current_page == 'new_project_add.php') ? 'active' : ''; ?>">
                <i class="fas fa-plus nav-icon"></i>
                <span class="nav-text">New Project</span>
            </a>
            <a href="<?php echo $basePath; ?>Blog/form.php" class="nav-item <?php echo ($current_page == 'form.php' && $current_dir == 'Blog') ? 'active' : ''; ?>">
                <i class="fas fa-edit nav-icon"></i>
                <span class="nav-text">New Idea</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Community</div>
            <a href="<?php echo $basePath; ?>all_projects.php" class="nav-item <?php echo ($current_page == 'all_projects.php') ? 'active' : ''; ?>">
                <i class="fas fa-users nav-icon"></i>
                <span class="nav-text">Explore Projects</span>
            </a>
            <a href="<?php echo $basePath; ?>Blog/list-project.php" class="nav-item <?php echo ($current_page == 'list-project.php' && $current_dir == 'Blog') ? 'active' : ''; ?>">
                <i class="fas fa-list nav-icon"></i>
                <span class="nav-text">All Ideas</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="<?php echo $basePath; ?>user_profile_setting.php" class="nav-item <?php echo ($current_page == 'user_profile_setting.php') ? 'active' : ''; ?>">
                <i class="fas fa-user nav-icon"></i>
                <span class="nav-text">Profile</span>
            </a>
            <a href="<?php echo $basePath; ?>github_profile_simple.php" class="nav-item <?php echo ($current_page == 'github_profile_simple.php') ? 'active' : ''; ?>">
                <i class="fab fa-github nav-icon"></i>
                <span class="nav-text">GitHub</span>
            </a>
            <a href="<?php echo $basePath; ?>user_profile_setting.php" class="nav-item <?php echo ($current_page == 'user_profile_setting.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog nav-icon"></i>
                <span class="nav-text">Settings</span>
            </a>
            <a href="../Login/Login/logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt nav-icon"></i>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </nav>
</aside>
</body>
</html>
