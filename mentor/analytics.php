<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: http://localhost/IdeaNest/Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Get mentor statistics
$stats_query = "SELECT 
                (SELECT COUNT(*) FROM mentor_student_pairs WHERE mentor_id = ? AND status = 'active') as active_students,
                (SELECT COUNT(*) FROM mentor_student_pairs WHERE mentor_id = ? AND status = 'completed') as completed_students,
                (SELECT AVG(rating) FROM mentor_student_pairs WHERE mentor_id = ? AND rating IS NOT NULL) as avg_rating,
                (SELECT COUNT(*) FROM mentoring_sessions ms JOIN mentor_student_pairs msp ON ms.pair_id = msp.id WHERE msp.mentor_id = ? AND ms.status = 'completed') as total_sessions";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iiii", $mentor_id, $mentor_id, $mentor_id, $mentor_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get monthly pairing data
$monthly_query = "SELECT 
                  DATE_FORMAT(paired_at, '%Y-%m') as month,
                  COUNT(*) as pairings
                  FROM mentor_student_pairs 
                  WHERE mentor_id = ? AND paired_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                  GROUP BY DATE_FORMAT(paired_at, '%Y-%m')
                  ORDER BY month";
$stmt = $conn->prepare($monthly_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$monthly_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get project type distribution
$project_types_query = "SELECT 
                        p.project_type,
                        COUNT(*) as count
                        FROM projects p
                        JOIN mentor_student_pairs msp ON msp.student_id = p.user_id
                        WHERE msp.mentor_id = ? AND p.status = 'approved'
                        GROUP BY p.project_type";
$stmt = $conn->prepare($project_types_query);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$project_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent activity
$activity_query = "SELECT 
                   'pairing' as type, 
                   CONCAT('Paired with ', r.name) as activity,
                   msp.paired_at as date
                   FROM mentor_student_pairs msp
                   JOIN register r ON msp.student_id = r.id
                   WHERE msp.mentor_id = ?
                   UNION ALL
                   SELECT 
                   'session' as type,
                   CONCAT('Session with ', r.name) as activity,
                   ms.session_date as date
                   FROM mentoring_sessions ms
                   JOIN mentor_student_pairs msp ON ms.pair_id = msp.id
                   JOIN register r ON msp.student_id = r.id
                   WHERE msp.mentor_id = ? AND ms.status = 'completed'
                   ORDER BY date DESC LIMIT 10";
$stmt = $conn->prepare($activity_query);
$stmt->bind_param("ii", $mentor_id, $mentor_id);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="glass-card p-4">
            <h2><i class="fas fa-chart-line text-primary me-2"></i>Analytics</h2>
            <p class="text-muted">Your mentoring performance and insights</p>
        </div>
    </div>
</div>

<!-- Key Metrics -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-users fa-2x text-primary mb-2"></i>
            <h3><?= $stats['active_students'] ?></h3>
            <p class="mb-0">Active Students</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-trophy fa-2x text-success mb-2"></i>
            <h3><?= $stats['completed_students'] ?></h3>
            <p class="mb-0">Completed</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-star fa-2x text-warning mb-2"></i>
            <h3><?= number_format($stats['avg_rating'] ?? 0, 1) ?></h3>
            <p class="mb-0">Avg Rating</p>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="glass-card p-3 text-center">
            <i class="fas fa-calendar-check fa-2x text-info mb-2"></i>
            <h3><?= $stats['total_sessions'] ?></h3>
            <p class="mb-0">Total Sessions</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Monthly Pairings Chart -->
    <div class="col-lg-8 mb-4">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Monthly Pairings</h5>
            </div>
            <div class="card-body p-4">
                <canvas id="monthlyChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Project Types -->
    <div class="col-lg-4 mb-4">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Project Types</h5>
            </div>
            <div class="card-body p-4">
                <canvas id="projectTypesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="glass-card">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body p-4">
                <?php if (empty($activities)) : ?>
                    <div class="text-center py-3">
                        <i class="fas fa-history fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No recent activity</p>
                    </div>
                <?php else : ?>
                    <div class="timeline">
                        <?php foreach ($activities as $activity) : ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-<?= $activity['type'] == 'pairing' ? 'success' : 'info' ?> rounded-circle p-2 me-3">
                                    <i class="fas fa-<?= $activity['type'] == 'pairing' ? 'handshake' : 'video' ?> text-white"></i>
                                </div>
                                <div>
                                    <p class="mb-0"><?= htmlspecialchars($activity['activity']) ?></p>
                                    <small class="text-muted"><?= date('M j, Y g:i A', strtotime($activity['date'])) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Monthly Pairings Chart
const monthlyData = <?= json_encode($monthly_data) ?>;
const monthlyLabels = monthlyData.map(item => {
    const date = new Date(item.month + '-01');
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
});
const monthlyValues = monthlyData.map(item => item.pairings);

const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'New Pairings',
            data: monthlyValues,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            tension: 0.4,
            fill: true
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

// Project Types Chart
const projectTypesData = <?= json_encode($project_types) ?>;
const typeLabels = projectTypesData.map(item => item.project_type.charAt(0).toUpperCase() + item.project_type.slice(1));
const typeValues = projectTypesData.map(item => item.count);

const typesCtx = document.getElementById('projectTypesChart').getContext('2d');
new Chart(typesCtx, {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [{
            data: typeValues,
            backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
renderLayout('Analytics', $content);
?>