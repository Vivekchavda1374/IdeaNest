<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($basePath)) {
    $basePath = './';
}

// Check if Google user needs to complete profile
if (isset($_SESSION['google_new_user']) && $_SESSION['google_new_user'] === true) {
    header("Location: user_profile_setting.php?google_setup=1");
    exit();
}



// DB connection for stats
include_once dirname(__DIR__) . '/Login/Login/db.php';
$user_id = session_id();
// Get user info from session and database
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : "User";
$user_initial = !empty($user_name) ? strtoupper(substr($user_name, 0, 1)) : "U";

$user_email = "user@example.com";
$github_username = '';
$user_db_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (isset($conn) && $user_db_id) {
    $stmt = $conn->prepare("SELECT email, github_username FROM register WHERE id = ?");
    $stmt->bind_param("i", $user_db_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_email = $row['email'];
        $github_username = $row['github_username'] ?? '';
    }
    $stmt->close();
}
$bookmark_count = 0;
$my_projects_count = 0;
$my_ideas_count = 0;
$my_pending_projects = 0;
$my_approved_projects = 0;

if (isset($conn) && $user_db_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookmark WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->bind_result($bookmark_count);
    $stmt->fetch();
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ?");
    $stmt->bind_param("i", $user_db_id);
    $stmt->execute();
    $stmt->bind_result($my_projects_count);
    $stmt->fetch();
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM blog WHERE user_id = ?");
    $stmt->bind_param("i", $user_db_id);
    $stmt->execute();
    $stmt->bind_result($my_ideas_count);
    $stmt->fetch();
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $user_db_id);
    $stmt->execute();
    $stmt->bind_result($my_pending_projects);
    $stmt->fetch();
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $user_db_id);
    $stmt->execute();
    $stmt->bind_result($my_approved_projects);
    $stmt->fetch();
    $stmt->close();
}

// Get real-time project statistics
$total_projects = 0;
$total_ideas = 0;
$classification_stats = [];

