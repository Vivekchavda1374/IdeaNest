<?php
session_start();
require_once '../Login/Login/db.php';
require_once 'mentor_layout.php';

if (!isset($_SESSION['mentor_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$mentor_id = $_SESSION['mentor_id'];

// Initialize default values
$daily_stats = [];
$overall_stats = ['total_emails' => 0, 'sent_emails' => 0, 'failed_emails' => 0, 'success_rate' => 0];
$recent_emails = [];
$queue_stats = ['total_queued' => 0, 'pending' => 0, 'processing' => 0, 'failed_queued' => 0];

// Check if email tables exist
$table_check = $conn->query("SHOW TABLES LIKE 'mentor_email_logs'");
if ($table_check && $table_check->num_rows > 0) {
    try {
        // Get overall statistics
        $overall_query = "SELECT 
                            COUNT(*) as total_emails,
                            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_emails,
                            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_emails,
                            ROUND(AVG(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) * 100, 2) as success_rate
                          FROM mentor_email_logs 
                          WHERE mentor_id = ?";
        
        $stmt = $conn->prepare($overall_query);
        $stmt->bind_param("i", $mentor_id);
        $stmt->execute();
        $overall_stats = $stmt->get_result()->fetch_assoc();
        
        // Get recent email activity
        $recent_query = "SELECT mel.*, r.name as recipient_name, r.email as recipient_email 
                         FROM mentor_email_logs mel 
                         JOIN register r ON mel.recipient_id = r.id 
                         WHERE mel.mentor_id = ? 
                         ORDER BY mel.sent_at DESC 
                         LIMIT 20";
        
        $stmt = $conn->prepare($recent_query);
        $stmt->bind_param("i", $mentor_id);
        $stmt->execute();
        $recent_emails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Keep default values if queries fail
    }
}

ob_start();
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-line"></i> Email Dashboard</h2>
        <a href="send_email.php" class="btn btn-primary">
            <i class="fas fa-envelope"></i> Send Email
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <h3><?= $overall_stats['total_emails'] ?></h3>
                <p class="mb-0">Total Emails</p>
                <small>All time</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card success">
                <h3><?= $overall_stats['sent_emails'] ?></h3>
                <p class="mb-0">Successfully Sent</p>
                <small><?= $overall_stats['success_rate'] ?>% success rate</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card danger">
                <h3><?= $overall_stats['failed_emails'] ?></h3>
                <p class="mb-0">Failed</p>
                <small>Delivery failures</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <h3><?= $queue_stats['pending'] ?></h3>
                <p class="mb-0">Queued</p>
                <small>Pending delivery</small>
            </div>
        </div>
    </div>

    <!-- Setup Notice -->
    <?php if (empty($recent_emails) && $overall_stats['total_emails'] == 0): ?>
        <div class="alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Email System Setup</h5>
            <p>The email system is ready to use! To get started:</p>
            <ol>
                <li>Run the setup script: <code>php setup_mentor_email_system.php</code></li>
                <li>Configure SMTP settings in admin panel</li>
                <li>Start sending emails to your students</li>
            </ol>
            <a href="send_email.php" class="btn btn-primary">Send Your First Email</a>
        </div>
    <?php endif; ?>

    <!-- Recent Email Activity -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-history"></i> Recent Email Activity</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_emails)): ?>
                <p class="text-muted">No recent email activity.</p>
                <a href="send_email.php" class="btn btn-primary">Send Your First Email</a>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Recipient</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_emails as $email): ?>
                                <tr>
                                    <td><?= date('M j, Y g:i A', strtotime($email['sent_at'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($email['recipient_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($email['recipient_email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge email-type-badge bg-info">
                                            <?= ucfirst(str_replace('_', ' ', $email['email_type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $email['status'] === 'sent' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($email['status']) ?>
                                        </span>
                                        <?php if ($email['error_message']): ?>
                                            <br><small class="text-danger"><?= htmlspecialchars($email['error_message']) ?></small>
                                        <?php endif; ?>
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

<?php
$content = ob_get_clean();

$additionalCSS = '
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .stat-card.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .stat-card.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .stat-card.danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    .email-type-badge {
        font-size: 0.8em;
        padding: 4px 8px;
    }
';

renderLayout('Email Dashboard', $content, $additionalCSS);
?>