-- Migration to add user notification system
-- This table stores all user notifications for project activities

CREATE TABLE IF NOT EXISTS `user_notifications` (
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
  FOREIGN KEY (`user_id`) REFERENCES `register`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add index for faster queries
CREATE INDEX idx_user_unread ON user_notifications(user_id, is_read, created_at);
