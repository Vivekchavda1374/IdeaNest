<?php
session_start();
include "../Login/Login/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Login/Login/login.php");
    exit();
}

// Get analytics data
$project_stats = $conn->query("
    SELECT 
        project_type,
        COUNT(*) as count 
    FROM projects 
    GROUP BY project_type
")->fetch_all(MYSQLI_ASSOC);

$monthly_projects = $conn->query("
    SELECT 
        DATE_FORMAT(submission_date, '%Y-%m') as month,
        COUNT(*) as count 
    FROM projects 
    WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(submission_date, '%Y-%m')
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

$classification_stats = $conn->query("
    SELECT 
        classification,
        COUNT(*) as count 
    FROM projects 
    WHERE classification IS NOT NULL
    GROUP BY classification
    ORDER BY count DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
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
            <div class="row g-4">
                <!-- Project Types Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Projects by Type</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="projectTypeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Monthly Projects Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Monthly Project Submissions</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyChart"></canvas>
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

        // Monthly Projects Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_projects, 'month')); ?>,
                datasets: [{
                    label: 'Projects',
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
    </script>
</body>
</html>