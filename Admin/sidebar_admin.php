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

<link rel="stylesheet" href="../assets/css/sidebar_admin.css">

<!-- Sidebar Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar JavaScript -->
<script src="../assets/js/sidebar_admin.js"></script>