<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "../Login/Login/db.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get analytics data
$total_users = $conn->query("SELECT COUNT(*) as count FROM register")->fetch_assoc()['count'];
$total_mentors = $conn->query("SELECT COUNT(*) as count FROM register WHERE role = 'mentor'")->fetch_assoc()['count'];
$total_subadmins = $conn->query("SELECT COUNT(*) as count FROM subadmins")->fetch_assoc()['count'];
$total_projects = $conn->query("SELECT COUNT(*) as count FROM blog")->fetch_assoc()['count'];
// Check if tables exist before querying
$tables_check = $conn->query("SHOW TABLES LIKE 'idea_reports'");
$total_reports = 0;
$pending_reports = 0;
if ($tables_check->num_rows > 0) {
    $total_reports = $conn->query("SELECT COUNT(*) as count FROM idea_reports")->fetch_assoc()['count'];
    $pending_reports = $conn->query("SELECT COUNT(*) as count FROM idea_reports WHERE status = 'pending'")->fetch_assoc()['count'];
}

$project_stats = $conn->query("
    SELECT 
        project_type,
        COUNT(*) as count 
    FROM blog 
    GROUP BY project_type
")->fetch_all(MYSQLI_ASSOC);

$monthly_projects = $conn->query("
    SELECT 
        DATE_FORMAT(submission_datetime, '%Y-%m') as month,
        COUNT(*) as count 
    FROM blog 
    WHERE submission_datetime >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(submission_datetime, '%Y-%m')
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

$classification_stats = $conn->query("
    SELECT 
        classification,
        COUNT(*) as count 
    FROM blog 
    WHERE classification IS NOT NULL
    GROUP BY classification
    ORDER BY count DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$user_stats = [];
$user_check = $conn->query("SHOW COLUMNS FROM register LIKE 'created_at'");
if ($user_check->num_rows > 0) {
    $user_stats = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count 
        FROM register 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ")->fetch_all(MYSQLI_ASSOC);
} else {
    // Fallback if created_at column doesn't exist
    $user_stats = $conn->query("
        SELECT 
            DATE_FORMAT(NOW(), '%Y-%m') as month,
            COUNT(*) as count 
        FROM register 
        GROUP BY DATE_FORMAT(NOW(), '%Y-%m')
    ")->fetch_all(MYSQLI_ASSOC);
}

$report_stats = [];
if ($tables_check->num_rows > 0) {
    $report_stats = $conn->query("
        SELECT 
            report_type,
            COUNT(*) as count 
        FROM idea_reports 
        GROUP BY report_type
    ")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Analytics - IdeaNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/sidebar_admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    
    <div class="main-content">
        <div class="topbar">
            <h1 class="page-title">System Analytics</h1>
        </div>

        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-people fs-1 text-primary"></i>
                            <h3 class="mt-2"><?php echo $total_users; ?></h3>
                            <p class="text-muted">Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-workspace fs-1 text-info"></i>
                            <h3 class="mt-2"><?php echo $total_mentors; ?></h3>
                            <p class="text-muted">Mentors</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-plus fs-1 text-secondary"></i>
                            <h3 class="mt-2"><?php echo $total_subadmins; ?></h3>
                            <p class="text-muted">Subadmins</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-lightbulb fs-1 text-warning"></i>
                            <h3 class="mt-2"><?php echo $total_projects; ?></h3>
                            <p class="text-muted">Total Ideas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-flag fs-1 text-danger"></i>
                            <h3 class="mt-2"><?php echo $total_reports; ?></h3>
                            <p class="text-muted">Total Reports</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                            <h3 class="mt-2"><?php echo $pending_reports; ?></h3>
                            <p class="text-muted">Pending Reports</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Project Types Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Ideas by Type</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="projectTypeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Report Types Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Reports by Type</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="reportChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Monthly Ideas Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Monthly Idea Submissions</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- User Growth Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>User Growth</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="userChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Classification Stats -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Top Classifications</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Classification</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total = array_sum(array_column($classification_stats, 'count'));
                                        foreach($classification_stats as $stat): 
                                            $percentage = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['classification']); ?></td>
                                            <td><?php echo $stat['count']; ?></td>
                                            <td><?php echo $percentage; ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Project Types Chart
        const projectTypeCtx = document.getElementById('projectTypeChart').getContext('2d');
        new Chart(projectTypeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($project_stats, 'project_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($project_stats, 'count')); ?>,
                    backgroundColor: ['#4361ee', '#10b981', '#f59e0b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Report Types Chart
        const reportCtx = document.getElementById('reportChart').getContext('2d');
        new Chart(reportCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($report_stats, 'report_type')); ?>,
                datasets: [{
                    label: 'Reports',
                    data: <?php echo json_encode(array_column($report_stats, 'count')); ?>,
                    backgroundColor: '#ef4444'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Ideas Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_projects, 'month')); ?>,
                datasets: [{
                    label: 'Ideas',
                    data: <?php echo json_encode(array_column($monthly_projects, 'count')); ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // User Growth Chart
        const userCtx = document.getElementById('userChart').getContext('2d');
        new Chart(userCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($user_stats, 'month')); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column($user_stats, 'count')); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>