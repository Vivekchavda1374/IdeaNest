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

<style>
/* Sidebar Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width, 280px);
    height: 100vh;
    background: var(--white, #ffffff);
    border-right: 1px solid var(--gray-200, #e2e8f0);
    z-index: 1000;
    overflow-y: auto;
    transition: transform 0.3s ease;
    box-shadow: var(--shadow-lg, 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1));
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200, #e2e8f0);
    background: linear-gradient(135deg, var(--primary-color, #6366f1), var(--secondary-color, #8b5cf6));
    color: var(--white, #ffffff);
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.sidebar-logo-icon {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: var(--border-radius, 12px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.sidebar-logo-text {
    font-size: 1.5rem;
    font-weight: 700;
}

.sidebar-user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.sidebar-user-avatar {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.sidebar-user-info h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.sidebar-user-info p {
    font-size: 0.9rem;
    opacity: 0.8;
    margin: 0;
}

.sidebar-nav {
    padding: 1.5rem 0;
}

.nav-section {
    margin-bottom: 2rem;
    padding: 0 1.5rem;
}

.nav-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--gray-500, #64748b);
    margin-bottom: 1rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius, 12px);
    color: var(--gray-700, #334155);
    text-decoration: none;
    transition: all 0.2s ease;
    margin-bottom: 0.25rem;
    position: relative;
}

.nav-item:hover {
    background: var(--gray-100, #f1f5f9);
    color: var(--primary-color, #6366f1);
    transform: translateX(4px);
}

.nav-item.active {
    background: linear-gradient(135deg, var(--primary-color, #6366f1), var(--secondary-color, #8b5cf6));
    color: var(--white, #ffffff);
    box-shadow: var(--shadow-md, 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1));
}

.nav-item.active .nav-icon {
    color: var(--white, #ffffff);
    opacity: 1;
}

.nav-item.active .nav-text {
    color: var(--white, #ffffff);
    font-weight: 600;
}

.nav-icon {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.nav-text {
    flex: 1;
    font-weight: 500;
}

.nav-badge {
    background: var(--danger-color, #ef4444);
    color: var(--white, #ffffff);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Mobile Menu Toggle Button */
.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: linear-gradient(135deg, var(--primary-color, #6366f1), var(--secondary-color, #8b5cf6));
    border: none;
    border-radius: 8px;
    padding: 12px;
    color: var(--white, #ffffff);
    font-size: 1.2rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    cursor: pointer;
}

.mobile-menu-toggle:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.mobile-menu-toggle:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
}

/* Mobile responsive */
@media (max-width: 1024px) {
    .mobile-menu-toggle {
        display: block;
    }
    
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .sidebar.open {
        transform: translateX(0);
    }
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    display: none;
}

.overlay.active {
    display: block;
}
</style>

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
            <a href="<?php echo $basePath; ?>search.php" class="nav-item <?php echo ($current_page == 'search.php') ? 'active' : ''; ?>">
                <i class="fas fa-search nav-icon"></i>
                <span class="nav-text">Search</span>
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
            <a href="<?php echo $basePath; ?>search.php" class="nav-item <?php echo ($current_page == 'search.php') ? 'active' : ''; ?>">
                <i class="fas fa-search nav-icon"></i>
                <span class="nav-text">Search</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="<?php echo $basePath; ?>user_profile_setting.php" class="nav-item <?php echo ($current_page == 'user_profile_setting.php') ? 'active' : ''; ?>">
                <i class="fas fa-user nav-icon"></i>
                <span class="nav-text">Profile</span>
            </a>
            <a href="<?php echo $basePath; ?>user_profile_setting.php" class="nav-item <?php echo ($current_page == 'user_profile_setting.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog nav-icon"></i>
                <span class="nav-text">Settings</span>
            </a>
            <a href="<?php echo $basePath; ?>Login/Login/logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt nav-icon"></i>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </nav>
</aside>

<script>
// Sidebar functionality
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            
            // Change icon based on state
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('open')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        });
    }

    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            
            // Reset icon
            if (mobileMenuToggle) {
                const icon = mobileMenuToggle.querySelector('i');
                icon.className = 'fas fa-bars';
            }
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 1024) {
            if (sidebar && !sidebar.contains(event.target) && 
                mobileMenuToggle && !mobileMenuToggle.contains(event.target)) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        }
    });

    // Navigation item click handlers - only for mobile sidebar closing
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Close sidebar on mobile after clicking (but allow navigation to proceed)
            if (window.innerWidth <= 1024) {
                setTimeout(() => {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                    
                    // Reset icon
                    if (mobileMenuToggle) {
                        const icon = mobileMenuToggle.querySelector('i');
                        icon.className = 'fas fa-bars';
                    }
                }, 100); // Small delay to allow navigation to start
            }
        });
    });

    // Responsive sidebar handling
    function handleResize() {
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            
            // Reset icon
            if (mobileMenuToggle) {
                const icon = mobileMenuToggle.querySelector('i');
                icon.className = 'fas fa-bars';
            }
        }
    }

    window.addEventListener('resize', handleResize);
});
</script>