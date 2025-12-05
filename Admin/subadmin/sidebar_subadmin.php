<?php
require_once __DIR__ . '/../../includes/security_init.php';
// layout.php - Main layout template
function renderLayout($title, $content, $activePage = '')
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../assets/js/anti_injection.js"></script>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - IdeaNest Admin</title>
    <link rel="icon" type="image/png" href="../../assets/image/fevicon.png">

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar_subadmin.css">
        <link rel="stylesheet" href="../../assets/css/loader.css">
</head>
    <body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="bi bi-lightbulb-fill"></i>
                <span>IdeaNest</span>
            </a>
        </div>

        <div class="sidebar-content">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="dashboard.php" class="sidebar-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" onclick="showPageLoading()">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="profile.php" class="sidebar-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>" onclick="showPageLoading()">
                        <i class="bi bi-person-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="assigned_projects.php" class="sidebar-link <?php echo $activePage === 'projects' ? 'active' : ''; ?>" onclick="showPageLoading()">
                        <i class="bi bi-kanban-fill"></i>
                        <span>Assigned Projects</span>
                    </a>
                </li>


                <li class="sidebar-item">
                    <a href="support.php" class="sidebar-link <?php echo $activePage === 'support' ? 'active' : ''; ?>" onclick="showPageLoading()">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Support</span>
                    </a>
                </li>
            </ul>

            <hr class="sidebar-divider">


        </div>

        <div class="sidebar-footer">
            <a href="../../Login/Login/logout.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                <i class="bi bi-box-arrow-right me-2"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <button class="mobile-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>

            <h1 class="page-title"><?php echo htmlspecialchars($title); ?></h1>

            <div class="topbar-actions">
                <div class="dropdown">
                    <div class="user-avatar" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="profile.php">
                                <i class="bi bi-person me-2"></i>
                                <span>Profile</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="settings.php">
                                <i class="bi bi-gear me-2"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center text-danger" href="../../Login/Login/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <?php echo $content; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/sidebar_subadmin.js"></script>
    <script>
        function showPageLoading() {
            if (window.loadingManager) {
                window.loadingManager.show('Loading page...');
            }
        }
    </script>
    
<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../../assets/js/loader.js"></script>
</body>
    </html>
    <?php
}
?>