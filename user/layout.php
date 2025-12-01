<?php
require_once __DIR__ . '/../includes/security_init.php';
require_once __DIR__ . '/../includes/html_helpers.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($basePath)) {
    $basePath = './';
}

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

<!-- Layout Styles and Scripts -->
<link rel="stylesheet" href="<?php echo $basePath; ?>../assets/css/layout_user.css">
<link rel="stylesheet" href="<?php echo $basePath; ?>../assets/css/loading.css">
<link rel="stylesheet" href="<?php echo $basePath; ?>../assets/css/loader.css">
<link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/educational-ui.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- Fallback styles for layout -->
<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    z-index: 1000;
    overflow-y: auto;
    transition: transform 0.3s ease;
}

.sidebar-header {
    padding: 2rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.sidebar-logo-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.sidebar-logo-text {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
}

.sidebar-user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.sidebar-user-avatar {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.1rem;
}

.sidebar-user-info h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.sidebar-user-info p {
    margin: 0;
    font-size: 0.85rem;
    opacity: 0.8;
}

.sidebar-nav {
    padding: 1rem 0;
}

.nav-section {
    margin-bottom: 1.5rem;
}

.nav-section-title {
    padding: 0 1.5rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.7;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    text-decoration: none;
}

.nav-item.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: white;
}

.nav-icon {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.nav-text {
    font-weight: 500;
}

.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1001;
    background: #667eea;
    color: white;
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 8px;
    font-size: 1.2rem;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .mobile-menu-toggle {
        display: block;
    }
    
    .overlay.active {
        display: block;
    }
}

/* Main content adjustment */
.main-content {
    margin-left: 280px;
    min-height: 100vh;
    background: #f8fafc;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }
}
</style>

<script src="<?php echo $basePath; ?>../assets/js/loading.js"></script>
<script src="<?php echo $basePath; ?>../assets/js/loader.js"></script>
<script src="<?php echo $basePath; ?>../assets/js/layout_user.js" defer></script>

<!-- Fallback JavaScript for mobile menu -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (mobileToggle && sidebar && overlay) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
});
</script>
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
            <div class="sidebar-user-avatar"><?php echo safe_html($user_initial); ?></div>
            <div class="sidebar-user-info">
                <h4><?php echo safe_html($user_name); ?></h4>
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
            <a href="<?php echo $basePath; ?>select_mentor.php" class="nav-item <?php echo ($current_page == 'select_mentor.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate nav-icon"></i>
                <span class="nav-text">Find Mentor</span>
            </a>
            <a href="<?php echo $basePath; ?>my_mentor_requests.php" class="nav-item <?php echo ($current_page == 'my_mentor_requests.php') ? 'active' : ''; ?>">
                <i class="fas fa-paper-plane nav-icon"></i>
                <span class="nav-text">My Requests</span>
            </a>
            <a href="<?php echo $basePath; ?>mentor_activities.php" class="nav-item <?php echo ($current_page == 'mentor_activities.php') ? 'active' : ''; ?>">
                <i class="fas fa-history nav-icon"></i>
                <span class="nav-text">Mentor Activities</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="<?php echo $basePath; ?>chat/index.php" class="nav-item <?php echo ($current_page == 'index.php' && $current_dir == 'chat') ? 'active' : ''; ?>">
                <i class="fas fa-comments nav-icon"></i>
                <span class="nav-text">Messages</span>
            </a>
            <a href="<?php echo $basePath; ?>user_profile_setting.php" class="nav-item <?php echo ($current_page == 'user_profile_setting.php') ? 'active' : ''; ?>">
                <i class="fas fa-user nav-icon"></i>
                <span class="nav-text">Profile</span>
            </a>
            <a href="<?php echo $basePath; ?>user_profile_setting.php" class="nav-item <?php echo ($current_page == 'user_profile_setting.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog nav-icon"></i>
                <span class="nav-text">Settings</span>
            </a>
            <a href="<?php echo $basePath; ?>../Login/Login/logout.php" class="nav-item" onclick="showLoader('Logging out...')">
                <i class="fas fa-sign-out-alt nav-icon"></i>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </nav>
</aside>