if (isset($conn)) {
    // Get total approved projects
    $total_projects_query = "SELECT COUNT(*) as total FROM admin_approved_projects WHERE status = 'approved'";
    $total_result = $conn->query($total_projects_query);
    if ($total_result) {
        $total_projects = $total_result->fetch_assoc()['total'];
    }

    // Get total ideas from blog table
    $total_ideas_query = "SELECT COUNT(*) as total FROM blog";
    $ideas_result = $conn->query($total_ideas_query);
    if ($ideas_result) {
        $total_ideas = $ideas_result->fetch_assoc()['total'];
    }

    // Get classification statistics
    $classification_query = "SELECT classification, COUNT(*) as count 
                           FROM admin_approved_projects 
                           WHERE classification IS NOT NULL AND classification != '' AND status = 'approved'
                           GROUP BY classification 
                           ORDER BY count DESC";
    $classification_result = $conn->query($classification_query);

    if ($classification_result) {
        while ($row = $classification_result->fetch_assoc()) {
            $classification_stats[] = $row;
        }
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
    if ($monthly_result) {
        while ($row = $monthly_result->fetch_assoc()) {
            $monthly_trends[] = $row;
        }
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
    if ($status_result) {
        while ($row = $status_result->fetch_assoc()) {
            $status_distribution[] = $row;
        }
    }

    // Get technology/language analysis
    $tech_analysis = [];
    $tech_query = "SELECT 
                        language,
                        COUNT(*) as count
                      FROM admin_approved_projects 
                      WHERE language IS NOT NULL AND language != '' AND status = 'approved'
                      GROUP BY language 
                      ORDER BY count DESC 
                      LIMIT 8";
    $tech_result = $conn->query($tech_query);
    if ($tech_result) {
        while ($row = $tech_result->fetch_assoc()) {
            $tech_analysis[] = $row;
        }
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
    if ($recent_result) {
        while ($row = $recent_result->fetch_assoc()) {
            $recent_activity[] = $row;
        }
    }

    // Get project type distribution
    $type_distribution = [];
    $type_query = "SELECT 
                        project_type,
                        COUNT(*) as count
                      FROM admin_approved_projects 
                      WHERE project_type IS NOT NULL AND project_type != '' AND status = 'approved'
                      GROUP BY project_type 
                      ORDER BY count DESC";
    $type_result = $conn->query($type_query);
    if ($type_result) {
        while ($row = $type_result->fetch_assoc()) {
            $type_distribution[] = $row;
        }
    }

    // Get user engagement metrics
    $engagement_metrics = [];
    $engagement_query = "SELECT 
                            DATE_FORMAT(submission_date, '%Y-%m-%d') as date,
                            COUNT(*) as submissions,
                            COUNT(DISTINCT user_id) as unique_users
                          FROM admin_approved_projects 
                          WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                          GROUP BY DATE_FORMAT(submission_date, '%Y-%m-%d')
                          ORDER BY date DESC
                          LIMIT 15";
    $engagement_result = $conn->query($engagement_query);
    if ($engagement_result) {
        while ($row = $engagement_result->fetch_assoc()) {
            $engagement_metrics[] = $row;
        }
    }

    // Get project completion timeline
    $completion_timeline = [];
    $timeline_query = "SELECT 
                          DATEDIFF(NOW(), submission_date) as days_since_submission,
                          COUNT(*) as count,
                          status
                        FROM admin_approved_projects 
                        WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                        GROUP BY DATEDIFF(NOW(), submission_date), status
                        ORDER BY days_since_submission DESC";
    $timeline_result = $conn->query($timeline_query);
    if ($timeline_result) {
        while ($row = $timeline_result->fetch_assoc()) {
            $completion_timeline[] = $row;
        }
    }

    // Get top contributors (using register table join)
    $top_contributors = [];
    $contributors_query = "SELECT 
                              r.name as submitter_name,
                              COUNT(*) as project_count,
                              AVG(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) * 100 as approval_rate
                            FROM admin_approved_projects a
                            LEFT JOIN register r ON a.user_id = r.id
                            WHERE r.name IS NOT NULL AND r.name != ''
                            GROUP BY r.name 
                            HAVING project_count >= 1
                            ORDER BY project_count DESC 
                            LIMIT 8";
    $contributors_result = $conn->query($contributors_query);
    if ($contributors_result) {
        while ($row = $contributors_result->fetch_assoc()) {
            $top_contributors[] = $row;
        }
    }

    // Get project complexity analysis
    $complexity_analysis = [];
    $complexity_query = "SELECT 
                            CASE 
                                WHEN LENGTH(description) < 100 THEN 'Simple'
                                WHEN LENGTH(description) < 300 THEN 'Medium'
                                ELSE 'Complex'
                            END as complexity,
                            COUNT(*) as count,
                            AVG(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) * 100 as approval_rate
                          FROM admin_approved_projects 
                          WHERE description IS NOT NULL AND description != ''
                          GROUP BY 
                            CASE 
                                WHEN LENGTH(description) < 100 THEN 'Simple'
                                WHEN LENGTH(description) < 300 THEN 'Medium'
                                ELSE 'Complex'
                            END
                          ORDER BY count DESC";
    $complexity_result = $conn->query($complexity_query);
    if ($complexity_result) {
        while ($row = $complexity_result->fetch_assoc()) {
            $complexity_analysis[] = $row;
        }
    }

    // Get weekly performance metrics
    $weekly_performance = [];
    $weekly_query = "SELECT 
                        WEEK(submission_date) as week_num,
                        YEAR(submission_date) as year,
                        COUNT(*) as submissions,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        AVG(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) * 100 as approval_rate
                      FROM admin_approved_projects 
                      WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
                      GROUP BY YEAR(submission_date), WEEK(submission_date)
                      ORDER BY year DESC, week_num DESC
                      LIMIT 12";
    $weekly_result = $conn->query($weekly_query);
    if ($weekly_result) {
        while ($row = $weekly_result->fetch_assoc()) {
            $weekly_performance[] = $row;
        }
    }
}

// Ensure arrays have fallback data to prevent JavaScript errors
if (empty($classification_stats)) {
    $classification_stats = [['classification' => 'No Data', 'count' => 0]];
}
if (empty($monthly_trends)) {
    $monthly_trends = [['month' => date('Y-m'), 'count' => 0]];
}
if (empty($status_distribution)) {
    $status_distribution = [['status_name' => 'No Data', 'count' => 0]];
}
if (empty($tech_analysis)) {
    $tech_analysis = [['language' => 'No Data', 'count' => 0]];
}
if (!$recent_activity) {
    $recent_activity = [['project_name' => 'No recent activity', 'classification' => '', 'submission_date' => date('Y-m-d H:i:s'), 'status' => 'pending']];
}
if (empty($type_distribution)) {
    $type_distribution = [['project_type' => 'No Data', 'count' => 0]];
}
if (empty($engagement_metrics)) {
    $engagement_metrics = [['date' => date('Y-m-d'), 'submissions' => 0, 'unique_users' => 0]];
}
if (empty($completion_timeline)) {
    $completion_timeline = [['days_since_submission' => 0, 'count' => 0, 'status' => 'pending']];
}
if (empty($top_contributors)) {
    $top_contributors = [['submitter_name' => 'No contributors', 'project_count' => 0, 'approval_rate' => 0]];
}
if (empty($complexity_analysis)) {
    $complexity_analysis = [['complexity' => 'Simple', 'count' => 0, 'approval_rate' => 0]];
}
if (empty($weekly_performance)) {
    $weekly_performance = [['week_num' => date('W'), 'year' => date('Y'), 'submissions' => 0, 'approved' => 0, 'approval_rate' => 0]];
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
            <div class="stat-card projects" onclick="window.location.href='all_projects.php'">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-title">My Projects</div>
                </div>
                <div class="stat-value"><?php echo $my_projects_count; ?></div>
                <div class="stat-footer">
                    <span class="stat-badge success"><?php echo $my_approved_projects; ?> approved</span>
                    <span class="stat-badge warning"><?php echo $my_pending_projects; ?> pending</span>
                </div>
            </div>

            <div class="stat-card ideas" onclick="window.location.href='Blog/list-project.php'">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="stat-title">My Ideas</div>
                </div>
                <div class="stat-value"><?php echo $my_ideas_count; ?></div>
                <div class="stat-footer">
                    <span class="stat-badge info">Total shared</span>
                </div>
            </div>

            <div class="stat-card bookmarks" onclick="window.location.href='bookmark.php'">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <div class="stat-title">Saved Items</div>
                </div>
                <div class="stat-value"><?php echo $bookmark_count; ?></div>
                <div class="stat-footer">
                    <span class="stat-badge primary">Bookmarked</span>
                </div>
            </div>
            
            <div class="stat-card community">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-title">Community</div>
                </div>
                <div class="stat-value"><?php echo $total_projects + $total_ideas; ?></div>
                <div class="stat-footer">
                    <span class="stat-badge success"><?php echo $total_projects; ?> projects</span>
                    <span class="stat-badge warning"><?php echo $total_ideas; ?> ideas</span>
                </div>
            </div>
        </section>
        
        <!-- Quick Actions Panel -->
        <section class="quick-actions-panel">
            <h3 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="quick-actions-grid">
                <a href="./forms/new_project_add.php" class="quick-action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-content">
                        <h4>Submit Project</h4>
                        <p>Share your latest work</p>
                    </div>
                </a>
                
                <a href="Blog/form.php" class="quick-action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="action-content">
                        <h4>Share Idea</h4>
                        <p>Post your creative concept</p>
                    </div>
                </a>
                
                <a href="all_projects.php" class="quick-action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #10b981, #3b82f6);">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="action-content">
                        <h4>Explore Projects</h4>
                        <p>Discover amazing work</p>
                    </div>
                </a>
                
                <a href="select_mentor.php" class="quick-action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #ec4899, #8b5cf6);">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="action-content">
                        <h4>Find Mentor</h4>
                        <p>Get expert guidance</p>
                    </div>
                </a>
                
                <?php if (!empty($github_username)): ?>
                <a href="github_profile_view.php" class="quick-action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #24292e, #586069);">
                        <i class="fab fa-github"></i>
                    </div>
                    <div class="action-content">
                        <h4>GitHub Profile</h4>
                        <p>View your repositories</p>
                    </div>
                </a>
                <?php else: ?>
                <a href="user_profile_setting.php" class="quick-action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #24292e, #586069);">
                        <i class="fab fa-github"></i>
                    </div>
                    <div class="action-content">
                        <h4>Connect GitHub</h4>
                        <p>Link your profile</p>
                    </div>
                </a>
                <?php endif; ?>
                
                <a href="user_profile_setting.php" class="quick-action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #06b6d4, #3b82f6);">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="action-content">
                        <h4>Settings</h4>
                        <p>Manage your profile</p>
                    </div>
                </a>
            </div>
        </section>

        <!-- GitHub Profile Section -->
        <?php include 'github_profile.php'; ?>
        
        <!-- Personalized Recommendations -->
        <?php if ($my_projects_count > 0 || $my_ideas_count > 0 || $my_pending_projects > 0 || empty($github_username)): ?>
        <section class="recommendations-section">
            <h3 class="section-title"><i class="fas fa-magic"></i> Recommended For You</h3>
            <div class="recommendations-grid">
                <?php if ($my_pending_projects > 0): ?>
                <div class="recommendation-card">
                    <div class="recommendation-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="recommendation-content">
                        <h4>Pending Projects</h4>
                        <p>You have <?php echo $my_pending_projects; ?> project(s) awaiting review</p>
                        <a href="all_projects.php" class="recommendation-link">View Status <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($my_projects_count == 0): ?>
                <div class="recommendation-card">
                    <div class="recommendation-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="recommendation-content">
                        <h4>Start Your Journey</h4>
                        <p>Submit your first project and showcase your skills</p>
                        <a href="./forms/new_project_add.php" class="recommendation-link">Submit Project <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (empty($github_username)): ?>
                <div class="recommendation-card">
                    <div class="recommendation-icon" style="background: rgba(36, 41, 46, 0.1); color: #24292e;">
                        <i class="fab fa-github"></i>
                    </div>
                    <div class="recommendation-content">
                        <h4>Connect GitHub</h4>
                        <p>Showcase your repositories and contributions</p>
                        <a href="user_profile_setting.php" class="recommendation-link">Connect Now <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="recommendation-card">
                    <div class="recommendation-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="recommendation-content">
                        <h4>Explore Community</h4>
                        <p>Discover <?php echo $total_projects; ?> projects from talented creators</p>
                        <a href="all_projects.php" class="recommendation-link">Explore <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

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
                                <?php foreach ($classification_stats as $index => $classification) : ?>
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
                                <?php foreach ($status_distribution as $status) : ?>
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
                                <?php if (!!$recent_activity) : ?>
                                    <?php foreach ($recent_activity as $activity) : ?>
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
                                <?php else : ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No recent activity</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technology Stack and Project Types Row -->
                <div class="row g-4 mb-4">
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
                                <?php foreach (array_slice($tech_analysis, 0, 5) as $tech) : ?>
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

                    <div class="col-lg-6">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Project Types</h3>
                                    <p class="chart-subtitle">Distribution by project categories</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="typeChart"></canvas>
                            </div>
                            <div class="type-legend">
                                <?php foreach ($type_distribution as $index => $type) : ?>
                                    <div class="type-item">
                                        <span class="type-dot" style="background: <?php
                                        $colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b'];
                                        echo $colors[$index % count($colors)];
                                        ?>;"></span>
                                        <span class="type-label"><?php echo htmlspecialchars($type['project_type']); ?></span>
                                        <span class="type-count"><?php echo $type['count']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Engagement and Performance Metrics Row -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">User Engagement Trends</h3>
                                    <p class="chart-subtitle">Daily submissions and active users (Last 15 days)</p>
                                </div>
                                <div class="chart-actions">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary active" onclick="toggleEngagementView('daily')">Daily</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleEngagementView('weekly')">Weekly</button>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="engagementChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Project Complexity</h3>
                                    <p class="chart-subtitle">Analysis by description length</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="complexityChart"></canvas>
                            </div>
                            <div class="complexity-stats">
                                <?php foreach ($complexity_analysis as $complexity) : ?>
                                    <div class="complexity-item">
                                        <div class="complexity-info">
                                            <span class="complexity-label"><?php echo $complexity['complexity']; ?></span>
                                            <span class="complexity-count"><?php echo $complexity['count']; ?> projects</span>
                                        </div>
                                        <div class="complexity-rate">
                                            <span class="rate-value"><?php echo round($complexity['approval_rate'], 1); ?>%</span>
                                            <span class="rate-label">approval</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Contributors and Weekly Performance Row -->
                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Top Contributors</h3>
                                    <p class="chart-subtitle">Most active project submitters</p>
                                </div>
                            </div>
                            <div class="contributors-list">
                                <?php foreach ($top_contributors as $index => $contributor) : ?>
                                    <div class="contributor-item">
                                        <div class="contributor-rank">#<?php echo $index + 1; ?></div>
                                        <div class="contributor-avatar">
                                            <?php echo strtoupper(substr($contributor['submitter_name'], 0, 1)); ?>
                                        </div>
                                        <div class="contributor-info">
                                            <div class="contributor-name"><?php echo htmlspecialchars($contributor['submitter_name']); ?></div>
                                            <div class="contributor-stats">
                                                <span class="stat-item">
                                                    <i class="fas fa-project-diagram"></i>
                                                    <?php echo $contributor['project_count']; ?> projects
                                                </span>
                                                <span class="stat-item">
                                                    <i class="fas fa-check-circle"></i>
                                                    <?php echo round($contributor['approval_rate'], 1); ?>% approved
                                                </span>
                                            </div>
                                        </div>
                                        <div class="contributor-progress">
                                            <div class="progress-bar" style="width: <?php echo $contributor['approval_rate']; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Weekly Performance</h3>
                                    <p class="chart-subtitle">Submissions vs approval rates</p>
                                </div>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="weeklyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Timeline Analysis -->
                <div class="row g-4">
                    <div class="col-12">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Project Timeline Analysis</h3>
                                    <p class="chart-subtitle">Project status distribution over time (Last 90 days)</p>
                                </div>
                                <div class="chart-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="exportChart('timelineChart')">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                            <div class="chart-wrapper" style="height: 300px;">
                                <canvas id="timelineChart"></canvas>
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
        if (typeof Chart !== 'undefined') {
            initializeCharts();
        } else {
            console.error('Chart.js library not loaded');
        }
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

        // User Engagement Chart
        const engagementCtx = document.getElementById('engagementChart');
        if (engagementCtx) {
            const engagementData = <?php echo json_encode($engagement_metrics); ?>;
            const dates = engagementData.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }).reverse();
            const submissions = engagementData.map(item => item.submissions).reverse();
            const uniqueUsers = engagementData.map(item => item.unique_users).reverse();

            new Chart(engagementCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Submissions',
                        data: submissions,
                        borderColor: 'rgba(99, 102, 241, 1)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }, {
                        label: 'Active Users',
                        data: uniqueUsers,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end'
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
                    }
                }
            });
        }

        // Project Complexity Chart
        const complexityCtx = document.getElementById('complexityChart');
        if (complexityCtx) {
            const complexityData = <?php echo json_encode($complexity_analysis); ?>;
            const complexityLabels = complexityData.map(item => item.complexity);
            const complexityCounts = complexityData.map(item => item.count);
            const complexityColors = ['#10b981', '#f59e0b', '#ef4444'];

            new Chart(complexityCtx, {
                type: 'doughnut',
                data: {
                    labels: complexityLabels,
                    datasets: [{
                        data: complexityCounts,
                        backgroundColor: complexityColors.slice(0, complexityLabels.length),
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
                    cutout: '65%'
                }
            });
        }

        // Weekly Performance Chart
        const weeklyCtx = document.getElementById('weeklyChart');
        if (weeklyCtx) {
            const weeklyData = <?php echo json_encode($weekly_performance); ?>;
            const weekLabels = weeklyData.map(item => `W${item.week_num}`).reverse();
            const weeklySubmissions = weeklyData.map(item => item.submissions).reverse();
            const weeklyApprovalRates = weeklyData.map(item => parseFloat(item.approval_rate)).reverse();

            new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: weekLabels,
                    datasets: [{
                        label: 'Submissions',
                        data: weeklySubmissions,
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    }, {
                        label: 'Approval Rate (%)',
                        data: weeklyApprovalRates,
                        type: 'line',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Submissions'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            min: 0,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Approval Rate (%)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }

        // Project Timeline Chart
        const timelineCtx = document.getElementById('timelineChart');
        if (timelineCtx) {
            const timelineData = <?php echo json_encode($completion_timeline); ?>;

            // Process timeline data for stacked chart
            const timelineMap = {};
            timelineData.forEach(item => {
                const day = item.days_since_submission;
                if (!timelineMap[day]) {
                    timelineMap[day] = { approved: 0, pending: 0, rejected: 0 };
                }
                timelineMap[day][item.status] = item.count;
            });

            const sortedDays = Object.keys(timelineMap).sort((a, b) => b - a).slice(0, 30);
            const approvedData = sortedDays.map(day => timelineMap[day].approved || 0);
            const pendingData = sortedDays.map(day => timelineMap[day].pending || 0);
            const rejectedData = sortedDays.map(day => timelineMap[day].rejected || 0);
            const dayLabels = sortedDays.map(day => `${day}d ago`);

            new Chart(timelineCtx, {
                type: 'bar',
                data: {
                    labels: dayLabels.reverse(),
                    datasets: [{
                        label: 'Approved',
                        data: approvedData.reverse(),
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Pending',
                        data: pendingData.reverse(),
                        backgroundColor: 'rgba(245, 158, 11, 0.8)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Rejected',
                        data: rejectedData.reverse(),
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
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

    // Toggle engagement view
    function toggleEngagementView(view) {
        const buttons = document.querySelectorAll('.btn-group .btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        // Here you would switch between daily and weekly views
        console.log(`Switching to ${view} view`);
    }

    // Export chart function
    function exportChart(chartId) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
        button.disabled = true;

        setTimeout(() => {
            const canvas = document.getElementById(chartId);
            if (canvas) {
                const url = canvas.toDataURL('image/png');
                const link = document.createElement('a');
                link.download = `${chartId}_export.png`;
                link.href = url;
                link.click();
            }
            button.innerHTML = originalText;
            button.disabled = false;
        }, 1500);
    }

    // Show notification function for GitHub AJAX operations
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : (type === 'success' ? 'check-circle' : 'info-circle')}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" class="notification-close">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
</script>

<!-- GitHub Profile AJAX Integration -->
<script>
// Make showNotification globally available for GitHub profile operations
window.showNotification = showNotification;
</script>

</body>
</html>
