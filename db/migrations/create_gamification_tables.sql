-- Migration: Create gamification system tables
-- Date: 2025-12-05
-- Description: Creates all tables needed for the gamification system

-- 1. User Points Table
CREATE TABLE IF NOT EXISTS `user_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `level` int(11) DEFAULT 1,
  `current_streak` int(11) DEFAULT 0,
  `longest_streak` int(11) DEFAULT 0,
  `last_activity_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_total_points` (`total_points`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Points History Table
CREATE TABLE IF NOT EXISTS `points_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Badges Table
CREATE TABLE IF NOT EXISTS `badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `requirement_type` varchar(50) NOT NULL,
  `requirement_value` int(11) NOT NULL,
  `points_reward` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_requirement_type` (`requirement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. User Badges Table
CREATE TABLE IF NOT EXISTS `user_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_badge` (`user_id`, `badge_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_badge_id` (`badge_id`),
  KEY `idx_earned_at` (`earned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default badges
INSERT INTO `badges` (`name`, `description`, `icon`, `category`, `requirement_type`, `requirement_value`, `points_reward`, `is_active`) VALUES
('First Steps', 'Submit your first idea', 'fa-rocket', 'ideas', 'ideas_submitted', 1, 10, 1),
('Idea Machine', 'Submit 10 ideas', 'fa-lightbulb', 'ideas', 'ideas_submitted', 10, 50, 1),
('Innovation Master', 'Submit 50 ideas', 'fa-trophy', 'ideas', 'ideas_submitted', 50, 200, 1),
('Project Pioneer', 'Submit your first project', 'fa-folder-open', 'projects', 'projects_submitted', 1, 10, 1),
('Project Pro', 'Submit 5 projects', 'fa-briefcase', 'projects', 'projects_submitted', 5, 50, 1),
('Project Legend', 'Submit 20 projects', 'fa-crown', 'projects', 'projects_submitted', 20, 200, 1),
('Popular Creator', 'Get 50 likes on your content', 'fa-heart', 'engagement', 'total_likes', 50, 50, 1),
('Viral Sensation', 'Get 200 likes on your content', 'fa-fire', 'engagement', 'total_likes', 200, 150, 1),
('Conversation Starter', 'Receive 25 comments', 'fa-comments', 'engagement', 'total_comments', 25, 50, 1),
('Community Leader', 'Receive 100 comments', 'fa-users', 'engagement', 'total_comments', 100, 150, 1),
('Week Warrior', 'Maintain a 7-day streak', 'fa-calendar-check', 'streaks', 'streak_days', 7, 30, 1),
('Month Master', 'Maintain a 30-day streak', 'fa-calendar-alt', 'streaks', 'streak_days', 30, 100, 1),
('Year Champion', 'Maintain a 365-day streak', 'fa-medal', 'streaks', 'streak_days', 365, 500, 1),
('Rising Star', 'Reach level 5', 'fa-star', 'levels', 'level_reached', 5, 50, 1),
('Elite Member', 'Reach level 10', 'fa-gem', 'levels', 'level_reached', 10, 100, 1),
('Legend', 'Reach level 25', 'fa-dragon', 'levels', 'level_reached', 25, 300, 1);
