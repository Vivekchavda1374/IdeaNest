<?php
/**
 * Report Dashboard - Analytics and Report Generation
 * IdeaNest System
 */

session_start();
require_once '../Login/Login/db.php';
require_once '../config/security.php';
require_once '../includes/csrf_helper.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_id'])) {
    header('Location: ../Login/Login/login.php');
    exit();
}

// Determine user role
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$user_id = $_SESSION['user_id'] ?? null;

// Get date range from request
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'overview';

// Fetch statistics based on role
if ($is_admin) {
    // Admin can see all data
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM projects) as total_projects,
        (SELECT COUNT(*) FROM admin_approved_projects WHERE status = 'approved') as approved_projects,
        (SELECT COUNT(*) FROM projects WHERE status = 'pending') as pending_projects,
        (SELECT COUNT(*) FROM denial_projects) as rejected_projects,
        (SELECT COUNT(*) FROM register WHERE role = 'user') as total_users,
        (SELECT COUNT(*) FROM register WHERE role = 'mentor') as total_mentors,
        (SELECT COUNT(*) FROM blog) as total_ideas,
        (SELECT COUNT(*) FROM mentor_student_pairs WHERE status = 'active') as active_mentorships";
} else {
    // Regular users see only their data
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = '$user_id') as total_projects,
        (SELECT COUNT(*) FROM admin_approved_projects WHERE user_id = '$user_id' AND status = 'approved') as approved_projects,
        (SELECT COUNT(*) FROM projects WHERE user_id = '$user_id' AND status = 'pending') as pending_projects,
        (SELECT COUNT(*) FROM denial_projects WHERE user_id = '$user_id') as rejected_projects,
        (SELECT COUNT(*) FROM blog WHERE user_id = '$user_id') as total_ideas,
        (SELECT COUNT(*) FROM mentor_requests WHERE student_id = '$user_id' AND status = 'accepted') as active_mentorships";
}

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get project trends
$trends_query = "SELECT 
    DATE_FORMAT(submission_date, '%Y-%m') as month,
    COUNT(*) as count,
    status
    FROM admin_approved_projects 
    WHERE submission_date BETWEEN '$start_date' AND '$end_date'
    " . (!$is_admin ? "AND user_id = '$user_id'" : "") . "
    GROUP BY DATE_FORMAT(submission_date, '%Y-%m'), status
    ORDER BY month DESC";

$trends_result = $conn->query($trends_query);
$trends = [];
while ($row = $trends_result->fetch_assoc()) {
    $trends[] = $row;
}

// Get category distribution
$category_query = "SELECT 
    classification,
    COUNT(*) as count
    FROM admin_approved_projects 
    WHERE submission_date BETWEEN '$start_date' AND '$end_date'
    " . (!$is_admin ? "AND user_id = '$user_id'" : "") . "
    GROUP BY classification
    ORDER BY count DESC
    LIMIT 10";

$category_result = $conn->query($category_query);
$categories = [];
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row;
}

