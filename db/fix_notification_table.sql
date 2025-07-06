-- Fix Notification Logs Table
-- Add missing columns to existing notification_logs table

-- Check if columns exist and add them if they don't
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'notification_logs' 
     AND COLUMN_NAME = 'email_to') = 0,
    'ALTER TABLE `notification_logs` ADD COLUMN `email_to` varchar(255) DEFAULT NULL AFTER `error_message`',
    'SELECT "email_to column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'notification_logs' 
     AND COLUMN_NAME = 'email_subject') = 0,
    'ALTER TABLE `notification_logs` ADD COLUMN `email_subject` varchar(255) DEFAULT NULL AFTER `email_to`',
    'SELECT "email_subject column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes if they don't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'notification_logs' 
     AND INDEX_NAME = 'idx_type') = 0,
    'ALTER TABLE `notification_logs` ADD INDEX `idx_type` (`type`)',
    'SELECT "idx_type index already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'notification_logs' 
     AND INDEX_NAME = 'idx_user_id') = 0,
    'ALTER TABLE `notification_logs` ADD INDEX `idx_user_id` (`user_id`)',
    'SELECT "idx_user_id index already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'notification_logs' 
     AND INDEX_NAME = 'idx_project_id') = 0,
    'ALTER TABLE `notification_logs` ADD INDEX `idx_project_id` (`project_id`)',
    'SELECT "idx_project_id index already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'notification_logs' 
     AND INDEX_NAME = 'idx_status') = 0,
    'ALTER TABLE `notification_logs` ADD INDEX `idx_status` (`status`)',
    'SELECT "idx_status index already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'notification_logs' 
     AND INDEX_NAME = 'idx_created_at') = 0,
    'ALTER TABLE `notification_logs` ADD INDEX `idx_created_at` (`created_at`)',
    'SELECT "idx_created_at index already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Now insert sample data
INSERT INTO `notification_logs` (`type`, `user_id`, `project_id`, `status`, `email_to`, `email_subject`, `created_at`) VALUES
('new_user_notification', 1, NULL, 'sent', 'ideanest.ict@gmail.com', 'New User Registration - IdeaNest', NOW()),
('project_approval', 2, 1, 'sent', 'user@example.com', 'Congratulations! Your Project "Test Project" Has Been Approved', NOW()),
('project_rejection', 3, 2, 'sent', 'user2@example.com', 'Important Update About Your Project "Another Project"', NOW())
ON DUPLICATE KEY UPDATE 
    `email_to` = VALUES(`email_to`),
    `email_subject` = VALUES(`email_subject`);

-- Show the updated table structure
DESCRIBE `notification_logs`;

-- Show sample data
SELECT * FROM `notification_logs` ORDER BY `created_at` DESC LIMIT 5; 