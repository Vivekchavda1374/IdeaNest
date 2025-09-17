<?php
// sidebar.php - Common Sidebar Component for Admin Panel

// Get current page name to set active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Set web base path for links
$web_base_path = '/IdeaNest/Admin/';

// Define menu items with their corresponding pages
$menu_items = [
        [
                'icon' => 'bi-grid-1x2',
                'title' => 'Dashboard',
                'url' => $web_base_path . 'admin.php',
                'page' => 'admin.php'
        ],
        [
                'icon' => 'bi-speedometer2',
                'title' => 'Overview',
                'url' => $web_base_path . 'overview.php',
                'page' => 'overview.php'
        ],
        [
                'icon' => 'bi-graph-up',
                'title' => 'System Analytics',
                'url' => $web_base_path . 'system_analytics.php',
                'page' => 'system_analytics.php'
        ],
        [
                'icon' => 'bi-download',
                'title' => 'Export Overview',
                'url' => $web_base_path . 'export_overview.php',
                'page' => 'export_overview.php'
        ],
        [
                'icon' => 'bi-kanban',
                'title' => 'Projects',
                'url' => $web_base_path . 'admin_view_project.php',
                'page' => 'admin_view_project.php'
        ],
        [
                'icon' => 'bi-people',
                'title' => 'Users Management',
                'url' => $web_base_path . 'user_manage_by_admin.php',
                'page' => 'user_manage_by_admin.php'
        ],
        [
                'icon' => 'bi-person-workspace',
                'title' => 'Manage Mentors',
                'url' => $web_base_path . 'manage_mentors.php',
                'page' => 'manage_mentors.php'
        ],
        [
                'icon' => 'bi-person-plus',
                'title' => 'Subadmin Overview',
                'url' => $web_base_path . 'subadmin_overview.php',
                'page' => 'subadmin_overview.php'
        ],
        [
                'icon' => 'bi-plus-circle',
                'title' => 'Add Subadmin',
                'url' => $web_base_path . 'subadmin/add_subadmin.php',
                'page' => 'add_subadmin.php'
        ],
        [
                'icon' => 'bi-bell',
                'title' => 'Notifications',
                'url' => $web_base_path . 'notifications.php',
                'page' => 'notifications.php'
        ],
        [
                'icon' => 'bi-gear',
                'title' => 'Settings',
                'url' => $web_base_path . 'settings.php',
                'page' => 'settings.php'
        ]
];

// Site configuration
$site_name = isset($site_name) ? $site_name : "IdeaNest Admin";
$logout_url = isset($logout_url) ? $logout_url : $web_base_path . "logout.php";
?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo $web_base_path; ?>admin.php" class="sidebar-brand">
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

<link rel="stylesheet" href="../assets/css/sidebar_admin.css">
<style>
.sidebar-divider {
    margin: 15px 0 10px 0;
    padding: 0 20px;
}
.sidebar-divider-text {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}
</style>

<!-- Sidebar Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar JavaScript -->
<script src="../assets/js/sidebar_admin.js"></script>