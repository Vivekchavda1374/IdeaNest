<?php
// sidebar.php - Common Sidebar Component for Admin Panel (Dynamic Paths)

// Get current page name to set active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Automatically detect the admin base path
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Extract base path dynamically
if (strpos($request_uri, '/Admin/') !== false) {
    $base_admin_path = substr($request_uri, 0, strpos($request_uri, '/Admin/') + 7);
} else {
    // Fallback method
    $base_admin_path = dirname($_SERVER['SCRIPT_NAME']) . '/';
    if (strpos($base_admin_path, '/subadmin') !== false) {
        $base_admin_path = str_replace('/subadmin', '', $base_admin_path);
    }
}

// Ensure path ends with /
if (substr($base_admin_path, -1) !== '/') {
    $base_admin_path .= '/';
}

// Define menu items with their corresponding pages using dynamic base path
$menu_items = [
        [
                'icon' => 'bi-grid-1x2',
                'title' => 'Dashboard',
                'url' => $base_admin_path . 'admin.php',
                'page' => 'admin.php'
        ],
        [
                'icon' => 'bi-kanban',
                'title' => 'Projects',
                'url' => $base_admin_path . 'admin_view_project.php',
                'page' => 'admin_view_project.php'
        ],
        [
                'icon' => 'bi-people',
                'title' => 'Users Management',
                'url' => $base_admin_path . 'user_manage_by_admin.php',
                'page' => 'user_manage_by_admin.php'
        ],
        [
                'icon' => 'bi-person-plus',
                'title' => 'Add Subadmin',
                'url' => $base_admin_path . 'subadmin/add_subadmin.php',
                'page' => 'add_subadmin.php'
        ],
        [
                'icon' => 'bi-bell',
                'title' => 'Notifications',
                'url' => $base_admin_path . 'notifications.php',
                'page' => 'notifications.php'
        ],
        [
                'icon' => 'bi-gear',
                'title' => 'Settings',
                'url' => $base_admin_path . 'settings.php',
                'page' => 'settings.php'
        ]
];

// Site configuration
$site_name = isset($site_name) ? $site_name : "IdeaNest Admin";
$logout_url = isset($logout_url) ? $logout_url : $base_admin_path . "logout.php";
?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo $base_admin_path; ?>admin.php" class="sidebar-brand">
            <i class="bi bi-lightbulb"></i>
            <span><?php echo htmlspecialchars($site_name); ?></span>
        </a>
    </div>
    <ul class="sidebar-menu">
        <?php foreach ($menu_items as $item): ?>
            <li class="sidebar-item">
                <a href="<?php echo $item['url']; ?>"
                   class="sidebar-link <?php echo ($current_page == $item['page']) ? 'active' : ''; ?>">
                    <i class="bi <?php echo $item['icon']; ?>"></i>
                    <span><?php echo $item['title']; ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="sidebar-footer">
        <a href="<?php echo $logout_url; ?>" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>

<!-- Sidebar CSS Styles -->
<style>
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 250px;
        background-color: #fff;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
        z-index: 1000;
        transition: all 0.3s;
        overflow-y: auto;
        padding: 1rem;
    }

    .sidebar-header {
        padding: 1rem 0;
        text-align: center;
        border-bottom: 1px solid #f1f1f1;
        margin-bottom: 1rem;
    }

    .sidebar-brand {
        font-size: 1.5rem;
        font-weight: 600;
        color: #4361ee;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .sidebar-brand:hover {
        color: #4361ee;
        text-decoration: none;
    }

    .sidebar-brand i {
        margin-right: 0.5rem;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-item {
        margin-bottom: 0.5rem;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: #6c757d;
        text-decoration: none;
        border-radius: 0.25rem;
        transition: all 0.2s;
    }

    .sidebar-link i {
        margin-right: 0.75rem;
        font-size: 1.25rem;
        width: 20px;
        text-align: center;
    }

    .sidebar-link.active {
        background-color: #4361ee;
        color: #fff;
    }

    .sidebar-link:hover:not(.active) {
        background-color: #f8f9fa;
        color: #4361ee;
        text-decoration: none;
    }

    .sidebar-link:hover {
        text-decoration: none;
    }

    .sidebar-divider {
        margin: 1rem 0;
        border-top: 1px solid #f1f1f1;
    }

    .sidebar-footer {
        padding: 1rem 0;
        border-top: 1px solid #f1f1f1;
        margin-top: 1rem;
    }

    /* Main Content Adjustment */
    .main-content {
        margin-left: 250px;
        padding: 1rem;
        transition: all 0.3s;
        min-height: 100vh;
    }

    /* Mobile Responsive */
    @media (max-width: 991.98px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
        }

        .main-content.pushed {
            margin-left: 250px;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }
    }

    /* Topbar styles for mobile toggle */
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 0;
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
    }

    .topbar-actions {
        display: flex;
        align-items: center;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #4361ee;
        margin-left: 1rem;
        text-decoration: none;
    }

    .user-avatar:hover {
        background-color: #e9ecef;
        color: #4361ee;
        text-decoration: none;
    }

    #sidebarToggle {
        border: none;
        background: none;
        font-size: 1.5rem;
        color: #6c757d;
        padding: 0.25rem 0.5rem;
    }

    #sidebarToggle:hover {
        color: #4361ee;
    }
</style>

<!-- Sidebar Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Toggle Elements
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.querySelector('.main-content');

        // Toggle Sidebar Function
        function toggleSidebar() {
            if (sidebar) {
                sidebar.classList.toggle('show');
            }
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('show');
            }
            if (mainContent) {
                mainContent.classList.toggle('pushed');
            }
        }

        // Close Sidebar Function
        function closeSidebar() {
            if (sidebar) {
                sidebar.classList.remove('show');
            }
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('show');
            }
            if (mainContent) {
                mainContent.classList.remove('pushed');
            }
        }

        // Event Listeners
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 991.98) {
                const isClickInsideSidebar = sidebar && sidebar.contains(event.target);
                const isToggleButton = sidebarToggle && sidebarToggle.contains(event.target);

                if (!isClickInsideSidebar && !isToggleButton && sidebar && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991.98) {
                closeSidebar();
            }
        });
    });
</script>