<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($basePath)) { $basePath = './'; }

// Get user info from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "vivek";
$user_initial = !empty($user_name) ? strtoupper(substr($user_name, 0, 1)) : "V";
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : "viveksinhchavda@gmail.com";

// DB connection for stats
include_once dirname(__DIR__) . '/Login/Login/db.php';
$user_id = session_id();
$bookmark_count = 0;
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookmark WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->bind_result($bookmark_count);
    $stmt->fetch();
    $stmt->close();
}

// Get real-time project statistics
$total_projects = 0;
$total_ideas = 0;
$classification_stats = [];

if (isset($conn)) {
    // Get total approved projects
    $total_projects_query = "SELECT COUNT(*) as total FROM admin_approved_projects";
    $total_result = $conn->query($total_projects_query);
    $total_projects = $total_result->fetch_assoc()['total'];
    
    // Get total ideas from blog table
    $total_ideas_query = "SELECT COUNT(*) as total FROM blog";
    $ideas_result = $conn->query($total_ideas_query);
    $total_ideas = $ideas_result->fetch_assoc()['total'];
    
    // Get classification statistics
    $classification_query = "SELECT classification, COUNT(*) as count 
                           FROM admin_approved_projects 
                           WHERE classification IS NOT NULL AND classification != '' 
                           GROUP BY classification 
                           ORDER BY count DESC";
    $classification_result = $conn->query($classification_query);
    
    while ($row = $classification_result->fetch_assoc()) {
        $classification_stats[] = $row;
    }

    // Get monthly submission trends (last 6 months)
    $monthly_trends = [];
    $monthly_query = "SELECT 
                        DATE_FORMAT(submission_date, '%Y-%m') as month,
                        COUNT(*) as count
                      FROM admin_approved_projects 
                      WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
                      ORDER BY month DESC";
    $monthly_result = $conn->query($monthly_query);
    while ($row = $monthly_result->fetch_assoc()) {
        $monthly_trends[] = $row;
    }

    // Get project status distribution
    $status_distribution = [];
    $status_query = "SELECT 
                        CASE 
                            WHEN status = 'approved' THEN 'Approved'
                            WHEN status = 'pending' THEN 'Pending'
                            WHEN status = 'rejected' THEN 'Rejected'
                            ELSE 'Unknown'
                        END as status_name,
                        COUNT(*) as count
                      FROM admin_approved_projects 
                      GROUP BY status";
    $status_result = $conn->query($status_query);
    while ($row = $status_result->fetch_assoc()) {
        $status_distribution[] = $row;
    }

    // Get technology/language analysis
    $tech_analysis = [];
    $tech_query = "SELECT 
                        language,
                        COUNT(*) as count
                      FROM admin_approved_projects 
                      WHERE language IS NOT NULL AND language != ''
                      GROUP BY language 
                      ORDER BY count DESC 
                      LIMIT 8";
    $tech_result = $conn->query($tech_query);
    while ($row = $tech_result->fetch_assoc()) {
        $tech_analysis[] = $row;
    }

    // Get recent activity (latest 5 projects)
    $recent_activity = [];
    $recent_query = "SELECT 
                        project_name,
                        classification,
                        submission_date,
                        status
                      FROM admin_approved_projects 
                      ORDER BY submission_date DESC 
                      LIMIT 5";
    $recent_result = $conn->query($recent_query);
    while ($row = $recent_result->fetch_assoc()) {
        $recent_activity[] = $row;
    }

    // Get project type distribution
    $type_distribution = [];
    $type_query = "SELECT 
                        project_type,
                        COUNT(*) as count
                      FROM admin_approved_projects 
                      WHERE project_type IS NOT NULL AND project_type != ''
                      GROUP BY project_type 
                      ORDER BY count DESC";
    $type_result = $conn->query($type_query);
    while ($row = $type_result->fetch_assoc()) {
        $type_distribution[] = $row;
    }
}
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
    <!-- Add Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/index.css">
