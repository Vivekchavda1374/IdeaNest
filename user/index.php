<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($basePath)) { $basePath = './'; }

// Get user info from session
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "vivek";
$user_initial = !empty($user_name) ? strtoupper(substr($user_name, 0, 1)) : "V";
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : "viveksinhchavda@gmail.com";
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #6366f1;
            --primary-hover: #5855eb;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--light) 0%, var(--gray-100) 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
    min-height: 100vh;
}

        /* Header */
        .header {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 1rem 2rem;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }

        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--gray-600);
            cursor: pointer;
        }

        .search-container {
            flex: 1;
            max-width: 500px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            background: var(--gray-50);
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(99 102 241 / 0.1);
            background: var(--white);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 0.9rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--gray-50);
        }

        .user-profile:hover {
            background: var(--gray-100);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-name {
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .dropdown-icon {
            color: var(--gray-400);
            font-size: 0.8rem;
        }

        /* Dashboard Content */
        .dashboard-container {
            padding: 2rem;
        }

        /* Welcome Section */
        .welcome-section {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
    margin-bottom: 2.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
    position: relative;
    overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color), var(--info-color));
        }

        .welcome-content {
    display: flex;
    align-items: center;
            justify-content: space-between;
    gap: 2rem;
}

        .welcome-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .welcome-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
            color: var(--white);
            font-weight: 700;
            font-size: 2rem;
            box-shadow: var(--shadow-lg);
            border: 4px solid var(--white);
        }

        .welcome-text h1 {
            font-size: 2rem;
    font-weight: 700;
            color: var(--gray-900);
    margin-bottom: 0.5rem;
}

        .welcome-subtitle {
            color: var(--gray-600);
    font-size: 1.1rem;
            margin-bottom: 0.75rem;
        }

        .user-email {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .new-project-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-md);
            text-decoration: none;
        }

        .new-project-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, var(--primary-hover), var(--secondary-color));
            color: var(--white);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
    display: flex;
    align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
}

.stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
            font-size: 1.25rem;
            color: var(--white);
        }

        .stat-card.projects .stat-icon {
            background: linear-gradient(135deg, var(--info-color), var(--primary-color));
        }

        .stat-card.ideas .stat-icon {
            background: linear-gradient(135deg, var(--warning-color), var(--danger-color));
        }

        .stat-card.bookmarks .stat-icon {
            background: linear-gradient(135deg, var(--success-color), var(--info-color));
        }

        .stat-title {
            color: var(--gray-500);
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1;
        }

        /* Dashboard Section */
        .dashboard-section {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
    margin-bottom: 2rem;
}

        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .dashboard-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: var(--gray-600);
            font-size: 1rem;
        }

        /* Project Classifications */
        .classifications-section {
            margin-top: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .classification-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border: 1px solid var(--gray-200);
            transition: all 0.2s ease;
        }

        .classification-item:hover {
            background: var(--white);
            box-shadow: var(--shadow-md);
            transform: translateX(4px);
        }

        .classification-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .classification-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 0.9rem;
        }

        .classification-details h4 {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .classification-details p {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .classification-stats {
            text-align: right;
        }

        .classification-percentage {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .progress-bar {
            width: 100px;
            height: 6px;
            background: var(--gray-200);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .header-content {
                flex-wrap: wrap;
            }

            .search-container {
                order: 3;
                flex-basis: 100%;
                margin-top: 1rem;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .welcome-section {
                padding: 2rem 1.5rem;
            }
            
            .welcome-content {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome-text h1 {
                font-size: 1.5rem;
            }
            
            .welcome-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .dashboard-section {
                padding: 2rem 1.5rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-section,
        .stat-card,
        .dashboard-section {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
</style>
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
                
                <div class="user-profile">
                    <div class="user-avatar"><?php echo htmlspecialchars($user_initial); ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="fas fa-chevron-down dropdown-icon"></i>
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
                    <a href="#" class="new-project-btn">
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
                    <div class="stat-value">14</div>
                </div>
                
                <div class="stat-card ideas">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-lightbulb"></i>
            </div>
                        <div class="stat-title">Creative Ideas</div>
        </div>
                    <div class="stat-value">12</div>
                </div>
                
                <div class="stat-card bookmarks">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-bookmark"></i>
                        </div>
                        <div class="stat-title">Saved Items</div>
            </div>
                    <div class="stat-value">5</div>
        </div>
            </section>

            <!-- Dashboard Section -->
            <section class="dashboard-section">
                <div class="dashboard-header">
                    <h2 class="dashboard-title">Project Classification Dashboard</h2>
                    <p class="dashboard-subtitle">Overview of your project categories and their distribution</p>
                </div>

                <div class="classifications-section">
                    <h3 class="section-title">
                        <i class="fas fa-chart-pie"></i>
                        Project Classifications
                    </h3>
                    
                    <div class="classification-item">
                        <div class="classification-info">
                            <div class="classification-icon">
                                <i class="fas fa-code"></i>
                            </div>
                            <div class="classification-details">
                                <h4>IoT</h4>
                                <p>Projects: 2</p>
                            </div>
                        </div>
                        <div class="classification-stats">
                            <div class="classification-percentage">14.3%</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 14.3%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="classification-item">
                        <div class="classification-info">
                            <div class="classification-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="classification-details">
                                <h4>Mobile Development</h4>
                                <p>Projects: 4</p>
                            </div>
                        </div>
                        <div class="classification-stats">
                            <div class="classification-percentage">28.6%</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 28.6%"></div>
            </div>
        </div>
    </div>

                    <div class="classification-item">
                        <div class="classification-info">
                            <div class="classification-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="classification-details">
                                <h4>Web Development</h4>
                                <p>Projects: 5</p>
                            </div>
        </div>
                        <div class="classification-stats">
                            <div class="classification-percentage">35.7%</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 35.7%"></div>
        </div>
    </div>
</div>

                    <div class="classification-item">
                        <div class="classification-info">
                            <div class="classification-icon">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="classification-details">
                                <h4>AI/ML</h4>
                                <p>Projects: 3</p>
                            </div>
                        </div>
                        <div class="classification-stats">
                            <div class="classification-percentage">21.4%</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 21.4%"></div>
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

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Any initialization code can go here
            console.log('IdeaNest Dashboard Loaded');
        });
    </script>
</body>
</html>