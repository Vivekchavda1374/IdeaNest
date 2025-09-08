-- Add GitHub integration fields to register table
ALTER TABLE `register` 
ADD COLUMN `github_username` VARCHAR(100) DEFAULT NULL AFTER `last_notification_sent`,
ADD COLUMN `github_token` TEXT DEFAULT NULL AFTER `github_username`,
ADD COLUMN `github_profile_url` VARCHAR(255) DEFAULT NULL AFTER `github_token`,
ADD COLUMN `github_repos_count` INT DEFAULT 0 AFTER `github_profile_url`,
ADD COLUMN `github_followers` INT DEFAULT 0 AFTER `github_repos_count`,
ADD COLUMN `github_following` INT DEFAULT 0 AFTER `github_followers`,
ADD COLUMN `github_bio` TEXT DEFAULT NULL AFTER `github_following`,
ADD COLUMN `github_location` VARCHAR(100) DEFAULT NULL AFTER `github_bio`,
ADD COLUMN `github_company` VARCHAR(100) DEFAULT NULL AFTER `github_location`,
ADD COLUMN `github_last_sync` TIMESTAMP NULL DEFAULT NULL AFTER `github_company`;

-- Create table for storing user's GitHub repositories
CREATE TABLE IF NOT EXISTS `user_github_repos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `repo_name` VARCHAR(255) NOT NULL,
    `repo_full_name` VARCHAR(255) NOT NULL,
    `repo_description` TEXT DEFAULT NULL,
    `repo_url` VARCHAR(255) NOT NULL,
    `language` VARCHAR(50) DEFAULT NULL,
    `stars_count` INT DEFAULT 0,
    `forks_count` INT DEFAULT 0,
    `is_private` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `synced_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `register`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_repo` (`user_id`, `repo_full_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;