</head>
<body>
<?php include 'layout.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search projects, ideas, mentors..." id="search" onkeyup="fetchResults()">
                <div id="searchResults" class="position-absolute start-0 bg-white shadow-lg rounded-3 mt-1 w-100 p-2 d-none" style="z-index: 1000; max-height: 300px; overflow-y: auto;"></div>
            </div>
            
            <div class="user-profile" id="userProfileDropdown">
                <div class="user-avatar"><?php echo htmlspecialchars($user_initial); ?></div>
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
                
                <!-- Dropdown Menu -->
                <div class="user-dropdown-menu" id="userDropdownMenu">
                    <div class="dropdown-header">
                        <div class="dropdown-user-info">
                            <div class="dropdown-avatar"><?php echo htmlspecialchars($user_initial); ?></div>
                            <div class="dropdown-user-details">
                                <div class="dropdown-user-name"><?php echo htmlspecialchars($user_name); ?></div>
                                <div class="dropdown-user-email"><?php echo htmlspecialchars($user_email); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-menu-items">
                        <a href="user_profile_setting.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>Profile Settings</span>
                        </a>
                        <a href="bookmark.php" class="dropdown-item">
                            <i class="fas fa-bookmark"></i>
                            <span>My Bookmarks</span>
                        </a>
                        <a href="all_projects.php" class="dropdown-item">
                            <i class="fas fa-project-diagram"></i>
                            <span>All Projects</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../Login/Login/logout.php" class="dropdown-item dropdown-item-danger">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard Container -->
    <main class="dashboard-container">
        <!-- Welcome Section -->
        <section class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-info">
                    <div class="welcome-avatar"><?php echo htmlspecialchars($user_initial); ?></div>
                    <div class="welcome-text">
                        <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                        <p class="welcome-subtitle">Your innovation journey continues here</p>
                        <div class="user-email">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($user_email); ?></span>
                        </div>
                    </div>
                </div>
                <a href="./forms/new_project_add.php" class="new-project-btn">
                    <i class="fas fa-plus"></i>
                    <span>New Project</span>
                </a>
            </div>
        </section>

        <!-- Stats Grid -->
        <section class="stats-grid">
            <div class="stat-card projects">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-title">Total Projects</div>
                </div>
                <div class="stat-value"><?php echo $total_projects; ?></div>
            </div>
            
            <div class="stat-card ideas">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="stat-title">Creative Ideas</div>
                </div>
                <div class="stat-value"><?php echo $total_ideas; ?></div>
            </div>
            
            <div class="stat-card bookmarks">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <div class="stat-title">Saved Items</div>
                </div>
                <div class="stat-value"><?php echo $bookmark_count; ?></div>
            </div>
        </section>

        <!-- Dashboard Section -->
        <section class="dashboard-section">
            <div class="dashboard-header">
                <h2 class="dashboard-title">Project Dashboard</h2>
                <p class="dashboard-subtitle">Overview of your projects and their statistics</p>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <!-- Analytics Overview Cards -->
                <div class="analytics-overview mb-4">
                </div>

                <!-- Main Charts Row -->
                <div class="row g-4 mb-4">
                    <!-- Project Distribution Pie Chart -->
                    <div class="col-lg-8">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Project Distribution</h3>
                                    <p class="chart-subtitle">Real-time breakdown of projects by classification</p>
                                </div>
                                <div class="chart-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="refreshChart('classificationsChart')">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="classificationsChart"></canvas>
                            </div>
                            <div class="chart-stats">
                                <?php foreach ($classification_stats as $index => $classification): ?>
                                    <div class="chart-stat-item">
                                        <div class="chart-stat-icon" style="background: <?php
                                            $colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#ec4899', '#06b6d4'];
                                            echo $colors[$index % count($colors)];
                                        ?>;">
                                            <i class="fas fa-circle"></i>
                                        </div>
                                        <div>
                                            <div class="chart-stat-value"><?php echo $classification['count']; ?></div>
                                            <div class="chart-stat-label"><?php echo htmlspecialchars($classification['classification']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Status Distribution -->
                    <div class="col-lg-4">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Project Status</h3>
                                    <p class="chart-subtitle">Current project approval status</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="statusChart"></canvas>
                            </div>
                            <div class="status-legend">
                                <?php foreach ($status_distribution as $status): ?>
                                    <div class="status-item">
                                        <span class="status-dot" style="background: <?php
                                            echo $status['status_name'] === 'Approved' ? '#10b981' :
                                                ($status['status_name'] === 'Pending' ? '#f59e0b' : '#ef4444');
                                        ?>;"></span>
                                        <span class="status-label"><?php echo $status['status_name']; ?></span>
                                        <span class="status-count"><?php echo $status['count']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends and Recent Activity -->
                <div class="row g-4 mb-4">
                    <!-- Monthly Submissions Bar Chart -->
                    <div class="col-lg-8">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Monthly Project Submissions</h3>
                                    <p class="chart-subtitle">Submission trends over the last 6 months</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-lg-4">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Recent Activity</h3>
                                    <p class="chart-subtitle">Latest project submissions</p>
                                </div>
                            </div>
                            <div class="activity-feed">
                                <?php if (!empty($recent_activity)): ?>
                                    <?php foreach ($recent_activity as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-plus-circle text-success"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title"><?php echo htmlspecialchars($activity['project_name']); ?></div>
                                                <div class="activity-meta">
                                                    <span class="activity-category"><?php echo htmlspecialchars($activity['classification']); ?></span>
                                                    <span class="activity-date"><?php echo date('M d', strtotime($activity['submission_date'])); ?></span>
                                                </div>
                                                <div class="activity-status status-<?php echo $activity['status']; ?>">
                                                    <?php echo ucfirst($activity['status']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No recent activity</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Technology Stack</h3>
                                    <p class="chart-subtitle">Most used programming languages and technologies</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="techChart"></canvas>
                            </div>
                            <div class="tech-list">
                                <?php foreach (array_slice($tech_analysis, 0, 5) as $tech): ?>
                                    <div class="tech-item">
                                        <div class="tech-name"><?php echo htmlspecialchars($tech['language']); ?></div>
                                        <div class="tech-bar">
                                            <div class="tech-progress" style="width: <?php echo $total_projects > 0 ? ($tech['count'] / $total_projects) * 100 : 0; ?>%"></div>
                                        </div>
                                        <div class="tech-count"><?php echo $tech['count']; ?></div>
                                        <div class="type-percentage">
                                            <?php echo $total_projects > 0 ? round(($tech['count'] / $total_projects) * 100, 1) : 0; ?>%
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

    <script>
        // Search functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            searchInput.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        }

        // Animate progress bars on scroll
        const progressBars = document.querySelectorAll('.progress-fill');

        function animateProgressBars() {
            progressBars.forEach(bar => {
                const rect = bar.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 200);
                }
            });
        }

        // Initial animation
        setTimeout(animateProgressBars, 1000);

        // Animate on scroll
        window.addEventListener('scroll', animateProgressBars);

        // Add click handlers for stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Classification items hover effect
        document.querySelectorAll('.classification-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                const progressBar = this.querySelector('.progress-fill');
                const currentWidth = progressBar.style.width;
                progressBar.style.width = '100%';
                setTimeout(() => {
                    progressBar.style.width = currentWidth;
                }, 300);
            });
        });

        // Search functionality placeholder
        function fetchResults() {
            const query = document.getElementById('search').value;
            const resultsDiv = document.getElementById('searchResults');

            if (query.length > 2) {
                // Show search results div
                resultsDiv.classList.remove('d-none');

                // This is where you would implement actual search functionality
                // For now, it's just a placeholder
                resultsDiv.innerHTML = `
                    <div class="p-2">
                        <p class="text-muted small mb-0">Search results for "${query}" would appear here...</p>
                    </div>
                `;
            } else {
                // Hide search results div
                resultsDiv.classList.add('d-none');
            }
        }

        // Hide search results when clicking outside
        document.addEventListener('click', function(event) {
            const searchContainer = document.querySelector('.search-container');
            const resultsDiv = document.getElementById('searchResults');

            if (searchContainer && !searchContainer.contains(event.target)) {
                resultsDiv.classList.add('d-none');
            }
        });

        // User Profile Dropdown Functionality
        const userProfileDropdown = document.getElementById('userProfileDropdown');
        const userDropdownMenu = document.getElementById('userDropdownMenu');

        if (userProfileDropdown && userDropdownMenu) {
            // Toggle dropdown on click
            userProfileDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdownMenu.classList.toggle('show');
                userProfileDropdown.classList.toggle('active');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userProfileDropdown.contains(event.target)) {
                    userDropdownMenu.classList.remove('show');
                    userProfileDropdown.classList.remove('active');
                }
            });

            // Close dropdown on escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    userDropdownMenu.classList.remove('show');
                    userProfileDropdown.classList.remove('active');
                }
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Any initialization code can go here
            console.log('IdeaNest Dashboard Loaded');

            // Load More Button Functionality
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const additionalDetails = document.getElementById('additionalDetails');

            if (loadMoreBtn && additionalDetails) {
                loadMoreBtn.addEventListener('click', function() {
                    const isExpanded = additionalDetails.style.display !== 'none';

                    if (isExpanded) {
                        // Collapse
                        additionalDetails.style.display = 'none';
                        loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down me-2"></i><span>Load More Details</span>';
                        loadMoreBtn.classList.remove('expanded');
                    } else {
                        // Expand
                        loadMoreBtn.classList.add('loading');
                        loadMoreBtn.innerHTML = '<i class="fas fa-spinner me-2"></i><span>Loading...</span>';

                        // Simulate loading delay
                        setTimeout(() => {
                            additionalDetails.style.display = 'block';
                            loadMoreBtn.classList.remove('loading');
                            loadMoreBtn.classList.add('expanded');
                            loadMoreBtn.innerHTML = '<i class="fas fa-chevron-up me-2"></i><span>Show Less</span>';

                            // Smooth scroll to additional details
                            additionalDetails.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }, 800);
                    }
                });
            }

            // Initialize Charts
            initializeCharts();
        });

        // Chart Configuration and Initialization
        function initializeCharts() {
            // Chart.js global configuration
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#64748b';
            Chart.defaults.plugins.legend.labels.usePointStyle = true;
            Chart.defaults.plugins.legend.labels.padding = 20;

            // Project Classifications Pie Chart
            const classificationsCtx = document.getElementById('classificationsChart');
            if (classificationsCtx) {
                const classificationData = <?php echo json_encode($classification_stats); ?>;
                const labels = classificationData.map(item => item.classification);
                const data = classificationData.map(item => item.count);
                const colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#ec4899', '#06b6d4'];

                new Chart(classificationsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: colors.slice(0, labels.length),
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            }
                        },
                        animation: {
                            animateRotate: true,
                            animateScale: true
                        }
                    }
                });
            }

            // Monthly Submissions Bar Chart with Real Data
            const monthlyCtx = document.getElementById('monthlyChart');
            if (monthlyCtx) {
                const monthlyData = <?php echo json_encode($monthly_trends); ?>;
                const months = monthlyData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
                }).reverse();
                const submissions = monthlyData.map(item => item.count).reverse();

                new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'Project Submissions',
                            data: submissions,
                            backgroundColor: 'rgba(99, 102, 241, 0.8)',
                            borderColor: 'rgba(99, 102, 241, 1)',
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        animation: {
                            duration: 2000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }

            // Status Distribution Chart with Real Data
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                const statusData = <?php echo json_encode($status_distribution); ?>;
                const statusLabels = statusData.map(item => item.status_name);
                const statusCounts = statusData.map(item => item.count);
                const statusColors = statusData.map(item =>
                    item.status_name === 'Approved' ? '#10b981' :
                    (item.status_name === 'Pending' ? '#f59e0b' : '#ef4444')
                );

                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusCounts,
                            backgroundColor: statusColors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        cutout: '70%',
                        animation: {
                            animateRotate: true,
                            animateScale: true
                        }
                    }
                });
            }

            // Technology Stack Chart with Real Data
            const techCtx = document.getElementById('techChart');
            if (techCtx) {
                const techData = <?php echo json_encode($tech_analysis); ?>;
                const techLabels = techData.map(item => item.language);
                const techCounts = techData.map(item => item.count);
                const techColors = [
                    'rgba(99, 102, 241, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(6, 182, 212, 0.8)'
                ];

                new Chart(techCtx, {
                    type: 'bar',
                    data: {
                        labels: techLabels,
                        datasets: [{
                            label: 'Number of Projects',
                            data: techCounts,
                            backgroundColor: techColors.slice(0, techLabels.length),
                            borderRadius: 4,
                            borderWidth: 1,
                            borderColor: techColors.map(color => color.replace('0.8', '1')),
                            hoverBackgroundColor: techColors.map(color => color.replace('0.8', '0.9')),
                            barPercentage: 0.7,
                            categoryPercentage: 0.8
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => parseInt(a) + parseInt(b), 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return `${value} projects (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 2000,
                            easing: 'easeInOutQuart',
                            onProgress: function(animation) {
                                const chart = animation.chart;
                                const ctx = chart.ctx;
                                const dataset = chart.data.datasets[0];
                                const meta = chart.getDatasetMeta(0);

                                ctx.save();
                                ctx.fillStyle = '#1f2937';
                                ctx.font = '600 12px Inter';
                                ctx.textAlign = 'left';
                                ctx.textBaseline = 'middle';

                                const total = dataset.data.reduce((a, b) => parseInt(a) + parseInt(b), 0);

                                meta.data.forEach((bar, index) => {
                                    const value = parseInt(dataset.data[index]);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    const position = bar.getCenterPoint();

                                    // Format text and ensure it's visible
                                    ctx.fillText(`${value} (${percentage}%)`, position.x + 15, position.y);
                                });
                                ctx.restore();
                            }
                        },
                        onClick: (event, elements) => {
                            if (elements.length > 0) {
                                const index = elements[0].index;
                                const label = techLabels[index];
                                const count = techCounts[index];
                                // You can add click interaction here
                                console.log(`Clicked on ${label}: ${count} projects`);
                            }
                        }
                    }
                });
            }

            // Project Types Chart with Real Data
            const typeCtx = document.getElementById('typeChart');
            if (typeCtx) {
                const typeData = <?php echo json_encode($type_distribution); ?>;
                const typeLabels = typeData.map(item => item.project_type);
                const typeCounts = typeData.map(item => item.count);
                const typeColors = [
                    'rgba(99, 102, 241, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)'
                ];

                new Chart(typeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: typeLabels,
                        datasets: [{
                            data: typeCounts,
                            backgroundColor: typeColors.slice(0, typeLabels.length),
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        cutout: '60%',
                        animation: {
                            animateRotate: true,
                            animateScale: true
                        }
                    }
                });
            }
        }

        // Refresh chart function
        function refreshChart(chartId) {
            // This would typically make an AJAX call to refresh data
            // For now, we'll just show a loading state
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            button.disabled = true;

            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                // Here you would typically reload the chart with fresh data
            }, 1000);
        }
    </script>
</body>
</html>

