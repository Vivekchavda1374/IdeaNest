<?php
session_start();
require_once '../config/config.php';
require_once '../Login/Login/db.php';
require_once '../includes/credential_manager.php';
require_once '../includes/email_logger.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$credManager = new CredentialManager($conn);
$emailLogger = new EmailLogger($conn);

// Handle resend request
if (isset($_POST['resend_credential'])) {
    $credential_id = intval($_POST['credential_id']);
    $result = $credManager->resendCredentials($credential_id);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Get all unsent credentials
$unsent_credentials = $credManager->getUnsentCredentials(50);

// Get email statistics
$email_stats = $emailLogger->getStats(30);
$failed_emails = $emailLogger->getFailedEmails(20);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Credentials - IdeaNest Admin</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .credential-card {
            border-left: 4px solid #dc3545;
            background: #fff3cd;
        }
        .credential-card.sent {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .password-field {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            border: 1px solid #dee2e6;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
    <link rel="stylesheet" href="assets/css/loader.css">
    <link rel="stylesheet" href="assets/css/loading.css">
</head>
<body>
<?php include 'sidebar_admin.php'; ?>

<div class="main-content">
    <div class="topbar">
        <button class="btn d-lg-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title">Credential Management</h1>
    </div>

    <div class="dashboard-content">
        <?php if (isset($success)) : ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)) : ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-x-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Email Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h6>Total Emails (30 days)</h6>
                    <h2><?= $email_stats['total'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #1dd1a1 0%, #10ac84 100%);">
                    <h6>Successfully Sent</h6>
                    <h2><?= $email_stats['sent'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
                    <h6>Failed</h6>
                    <h2><?= $email_stats['failed'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card" style="background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%);">
                    <h6>Pending</h6>
                    <h2><?= $email_stats['pending'] ?></h2>
                </div>
            </div>
        </div>

        <!-- Unsent Credentials -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="card-title mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Unsent Credentials (<?= count($unsent_credentials) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($unsent_credentials)) : ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        All credentials have been successfully emailed!
                    </div>
                <?php else : ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Important:</strong> These credentials were not successfully emailed. Please resend or manually share them.
                    </div>
                    
                    <?php foreach ($unsent_credentials as $cred) : ?>
                        <div class="credential-card card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <span class="badge bg-<?= $cred['user_type'] === 'mentor' ? 'primary' : 'info' ?> fs-6">
                                            <?= strtoupper($cred['user_type']) ?>
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Email:</strong><br>
                                        <?= htmlspecialchars($cred['email']) ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Password:</strong><br>
                                        <code class="password-field"><?= htmlspecialchars($cred['plain_password']) ?></code>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">
                                            Attempts: <?= $cred['email_attempts'] ?><br>
                                            Created: <?= date('M d, Y', strtotime($cred['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="credential_id" value="<?= $cred['id'] ?>">
                                            <button type="submit" name="resend_credential" class="btn btn-sm btn-primary w-100">
                                                <i class="bi bi-envelope me-1"></i>Resend Email
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php if ($cred['error_message']) : ?>
                                    <div class="mt-2">
                                        <small class="text-danger">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            Error: <?= htmlspecialchars($cred['error_message']) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Failed Emails -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-x-circle me-2"></i>
                    Recent Failed Emails (<?= count($failed_emails) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($failed_emails)) : ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        No failed emails in the last 20 attempts!
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Recipient</th>
                                    <th>Subject</th>
                                    <th>Type</th>
                                    <th>Error</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($failed_emails as $log) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['recipient_email']) ?></td>
                                        <td><?= htmlspecialchars($log['subject']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($log['email_type']) ?></span></td>
                                        <td><small class="text-danger"><?= htmlspecialchars($log['error_message']) ?></small></td>
                                        <td><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="assets/js/loader.js"></script>
<script src="assets/js/loading.js"></script>
</body>
</html>
