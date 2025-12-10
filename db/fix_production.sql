-- Quick fix for production database issues
-- Run this AFTER the main import to fix foreign key and view issues

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Drop and recreate temp_credentials with correct syntax
DROP TABLE IF EXISTS `temp_credentials`;
CREATE TABLE `temp_credentials` (
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
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_type_id` (`user_type`,`user_id`),
  KEY `idx_email_sent` (`email_sent`,`email_sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Recreate views without DEFINER (fixes SUPER privilege errors)
DROP VIEW IF EXISTS `leaderboard_view`;
CREATE VIEW `leaderboard_view` AS 
SELECT 
  `r`.`id` AS `id`, 
  `r`.`name` AS `user_name`, 
  `r`.`email` AS `email`, 
  `r`.`user_image` AS `profile_image`, 
  COALESCE(`up`.`total_points`, 0) AS `total_points`, 
  COALESCE(`up`.`level`, 1) AS `level`, 
  COALESCE(`up`.`current_streak`, 0) AS `current_streak`, 
  COALESCE(`up`.`longest_streak`, 0) AS `longest_streak`, 
  COUNT(DISTINCT `ub`.`badge_id`) AS `badges_count`, 
  COUNT(DISTINCT `p`.`id`) AS `projects_count`, 
  COUNT(DISTINCT `b`.`id`) AS `ideas_count`, 
  (SELECT COUNT(0) FROM `idea_likes` WHERE `idea_likes`.`idea_id` IN 
    (SELECT `blog`.`id` FROM `blog` WHERE `blog`.`user_id` = `r`.`id`)) AS `total_likes` 
FROM `register` `r` 
LEFT JOIN `user_points` `up` ON `r`.`id` = `up`.`user_id` 
LEFT JOIN `user_badges` `ub` ON `r`.`id` = `ub`.`user_id` 
LEFT JOIN `projects` `p` ON `r`.`id` = `p`.`user_id` 
LEFT JOIN `blog` `b` ON `r`.`id` = `b`.`user_id` 
GROUP BY `r`.`id` 
ORDER BY `up`.`total_points` DESC, `up`.`level` DESC;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify tables exist
SELECT 'Database repair completed!' AS status;
SELECT COUNT(*) AS total_tables FROM information_schema.tables 
WHERE table_schema = DATABASE();
