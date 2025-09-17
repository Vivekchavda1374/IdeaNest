<?php
function renderLayout($title, $content, $additionalCSS = '', $additionalJS = '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> - Mentor Portal</title>

        <!-- Bootstrap 5 -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <style>
            :root {
                --primary-color: #4f46e5;
                --secondary-color: #6366f1;
                --success-color: #10b981;
                --warning-color: #f59e0b;
                --danger-color: #ef4444;
                --info-color: #3b82f6;
                --dark-color: #1f2937;
                --light-color: #f8fafc;
            }

            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
            }

            .sidebar {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                border-right: 1px solid rgba(255, 255, 255, 0.2);
                min-height: 100vh;
                position: fixed;
                width: 280px;
                z-index: 1000;
                box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            }

            .main-content {
                margin-left: 280px;
                padding: 20px;
                min-height: 100vh;
            }

            .glass-card {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 16px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
            }

            .glass-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            }

            .stat-card {
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                color: white;
                border-radius: 16px;
                padding: 1.5rem;
                position: relative;
                overflow: hidden;
            }

            .stat-card::before {
                content: '';
                position: absolute;
                top: 0;
                right: 0;
                width: 100px;
                height: 100px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                transform: translate(30px, -30px);
            }

            .nav-link {
                color: var(--dark-color);
                padding: 12px 20px;
                margin: 4px 0;
                border-radius: 12px;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .nav-link:hover, .nav-link.active {
                background: var(--primary-color);
                color: white;
                transform: translateX(5px);
            }

            .btn-gradient {
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                border: none;
                border-radius: 10px;
                padding: 8px 16px;
                color: white;
                transition: all 0.3s ease;
            }

            .btn-gradient:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
                color: white;
            }

            .student-card {
                border-left: 4px solid var(--primary-color);
                transition: all 0.3s ease;
            }

            .student-card:hover {
                border-left-width: 6px;
                transform: translateX(5px);
            }

            .progress-ring {
                transform: rotate(-90deg);
            }

            .animate-counter {
                animation: countUp 2s ease-out;
            }

            @keyframes countUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .notification-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                width: 20px;
                height: 20px;
                background: var(--danger-color);
                color: white;
                border-radius: 50%;
                font-size: 11px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            @media (max-width: 768px) {
                .sidebar {
                    transform: translateX(-100%);
                    transition: transform 0.3s ease;
                }
                .sidebar.show {
                    transform: translateX(0);
                }
                .main-content {
                    margin-left: 0;
                }
            }

            <?= $additionalCSS ?>
        </style>
    </head>
    <body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary rounded-circle p-2 me-3">
                    <i class="fas fa-graduation-cap text-white"></i>
                </div>
                <h4 class="mb-0 fw-bold">Mentor Portal</h4>
            </div>

            <div class="nav flex-column">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a class="nav-link" href="students.php">
                    <i class="fas fa-users"></i>
                    My Students
                    <span class="notification-badge" id="studentsBadge">3</span>
                </a>
                <a class="nav-link" href="sessions.php">
                    <i class="fas fa-calendar-alt"></i>
                    Sessions
                </a>
                <a class="nav-link" href="projects.php">
                    <i class="fas fa-project-diagram"></i>
                    Projects
                </a>
                <a class="nav-link" href="analytics.php">
                    <i class="fas fa-chart-line"></i>
                    Analytics
                </a>
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user-cog"></i>
                    Profile Settings
                </a>
                <hr class="my-3">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <?= $content ?>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script>
        // Mobile menu toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }

        // Active nav link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    document.querySelector('.nav-link.active')?.classList.remove('active');
                    link.classList.add('active');
                }
            });
        });

        // Notification system
        function showNotification(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;

            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            toastContainer.appendChild(toast);

            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            setTimeout(() => toast.remove(), 5000);
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
            return container;
        }

        <?= $additionalJS ?>
    </script>
    </body>
    </html>
    <?php
}
?>