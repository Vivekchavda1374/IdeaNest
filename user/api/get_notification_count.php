<?php
require_once __DIR__ . '/../../includes/security_init.php';
session_start();
header('Content-Type: application/json');

require_once '../../Login/Login/db.php';
require_once '../../includes/notification_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$notifier = new NotificationHelper($conn);

// Get unread count
$count = $notifier->getUnreadCount($user_id);

echo json_encode([
    'success' => true,
    'count' => $count
]);
