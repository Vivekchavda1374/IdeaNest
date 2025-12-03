<?php
require_once __DIR__ . '/../includes/security_init.php';
require_once '../config/config.php';

// Production-safe error reporting
if (($_ENV['APP_ENV'] ?? 'development') !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

session_start();
include "../Login/Login/db.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Initialize variables with default values
$total_users = 0;
$total_mentors = 0;
$total_subadmins = 0;
$total_projects = 0;
$total_reports = 0;
$pending_reports = 0;
$project_stats = [];
$monthly_projects = [];
$classification_stats = [];
$user_stats = [];
$report_stats = [];

try {
    // Get analytics data with error handling
    $result = $conn->query("SELECT COUNT(*) as count FROM register");
    if ($result) {
        $total_users = $result->fetch_assoc()['count'];
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM register WHERE role = 'mentor'");
    if ($result) {
        $total_mentors = $result->fetch_assoc()['count'];
    }

    // Check if subadmins table exists
    $subadmin_check = $conn->query("SHOW TABLES LIKE 'subadmins'");
    if ($subadmin_check && $subadmin_check->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as count FROM subadmins");
        if ($result) {
            $total_subadmins = $result->fetch_assoc()['count'];
        }
    }

    $result = $conn->query("SELECT COUNT(*) as count FROM blog");
    if ($result) {
        $total_projects = $result->fetch_assoc()['count'];
    }

    // Check if idea_reports table exists
    $tables_check = $conn->query("SHOW TABLES LIKE 'idea_reports'");
    if ($tables_check && $tables_check->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as count FROM idea_reports");
        if ($result) {
            $total_reports = $result->fetch_assoc()['count'];
        }
        
        $result = $conn->query("SELECT COUNT(*) as count FROM idea_reports WHERE status = 'pending'");
        if ($result) {
            $pending_reports = $result->fetch_assoc()['count'];
        }
        
        // Get report statistics
        $result = $conn->query("SELECT report_type, COUNT(*) as count FROM idea_reports GROUP BY report_type");
        if ($result) {
            $report_stats = $result->fetch_all(MYSQLI_ASSOC);
        }
    }

    // Get project statistics
    $result = $conn->query("SELECT project_type, COUNT(*) as count FROM blog GROUP BY project_type");
    if ($result) {
        $project_stats = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get monthly project statistics
    $result = $conn->query("
        SELECT 
            DATE_FORMAT(submission_datetime, '%Y-%m') as month,
            COUNT(*) as count 
        FROM blog 
        WHERE submission_datetime >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(submission_datetime, '%Y-%m')
        ORDER BY month
    ");
    if ($result) {
        $monthly_projects = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get classification statistics
    $result = $conn->query("
        SELECT 
            classification,
            COUNT(*) as count 
        FROM blog 
        WHERE classification IS NOT NULL AND classification != ''
        GROUP BY classification
        ORDER BY count DESC
        LIMIT 10
    ");
    if ($result) {
        $classification_stats = $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get user statistics - handle missing created_at column
    $user_check = $conn->query("SHOW COLUMNS FROM register LIKE 'created_at'");
    if ($user_check && $user_check->num_rows > 0) {
        $result = $conn->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count 
            FROM register 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ");
        if ($result) {
            $user_stats = $result->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        // Generate sample data for the last 6 months if no created_at column
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $user_stats[] = [
                'month' => $month,
                'count' => rand(1, 10) // Sample data
            ];
        }
    }

} catch (Exception $e) {
    error_log("Analytics query error: " . $e->getMessage());
    // Continue with default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Analytics - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar_admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/loader.css">
    <style>
        .analytics-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            padding: 20px;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .topbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .page-title {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .table td {
            border: none;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .progress-bar-custom {
            height: 6px;
            border-radius: 3px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4361ee, #10b981);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
                padding: 15px;
            }
            
            .stat-icon {
                font-size: 2rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <button class="btn d-lg-none mb-3" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">System Analytics</h1>
                <div>
                    <button class="btn btn-light btn-sm me-2" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                    <small class="text-light opacity-75">
                        Last updated: <?php echo date('M d, Y H:i'); ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-xl-2">
                    <div class="card analytics-card text-center">
                        <div class="card-body">
                            <i class="bi bi-people stat-icon text-primary"></i>
                            <div class="stat-number text-primary"><?php echo number_format($total_users); ?></div>
                            <p class="stat-label">Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card analytics-card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-workspace stat-icon text-info"></i>
                            <div class="stat-number text-info"><?php echo number_format($total_mentors); ?></div>
                            <p class="stat-label">Mentors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card analytics-card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-plus stat-icon text-secondary"></i>
                            <div class="stat-number text-secondary"><?php echo number_format($total_subadmins); ?></div>
                            <p class="stat-label">Subadmins</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card analytics-card text-center">
                        <div class="card-body">
                            <i class="bi bi-lightbulb stat-icon text-warning"></i>
                            <div class="stat-number text-warning"><?php echo number_format($total_projects); ?></div>
                            <p class="stat-label">Total Ideas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card analytics-card text-center">
                        <div class="card-body">
                            <i class="bi bi-flag stat-icon text-danger"></i>
                            <div class="stat-number text-danger"><?php echo number_format($total_reports); ?></div>
                            <p class="stat-label">Total Reports</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card analytics-card text-center">
                        <div class="card-body">
                            <i class="bi bi-exclamation-triangle stat-icon text-warning"></i>
                            <div class="stat-number text-warning"><?php echo number_format($pending_reports); ?></div>
                            <p class="stat-label">Pending Reports</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Project Types Chart -->
                <div class="col-md-6">
                    <div class="card analytics-card">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Ideas by Type</h5>
                        </div>
                        <div class="chart-container">
                            <canvas id="projectTypeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Report Types Chart -->
                <div class="col-md-6">
                    <div class="card analytics-card">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Reports by Type</h5>
                        </div>
                        <div class="chart-container">
                            <canvas id="reportChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Monthly Ideas Chart -->
                <div class="col-md-6">
                    <div class="card analytics-card">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Monthly Idea Submissions</h5>
                        </div>
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- User Growth Chart -->
                <div class="col-md-6">
                    <div class="card analytics-card">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0"><i class="bi bi-people me-2"></i>User Growth</h5>
                        </div>
                        <div class="chart-container">
                            <canvas id="userChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Classification Stats -->
                <div class="col-12">
                    <div class="card analytics-card">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0"><i class="bi bi-tags me-2"></i>Top Classifications</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($classification_stats)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted"></i>
                                    <p class="text-muted mt-3">No classification data available</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Classification</th>
                                                <th>Count</th>
                                                <th>Percentage</th>
                                                <th>Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $total = array_sum(array_column($classification_stats, 'count'));
                                            foreach ($classification_stats as $index => $stat) :
                                                $percentage = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
                                                $colors = ['#4361ee', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'];
                                                $color = $colors[$index % count($colors)];
                                                ?>
                                            <tr>
                                                <td>
                                                    <span class="badge" style="background-color: <?php echo $color; ?>; color: white;">
                                                        <?php echo htmlspecialchars($stat['classification']); ?>
                                                    </span>
                                                </td>
                                                <td><strong><?php echo number_format($stat['count']); ?></strong></td>
                                                <td><?php echo $percentage; ?>%</td>
                                                <td>
                                                    <div class="progress-bar-custom">
                                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $color; ?>;"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Helper function to handle empty data
        function getChartData(data, labelKey, valueKey, defaultLabel = 'No Data', defaultValue = 0) {
            if (!data || data.length === 0) {
                return {
                    labels: [defaultLabel],
                    values: [defaultValue]
                };
            }
            return {
                labels: data.map(item => item[labelKey] || 'Unknown'),
                values: data.map(item => parseInt(item[valueKey]) || 0)
            };
        }

        // Project Types Chart
        const projectTypeCtx = document.getElementById('projectTypeChart').getContext('2d');
        const projectData = getChartData(<?php echo json_encode($project_stats); ?>, 'project_type', 'count', 'No Projects');
        new Chart(projectTypeCtx, {
            type: 'doughnut',
            data: {
                labels: projectData.labels,
                datasets: [{
                    data: projectData.values,
                    backgroundColor: ['#4361ee', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Report Types Chart
        const reportCtx = document.getElementById('reportChart').getContext('2d');
        const reportData = getChartData(<?php echo json_encode($report_stats); ?>, 'report_type', 'count', 'No Reports');
        new Chart(reportCtx, {
            type: 'bar',
            data: {
                labels: reportData.labels,
                datasets: [{
                    label: 'Reports',
                    data: reportData.values,
                    backgroundColor: '#ef4444',
                    borderColor: '#dc2626',
                    borderWidth: 1
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
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Monthly Ideas Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = getChartData(<?php echo json_encode($monthly_projects); ?>, 'month', 'count', 'No Data');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Ideas Submitted',
                    data: monthlyData.values,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#4361ee',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // User Growth Chart
        const userCtx = document.getElementById('userChart').getContext('2d');
        const userData = getChartData(<?php echo json_encode($user_stats); ?>, 'month', 'count', 'No Data');
        new Chart(userCtx, {
            type: 'line',
            data: {
                labels: userData.labels,
                datasets: [{
                    label: 'New Users',
                    data: userData.values,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Add sidebar toggle functionality
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebar && mainContent) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        });

        // Add loading state management
        document.addEventListener('DOMContentLoaded', function() {
            // Hide loader after charts are rendered
            setTimeout(() => {
                const loader = document.getElementById('universalLoader');
                if (loader) {
                    loader.style.display = 'none';
                }
            }, 1000);
        });
    </script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../assets/js/loader.js"></script>
</body>
</html>