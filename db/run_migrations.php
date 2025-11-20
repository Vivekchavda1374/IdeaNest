<?php
/**
 * Database Migration Runner
 * Run this file once to create the necessary tables for credential management
 */

require_once '../Login/Login/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Migration</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
    <div class='card shadow'>
        <div class='card-header bg-primary text-white'>
            <h3 class='mb-0'><i class='bi bi-database'></i> Database Migration</h3>
        </div>
        <div class='card-body'>";

// Create temp_credentials table
$sql1 = "CREATE TABLE IF NOT EXISTS `temp_credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` enum('mentor','subadmin') NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `plain_password` varchar(255) NOT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `email_attempts` int(11) DEFAULT 0,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + INTERVAL 7 DAY),
  PRIMARY KEY (`id`),
  KEY `idx_user_type_id` (`user_type`, `user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

echo "<div class='alert alert-info'>Creating temp_credentials table...</div>";
if ($conn->query($sql1) === TRUE) {
    echo "<div class='alert alert-success'><i class='bi bi-check-circle'></i> temp_credentials table created successfully!</div>";
} else {
    echo "<div class='alert alert-danger'><i class='bi bi-x-circle'></i> Error creating temp_credentials table: " . $conn->error . "</div>";
}

// Create email_logs table
$sql2 = "CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `email_type` varchar(100) DEFAULT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient_email`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

echo "<div class='alert alert-info'>Creating email_logs table...</div>";
if ($conn->query($sql2) === TRUE) {
    echo "<div class='alert alert-success'><i class='bi bi-check-circle'></i> email_logs table created successfully!</div>";
} else {
    echo "<div class='alert alert-danger'><i class='bi bi-x-circle'></i> Error creating email_logs table: " . $conn->error . "</div>";
}

// Create user_notifications table
$sql3 = "CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` enum('project_approved','project_rejected','project_submitted','mentor_assigned','mentor_request','session_scheduled','general') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL COMMENT 'Project ID, Mentor ID, etc.',
  `related_type` varchar(50) DEFAULT NULL COMMENT 'project, mentor, session, etc.',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `action_url` varchar(500) DEFAULT NULL COMMENT 'URL to view details',
  `icon` varchar(50) DEFAULT 'bi-bell' COMMENT 'Bootstrap icon class',
  `color` varchar(20) DEFAULT 'primary' COMMENT 'Badge color',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_user_unread` (`user_id`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

echo "<div class='alert alert-info'>Creating user_notifications table...</div>";
if ($conn->query($sql3) === TRUE) {
    echo "<div class='alert alert-success'><i class='bi bi-check-circle'></i> user_notifications table created successfully!</div>";
} else {
    echo "<div class='alert alert-danger'><i class='bi bi-x-circle'></i> Error creating user_notifications table: " . $conn->error . "</div>";
}

echo "<div class='alert alert-success mt-4'>
        <h5><i class='bi bi-check-circle'></i> Migration Complete!</h5>
        <p>All database tables have been created successfully.</p>
        <ul class='mb-3'>
            <li>temp_credentials - Password storage</li>
            <li>email_logs - Email tracking</li>
            <li>user_notifications - User notifications</li>
        </ul>
        <a href='../Admin/admin.php' class='btn btn-primary'>Go to Admin Dashboard</a>
      </div>";

echo "</div>
    </div>
</div>
</body>
</html>";

$conn->close();
?>
