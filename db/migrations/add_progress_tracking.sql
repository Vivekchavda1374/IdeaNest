-- Progress Tracking System Tables
-- Add these tables to support the new progress tracking features

-- Progress Milestones Table
CREATE TABLE IF NOT EXISTS `progress_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pair_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_date` date NOT NULL,
  `completed_date` datetime DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `completion_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pair_id` (`pair_id`),
  KEY `status` (`status`),
  KEY `target_date` (`target_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Progress Notes Table
CREATE TABLE IF NOT EXISTS `progress_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pair_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `category` enum('general','achievement','concern','feedback') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pair_id` (`pair_id`),
  KEY `mentor_id` (`mentor_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add reminder fields to mentoring_sessions table
ALTER TABLE `mentoring_sessions` 
ADD COLUMN IF NOT EXISTS `reminder_sent` tinyint(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `immediate_reminder_sent` tinyint(1) DEFAULT 0;

-- Mentoring Sessions Archive Table (for old sessions)
CREATE TABLE IF NOT EXISTS `mentoring_sessions_archive` (
  `id` int(11) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `session_date` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `meeting_link` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','missed') DEFAULT 'scheduled',
  `reminder_sent` tinyint(1) DEFAULT 0,
  `immediate_reminder_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pair_id` (`pair_id`),
  KEY `session_date` (`session_date`),
  KEY `archived_at` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_projects_user_status ON projects(user_id, status);
CREATE INDEX IF NOT EXISTS idx_approved_projects_user ON admin_approved_projects(user_id);
CREATE INDEX IF NOT EXISTS idx_mentor_requests_status ON mentor_requests(mentor_id, status);
CREATE INDEX IF NOT EXISTS idx_mentor_pairs_status ON mentor_student_pairs(mentor_id, status);
