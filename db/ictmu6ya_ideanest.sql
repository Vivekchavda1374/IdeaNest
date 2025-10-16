-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 03, 2025 at 10:48 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ictmu6ya_ideanest`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_approved_projects`
--

CREATE TABLE `admin_approved_projects` (
  `id` int(5) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_type` varchar(50) NOT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `project_category` varchar(100) DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced','expert') DEFAULT NULL,
  `development_time` varchar(50) DEFAULT NULL,
  `team_size` varchar(50) DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `project_goals` text DEFAULT NULL,
  `challenges_faced` text DEFAULT NULL,
  `future_enhancements` text DEFAULT NULL,
  `github_repo` varchar(255) DEFAULT NULL,
  `live_demo_url` varchar(255) DEFAULT NULL,
  `project_license` varchar(100) DEFAULT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `social_links` text DEFAULT NULL,
  `description` text NOT NULL,
  `language` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `code_file_path` varchar(255) DEFAULT NULL,
  `instruction_file_path` varchar(255) DEFAULT NULL,
  `presentation_file_path` varchar(255) DEFAULT NULL,
  `additional_files_path` varchar(255) DEFAULT NULL,
  `submission_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog`
--

CREATE TABLE `blog` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `er_number` varchar(50) NOT NULL,
  `project_name` varchar(100) NOT NULL,
  `project_type` enum('software','hardware') NOT NULL,
  `classification` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `submission_datetime` datetime NOT NULL,
  `status` enum('pending','in_progress','completed','rejected') DEFAULT 'pending',
  `priority1` enum('low','medium','high') DEFAULT 'medium',
  `assigned_to` varchar(100) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookmark`
--

CREATE TABLE `bookmark` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `idea_id` int(11) DEFAULT 0,
  `bookmarked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment_likes`
--

CREATE TABLE `comment_likes` (
  `id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_ideas`
--

CREATE TABLE `deleted_ideas` (
  `id` int(11) NOT NULL,
  `original_idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `deletion_reason` text NOT NULL,
  `deleted_by_admin` int(11) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `denial_projects`
--

CREATE TABLE `denial_projects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `project_type` varchar(100) DEFAULT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `project_category` varchar(100) DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced','expert') DEFAULT NULL,
  `development_time` varchar(50) DEFAULT NULL,
  `team_size` varchar(50) DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `project_goals` text DEFAULT NULL,
  `challenges_faced` text DEFAULT NULL,
  `future_enhancements` text DEFAULT NULL,
  `github_repo` varchar(255) DEFAULT NULL,
  `live_demo_url` varchar(255) DEFAULT NULL,
  `project_license` varchar(100) DEFAULT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `social_links` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `language` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `code_file_path` varchar(255) DEFAULT NULL,
  `instruction_file_path` varchar(255) DEFAULT NULL,
  `presentation_file_path` varchar(255) DEFAULT NULL,
  `additional_files_path` varchar(255) DEFAULT NULL,
  `submission_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `rejection_date` datetime DEFAULT current_timestamp(),
  `rejection_reason` text DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `idea_comments`
--

CREATE TABLE `idea_comments` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idea_likes`
--

CREATE TABLE `idea_likes` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idea_reports`
--

CREATE TABLE `idea_reports` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idea_warnings`
--

CREATE TABLE `idea_warnings` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `warning_reason` text NOT NULL,
  `warning_sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_id` int(11) DEFAULT NULL,
  `status` enum('sent','failed') DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentoring_sessions`
--

CREATE TABLE `mentoring_sessions` (
  `id` int(11) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `session_date` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `notes` text DEFAULT NULL,
  `meeting_link` varchar(500) DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reminder_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentors`
--

CREATE TABLE `mentors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(255) NOT NULL,
  `experience_years` int(11) DEFAULT 0,
  `max_students` int(11) DEFAULT 5,
  `current_students` int(11) DEFAULT 0,
  `bio` text DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_activity_logs`
--

CREATE TABLE `mentor_activity_logs` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_email_logs`
--

CREATE TABLE `mentor_email_logs` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `email_type` enum('welcome_message','session_invitation','session_reminder','project_feedback','progress_update') NOT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_email_queue`
--

CREATE TABLE `mentor_email_queue` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `email_type` enum('welcome_message','session_invitation','session_reminder','project_feedback','progress_update') NOT NULL,
  `email_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`email_data`)),
  `priority` tinyint(4) DEFAULT 5,
  `status` enum('pending','processing','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 3,
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_email_stats`
--

CREATE TABLE `mentor_email_stats` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `emails_sent` int(11) DEFAULT 0,
  `emails_failed` int(11) DEFAULT 0,
  `welcome_emails` int(11) DEFAULT 0,
  `session_invitations` int(11) DEFAULT 0,
  `session_reminders` int(11) DEFAULT 0,
  `project_feedback` int(11) DEFAULT 0,
  `progress_updates` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_project_access`
--

CREATE TABLE `mentor_project_access` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_requests`
--

CREATE TABLE `mentor_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentor_student_pairs`
--

CREATE TABLE `mentor_student_pairs` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `paired_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `welcome_sent` tinyint(1) DEFAULT 0,
  `last_progress_email` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_counters`
--

CREATE TABLE `notification_counters` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_to` varchar(255) DEFAULT NULL,
  `email_subject` varchar(255) DEFAULT NULL,
  `email_body` text DEFAULT NULL,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_logs`
--

INSERT INTO `notification_logs` (`id`, `type`, `user_id`, `project_id`, `status`, `created_at`, `email_to`, `email_subject`, `email_body`, `error_message`) VALUES
(1, 'new_user_notification', 1, NULL, 'failed', '2025-09-27 05:17:06', 'ideanest.ict@gmail.com', 'New User Registration - IdeaNest', NULL, 'Email notifications are disabled');

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `variables` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(5) NOT NULL,
  `user_id` int(15) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_type` varchar(50) NOT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `project_category` varchar(100) DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced','expert') DEFAULT NULL,
  `development_time` varchar(50) DEFAULT NULL,
  `team_size` varchar(50) DEFAULT NULL,
  `target_audience` text DEFAULT NULL,
  `project_goals` text DEFAULT NULL,
  `challenges_faced` text DEFAULT NULL,
  `future_enhancements` text DEFAULT NULL,
  `github_repo` varchar(255) DEFAULT NULL,
  `live_demo_url` varchar(255) DEFAULT NULL,
  `project_license` varchar(100) DEFAULT NULL,
  `keywords` varchar(500) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `social_links` text DEFAULT NULL,
  `description` text NOT NULL,
  `language` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `code_file_path` varchar(255) DEFAULT NULL,
  `instruction_file_path` varchar(255) DEFAULT NULL,
  `presentation_file_path` varchar(255) DEFAULT NULL,
  `additional_files_path` varchar(255) DEFAULT NULL,
  `submission_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `title` varchar(255) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `project_comments`
--

CREATE TABLE `project_comments` (
  `id` int(11) NOT NULL,
  `project_id` int(5) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `comment_text` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_likes`
--

CREATE TABLE `project_likes` (
  `id` int(11) NOT NULL,
  `project_id` int(5) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `realtime_notifications`
--

CREATE TABLE `realtime_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `enrollment_number` varchar(50) NOT NULL,
  `gr_number` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `about` varchar(500) NOT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `passout_year` year(4) NOT NULL,
  `user_image` text NOT NULL DEFAULT '',
  `google_id` varchar(255) DEFAULT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `last_notification_sent` datetime DEFAULT NULL,
  `github_token` text DEFAULT NULL,
  `role` enum('student','mentor','admin') DEFAULT 'student',
  `expertise` text DEFAULT NULL,
  `mentor_rating` decimal(3,2) DEFAULT 0.00,
  `is_available` tinyint(1) DEFAULT 1,
  `github_username` varchar(100) DEFAULT NULL,
  `github_profile_url` varchar(255) DEFAULT NULL,
  `github_repos_count` int(11) DEFAULT 0,
  `github_last_sync` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`id`, `name`, `email`, `enrollment_number`, `gr_number`, `password`, `about`, `phone_no`, `department`, `passout_year`, `user_image`, `google_id`, `email_notifications`, `last_notification_sent`, `github_token`, `role`, `expertise`, `mentor_rating`, `is_available`, `github_username`, `github_profile_url`, `github_repos_count`, `github_last_sync`) VALUES
(1, 'Vivek', 'viveksinhchavda@gmail.com', '92200133026', '119486', '$2y$10$feM86hXmBoWO7ThecWQifulVl6RzSrzwiCvGTPd8ZtHLd9TeyKChu', '', '09104231590', 'ICT', '2026', '', '116644441139882349952', 1, NULL, NULL, 'student', NULL, 0.00, 1, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `removed_user`
--

CREATE TABLE `removed_user` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `enrollment_number` varchar(100) DEFAULT NULL,
  `gr_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_email_preferences`
--

CREATE TABLE `student_email_preferences` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `receive_session_reminders` tinyint(1) DEFAULT 1,
  `receive_progress_updates` tinyint(1) DEFAULT 1,
  `receive_project_feedback` tinyint(1) DEFAULT 1,
  `receive_welcome_emails` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subadmins`
--

CREATE TABLE `subadmins` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `domain` varchar(100) DEFAULT NULL,
  `domains` text DEFAULT NULL,
  `profile_complete` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subadmins`
--

INSERT INTO `subadmins` (`id`, `email`, `password`, `name`, `domain`, `domains`, `profile_complete`, `status`, `created_at`, `last_login`) VALUES
(5, 'vivek.chavda119486@marwadiuniversity.ac.in', '$2y$10$viI9wnQ7orbtt1kHJa5TPO1G8nntixlFI7f0DhdK2vy7ODV3Xrrtm', NULL, NULL, NULL, 0, 'active', '2025-10-03 08:38:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subadmin_classification_requests`
--

CREATE TABLE `subadmin_classification_requests` (
  `id` int(11) NOT NULL,
  `subadmin_id` int(11) NOT NULL,
  `requested_domains` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `decision_date` timestamp NULL DEFAULT NULL,
  `admin_comment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `subadmin_id` int(11) NOT NULL,
  `subadmin_name` varchar(100) NOT NULL,
  `subadmin_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('technical','account','project','bug_report','feature_request','other') NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `admin_response` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `attachments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_ticket_replies`
--

CREATE TABLE `support_ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_type` enum('admin','subadmin') NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_project_ownership`
--

CREATE TABLE `temp_project_ownership` (
  `project_id` int(11) NOT NULL,
  `user_session` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','moderator','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_approved_projects`
--
ALTER TABLE `admin_approved_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_project_category` (`project_category`),
  ADD KEY `idx_difficulty_level` (`difficulty_level`),
  ADD KEY `idx_development_time` (`development_time`),
  ADD KEY `idx_team_size` (`team_size`),
  ADD KEY `idx_project_license` (`project_license`),
  ADD KEY `idx_type_category_status` (`project_type`,`project_category`,`status`),
  ADD KEY `idx_difficulty_type` (`difficulty_level`,`project_type`),
  ADD KEY `idx_submission_status` (`submission_date`,`status`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `blog`
--
ALTER TABLE `blog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_er_number` (`er_number`),
  ADD KEY `idx_project_type` (`project_type`),
  ADD KEY `idx_classification` (`classification`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_blog_user_id` (`user_id`),
  ADD KEY `idx_blog_status` (`status`),
  ADD KEY `idx_blog_priority` (`priority1`),
  ADD KEY `idx_blog_submission_date` (`submission_datetime`),
  ADD KEY `idx_blog_title` (`title`);

--
-- Indexes for table `bookmark`
--
ALTER TABLE `bookmark`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bookmark` (`project_id`,`user_id`);

--
-- Indexes for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_comment_like` (`comment_id`,`user_id`),
  ADD KEY `idx_comment_id` (`comment_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `deleted_ideas`
--
ALTER TABLE `deleted_ideas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_deleted_by` (`deleted_by_admin`);

--
-- Indexes for table `denial_projects`
--
ALTER TABLE `denial_projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `idea_comments`
--
ALTER TABLE `idea_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_idea_comments_idea_id` (`idea_id`),
  ADD KEY `idx_idea_comments_user_id` (`user_id`),
  ADD KEY `idx_idea_comments_created_at` (`created_at`),
  ADD KEY `idx_comments_count` (`idea_id`);

--
-- Indexes for table `idea_likes`
--
ALTER TABLE `idea_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`idea_id`,`user_id`),
  ADD KEY `idx_idea_likes_idea_id` (`idea_id`),
  ADD KEY `idx_idea_likes_user_id` (`user_id`),
  ADD KEY `idx_likes_count` (`idea_id`);

--
-- Indexes for table `idea_reports`
--
ALTER TABLE `idea_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `idea_warnings`
--
ALTER TABLE `idea_warnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_idea_id` (`idea_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `mentoring_sessions`
--
ALTER TABLE `mentoring_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pair_id` (`pair_id`);

--
-- Indexes for table `mentors`
--
ALTER TABLE `mentors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mentor_activity_logs`
--
ALTER TABLE `mentor_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mentor_activity` (`mentor_id`,`created_at`),
  ADD KEY `idx_student_activity` (`student_id`,`created_at`);

--
-- Indexes for table `mentor_email_logs`
--
ALTER TABLE `mentor_email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mentor_id` (`mentor_id`),
  ADD KEY `idx_recipient_id` (`recipient_id`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `mentor_email_queue`
--
ALTER TABLE `mentor_email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_priority` (`status`,`priority`),
  ADD KEY `idx_scheduled_at` (`scheduled_at`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `mentor_email_stats`
--
ALTER TABLE `mentor_email_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mentor_date` (`mentor_id`,`date`);

--
-- Indexes for table `mentor_project_access`
--
ALTER TABLE `mentor_project_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mentor_student_project` (`mentor_id`,`student_id`,`project_id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_mentor_project_access_mentor` (`mentor_id`),
  ADD KEY `idx_mentor_project_access_student` (`student_id`);

--
-- Indexes for table `mentor_requests`
--
ALTER TABLE `mentor_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_mentor_requests_student_status` (`student_id`,`status`),
  ADD KEY `idx_mentor_requests_mentor_status` (`mentor_id`,`status`);

--
-- Indexes for table `mentor_student_pairs`
--
ALTER TABLE `mentor_student_pairs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `notification_counters`
--
ALTER TABLE `notification_counters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_type_status` (`type`,`status`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_type` (`type`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_category` (`project_category`),
  ADD KEY `idx_difficulty_level` (`difficulty_level`),
  ADD KEY `idx_development_time` (`development_time`),
  ADD KEY `idx_team_size` (`team_size`),
  ADD KEY `idx_project_license` (`project_license`),
  ADD KEY `idx_type_category_status` (`project_type`,`project_category`,`status`),
  ADD KEY `idx_difficulty_type` (`difficulty_level`,`project_type`),
  ADD KEY `idx_submission_status` (`submission_date`,`status`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_project_type` (`project_type`),
  ADD KEY `idx_projects_title` (`title`);

--
-- Indexes for table `project_comments`
--
ALTER TABLE `project_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_parent_comment` (`parent_comment_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `project_likes`
--
ALTER TABLE `project_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`project_id`,`user_id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `realtime_notifications`
--
ALTER TABLE `realtime_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `er_number` (`enrollment_number`),
  ADD UNIQUE KEY `gr_number` (`gr_number`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_register_email` (`email`),
  ADD KEY `idx_register_enrollment` (`enrollment_number`),
  ADD KEY `idx_github_username` (`github_username`);

--
-- Indexes for table `removed_user`
--
ALTER TABLE `removed_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_email_preferences`
--
ALTER TABLE `student_email_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `subadmins`
--
ALTER TABLE `subadmins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `subadmin_classification_requests`
--
ALTER TABLE `subadmin_classification_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subadmin_id` (`subadmin_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_subadmin_id` (`subadmin_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_ticket_number` (`ticket_number`);

--
-- Indexes for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`);

--
-- Indexes for table `temp_project_ownership`
--
ALTER TABLE `temp_project_ownership`
  ADD PRIMARY KEY (`project_id`,`user_session`),
  ADD KEY `idx_session` (`user_session`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_approved_projects`
--
ALTER TABLE `admin_approved_projects`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog`
--
ALTER TABLE `blog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookmark`
--
ALTER TABLE `bookmark`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deleted_ideas`
--
ALTER TABLE `deleted_ideas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `denial_projects`
--
ALTER TABLE `denial_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `idea_comments`
--
ALTER TABLE `idea_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `idea_likes`
--
ALTER TABLE `idea_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `idea_reports`
--
ALTER TABLE `idea_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `idea_warnings`
--
ALTER TABLE `idea_warnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentoring_sessions`
--
ALTER TABLE `mentoring_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentors`
--
ALTER TABLE `mentors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_activity_logs`
--
ALTER TABLE `mentor_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_email_logs`
--
ALTER TABLE `mentor_email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_email_queue`
--
ALTER TABLE `mentor_email_queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_email_stats`
--
ALTER TABLE `mentor_email_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_project_access`
--
ALTER TABLE `mentor_project_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_requests`
--
ALTER TABLE `mentor_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_student_pairs`
--
ALTER TABLE `mentor_student_pairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_counters`
--
ALTER TABLE `notification_counters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_comments`
--
ALTER TABLE `project_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_likes`
--
ALTER TABLE `project_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `realtime_notifications`
--
ALTER TABLE `realtime_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `removed_user`
--
ALTER TABLE `removed_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_email_preferences`
--
ALTER TABLE `student_email_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subadmins`
--
ALTER TABLE `subadmins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `subadmin_classification_requests`
--
ALTER TABLE `subadmin_classification_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blog`
--
ALTER TABLE `blog`
  ADD CONSTRAINT `fk_blog_user` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bookmark`
--
ALTER TABLE `bookmark`
  ADD CONSTRAINT `bookmark_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `admin_approved_projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `project_comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_comments`
--
ALTER TABLE `idea_comments`
  ADD CONSTRAINT `idea_comments_ibfk_1` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `idea_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_likes`
--
ALTER TABLE `idea_likes`
  ADD CONSTRAINT `idea_likes_ibfk_1` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `idea_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_warnings`
--
ALTER TABLE `idea_warnings`
  ADD CONSTRAINT `fk_idea_warnings_idea` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_idea_warnings_user` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentoring_sessions`
--
ALTER TABLE `mentoring_sessions`
  ADD CONSTRAINT `mentoring_sessions_ibfk_1` FOREIGN KEY (`pair_id`) REFERENCES `mentor_student_pairs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentors`
--
ALTER TABLE `mentors`
  ADD CONSTRAINT `mentors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_activity_logs`
--
ALTER TABLE `mentor_activity_logs`
  ADD CONSTRAINT `mentor_activity_logs_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_activity_logs_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `register` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mentor_email_logs`
--
ALTER TABLE `mentor_email_logs`
  ADD CONSTRAINT `mentor_email_logs_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_email_logs_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_email_queue`
--
ALTER TABLE `mentor_email_queue`
  ADD CONSTRAINT `mentor_email_queue_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_email_queue_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_email_stats`
--
ALTER TABLE `mentor_email_stats`
  ADD CONSTRAINT `mentor_email_stats_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_project_access`
--
ALTER TABLE `mentor_project_access`
  ADD CONSTRAINT `mentor_project_access_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_project_access_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_project_access_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_requests`
--
ALTER TABLE `mentor_requests`
  ADD CONSTRAINT `mentor_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_requests_ibfk_2` FOREIGN KEY (`mentor_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_requests_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mentor_student_pairs`
--
ALTER TABLE `mentor_student_pairs`
  ADD CONSTRAINT `mentor_student_pairs_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_student_pairs_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_student_pairs_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_comments`
--
ALTER TABLE `project_comments`
  ADD CONSTRAINT `project_comments_ibfk_1` FOREIGN KEY (`parent_comment_id`) REFERENCES `project_comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_email_preferences`
--
ALTER TABLE `student_email_preferences`
  ADD CONSTRAINT `student_email_preferences_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subadmin_classification_requests`
--
ALTER TABLE `subadmin_classification_requests`
  ADD CONSTRAINT `subadmin_classification_requests_ibfk_1` FOREIGN KEY (`subadmin_id`) REFERENCES `subadmins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `fk_support_tickets_subadmin` FOREIGN KEY (`subadmin_id`) REFERENCES `subadmins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  ADD CONSTRAINT `fk_ticket_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;