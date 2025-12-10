<?php
require_once __DIR__ . '/../includes/security_init.php';
session_start();
require_once '../config/config.php';
require_once '../Login/Login/db.php';
require_once '../includes/notification_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Login/Login/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$notifier = new NotificationHelper($conn);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_read'])) {
        $notification_id = intval($_POST['notification_id']);
        $notifier->markAsRead($notification_id, $user_id);
        header('Location: notifications.php');
        exit;
    } elseif (isset($_POST['mark_all_read'])) {
        $notifier->markAllAsRead($user_id);
        header('Location: notifications.php');
        exit;
    } elseif (isset($_POST['delete_notification'])) {
        $notification_id = intval($_POST['notification_id']);
        $notifier->deleteNotification($notification_id, $user_id);
        header('Location: notifications.php');
        exit;
    } elseif (isset($_POST['delete_all_read'])) {
        $notifier->deleteAllRead($user_id);
        header('Location: notifications.php');
        exit;
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get notifications
if ($filter === 'unread') {
    $notifications = $notifier->getUserNotifications($user_id, 100, true);
} else {
    $notifications = $notifier->getUserNotifications($user_id, 100, false);
}

// Get statistics
$stats = $notifier->getStats($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Anti-injection script - MUST be first -->
    <script src="../assets/js/anti_injection.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .notification-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .notification-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .stat-card.danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .notification-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .notification-card.unread {
            background: #f0f9ff;
            border-left-color: var(--info-color);
        }
        
        .notification-card.success {
            border-left-color: var(--success-color);
        }
        
        .notification-card.danger {
            border-left-color: var(--danger-color);
        }
        
        .notification-card.warning {
            border-left-color: var(--warning-color);
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .notification-icon.success {
            background: #d1fae5;
            color: var(--success-color);
        }
        
        .notification-icon.danger {
            background: #fee2e2;
            color: var(--danger-color);
        }
        
        .notification-icon.warning {
            background: #fef3c7;
            color: var(--warning-color);
        }
        
        .notification-icon.info {
            background: #dbeafe;
            color: var(--info-color);
        }
        
        .notification-icon.primary {
            background: #e0e7ff;
            color: var(--primary-color);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        
        .notification-message {
            color: #6b7280;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        
        .notification-time {
            font-size: 0.875rem;
            color: #9ca3af;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .empty-state {
            background: white;
            border-radius: 15px;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .empty-state-icon {
            font-size: 5rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .filter-tabs {
            background: white;
            border-radius: 10px;
            padding: 0.5rem;
            display: inline-flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-tab {
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #6b7280;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/loader.css">
</head>
<body>
    <div class="notification-container">
        <!-- Header -->
        <div class="notification-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="mb-0">
                    <i class="bi bi-bell-fill me-2" style="color: var(--primary-color);"></i>
                    Notifications
                </h1>
                <a href="../user/dashboard.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total'] ?></span>
                    <span class="stat-label">Total</span>
                </div>
                <div class="stat-card warning">
                    <span class="stat-number"><?= $stats['unread'] ?></span>
                    <span class="stat-label">Unread</span>
                </div>
                <div class="stat-card success">
                    <span class="stat-number"><?= $stats['approved'] ?></span>
                    <span class="stat-label">Approved</span>
                </div>
                <div class="stat-card danger">
                    <span class="stat-number"><?= $stats['rejected'] ?></span>
                    <span class="stat-label">Rejected</span>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons mt-3">
                <form method="POST" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-all me-1"></i>Mark All as Read
                    </button>
                </form>
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete all read notifications?')">
                    <button type="submit" name="delete_all_read" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i>Delete All Read
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
                All (<?= $stats['total'] ?>)
            </a>
            <a href="?filter=unread" class="filter-tab <?= $filter === 'unread' ? 'active' : '' ?>">
                Unread (<?= $stats['unread'] ?>)
            </a>
        </div>
        
        <!-- Notifications List -->
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-bell-slash"></i>
                </div>
                <h3>No Notifications</h3>
                <p class="text-muted">You're all caught up! No notifications to show.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card <?= $notification['is_read'] ? '' : 'unread' ?> <?= $notification['color'] ?>">
                    <div class="d-flex">
                        <div class="notification-icon <?= $notification['color'] ?>">
                            <i class="<?= $notification['icon'] ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">
                                <?= htmlspecialchars($notification['title']) ?>
                                <?php if (!$notification['is_read']): ?>
                                    <span class="badge bg-primary ms-2">New</span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-message">
                                <?= htmlspecialchars($notification['message']) ?>
                            </div>
                            <div class="notification-time">
                                <i class="bi bi-clock me-1"></i>
                                <?= date('M d, Y g:i A', strtotime($notification['created_at'])) ?>
                            </div>
                            
                            <div class="notification-actions">
                                <?php if ($notification['action_url']): ?>
                                    <a href="<?= htmlspecialchars($notification['action_url']) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye me-1"></i>View Details
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                        <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-check me-1"></i>Mark as Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this notification?')">
                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                    <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

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