// Get top contributors (admin only)
$top_contributors = [];
if ($is_admin) {
    $contributors_query = "SELECT 
        r.name,
        r.email,
        COUNT(p.id) as project_count,
        AVG(CASE WHEN p.status = 'approved' THEN 1 ELSE 0 END) * 100 as approval_rate
        FROM register r
        LEFT JOIN admin_approved_projects p ON r.id = p.user_id
        WHERE p.submission_date BETWEEN '$start_date' AND '$end_date'
        GROUP BY r.id
        ORDER BY project_count DESC
        LIMIT 10";
    
    $contributors_result = $conn->query($contributors_query);
    while ($row = $contributors_result->fetch_assoc()) {
        $top_contributors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', system-ui, sans-serif;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin: 2rem 0;
        }
        
        .export-btn {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-chart-line me-2" style="color: var(--primary-color);"></i>
                        Reports & Analytics
                    </h1>
                    <p class="text-muted mb-0">Comprehensive insights and data visualization</p>
                </div>
                <div>
                    <a href="<?php echo $is_admin ? '../Admin/admin.php' : '../user/index.php'; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="filter-card">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-select">
                        <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                        <option value="projects" <?php echo $report_type === 'projects' ? 'selected' : ''; ?>>Projects</option>
                        <option value="users" <?php echo $report_type === 'users' ? 'selected' : ''; ?>>Users</option>
                        <option value="mentorship" <?php echo $report_type === 'mentorship' ? 'selected' : ''; ?>>Mentorship</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Apply Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['total_projects'] ?? 0; ?></span>
                    <span class="stat-label">Total Projects</span>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                    <span class="stat-number"><?php echo $stats['approved_projects'] ?? 0; ?></span>
                    <span class="stat-label">Approved</span>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--warning-color), #d97706);">
                    <span class="stat-number"><?php echo $stats['pending_projects'] ?? 0; ?></span>
                    <span class="stat-label">Pending</span>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--danger-color), #dc2626);">
                    <span class="stat-number"><?php echo $stats['rejected_projects'] ?? 0; ?></span>
                    <span class="stat-label">Rejected</span>
                </div>
            </div>
        </div>

        <?php if ($is_admin): ?>
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, var(--info-color), #2563eb);">
                    <span class="stat-number"><?php echo $stats['total_users'] ?? 0; ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <span class="stat-number"><?php echo $stats['total_mentors'] ?? 0; ?></span>
                    <span class="stat-label">Total Mentors</span>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #ec4899, #db2777);">
                    <span class="stat-number"><?php echo $stats['active_mentorships'] ?? 0; ?></span>
                    <span class="stat-label">Active Mentorships</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Charts -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><i class="fas fa-chart-bar me-2"></i>Project Trends</h4>
                        <button class="export-btn" onclick="exportChart('trendsChart')">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                    <div class="chart-container">
                        <canvas id="trendsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><i class="fas fa-chart-pie me-2"></i>Categories</h4>
                        <button class="export-btn" onclick="exportChart('categoryChart')">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($is_admin && !empty($top_contributors)): ?>
        <!-- Top Contributors -->
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-trophy me-2"></i>Top Contributors</h4>
                <button class="export-btn" onclick="exportTable()">
                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="contributorsTable">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Projects</th>
                            <th>Approval Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_contributors as $index => $contributor): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($contributor['name']); ?></td>
                            <td><?php echo htmlspecialchars($contributor['email']); ?></td>
                            <td><span class="badge bg-primary"><?php echo $contributor['project_count']; ?></span></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $contributor['approval_rate']; ?>%">
                                        <?php echo number_format($contributor['approval_rate'], 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Export Options -->
        <div class="glass-card">
            <h4 class="mb-3"><i class="fas fa-file-export me-2"></i>Export Reports</h4>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <button class="btn btn-success w-100" onclick="exportPDF()">
                        <i class="fas fa-file-pdf me-2"></i>Export as PDF
                    </button>
                </div>
                <div class="col-md-3 mb-3">
                    <button class="btn btn-info w-100" onclick="exportExcel()">
                        <i class="fas fa-file-excel me-2"></i>Export as Excel
                    </button>
                </div>
                <div class="col-md-3 mb-3">
                    <button class="btn btn-warning w-100" onclick="exportCSV()">
                        <i class="fas fa-file-csv me-2"></i>Export as CSV
                    </button>
                </div>
                <div class="col-md-3 mb-3">
                    <button class="btn btn-primary w-100" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Trends Chart
        const trendsData = <?php echo json_encode($trends); ?>;
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        
        const months = [...new Set(trendsData.map(d => d.month))];
        const approvedData = months.map(month => {
            const item = trendsData.find(d => d.month === month && d.status === 'approved');
            return item ? item.count : 0;
        });
        const pendingData = months.map(month => {
            const item = trendsData.find(d => d.month === month && d.status === 'pending');
            return item ? item.count : 0;
        });
        
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Approved',
                    data: approvedData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Pending',
                    data: pendingData,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Category Chart
        const categoryData = <?php echo json_encode($categories); ?>;
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(c => c.classification),
                datasets: [{
                    data: categoryData.map(c => c.count),
                    backgroundColor: [
                        '#667eea', '#764ba2', '#10b981', '#f59e0b', 
                        '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Export functions
        function exportPDF() {
            alert('PDF export functionality will be implemented with a library like jsPDF');
            // Implementation with jsPDF library
        }

        function exportExcel() {
            alert('Excel export functionality will be implemented with a library like SheetJS');
            // Implementation with SheetJS library
        }

        function exportCSV() {
            const data = <?php echo json_encode($trends); ?>;
            let csv = 'Month,Status,Count\n';
            data.forEach(row => {
                csv += `${row.month},${row.status},${row.count}\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'report_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
        }

        function exportChart(chartId) {
            const canvas = document.getElementById(chartId);
            const url = canvas.toDataURL('image/png');
            const a = document.createElement('a');
            a.href = url;
            a.download = chartId + '_' + new Date().toISOString().split('T')[0] + '.png';
            a.click();
        }

        function exportTable() {
            const table = document.getElementById('contributorsTable');
            let csv = '';
            
            // Headers
            const headers = table.querySelectorAll('thead th');
            headers.forEach((header, index) => {
                csv += header.textContent + (index < headers.length - 1 ? ',' : '\n');
            });
            
            // Rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    csv += cell.textContent.trim() + (index < cells.length - 1 ? ',' : '\n');
                });
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'contributors_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
        }
    </script>
</body>
</html>
