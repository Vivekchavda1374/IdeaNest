-- Migration: Create user following tables and views
-- Date: 2025-12-05
-- Description: Creates all tables and views needed for user following functionality

-- 1. Create the user_follows table
CREATE TABLE IF NOT EXISTS `user_follows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `follower_id` int(11) NOT NULL COMMENT 'User who is following',
  `following_id` int(11) NOT NULL COMMENT 'User being followed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notification_enabled` tinyint(1) DEFAULT 1 COMMENT 'Notify follower of new content',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_follow` (`follower_id`,`following_id`),
  KEY `idx_follower` (`follower_id`),
  KEY `idx_following` (`following_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create the user_follow_stats table
CREATE TABLE IF NOT EXISTS `user_follow_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `followers_count` int(11) DEFAULT 0 COMMENT 'Number of followers',
  `following_count` int(11) DEFAULT 0 COMMENT 'Number of users being followed',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_stats` (`user_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cached follower/following counts for performance';

-- 3. Create the user_follow_feed view
CREATE OR REPLACE VIEW `user_follow_feed` AS 
SELECT 'idea' AS `content_type`, 
       `b`.`id` AS `content_id`, 
       `b`.`user_id` AS `user_id`, 
       `b`.`project_name` AS `title`, 
       `b`.`description` AS `description`, 
       `b`.`classification` AS `classification`, 
       `b`.`project_type` AS `project_type`, 
       `b`.`submission_datetime` AS `created_at`, 
       `b`.`status` AS `status`, 
       (SELECT COUNT(*) FROM `idea_likes` WHERE `idea_likes`.`idea_id` = `b`.`id`) AS `likes_count`, 
       (SELECT COUNT(*) FROM `idea_comments` WHERE `idea_comments`.`idea_id` = `b`.`id`) AS `comments_count`, 
       (SELECT COUNT(*) FROM `idea_views` WHERE `idea_views`.`idea_id` = `b`.`id`) AS `views_count` 
FROM `blog` AS `b` 
WHERE `b`.`status` IN ('pending','in_progress','completed')
UNION ALL 
SELECT 'project' AS `content_type`,
       `p`.`id` AS `content_id`,
       `p`.`user_id` AS `user_id`,
       `p`.`project_name` AS `title`,
       `p`.`description` AS `description`,
       `p`.`classification` AS `classification`,
       `p`.`project_type` AS `project_type`,
       `p`.`submission_date` AS `created_at`,
       `p`.`status` AS `status`,
       0 AS `likes_count`,
       0 AS `comments_count`,
       0 AS `views_count` 
FROM `projects` `p` 
WHERE `p`.`status` = 'pending' 
UNION ALL 
SELECT 'approved_project' AS `content_type`,
       `ap`.`id` AS `content_id`,
       CAST(`ap`.`user_id` AS UNSIGNED) AS `user_id`,
       `ap`.`project_name` AS `title`,
       `ap`.`description` AS `description`,
       `ap`.`classification` AS `classification`,
       `ap`.`project_type` AS `project_type`,
       `ap`.`submission_date` AS `created_at`,
       `ap`.`status` AS `status`,
       0 AS `likes_count`,
       0 AS `comments_count`,
       0 AS `views_count` 
FROM `admin_approved_projects` `ap` 
WHERE `ap`.`status` = 'approved';
