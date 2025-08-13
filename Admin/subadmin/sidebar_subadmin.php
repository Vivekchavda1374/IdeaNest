<?php
// layout.php - Main layout template
function renderLayout($title, $content, $activePage = '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - IdeaNest Admin</title>

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <style>
            :root {
                --primary-color: #4f46e5;
                --primary-light: #6366f1;
                --primary-dark: #3730a3;
                --secondary-color: #64748b;
                --success-color: #059669;
                --warning-color: #d97706;
                --danger-color: #dc2626;
                --light-bg: #f8fafc;
                --card-bg: rgba(255, 255, 255, 0.95);
                --sidebar-bg: rgba(255, 255, 255, 0.98);
                --text-primary: #1e293b;
                --text-secondary: #64748b;
                --border-color: #e2e8f0;
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                background: linear-gradient(135deg, #e0e7ff 0%, #f1f5f9 50%, #f8fafc 100%);
                color: var(--text-primary);
                min-height: 100vh;
                font-size: 14px;
                line-height: 1.6;
            }

            /* Sidebar Styles */
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 280px;
                background: var(--sidebar-bg);
                backdrop-filter: blur(20px);
                border-right: 1px solid var(--border-color);
                z-index: 1000;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex;
                flex-direction: column;
                box-shadow: var(--shadow-xl);
            }

            .sidebar-header {
                padding: 2rem 1.5rem 1.5rem;
                border-bottom: 1px solid var(--border-color);
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
                color: white;
            }

            .sidebar-brand {
                display: flex;
                align-items: center;
                font-size: 1.5rem;
                font-weight: 700;
                color: white;
                text-decoration: none;
                letter-spacing: -0.025em;
            }

            .sidebar-brand i {
                margin-right: 0.75rem;
                font-size: 1.75rem;
            }

            .sidebar-content {
                flex: 1;
                padding: 1.5rem 1rem;
                overflow-y: auto;
            }

            .sidebar-menu {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .sidebar-item {
                margin-bottom: 0.5rem;
            }

            .sidebar-link {
                display: flex;
                align-items: center;
                padding: 0.875rem 1rem;
                color: var(--text-secondary);
                text-decoration: none;
                border-radius: 0.75rem;
                font-weight: 500;
                font-size: 14px;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }

            .sidebar-link i {
                margin-right: 0.875rem;
                font-size: 1.25rem;
                width: 20px;
                text-align: center;
            }

            .sidebar-link:hover {
                background: var(--light-bg);
                color: var(--primary-color);
                transform: translateX(4px);
            }

            .sidebar-link.active {
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
                color: white;
                box-shadow: var(--shadow-md);
            }

            .sidebar-link.active::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: rgba(255, 255, 255, 0.8);
                border-radius: 0 4px 4px 0;
            }

            .sidebar-divider {
                margin: 1.5rem 0;
                border: 0;
                border-top: 1px solid var(--border-color);
            }

            .sidebar-footer {
                padding: 1rem;
                border-top: 1px solid var(--border-color);
                background: var(--light-bg);
            }

            /* Main Content */
            .main-content {
                margin-left: 280px;
                min-height: 100vh;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .topbar {
                background: var(--card-bg);
                backdrop-filter: blur(20px);
                border-bottom: 1px solid var(--border-color);
                padding: 1.25rem 2rem;
                display: flex;
                align-items: center;
                justify-content: between;
                position: sticky;
                top: 0;
                z-index: 900;
                box-shadow: var(--shadow-sm);
            }

            .page-title {
                font-size: 1.875rem;
                font-weight: 700;
                color: var(--text-primary);
                margin: 0;
                letter-spacing: -0.025em;
            }

            .topbar-actions {
                margin-left: auto;
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 1.125rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                box-shadow: var(--shadow-md);
            }

            .user-avatar:hover {
                transform: scale(1.05);
                box-shadow: var(--shadow-lg);
            }

            .content-area {
                padding: 2rem;
            }

            /* Cards */
            .glass-card {
                background: var(--card-bg);
                backdrop-filter: blur(20px);
                border: 1px solid var(--border-color);
                border-radius: 1rem;
                box-shadow: var(--shadow-lg);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                overflow: hidden;
            }

            .glass-card:hover {
                transform: translateY(-2px);
                box-shadow: var(--shadow-xl);
            }

            /* Form Styles */
            .form-label {
                font-weight: 600;
                color: var(--text-primary);
                margin-bottom: 0.5rem;
                font-size: 14px;
            }

            .form-control, .form-select {
                border: 2px solid var(--border-color);
                border-radius: 0.5rem;
                padding: 0.75rem 1rem;
                font-size: 14px;
                transition: all 0.2s;
                background: white;
            }

            .form-control:focus, .form-select:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            }

            /* Buttons */
            .btn {
                font-weight: 600;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                font-size: 14px;
                transition: all 0.2s;
                border: none;
            }

            .btn-primary {
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
                color: white;
                box-shadow: var(--shadow-md);
            }

            .btn-primary:hover {
                background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
                transform: translateY(-1px);
                box-shadow: var(--shadow-lg);
            }

            .btn-outline-primary {
                border: 2px solid var(--primary-color);
                color: var(--primary-color);
                background: transparent;
            }

            .btn-outline-primary:hover {
                background: var(--primary-color);
                color: white;
                transform: translateY(-1px);
            }

            /* Alerts */
            .alert {
                border: none;
                border-radius: 0.75rem;
                padding: 1rem 1.25rem;
                font-weight: 500;
                box-shadow: var(--shadow-sm);
            }

            .alert-success {
                background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                color: var(--success-color);
                border-left: 4px solid var(--success-color);
            }

            .alert-danger {
                background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                color: var(--danger-color);
                border-left: 4px solid var(--danger-color);
            }

            .alert-warning {
                background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                color: var(--warning-color);
                border-left: 4px solid var(--warning-color);
            }

            /* Modal */
            .modal-content {
                border: none;
                border-radius: 1rem;
                box-shadow: var(--shadow-xl);
            }

            .modal-header {
                border-bottom: 1px solid var(--border-color);
                padding: 1.5rem;
            }

            .modal-title {
                font-weight: 700;
                color: var(--text-primary);
            }

            .modal-body {
                padding: 1.5rem;
            }

            .modal-footer {
                border-top: 1px solid var(--border-color);
                padding: 1.5rem;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .sidebar {
                    transform: translateX(-100%);
                    width: 280px;
                }

                .sidebar.show {
                    transform: translateX(0);
                }

                .main-content {
                    margin-left: 0;
                }

                .topbar {
                    padding: 1rem;
                }

                .page-title {
                    font-size: 1.5rem;
                }

                .content-area {
                    padding: 1rem;
                }
            }

            /* Mobile Toggle Button */
            .mobile-toggle {
                display: none;
                background: none;
                border: none;
                font-size: 1.5rem;
                color: var(--text-primary);
                cursor: pointer;
                padding: 0.5rem;
                border-radius: 0.5rem;
                transition: all 0.2s;
            }

            .mobile-toggle:hover {
                background: var(--light-bg);
            }

            @media (max-width: 768px) {
                .mobile-toggle {
                    display: block;
                }
            }

            /* Custom Scrollbar */
            .sidebar-content::-webkit-scrollbar {
                width: 6px;
            }

            .sidebar-content::-webkit-scrollbar-track {
                background: transparent;
            }

            .sidebar-content::-webkit-scrollbar-thumb {
                background: var(--border-color);
                border-radius: 3px;
            }

            .sidebar-content::-webkit-scrollbar-thumb:hover {
                background: var(--text-secondary);
            }
        </style>
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
                    <a href="dashboard.php" class="sidebar-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="profile.php" class="sidebar-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">
                        <i class="bi bi-person-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="assigned_projects.php" class="sidebar-link <?php echo $activePage === 'projects' ? 'active' : ''; ?>">
                        <i class="bi bi-kanban-fill"></i>
                        <span>Assigned Projects</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="notifications.php" class="sidebar-link <?php echo $activePage === 'notifications' ? 'active' : ''; ?>">
                        <i class="bi bi-bell-fill"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="support.php" class="sidebar-link <?php echo $activePage === 'support' ? 'active' : ''; ?>">
                        <i class="bi bi-envelope-fill"></i>
                        <span>Support</span>
                    </a>
                </li>
            </ul>

            <hr class="sidebar-divider">

            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="settings.php" class="sidebar-link <?php echo $activePage === 'settings' ? 'active' : ''; ?>">
                        <i class="bi bi-gear-fill"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="help.php" class="sidebar-link <?php echo $activePage === 'help' ? 'active' : ''; ?>">
                        <i class="bi bi-question-circle-fill"></i>
                        <span>Help & Support</span>
                    </a>
                </li>
            </ul>
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

    <script>
        // Sidebar toggle functionality
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');

            if (window.innerWidth <= 768 &&
                !sidebar.contains(e.target) &&
                !toggle.contains(e.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
            }
        });
    </script>
    </body>
    </html>
    <?php
}
?>