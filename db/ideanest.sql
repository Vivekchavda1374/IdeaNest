-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 18, 2025 at 08:03 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ideanest`
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

--
-- Dumping data for table `admin_approved_projects`
--

INSERT INTO `admin_approved_projects` (`id`, `user_id`, `project_name`, `project_type`, `classification`, `project_category`, `difficulty_level`, `development_time`, `team_size`, `target_audience`, `project_goals`, `challenges_faced`, `future_enhancements`, `github_repo`, `live_demo_url`, `project_license`, `keywords`, `contact_email`, `social_links`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `presentation_file_path`, `additional_files_path`, `submission_date`, `status`) VALUES
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (1, NULL, 'Arduino Project', 'hardware', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'this is my project', 'C++', 'uploads/images/WhatsApp Image 2025-03-01 at 13.13.29_3991b76d.jpg', '', '', '', NULL, NULL, '2025-03-01 23:50:01', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (2, NULL, 'bhaviik', 'hardware', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'vasd', 'vda', '', '', '', '', NULL, NULL, '2025-03-01 06:41:51', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (3, NULL, 'IdeaNest', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Collaboration with your Mentors ', 'HTML, CSS, JS, PHP, MYSQL', 'uploads/images/Screenshot 2025-03-01 125741.png', 'uploads/videos/2278095-hd_1920_1080_30fps.mp4', 'uploads/code_files/.gitignore', 'uploads/instructions/11.pdf', NULL, NULL, '2025-03-01 05:37:32', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (4, NULL, 'Github', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'This is a Test', 'Test13', '', '', '', '', NULL, NULL, '2025-04-06 02:51:09', 'pending'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (5, NULL, 'vivek', 'hardware', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dsa', 'ac', 'uploads/images/IdeaNest_Deployment_Diagram (1).png', '', '', '', NULL, NULL, '2025-03-26 01:25:40', 'pending'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (6, NULL, 'Mobil Park', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'this is a project', 'flutter , dart , firebase', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 00:31:34', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (7, NULL, 'vivek', 'hardware', 'iot', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'gewG', 'FSB', NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-06 03:25:05', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (8, NULL, '123', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EFC', 'EC', NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-06 03:27:18', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (9, NULL, 'FleetLedger', 'software', 'mobile', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kje', 'mldads', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 01:57:44', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (10, NULL, 'vivek', 'hardware', 'iot', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kj ra', 'ms', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:02:41', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (11, NULL, 'vivek11', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ww', 'w', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:06:37', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (12, NULL, 'q', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'q', 'q', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:09:04', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (13, NULL, 'w', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'w', 'w', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:09:39', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (14, NULL, 'vivek11', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kl', 'n', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:49:39', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (15, NULL, 'GYM', 'software', 'ai_ml', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'qertdgsf', 'adsv', 'uploads/images/68aaa77b242ba.png', 'uploads/videos/68aaa77b242ef.mp4', NULL, 'uploads/instructions/68aaa77b24321.pdf', NULL, NULL, '2025-08-24 02:17:39', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        (16, '1', 'Ideanest', 'hardware', 'wearable', 'education', 'advanced', '1 month', '3', 'wert', 'fdghjkd', 'wertgs', 'wdefrtds', 'https://github.com/Vivekchavda1374/IdeaNes', 'https://localhost/IdeaNest/user/forms/new_project_add.ph', 'BSD-3-Clause', 'wefrgdc', 'viveksinhchavda@gmail.com', 'Linkedin', 'gr hqer', 'ertwd', NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-25 02:10:04', 'approved');

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

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `action`, `details`, `admin_id`, `created_at`, `timestamp`) VALUES
                                                                                                (1, 'subadmin_removed', 'Removed subadmin: vivek.chavda119486@marwadiuniversity.ac.in (). Reason: remove', NULL, '2025-08-13 16:57:35', '2025-08-13 16:57:35'),
                                                                                                (2, 'mentor_removed', 'Removed mentor: Dr. Sarah Johnson (sarah.mentor@ideanest.com)', 1, '2025-09-17 14:46:24', '2025-09-17 14:46:24'),
                                                                                                (3, 'mentor_removed', 'Removed mentor: Prof. Mike Chen (mike.mentor@ideanest.com)', 1, '2025-09-17 14:46:27', '2025-09-17 14:46:27');

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

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `created_at`, `updated_at`) VALUES
                                                                                                                    (1, 'site_name', 'IdeaNest', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (2, 'site_url', 'http://localhost/IdeaNest', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (3, 'admin_email', 'ideanest.ict@gmail.com', 'text', '2025-07-06 06:53:37', '2025-07-06 07:49:42'),
                                                                                                                    (4, 'timezone', 'Asia/Kolkata', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (5, 'smtp_host', 'smtp.gmail.com', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (6, 'smtp_port', '587', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (7, 'smtp_username', 'ideanest.ict@gmail.com', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (8, 'smtp_password', 'luou xlhs ojuw auvx', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (9, 'smtp_secure', 'tls', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (10, 'from_email', 'ideanest.ict@gmail.com', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (11, 'email_notifications', '1', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (12, 'project_approval_emails', '1', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (13, 'project_rejection_emails', '1', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (14, 'new_user_notifications', '1', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (15, 'max_file_size', '10', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (16, 'allowed_file_types', 'jpg,jpeg,png,gif,pdf,zip,rar', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (17, 'session_timeout', '30', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20'),
                                                                                                                    (18, 'maintenance_mode', '0', 'text', '2025-07-06 06:53:37', '2025-07-06 07:41:20');

-- --------------------------------------------------------

--
-- Stand-in structure for view `approved_project_details_view`
-- (See below for the actual view)
--
CREATE TABLE `approved_project_details_view` (
                                                 `id` int(5)
    ,`user_id` varchar(255)
    ,`user_name` varchar(100)
    ,`user_email` varchar(100)
    ,`enrollment_number` varchar(50)
    ,`department` varchar(100)
    ,`project_name` varchar(255)
    ,`project_type` varchar(50)
    ,`classification` varchar(100)
    ,`project_category` varchar(100)
    ,`difficulty_level` enum('beginner','intermediate','advanced','expert')
    ,`description` text
    ,`language` varchar(100)
    ,`development_time` varchar(50)
    ,`team_size` varchar(50)
    ,`target_audience` text
    ,`project_goals` text
    ,`challenges_faced` text
    ,`future_enhancements` text
    ,`github_repo` varchar(255)
    ,`live_demo_url` varchar(255)
    ,`project_license` varchar(100)
    ,`keywords` varchar(500)
    ,`contact_email` varchar(255)
    ,`social_links` text
    ,`image_path` varchar(255)
    ,`video_path` varchar(255)
    ,`code_file_path` varchar(255)
    ,`instruction_file_path` varchar(255)
    ,`presentation_file_path` varchar(255)
    ,`additional_files_path` varchar(255)
    ,`submission_date` timestamp
    ,`status` enum('pending','approved','rejected')
);

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
                        `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog`
--

INSERT INTO `blog` (`id`, `user_id`, `er_number`, `project_name`, `project_type`, `classification`, `description`, `submission_datetime`, `status`, `priority1`, `assigned_to`, `completion_date`, `created_at`, `updated_at`) VALUES
                                                                                                                                                                                                                                   (1, 1, '92200133026', 'few', 'software', 'mobileapp', 'absjbhjavks', '2025-03-22 15:19:23', 'in_progress', 'medium', 'ad', '2025-03-22', '2025-03-21 21:40:26', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (2, 1, '92200133027', 'j', 'hardware', 'iotdevice', 'vvj', '2025-03-22 14:17:25', 'pending', 'medium', NULL, NULL, '2025-03-21 21:47:25', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (3, 2, '92200133052', '00000', 'software', 'webapp', 'a', '2025-03-22 16:46:22', 'in_progress', 'high', 'ad', '2025-03-22', '2025-03-21 22:08:30', '2025-08-21 04:59:01'),
                                                                                                                                                                                                                                   (4, 1, '92200133027', 'j', 'software', 'webapp', 'z', '2025-03-22 14:39:32', 'completed', 'medium', 'ad', '2025-03-22', '2025-03-21 22:09:32', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (5, 1, '92200133027', 'viv', 'hardware', 'robotics', 'sfbsb', '2025-03-22 15:29:19', 'pending', 'medium', 'sB', '2025-03-29', '2025-03-21 22:59:19', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (6, 1, '92200133027', 'viv', 'hardware', 'robotics', 'sfbsb', '2025-03-22 15:34:27', 'pending', 'medium', 'sB', '2025-03-29', '2025-03-21 23:04:27', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (7, 1, 'f', 'vsd', 'hardware', 'iotdevice', 'dsvvs', '2025-03-22 15:34:54', 'in_progress', 'medium', 'sd', '2025-03-27', '2025-03-21 23:04:54', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (8, 1, '92200133026', 'few', 'hardware', 'iotdevice', 'sfz', '2025-03-22 15:39:18', 'rejected', 'low', 'wvc', NULL, '2025-03-21 23:09:18', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (9, 1, '92200133026', 'viv', 'software', 'mobileapp', 'fs', '2025-03-22 15:48:36', 'in_progress', 'low', 'cd', '2025-03-21', '2025-03-21 23:18:36', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (10, 1, '92200133027', 'viv', 'software', 'webapp', 'a', '2025-03-22 16:39:37', 'pending', 'medium', '', NULL, '2025-03-22 00:09:37', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (11, 1, '92200133026', 'dcs', 'software', 'desktopapp', 'sd', '2025-03-22 16:42:23', 'in_progress', 'low', '', NULL, '2025-03-22 00:12:23', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (12, 1, '92200133027', 'e', 'software', 'data_science', 'j', '2025-07-05 22:10:01', 'pending', 'low', '2', '2025-07-05', '2025-07-05 11:10:01', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (13, 1, '92200133026', 'icon', 'software', 'cloud', 'This is a low priority', '2025-08-21 10:00:00', 'in_progress', 'low', '', NULL, '2025-08-21 04:30:00', '2025-08-21 04:46:27'),
                                                                                                                                                                                                                                   (14, 2, '92200133041', 'Dhruvi', 'software', 'web', 'good', '2025-08-21 10:27:19', 'in_progress', 'medium', NULL, NULL, '2025-08-21 04:57:19', '2025-08-21 04:57:19'),
                                                                                                                                                                                                                                   (15, 1, '92200133026', 'Vivek Chavda', 'software', 'cybersecurity', 'This is a project', '2025-08-25 10:16:37', 'in_progress', 'low', 'Na', '2025-08-27', '2025-08-25 04:46:37', '2025-08-25 04:46:37'),
                                                                                                                                                                                                                                   (16, 1, '92200133026', 'Vivek Chavda', 'hardware', 'sensor', 'This is a project', '2025-08-25 10:17:41', 'in_progress', 'low', 'Na', '2025-08-27', '2025-08-25 04:47:41', '2025-08-25 04:47:41');

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

--
-- Dumping data for table `bookmark`
--

INSERT INTO `bookmark` (`id`, `project_id`, `user_id`, `idea_id`, `bookmarked_at`) VALUES
                                                                                       (1, 1, '6ilreg4qmldhnhkm89982dq455', 0, '2025-03-26 05:07:37'),
                                                                                       (15, 5, '70j5rk4pf94a7q5qpj0pldsapf', 0, '2025-07-03 17:33:49'),
                                                                                       (23, 5, 'h0sh9bma1afc18quha57302313', 0, '2025-07-05 17:27:32'),
                                                                                       (24, 4, 'ffkhf2jr6rtfeg27km7997cluq', 0, '2025-07-06 03:55:02'),
                                                                                       (26, 14, 'lnvi7mtd8vlrmotvhvfd9odkvq', 0, '2025-07-06 08:28:41'),
                                                                                       (36, 10, 'nf5o5ju30r5v0fg1169b7l0k67', 0, '2025-08-04 05:24:54'),
                                                                                       (37, 14, 'gcbukoss3eb3u9c8pacu441ad9', 0, '2025-08-06 04:34:44'),
                                                                                       (38, 13, 'gcbukoss3eb3u9c8pacu441ad9', 0, '2025-08-06 04:34:46'),
                                                                                       (39, 12, 'gcbukoss3eb3u9c8pacu441ad9', 0, '2025-08-06 04:34:47'),
                                                                                       (40, 10, 'gcbukoss3eb3u9c8pacu441ad9', 0, '2025-08-06 04:34:52'),
                                                                                       (41, 9, 'gcbukoss3eb3u9c8pacu441ad9', 0, '2025-08-06 04:34:54'),
                                                                                       (46, 14, 'sahqq1d3oa82o26vhseo7pf4vp', 0, '2025-08-07 16:29:03'),
                                                                                       (769, 14, '4evm1tln9kd756idpd3kjmcrmh', 0, '2025-08-22 08:17:16'),
                                                                                       (770, 14, '6nb1r5mraq35ah2qnk4r8e071n', 0, '2025-08-24 04:02:50'),
                                                                                       (772, 8, '6nb1r5mraq35ah2qnk4r8e071n', 0, '2025-08-24 06:06:22'),
                                                                                       (773, 15, '6nb1r5mraq35ah2qnk4r8e071n', 0, '2025-08-24 07:00:02'),
                                                                                       (775, 15, 't19gk6a8s544dspidejpt5rhe1', 0, '2025-08-24 14:21:31'),
                                                                                       (776, 15, 'tojal0944iv6bik11hcismnunk', 0, '2025-08-25 04:19:48'),
                                                                                       (777, 16, '0rs4kauq45f3em8j863qgna23l', 0, '2025-09-01 08:53:12');

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

--
-- Dumping data for table `comment_likes`
--

INSERT INTO `comment_likes` (`id`, `comment_id`, `user_id`, `created_at`) VALUES
                                                                              (1, 1, '4evm1tln9kd756idpd3kjmcrmh', '2025-08-22 07:59:34'),
                                                                              (2, 2, '4evm1tln9kd756idpd3kjmcrmh', '2025-08-22 08:00:07'),
                                                                              (3, 3, '4evm1tln9kd756idpd3kjmcrmh', '2025-08-22 08:00:09');

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

--
-- Dumping data for table `denial_projects`
--

INSERT INTO `denial_projects` (`id`, `user_id`, `project_name`, `project_type`, `classification`, `project_category`, `difficulty_level`, `development_time`, `team_size`, `target_audience`, `project_goals`, `challenges_faced`, `future_enhancements`, `github_repo`, `live_demo_url`, `project_license`, `keywords`, `contact_email`, `social_links`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `presentation_file_path`, `additional_files_path`, `submission_date`, `status`, `rejection_date`, `rejection_reason`) VALUES
    (1, 1, 'vivek', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'w', 'w', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 07:47:37', 'rejected', '2025-07-06 11:17:57', 'hi');

-- --------------------------------------------------------

--
-- Table structure for table `github_analytics_cache`
--

CREATE TABLE `github_analytics_cache` (
                                          `id` int(11) NOT NULL,
                                          `user_id` int(11) NOT NULL,
                                          `cache_key` varchar(255) NOT NULL,
                                          `cache_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cache_data`)),
                                          `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                                          `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `github_contributions`
--

CREATE TABLE `github_contributions` (
                                        `id` int(11) NOT NULL,
                                        `user_id` int(11) NOT NULL,
                                        `github_username` varchar(100) NOT NULL,
                                        `contribution_date` date NOT NULL,
                                        `commit_count` int(11) DEFAULT 0,
                                        `pr_count` int(11) DEFAULT 0,
                                        `issue_count` int(11) DEFAULT 0,
                                        `review_count` int(11) DEFAULT 0,
                                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                                        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `github_language_stats`
--

CREATE TABLE `github_language_stats` (
                                         `id` int(11) NOT NULL,
                                         `user_id` int(11) NOT NULL,
                                         `language` varchar(50) NOT NULL,
                                         `bytes_count` bigint(20) DEFAULT 0,
                                         `percentage` decimal(5,2) DEFAULT 0.00,
                                         `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `github_repo_analytics`
--

CREATE TABLE `github_repo_analytics` (
                                         `id` int(11) NOT NULL,
                                         `user_id` int(11) NOT NULL,
                                         `repo_name` varchar(255) NOT NULL,
                                         `commit_frequency` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`commit_frequency`)),
                                         `contributor_count` int(11) DEFAULT 0,
                                         `issue_resolution_time` decimal(10,2) DEFAULT 0.00,
                                         `pr_merge_rate` decimal(5,2) DEFAULT 0.00,
                                         `code_quality_score` decimal(5,2) DEFAULT 0.00,
                                         `last_analyzed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `idea_likes`
--

INSERT INTO `idea_likes` (`id`, `idea_id`, `user_id`, `created_at`) VALUES
                                                                        (4, 14, 1, '2025-09-02 16:46:26'),
                                                                        (6, 3, 1, '2025-09-02 16:50:02');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
                         `id` int(11) NOT NULL,
                         `email` varchar(255) NOT NULL,
                         `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `email`, `password`) VALUES
    (1, 'viveksinhchavda@gmail.com', '$2y$10$wFnaiYlO0rMZLGh52kEmiOF8VMPjl6FCEohD.F5C/KiFBnLITW7ta');

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

--
-- Dumping data for table `mentoring_sessions`
--

INSERT INTO `mentoring_sessions` (`id`, `pair_id`, `session_date`, `duration_minutes`, `notes`, `meeting_link`, `status`, `created_at`, `reminder_sent`) VALUES
                                                                                                                                                             (1, 3, '2025-09-17 12:32:00', 60, 'good', NULL, 'scheduled', '2025-09-17 07:01:46', 0),
                                                                                                                                                             (2, 3, '2025-09-18 12:32:00', 60, 'discussion', NULL, 'cancelled', '2025-09-17 07:02:56', 0),
                                                                                                                                                             (3, 4, '2025-09-17 16:04:00', 30, 'NA', 'https://meet.google.com/sfv-atoe-ekq', 'scheduled', '2025-09-17 07:35:23', 0);

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

--
-- Dumping data for table `mentors`
--

INSERT INTO `mentors` (`id`, `user_id`, `specialization`, `experience_years`, `max_students`, `current_students`, `bio`, `linkedin_url`, `github_url`, `created_at`) VALUES
    (3, 7, 'General', 1, 1, 0, 'Web Development', '', '', '2025-09-11 18:06:26');

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

--
-- Dumping data for table `mentor_email_logs`
--

INSERT INTO `mentor_email_logs` (`id`, `mentor_id`, `recipient_id`, `email_type`, `status`, `error_message`, `sent_at`) VALUES
    (1, 7, 1, 'welcome_message', 'sent', NULL, '2025-09-17 13:12:21');

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
-- Table structure for table `mentor_email_templates`
--

CREATE TABLE `mentor_email_templates` (
                                          `id` int(11) NOT NULL,
                                          `mentor_id` int(11) NOT NULL,
                                          `template_type` enum('welcome_message','session_invitation','session_reminder','project_feedback','progress_update') NOT NULL,
                                          `subject` varchar(255) NOT NULL,
                                          `template_content` text NOT NULL,
                                          `is_active` tinyint(1) DEFAULT 1,
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

--
-- Dumping data for table `mentor_project_access`
--

INSERT INTO `mentor_project_access` (`id`, `mentor_id`, `student_id`, `project_id`, `granted_at`) VALUES
                                                                                                      (1, 7, 1, 5, '2025-09-17 13:35:29'),
                                                                                                      (5, 7, 1, 25, '2025-09-17 13:39:00');

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

--
-- Dumping data for table `mentor_requests`
--

INSERT INTO `mentor_requests` (`id`, `student_id`, `mentor_id`, `project_id`, `message`, `status`, `created_at`, `updated_at`) VALUES
                                                                                                                                   (1, 1, 7, 5, 'thi is my project\r\n', 'accepted', '2025-09-17 13:26:36', '2025-09-17 13:35:29'),
                                                                                                                                   (2, 1, 7, 25, 'i want to mentor from you\r\n', 'accepted', '2025-09-17 13:38:22', '2025-09-17 13:39:00');

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

--
-- Dumping data for table `mentor_student_pairs`
--

INSERT INTO `mentor_student_pairs` (`id`, `mentor_id`, `student_id`, `project_id`, `status`, `paired_at`, `completed_at`, `rating`, `feedback`, `welcome_sent`, `last_progress_email`) VALUES
                                                                                                                                                                                           (1, 7, 1, NULL, 'completed', '2025-09-11 18:12:03', '2025-09-11 18:12:18', 0, 'no', 0, NULL),
                                                                                                                                                                                           (2, 7, 1, NULL, 'completed', '2025-09-17 05:03:44', '2025-09-17 06:51:19', 5, 'done', 0, NULL),
                                                                                                                                                                                           (3, 7, 1, NULL, 'completed', '2025-09-17 07:01:10', '2025-09-17 07:24:18', 3, '', 0, NULL),
                                                                                                                                                                                           (4, 7, 1, NULL, 'completed', '2025-09-17 07:33:32', '2025-09-17 12:59:15', 4, 'done', 0, NULL);

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

--
-- Dumping data for table `notification_counters`
--

INSERT INTO `notification_counters` (`id`, `type`, `status`, `count`, `last_updated`) VALUES
                                                                                          (1, 'project_approval', 'sent', 0, '2025-07-06 07:55:03'),
                                                                                          (2, 'project_approval', 'failed', 0, '2025-07-06 07:55:03'),
                                                                                          (3, 'project_rejection', 'sent', 0, '2025-07-06 07:55:03'),
                                                                                          (4, 'project_rejection', 'failed', 0, '2025-07-06 07:55:03'),
                                                                                          (5, 'new_user_notification', 'sent', 0, '2025-07-06 07:55:03'),
                                                                                          (6, 'new_user_notification', 'failed', 0, '2025-07-06 07:55:03');

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
                                                                                                                                                                (1, 'new_user_notification', 1, NULL, 'sent', '2025-07-06 07:55:03', 'ideanest.ict@gmail.com', 'New User Registration - IdeaNest', NULL, NULL),
                                                                                                                                                                (2, 'project_approval', 2, 1, 'sent', '2025-07-06 07:55:03', 'user@example.com', 'Congratulations! Your Project \"Test Project\" Has Been Approved', NULL, NULL),
                                                                                                                                                                (3, 'project_rejection', 3, 2, 'sent', '2025-07-06 07:55:03', 'user2@example.com', 'Important Update About Your Project \"Another Project\"', NULL, NULL),
                                                                                                                                                                (4, 'new_user_notification', 3, NULL, 'sent', '2025-08-06 07:07:27', 'ideanest.ict@gmail.com', 'New User Registration - IdeaNest', NULL, NULL),
                                                                                                                                                                (5, 'weekly_notification', 1, NULL, 'sent', '2025-09-07 15:26:56', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (6, 'weekly_notification', 2, NULL, 'sent', '2025-09-07 15:27:02', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (7, 'weekly_notification', 1, NULL, 'sent', '2025-09-07 15:57:08', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (8, 'weekly_notification', 1, NULL, 'sent', '2025-09-07 15:57:08', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (9, 'weekly_notification', 1, NULL, 'sent', '2025-09-07 15:57:09', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (10, 'weekly_notification', 2, NULL, 'sent', '2025-09-07 15:58:08', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (11, 'weekly_notification', 2, NULL, 'sent', '2025-09-07 15:58:08', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (12, 'weekly_notification', 2, NULL, 'sent', '2025-09-07 15:58:08', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (13, 'weekly_notification', 1, NULL, 'sent', '2025-09-07 16:56:19', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (14, 'weekly_notification', 1, NULL, 'sent', '2025-09-07 16:56:19', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (15, 'weekly_notification', 1, NULL, 'sent', '2025-09-07 16:56:20', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (16, 'weekly_notification', 2, NULL, 'sent', '2025-09-07 16:56:25', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (17, 'weekly_notification', 2, NULL, 'sent', '2025-09-07 16:56:25', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (18, 'weekly_notification', 2, NULL, 'sent', '2025-09-07 16:56:25', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (19, 'weekly_notification', 1, NULL, 'sent', '2025-09-08 02:44:06', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (20, 'weekly_notification', 1, NULL, 'sent', '2025-09-08 02:44:06', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (21, 'weekly_notification', 1, NULL, 'sent', '2025-09-08 02:44:06', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (22, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 02:44:11', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (23, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 02:44:11', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (24, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 02:44:11', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (25, 'weekly_notification', 1, NULL, 'sent', '2025-09-08 03:15:06', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (26, 'weekly_notification', 1, NULL, 'sent', '2025-09-08 03:15:06', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (27, 'weekly_notification', 1, NULL, 'sent', '2025-09-08 03:15:06', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (28, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 03:15:11', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (29, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 03:15:11', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (30, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 03:15:12', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (31, 'weekly_notification', 1, NULL, 'sent', '2025-09-08 04:41:08', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (32, 'weekly_notification', 1, NULL, 'sent', '2025-09-08 04:41:08', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (33, 'weekly_notification', 1, NULL, 'sent', '2025-09-08 04:41:08', 'viveksinhchavda@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (34, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 04:41:15', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (35, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 04:41:16', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (36, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 04:41:16', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (37, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 05:12:08', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (38, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 05:12:08', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (39, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 05:12:08', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (40, 'weekly_notification', 2, NULL, 'failed', '2025-09-08 06:09:37', 'viveksinhchavda639@gmail.com', NULL, NULL, 'SMTP Error: Could not connect to SMTP host. Failed to connect to server'),
                                                                                                                                                                (41, 'weekly_notification', 2, NULL, 'failed', '2025-09-08 06:09:37', 'viveksinhchavda639@gmail.com', NULL, NULL, 'SMTP Error: Could not connect to SMTP host. Failed to connect to server'),
                                                                                                                                                                (42, 'weekly_notification', 2, NULL, 'failed', '2025-09-08 06:09:37', 'viveksinhchavda639@gmail.com', NULL, NULL, 'SMTP Error: Could not connect to SMTP host. Failed to connect to server'),
                                                                                                                                                                (43, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 06:11:22', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (44, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 06:11:24', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (45, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 06:11:25', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (46, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 06:11:53', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (47, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 06:11:57', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (48, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 06:11:58', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (49, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 06:42:24', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (50, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 06:42:25', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (51, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 06:42:29', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (52, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 07:13:07', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (53, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 07:13:07', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL),
                                                                                                                                                                (54, 'weekly_notification', 2, NULL, 'sent', '2025-09-08 07:13:07', 'viveksinhchavda639@gmail.com', '[TEST] 30min Update - New Projects & Ideas on IdeaNest', NULL, NULL);

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

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `type`, `subject`, `body`, `variables`, `is_active`, `created_at`, `updated_at`) VALUES
                                                                                                                                 (1, 'new_user_notification', 'New User Registration - {SITE_NAME}', '<h2>New User Registration</h2>\r\n<p>A new user has registered on {SITE_NAME}:</p>\r\n<ul>\r\n<li><strong>Name:</strong> {USER_NAME}</li>\r\n<li><strong>Email:</strong> {USER_EMAIL}</li>\r\n<li><strong>Registration Date:</strong> {REGISTRATION_DATE}</li>\r\n</ul>\r\n<p>Please review the user account in the admin panel.</p>', '{USER_NAME}, {USER_EMAIL}, {REGISTRATION_DATE}, {SITE_NAME}', 1, '2025-07-06 07:55:03', '2025-07-06 07:55:03'),
                                                                                                                                 (2, 'project_approval', 'Congratulations! Your Project \"{PROJECT_TITLE}\" Has Been Approved', '<h2>Project Approved!</h2>\r\n<p>Dear {USER_NAME},</p>\r\n<p>We are pleased to inform you that your project \"<strong>{PROJECT_TITLE}</strong>\" has been approved!</p>\r\n<p><strong>Project Details:</strong></p>\r\n<ul>\r\n<li><strong>Project Title:</strong> {PROJECT_TITLE}</li>\r\n<li><strong>Submission Date:</strong> {SUBMISSION_DATE}</li>\r\n<li><strong>Approval Date:</strong> {APPROVAL_DATE}</li>\r\n</ul>\r\n<p>You can now proceed with your project implementation.</p>\r\n<p>Best regards,<br>The {SITE_NAME} Team</p>', '{USER_NAME}, {PROJECT_TITLE}, {SUBMISSION_DATE}, {APPROVAL_DATE}, {SITE_NAME}', 1, '2025-07-06 07:55:03', '2025-07-06 07:55:03'),
                                                                                                                                 (3, 'project_rejection', 'Important Update About Your Project \"{PROJECT_TITLE}\"', '<h2>Project Status Update</h2>\r\n<p>Dear {USER_NAME},</p>\r\n<p>Thank you for submitting your project \"<strong>{PROJECT_TITLE}</strong>\" to {SITE_NAME}.</p>\r\n<p>After careful review, we regret to inform you that your project could not be approved at this time.</p>\r\n<p><strong>Reason:</strong> {REJECTION_REASON}</p>\r\n<p><strong>Project Details:</strong></p>\r\n<ul>\r\n<li><strong>Project Title:</strong> {PROJECT_TITLE}</li>\r\n<li><strong>Submission Date:</strong> {SUBMISSION_DATE}</li>\r\n<li><strong>Review Date:</strong> {REVIEW_DATE}</li>\r\n</ul>\r\n<p>We encourage you to review the feedback and consider resubmitting your project after addressing the mentioned concerns.</p>\r\n<p>Best regards,<br>The {SITE_NAME} Team</p>', '{USER_NAME}, {PROJECT_TITLE}, {SUBMISSION_DATE}, {REVIEW_DATE}, {REJECTION_REASON}, {SITE_NAME}', 1, '2025-07-06 07:55:03', '2025-07-06 07:55:03');

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
                            `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `user_id`, `project_name`, `project_type`, `classification`, `project_category`, `difficulty_level`, `development_time`, `team_size`, `target_audience`, `project_goals`, `challenges_faced`, `future_enhancements`, `github_repo`, `live_demo_url`, `project_license`, `keywords`, `contact_email`, `social_links`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `presentation_file_path`, `additional_files_path`, `submission_date`, `status`) VALUES
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (3, 8, 'vivek', 'hardware', 'iot', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'gewG', 'FSB', NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-06 03:25:05', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (4, 7, '123', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'EFC', 'EC', NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-06 03:27:18', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (5, 1, 'Mobil Park', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'this is a project', 'flutter , dart , firebase', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 00:31:34', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (6, 1, 'FleetLedger', 'software', 'mobile', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kje', 'mldads', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 01:57:44', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (7, 1, 'vivek', 'hardware', 'iot', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kj ra', 'ms', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:02:41', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (8, 1, 'vivek11', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ww', 'w', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:06:37', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (9, 1, 'q', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'q', 'q', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:09:04', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (10, 1, 'w', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'w', 'w', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:09:39', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (11, 1, 'vivek', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'w', 'w', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:17:37', 'rejected'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (12, 1, 'vivek11', 'software', 'web', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'kl', 'n', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-06 02:49:39', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (13, 1, 'Mobil Park', 'software', 'mobile', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'this is a moile', 'HTML, CSS, JS, PHP, MYSQL', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-07 13:48:09', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (14, 1, 'vivek', 'software', 'mobile', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'sssacnjhmghnfbgre', 'test', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-07 13:51:08', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (15, 1, 'GYM', 'software', 'ai_ml', 'healthcare', 'advanced', '1-2 weeks', '1', 'dvd', 'ewarfgf', 'dsfd', 'dsf', 'http://localhost/IdeaNest/user/forms/new_project_add.php', 'http://localhost/IdeaNest/user/forms/new_project_add.php', 'GPL-3.0', 'dfs', 'viveksinhchavda@gmail.com', 'Linkedin', 'qertdgsf', 'adsv', 'uploads/images/68aaa77b242ba.png', 'uploads/videos/68aaa77b242ef.mp4', NULL, 'uploads/instructions/68aaa77b24321.pdf', 'uploads/presentations/68aaa77b24338.pdf', 'uploads/additional/68aaa77b2434e.zip', '2025-08-24 02:17:39', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (16, 1, 'Ideanest', 'hardware', 'wearable', 'education', 'advanced', '1 month', '3', 'wert', 'fdghjkd', 'wertgs', 'wdefrtds', 'https://github.com/Vivekchavda1374/IdeaNes', 'https://localhost/IdeaNest/user/forms/new_project_add.ph', 'BSD-3-Clause', 'wefrgdc', 'viveksinhchavda@gmail.com', 'Linkedin', 'gr hqer', 'ertwd', NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-25 02:10:04', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (17, 1, 'Booktube', 'software', 'web', 'education', 'advanced', '2-3 months', '3', 'Students', 'Goal is to make learning fun and engaging.', 'The textbook pdf was taking 3-4 hours of time to get parsed which led towards bad efficiency.', 'Build an entire community of learners, more better user interface', 'https://github.com/aryan083/booktube', 'https://booktube-opal.vercel.app/', 'MIT', 'web app, machine learning, education, engaging', 'umanghirani.exe@gmail.com', '', 'A generative AI based learning platform which is used to generate topic wise article according to the user requirements.\r\nA textbook pdf and syllabus pdf needs to be given and based on that, the course will be generated which contains topic wise article.', 'ReactJS, typescript, flask, supabase, tailwindcss', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 00:50:21', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (18, 1, 'Voice Matcher-Akash Chudasama', 'software', 'web', 'research', 'intermediate', '1 month', '1', 'students and researchers exploring speech processing', 'To develop a Python &amp; Streamlit-based application that matches and compares voice samples.\r\n\r\nTo extract and analyze unique audio features (MFCC, pitch, tone) for accurate similarity detection.\r\n\r\nTo provide a simple, interactive, and real-time tool for speaker verification and experimentation.', 'During the development of the Voice Matcher App, one of the major challenges was audio preprocessing, as voice samples often contained background noise and required proper filtering for accurate results. Handling different audio formats (WAV, MP3, etc.)', 'In future updates, the Voice Matcher App can be enhanced with real-time voice recording directly within the app, eliminating the need for external tools.', 'https://github.com/Akashchudasama/voicematcher', 'https://voicematcher.streamlit.app/', 'other', 'web app', 'akashchudasama27@gmail.com', '', 'Voice Matcher – A Python &amp; Streamlit Based Application\r\n\r\nThe Voice Matcher App is a Python-based project developed using Streamlit for an interactive web interface. The application allows users to record or upload two voice samples and then compares them to check similarity between the speakers.\r\n\r\n🔹 Key Features:\r\n\r\n🎤 Voice Input Support – Users can upload audio files (WAV/MP3) or record their own voice.\r\n\r\n🔊 Feature Extraction – Uses libraries like Librosa and SpeechRecognition to extract unique voice features such as pitch, MFCCs (Mel-frequency cepstral coefficients), and tone.\r\n\r\n📊 Similarity Score – Computes and displays how closely two voice samples match.\r\n\r\n🖥 Streamlit UI – Clean and user-friendly interface for uploading files, running comparison, and visualizing results.', 'Python', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 00:53:03', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (19, 1, 'Warmer For Berverges - Harsh Sanghavi', 'hardware', 'embedded', 'social', 'advanced', '3-6 months', '3', 'student, staff, common people', 'The project was designed with specific goals in mind, aiming to provide a complete solution that is not only functional but also safe, affordable, and practical. The major objectives include:\r\n\r\nTo design and build a portable, cost-effective beverage reheating device that overcomes the limitations of traditional heating tools.', 'hardware many challenges faced', 'no', 'https://github.com/Vats1727/HCD-Project-Updates', NULL, 'other', 'power source: usb, li-ion battery, solar cell, wall socket heating element: nichrome wire, ceramic plate, induction coil safety controls: auto shutoff, temp sensor, user override form factor: slee', 'harsh.sanghavi121389@marwadiuniversity.ac.in', '', 'Hot beverages like tea and coffee are more than just drinks—they are habits, rituals, and sources of comfort embedded in our daily routines. From early morning refreshment to late-night study companions, these beverages play a vital role in how people begin and maintain their day. However, one common and frustrating issue persists: the rapid cooling of these drinks. Often, people prepare a warm cup of tea or coffee, become occupied with work or daily activities, and return to find it lukewarm or cold. This not only ruins the taste and satisfaction of the beverage but also often leads to wastage or the hassle of reheating.\r\n\r\nExisting solutions to this problem include thermally insulated flasks, electric kettles, or smart mugs. While insulated flasks slow down the cooling process, they do not reheat. On the other hand, electric kettles are impractical for reheating small quantities, and smart mugs are either too costly or lack portability. These options fail to fully address the need for a portable, low-cost, and user-friendly solution for maintaining beverage temperature.\r\n\r\nThis project arises from a need to bridge that gap. It aims to create a compact, efficient, and safe device that not only reheats beverages but also shows the current temperature, all while being easy to carry and operate. The innovation lies in simplifying the solution while keeping the user at the center of the design process. A stick-like device, powered by a small battery and controlled by a microcontroller, heats the beverage through a nichrome coil while showing the temperature on an OLED screen.', 'Arduino', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 00:56:36', 'pending'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (20, 1, 'Accreditation And Management System - Dhruvi Patel', 'software', 'system', 'education', 'advanced', '3-6 months', '5', 'Universities, Accreditation bodies', 'The main goals of the Data Management and Accreditation System are to centralize and streamline institutional data, ensure accuracy and transparency, and automate accreditation processes. Its objectives include simplifying data collection and validation, mapping information to accreditation standards, generating real-time reports, enhancing collaboration with secure role-based access, and reducing manual effort—ultimately improving efficiency, compliance, and institutional performance.', 'During development of the Data Management and Accreditation System, one major challenge was handling diverse and unstructured data from multiple departments while ensuring consistency and accuracy. Designing a system that could automatically map this data to different accreditation standards without manual intervention was also complex. Implementing secure role-based access control to protect sensitive information while allowing smooth collaboration required careful planning. Another challenge was building dynamic report generation that could adapt to varying requirements of different accreditation bodies.', 'In the future, we plan to enhance the Data Management and Accreditation System with advanced features such as AI-powered data validation to reduce errors, predictive analytics to forecast accreditation outcomes, and automated reminders for compliance deadlines. We also aim to integrate cloud-based storage for scalability, mobile app support for on-the-go access, and multi-language support to cater to diverse institutions. Blockchain-based data security is another planned feature to ensure tamper-proof records. Additionally, we intend to introduce smart recommendation systems to help institutions identify gaps in compliance and suggest improvements, along with seamless integration with existing ERP and LMS platforms.', 'https://github.com/VidyaSinha/MADMS_bounceback', 'https://madms-assistant.vercel.app/', 'other', 'website, system software', 'dhruviben.patel119539@marwadiuniversity.ac.in', 'https://www.linkedin.com/in/dhruvi-patel-580b9b252/', 'The Data Management and Accreditation System is a centralized platform that streamlines institutional data handling and accreditation processes by collecting, validating, and securely storing information from various departments in a standardized format. It automatically maps data to accreditation criteria, generates real-time analytics, and produces compliance-ready reports, reducing the manual effort and time typically required. With features like role-based access, document management, and audit trails, the system ensures transparency, accuracy, and accountability. What makes it special is its ability to automate the accreditation workflow, save resources, and provide data-driven insights that not only meet compliance requirements but also help improve overall institutional performance.', 'Python, Flask, React, Supabase, PostgreSQL', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 00:59:16', 'pending'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (21, 1, 'Food-Recipie-Vatsal Parmar', 'software', 'web', 'other', 'beginner', '2-3 months', '2', 'food lovers', 'to get intant food recipie based on user choice', 'mern iplementation', 'deployment', 'https://github.com/Vats1727/food', NULL, 'other', 'web app', 'vatsal.parmar121391@marwadiuniversity.ac.in', '', 'IT  is a food recipie wen application', 'MERN', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 01:01:06', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (22, 1, 'Face-Detection-Priya Shukla', 'software', 'ai_ml', 'education', 'intermediate', '2-3 months', '2', 'Students, Professionals or anyone can use for simple face detection', 'The main objective of this project is to design and implement a face recognition system that operates in real-time while maintaining computational efficiency. Specifically, the project aims to develop a system that can detect and recognize human faces accurately from live video streams or static images.', 'During the development of the system, several technical and practical challenges were encountered. Variations in lighting conditions posed a significant obstacle, especially in environments with shadows or backlight, which affected both face detection and recognition accuracy. Ensuring consistent face alignment and image quality during the capture phase was also difficult, particularly when users moved or changed facial expressions. Managing the dataset and ensuring that face images were correctly labeled and stored for each individual required careful handling. Another challenge was finding the optimal confidence threshold for the LBPH recognizer—too strict, and valid faces might be rejected; too loose, and false positives could occur. Despite these challenges, the system met its intended goals. It was able to capture and process new user data, train a reliable face recognizertests and', 'From the standpoint of scope for improvements, enhancing the dataset with more diverse facial expressions, different lighting conditions, and multiple head orientations will help improve model robustness. Implementing real-time pre-processing techniques like histogram equalization, background filtering, and automatic face alignment can further increase recognition accuracy. On the software side, building a more advanced UI/UX for managing users, adding admin panels, and incorporating real-time feedback on training quality can make the system more user-friendly. Adding logging, model retraining schedules, and user data protection mechanisms would also contribute to long-term usability and compliance with data privacy norms.', 'https://github.com/priya1804/FACE-DETECTION-IN-REAL-TIME.git', 'https://www.youtube.com/watch?v=-ZMc8d8-4ts', 'other', 'machine learning, web app', 'priya.shukla118307@marwadiuniversity.ac.in', '', 'Face recognition is a definitive example of how artificial intelligence and machine learning can mimic human cognitive abilities to interpret complex visual information. This project leverages AI techniques such as pattern recognition, supervised learning, and computer vision to enable machines to detect, analyze, and identify human faces automatically. By utilizing classical AI methods like Haar Cascade classifiers for detection and Local Binary Patterns Histograms (LBPH) for recognition, the project highlights the versatility of AI beyond deep learning, showing how traditional algorithms can still provide robust and efficient solutions in real-world applications. This contributes to AI’s broader goal of enhancing automation, security, and intelligent human-computer interaction.', 'Python', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 01:03:15', 'pending'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (23, 1, 'Know-Your-Company Diya Kaneriya', 'software', 'mobile', 'education', 'advanced', 'ongoing', '1', 'students, placement coordinator, HOD , faculty, alumni', 'to solve the mess of the placement progress system where students are able to have whole progress record faculty placement coordinator and HOD can seemlessly effortlessly track the student record and maintain data.', 'there is a lot challenges in modules in the database side the information exchanging from faculty to students and vic a verca is a hard task to do', 'to have proper structure of info from mail where i need to use ML model.', 'https://github.com/Shyamavagadiya/Know_your_company', NULL, '', 'mobile application', 'diya.kaneriya119625@marwadiuniversity.ac.in', '', 'know-your-company is a mobile application to maintain or track the whole placement drive details. which have 5 types of users ( student, HOD, student placement coordinator , faculty , alumni). For students they can attempt quizzes of aptitude, have record and progress of placements where they have applied and how many rounds they cleared, ask alumni if any query, have review if any alumni or seniors worked in particular company, see the company details directly from the mail and also can open mail to see more details, for the students coordinator ,  faculty and HOD things are same like they can create and view score of quizzez, add companies, review or see the students placement progress and record. for alumni they can answer the question of stuednts and give their insights of the companies.', 'flutter - dart', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 01:05:52', 'pending'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (24, 1, 'Ai-Powered Import And Export Analysis And Recommendation - Abhay Padariya', 'software', 'ai_ml', 'business', 'intermediate', '1 month', '2', 'The target audience includes farmers, agricultural traders, policymakers, and professionals in the import/export sector who seek data-driven insights to maximize profitability. Additionally, students and researchers in data science and agriculture can use the system as a practical tool for learning and analysis.', 'The project aims to analyze farming-related import and export data using AI and machine learning to identify profitable and high-demand products. It will provide farmers and stakeholders with data-driven recommendations on what to trade. The system will feature an interactive GUI with visual insights, trade trend analysis, and product recommendations to support informed decision-making.', 'Data collection and cleaning were difficult due to inconsistencies. Choosing suitable ML models and creating a simple, user-friendly GUI for complex trade insights also posed challenges.', 'We plan to add real-time trade data, improve prediction accuracy with advanced ML models, and develop a web/mobile version. Features like personalized recommendations, multi-language support, and market news integration will make the system more user-friendly and practical.', 'https://github.com/Abhaypadariya/Import-and-Export-Analysis-and-Recommendation', NULL, '', 'ai', 'abhaybhai.padariya119238@marwadiuniversity.ac.in', '', 'This project aims to analyze import and export data to identify the most profitable and in\r\ndemand farming-related products. By applying data analysis and machine learning \r\ntechniques, the system will evaluate trade trends, identify top-performing products, and \r\nrecommend the best products for farmers to export or import. \r\nThe results will be presented through an intuitive Graphical User Interface (GUI), enabling \r\nusers to explore trade insights and view product recommendations visually and \r\ninteractively.', 'Python', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 01:07:39', 'pending'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (25, 1, 'Solar Lead Management  - Rajnikant Hirapara', 'software', 'web', 'business', 'intermediate', '1 month', '2', 'The Solar Lead Management System is aimed at solar energy companies, EPC players, sales executives, and project managers who need to efficiently track and manage customer leads. It also benefits administrators and professionals involved in solar project sales pipelines.', 'The primary purpose of the Solar Lead Management System is to digitize and automate the process of handling solar project leads, addressing common issues such as poor organization, missed follow-ups, and lack of visibility. This system provides a structured workflow that guides leads from initial enquiry to final project completion. With an intuitive interface, users can easily add, manage,', 'During development, we faced multiple challenges such as integrating the frontend and backend seamlessly with real-time data updates from MongoDB Atlas, ensuring secure authentication and data handling, and implementing form validation with dynamic dashboards. Designing a responsive and intuitive UI with Bootstrap while keeping the application fast was also challenging. Additionally, setting up cloud connections, managing API routes, and debugging asynchronous data flow between React and Express required extra effort.', 'In the future, the Solar Lead Management System can be enhanced with several advanced features to improve its functionality, scalability, and user experience. Role-based access control can be implemented to define different access levels for Admins, Sales Executives, and Managers. The system can include automatic lead assignment based on region, availability, or performance, as well as integration of email and SMS notifications for real-time lead updates and appointment reminders. A detailed analytics dashboard with visual reports can help track lead conversion rates and team performance. Additionally, a mobile application can be developed for on-the-go updates during site visits, along with Google Maps integration for route optimization. Features like document uploads, lead source\r\ntracking, chatbot support, and secure payment gateway integration', 'https://github.com/rajnikant8200/solar-website', NULL, '', 'web app', 'rajnikant.hirapara118565@marwadiuniversity.ac.in', '', 'The Solar Lead Management System is a web-based application designed to manage and track solar project leads efficiently. It helps users organize leads through different stages such as Pre-Qualified, Enquiry, Quotation, Design, and Installation. Built using React.js (Vite) for the frontend, Node.js with Express for the backend, and MongoDB Atlas for cloud data storage, the system ensures speed, scalability, and security. Key features include user authentication, project and task management, and a clean dashboard interface with sections for Projects, Tasks, and Settings. This platform streamlines the lead\r\nhandling process, boosts team productivity, and keeps all project updates in one place.', 'Html ,CSS ,JavaScript , React.js ,Node.js , Express.js ,Mongo DB Atlas', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 01:09:55', 'approved'),
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         (26, 1, 'Ai Smart Attendance System-Vrajkumar Nandwana', 'software', 'ai_ml', 'education', 'intermediate', '2-3 months', '2', 'students, college professionals, faculty', 'Unlike traditional systems, this project allows bulk image input—the teacher or administrator \r\ncan upload group photos containing multiple students. Whether it&#039;s 5, 10, or more students in \r\na single image, the system can detect and identify each face from the uploaded photos. \r\nAttendance is marked automatically for all recognized students without needing individual \r\nscans. nd intelligent way to manage classroom \r\nattendance.', 'sunlight on peoples face , deployement', 'Add more management features for all other sections and class and in attendence system', NULL, NULL, '', 'artificial intelligence, camera vision', 'vrajnandwana26@gmail.com', 'https://www.linkedin.com/in/vrajkumar-nandwana', 'The Face Recognition-Based Attendance System is an AI-driven application designed to \r\nautomate classroom attendance using facial recognition technology. Unlike traditional methods where \r\nattendance is marked individually, this system allows multiple students to register by submitting around \r\n100 images each to build a robust dataset per student. These datasets are processed using MTCNN for \r\nface detection and InceptionResnetV1 for embedding generation through the facenet-pytorch \r\nframework. \r\nAfter registration, the model is trained on these embeddings, enabling the system to recognize', 'Python , openCV , mtcnn', NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-06 01:13:58', 'pending');

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

--
-- Dumping data for table `project_comments`
--

INSERT INTO `project_comments` (`id`, `project_id`, `user_id`, `user_name`, `comment_text`, `parent_comment_id`, `created_at`, `updated_at`, `is_deleted`) VALUES
                                                                                                                                                               (1, 14, '4evm1tln9kd756idpd3kjmcrmh', 'vivek', 'hello', NULL, '2025-08-22 07:59:29', '2025-08-22 07:59:29', 0),
                                                                                                                                                               (2, 9, '4evm1tln9kd756idpd3kjmcrmh', 'vivek', 'hi', NULL, '2025-08-22 07:59:50', '2025-08-22 07:59:50', 0),
                                                                                                                                                               (3, 9, '4evm1tln9kd756idpd3kjmcrmh', 'vivek', 'hello', 2, '2025-08-22 08:00:02', '2025-08-22 08:00:02', 0),
                                                                                                                                                               (4, 15, '6nb1r5mraq35ah2qnk4r8e071n', 'vivek', 'hi', NULL, '2025-08-24 06:41:11', '2025-08-24 06:41:11', 0),
                                                                                                                                                               (5, 15, '6nb1r5mraq35ah2qnk4r8e071n', 'vivek', 'hello', 4, '2025-08-24 06:41:20', '2025-08-24 06:41:20', 0),
                                                                                                                                                               (6, 15, '6nb1r5mraq35ah2qnk4r8e071n', 'vivek', 'hello', 4, '2025-08-24 06:41:25', '2025-08-24 06:41:25', 0),
                                                                                                                                                               (7, 15, '6nb1r5mraq35ah2qnk4r8e071n', 'vivek', 'how are you', 4, '2025-08-24 06:41:42', '2025-08-24 06:41:42', 0),
                                                                                                                                                               (8, 15, '6nb1r5mraq35ah2qnk4r8e071n', 'vivek', 'hello', NULL, '2025-08-24 06:41:53', '2025-08-24 06:41:53', 0),
                                                                                                                                                               (9, 15, '6nb1r5mraq35ah2qnk4r8e071n', 'vivek', 'how are you', 8, '2025-08-24 06:42:02', '2025-08-24 06:42:02', 0),
                                                                                                                                                               (11, 14, 'q68pts618n3tkla7sbb6b98dl0', 'vivek', 'hello', NULL, '2025-09-07 13:46:02', '2025-09-07 13:47:32', 0);

-- --------------------------------------------------------

--
-- Stand-in structure for view `project_details_view`
-- (See below for the actual view)
--
CREATE TABLE `project_details_view` (
                                        `id` int(5)
    ,`user_id` int(15)
    ,`user_name` varchar(100)
    ,`user_email` varchar(100)
    ,`enrollment_number` varchar(50)
    ,`department` varchar(100)
    ,`project_name` varchar(255)
    ,`project_type` varchar(50)
    ,`classification` varchar(100)
    ,`project_category` varchar(100)
    ,`difficulty_level` enum('beginner','intermediate','advanced','expert')
    ,`description` text
    ,`language` varchar(100)
    ,`development_time` varchar(50)
    ,`team_size` varchar(50)
    ,`target_audience` text
    ,`project_goals` text
    ,`challenges_faced` text
    ,`future_enhancements` text
    ,`github_repo` varchar(255)
    ,`live_demo_url` varchar(255)
    ,`project_license` varchar(100)
    ,`keywords` varchar(500)
    ,`contact_email` varchar(255)
    ,`social_links` text
    ,`image_path` varchar(255)
    ,`video_path` varchar(255)
    ,`code_file_path` varchar(255)
    ,`instruction_file_path` varchar(255)
    ,`presentation_file_path` varchar(255)
    ,`additional_files_path` varchar(255)
    ,`submission_date` timestamp
    ,`status` enum('pending','approved','rejected')
);

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

--
-- Dumping data for table `project_likes`
--

INSERT INTO `project_likes` (`id`, `project_id`, `user_id`, `created_at`) VALUES
                                                                              (13, 7, '4evm1tln9kd756idpd3kjmcrmh', '2025-08-22 07:46:45'),
                                                                              (15, 14, '4evm1tln9kd756idpd3kjmcrmh', '2025-08-22 07:48:07'),
                                                                              (21, 13, 'ddb5kuco9gb90g3odh5744ipou', '2025-08-24 12:45:13'),
                                                                              (22, 12, 'ddb5kuco9gb90g3odh5744ipou', '2025-08-24 12:45:15'),
                                                                              (25, 14, 'ddb5kuco9gb90g3odh5744ipou', '2025-08-24 12:50:16'),
                                                                              (26, 15, 't19gk6a8s544dspidejpt5rhe1', '2025-08-24 13:10:34');

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
                            `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`id`, `name`, `email`, `enrollment_number`, `gr_number`, `password`, `about`, `phone_no`, `department`, `passout_year`, `user_image`, `google_id`, `email_notifications`, `last_notification_sent`, `github_token`, `role`, `expertise`, `mentor_rating`, `is_available`) VALUES
                                                                                                                                                                                                                                                                                                      (1, 'vivek', 'viveksinhchavda@gmail.com', '92200133026', '119486', '$2y$10$nG7UVKosppZ89J4ZiG3XHuRpno.EVUlfuBiZzuoNaJbpxU2P250zm', 'i am vivek', '9104231590', 'ict', '2026', 'profile_68a6b4d8458ae.png', '116644441139882349952', 0, '2025-09-08 10:11:08', NULL, 'student', NULL, 0.00, 1),
                                                                                                                                                                                                                                                                                                      (2, 'vivek', 'viveksinhchavda639@gmail.com', '92200133041', '119485', '$2y$10$nG7UVKosppZ89J4ZiG3XHuRpno.EVUlfuBiZzuoNaJbpxU2P250zm', 'hi i am vivek', '9104231590', 'ict', '2026', '', '111579585627734057498', 1, '2025-09-08 12:43:07', NULL, 'student', NULL, 0.00, 1),
                                                                                                                                                                                                                                                                                                      (4, 'Dr. Sarah Johnson', 'sarah.mentor@ideanest.com', 'MEN001', 'MEN001', '$2y$10$nG7UVKosppZ89J4ZiG3XHuRpno.EVUlfuBiZzuoNaJbpxU2P250zm', 'AI/ML Expert with 10 years experience', NULL, 'Computer Science', '2015', '', NULL, 1, NULL, NULL, 'student', 'Artificial Intelligence, Machine Learning, Data Science', 0.00, 1),
                                                                                                                                                                                                                                                                                                      (5, 'Prof. Mike Chen', 'mike.mentor@ideanest.com', 'MEN002', 'MEN002', '$2y$10$nG7UVKosppZ89J4ZiG3XHuRpno.EVUlfuBiZzuoNaJbpxU2P250zm', 'Full-stack developer and IoT specialist', NULL, 'ICT', '2012', '', NULL, 1, NULL, NULL, 'student', 'Web Development, IoT, Mobile Apps', 0.00, 1),
                                                                                                                                                                                                                                                                                                      (7, 'vivek', 'chavdaviveksinh1374@gmail.com', 'MEN336', 'MEN336', '$2y$10$nG7UVKosppZ89J4ZiG3XHuRpno.EVUlfuBiZzuoNaJbpxU2P250zm', 'Web Development', NULL, 'Mentor', '2024', '', '109218137896780820209', 1, NULL, NULL, 'mentor', 'Web Development', 0.00, 1);

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

--
-- Dumping data for table `student_email_preferences`
--

INSERT INTO `student_email_preferences` (`id`, `student_id`, `receive_session_reminders`, `receive_progress_updates`, `receive_project_feedback`, `receive_welcome_emails`, `created_at`, `updated_at`) VALUES
                                                                                                                                                                                                            (1, 1, 1, 1, 1, 1, '2025-09-17 12:55:45', '2025-09-17 12:55:45'),
                                                                                                                                                                                                            (2, 2, 1, 1, 1, 1, '2025-09-17 12:55:45', '2025-09-17 12:55:45');

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
                             `profile_complete` tinyint(1) DEFAULT 0,
                             `software_classification` varchar(100) DEFAULT NULL,
                             `hardware_classification` varchar(100) DEFAULT NULL,
                             `status` enum('active','inactive') DEFAULT 'active',
                             `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                             `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subadmins`
--

INSERT INTO `subadmins` (`id`, `email`, `password`, `name`, `domain`, `profile_complete`, `software_classification`, `hardware_classification`, `status`, `created_at`, `last_login`) VALUES
                                                                                                                                                                                          (4, 'viveksinhchavda@gmail.com', '$2y$10$nG7UVKosppZ89J4ZiG3XHuRpno.EVUlfuBiZzuoNaJbpxU2P250zm', 'vivek', 'ICT', 1, 'Mobile', 'Internet of Things (IoT)', 'active', '2025-08-13 16:55:57', NULL),
                                                                                                                                                                                          (6, 'vivek.chavda119486@marwadiuniversity.ac.in', '$2y$10$nG7UVKosppZ89J4ZiG3XHuRpno.EVUlfuBiZzuoNaJbpxU2P250zm', 'vivek', 'ict', 0, 'Web', 'Internet of Things (IoT)', 'active', '2025-08-13 17:06:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subadmin_classification_requests`
--

CREATE TABLE `subadmin_classification_requests` (
                                                    `id` int(11) NOT NULL,
                                                    `subadmin_id` int(11) NOT NULL,
                                                    `requested_software_classification` varchar(100) DEFAULT NULL,
                                                    `requested_hardware_classification` varchar(100) DEFAULT NULL,
                                                    `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                                                    `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
                                                    `decision_date` timestamp NULL DEFAULT NULL,
                                                    `admin_comment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subadmin_classification_requests`
--

INSERT INTO `subadmin_classification_requests` (`id`, `subadmin_id`, `requested_software_classification`, `requested_hardware_classification`, `status`, `request_date`, `decision_date`, `admin_comment`) VALUES
                                                                                                                                                                                                               (1, 4, '', 'Robotics', 'approved', '2025-07-07 17:59:14', '2025-07-07 17:59:36', ''),
                                                                                                                                                                                                               (2, 4, 'Mobile', '', 'approved', '2025-07-07 17:59:40', '2025-07-07 18:00:04', ''),
                                                                                                                                                                                                               (3, 4, 'Web', '', 'approved', '2025-07-07 18:00:16', '2025-07-07 18:02:36', ''),
                                                                                                                                                                                                               (4, 4, 'Web', '', 'approved', '2025-07-07 18:02:41', '2025-07-07 18:02:59', ''),
                                                                                                                                                                                                               (5, 4, 'Mobile', 'Internet of Things (IoT)', 'approved', '2025-07-07 18:03:13', '2025-07-07 18:03:18', ''),
                                                                                                                                                                                                               (6, 6, 'Web', 'Embedded Systems', 'approved', '2025-08-14 03:09:06', '2025-08-14 03:10:14', 'Request approved by admin'),
                                                                                                                                                                                                               (7, 6, 'Artificial Intelligence & Machine Learning', '', 'approved', '2025-09-06 05:07:40', '2025-09-06 05:07:53', 'Request approved by admin'),
                                                                                                                                                                                                               (8, 6, 'Web', 'Internet of Things (IoT)', 'approved', '2025-09-07 13:30:52', '2025-09-07 13:31:21', 'Request approved by admin');

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

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `ticket_number`, `subadmin_id`, `subadmin_name`, `subadmin_email`, `subject`, `category`, `priority`, `message`, `status`, `admin_response`, `admin_id`, `admin_name`, `created_at`, `updated_at`, `resolved_at`, `closed_at`, `attachments`) VALUES
                                                                                                                                                                                                                                                                                       (1, 'TK-001-2025', 4, 'vivek', 'viveksinhchavda@gmail.com', 'Login Issue', 'technical', 'medium', 'I am unable to login to my account. The password reset is not working properly.', 'resolved', 'rejected your query', 1, 'Admin', '2025-08-14 03:20:14', '2025-08-14 03:28:12', '2025-08-14 03:28:12', NULL, NULL),
                                                                                                                                                                                                                                                                                       (2, 'TK-002-2025', 4, 'vivek', 'viveksinhchavda@gmail.com', 'Project Approval Question', 'project', 'low', 'How long does it take to approve a project? I submitted one last week.', 'closed', 'not approved your query', 1, 'Admin', '2025-08-14 03:20:14', '2025-08-14 03:27:32', NULL, NULL, NULL),
                                                                                                                                                                                                                                                                                       (3, 'TK-003-2025', 4, 'vivek', 'viveksinhchavda@gmail.com', 'Dashboard Bug', 'bug_report', 'high', 'The dashboard is showing incorrect project counts.', 'resolved', 'ok your respone will consider soon', 1, 'Admin', '2025-08-14 03:20:14', '2025-08-14 03:26:39', '2025-08-14 03:26:39', NULL, NULL),
                                                                                                                                                                                                                                                                                       (4, 'TK-981-2025', 6, 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'this is a error', 'technical', 'low', 'solve this error', 'resolved', 'we will try to solve this eror', 1, 'Admin', '2025-08-14 03:37:04', '2025-08-14 03:38:49', '2025-08-14 03:38:49', NULL, NULL),
                                                                                                                                                                                                                                                                                       (5, 'TK-489-2025', 6, 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'this is a error', 'technical', 'low', 'solve this error', 'resolved', 'ok solved this erre', 1, 'Admin', '2025-08-14 03:39:27', '2025-08-14 03:40:21', '2025-08-14 03:40:21', NULL, NULL),
                                                                                                                                                                                                                                                                                       (6, 'TK-107-2025', 6, 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'this is a error', 'technical', 'low', 'solve this error', 'open', NULL, NULL, NULL, '2025-08-14 03:40:35', '2025-08-14 03:40:35', NULL, NULL, NULL),
                                                                                                                                                                                                                                                                                       (7, 'TK-807-2025', 6, 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'this is a error', 'technical', 'low', 'solve this error', 'open', NULL, NULL, NULL, '2025-08-14 03:40:44', '2025-08-14 03:40:44', NULL, NULL, NULL),
                                                                                                                                                                                                                                                                                       (8, 'TK-916-2025', 6, 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'this is a error', 'technical', 'low', 'solve this error', 'open', NULL, NULL, NULL, '2025-08-14 03:40:48', '2025-08-14 03:40:48', NULL, NULL, NULL);

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

--
-- Dumping data for table `support_ticket_replies`
--

INSERT INTO `support_ticket_replies` (`id`, `ticket_id`, `sender_type`, `sender_name`, `sender_email`, `message`, `created_at`) VALUES
                                                                                                                                    (1, 1, 'subadmin', 'vivek', 'viveksinhchavda@gmail.com', 'I am unable to login to my account. The password reset is not working properly.', '2025-08-14 03:20:14'),
                                                                                                                                    (2, 2, 'subadmin', 'vivek', 'viveksinhchavda@gmail.com', 'How long does it take to approve a project? I submitted one last week.', '2025-08-14 03:20:14'),
                                                                                                                                    (3, 2, 'admin', 'Admin', 'admin@ideanest.com', 'Project approvals usually take 3-5 business days. Let me check the status of your specific project.', '2025-08-14 03:20:14'),
                                                                                                                                    (4, 3, 'subadmin', 'vivek', 'viveksinhchavda@gmail.com', 'The dashboard is showing incorrect project counts.', '2025-08-14 03:20:14'),
                                                                                                                                    (5, 3, 'admin', 'Admin', 'admin@ideanest.com', 'ok your respone will consider soon', '2025-08-14 03:26:39'),
                                                                                                                                    (6, 2, 'admin', 'Admin', 'admin@ideanest.com', 'not approved your query', '2025-08-14 03:27:32'),
                                                                                                                                    (7, 1, 'admin', 'Admin', 'admin@ideanest.com', 'not working and good', '2025-08-14 03:27:45'),
                                                                                                                                    (8, 1, 'admin', 'Admin', 'admin@ideanest.com', 'rejected your query', '2025-08-14 03:28:12'),
                                                                                                                                    (9, 4, 'subadmin', 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'solve this error', '2025-08-14 03:37:04'),
                                                                                                                                    (10, 4, 'admin', 'Admin', 'ideanest.ict@gmail.com', 'we will try to solve this eror', '2025-08-14 03:38:49'),
                                                                                                                                    (11, 5, 'subadmin', 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'solve this error', '2025-08-14 03:39:27'),
                                                                                                                                    (12, 5, 'admin', 'Admin', 'ideanest.ict@gmail.com', 'ok solved this erre', '2025-08-14 03:40:21'),
                                                                                                                                    (13, 6, 'subadmin', 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'solve this error', '2025-08-14 03:40:35'),
                                                                                                                                    (14, 7, 'subadmin', 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'solve this error', '2025-08-14 03:40:44'),
                                                                                                                                    (15, 8, 'subadmin', 'vivek', 'vivek.chavda119486@marwadiuniversity.ac.in', 'solve this error', '2025-08-14 03:40:48');

-- --------------------------------------------------------

--
-- Table structure for table `temp_project_ownership`
--

CREATE TABLE `temp_project_ownership` (
                                          `project_id` int(11) NOT NULL,
                                          `user_session` varchar(255) NOT NULL,
                                          `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temp_project_ownership`
--

INSERT INTO `temp_project_ownership` (`project_id`, `user_session`, `created_at`) VALUES
                                                                                      (1, '4evm1tln9kd756idpd3kjmcrmh', '2025-08-22 07:48:23'),
                                                                                      (1, 'mr07ig97qvcibbdoafliq16fm6', '2025-08-22 05:30:12'),
                                                                                      (2, '036qneuqcmlmb17go2h301l6s3', '2025-09-17 13:26:01'),
                                                                                      (2, '0rs4kauq45f3em8j863qgna23l', '2025-09-01 09:23:06'),
                                                                                      (2, '12b1lt7j0j9n5sc2q04cu5e5t2', '2025-09-18 04:07:29'),
                                                                                      (2, 'm8qsgh56nuce6ibf33i0e45ec2', '2025-09-06 04:46:37'),
                                                                                      (3, 't19gk6a8s544dspidejpt5rhe1', '2025-08-24 13:10:43'),
                                                                                      (3, 'tojal0944iv6bik11hcismnunk', '2025-08-25 04:24:56'),
                                                                                      (4, '036qneuqcmlmb17go2h301l6s3', '2025-09-17 13:26:01'),
                                                                                      (4, '0rs4kauq45f3em8j863qgna23l', '2025-09-01 09:23:06'),
                                                                                      (4, '12b1lt7j0j9n5sc2q04cu5e5t2', '2025-09-18 04:07:29'),
                                                                                      (4, 'm8qsgh56nuce6ibf33i0e45ec2', '2025-09-06 04:46:37'),
                                                                                      (5, 't19gk6a8s544dspidejpt5rhe1', '2025-08-24 13:10:43'),
                                                                                      (5, 'tojal0944iv6bik11hcismnunk', '2025-08-25 04:24:56'),
                                                                                      (6, '036qneuqcmlmb17go2h301l6s3', '2025-09-17 13:25:45'),
                                                                                      (6, '0q7k0f10mc625p0tho5fskpv21', '2025-09-08 14:32:13'),
                                                                                      (6, '0rs4kauq45f3em8j863qgna23l', '2025-09-01 08:50:01'),
                                                                                      (6, '12b1lt7j0j9n5sc2q04cu5e5t2', '2025-09-18 03:56:41'),
                                                                                      (6, '1asmr6k7vqhajeif0ugol6b825', '2025-09-08 03:30:10'),
                                                                                      (6, '7p4ngrvpot69vtv4ranmtqjprf', '2025-09-02 16:39:35'),
                                                                                      (6, 'f2p8bp3d2idnme0b860kkfebj1', '2025-09-03 05:14:04'),
                                                                                      (6, 'f752vcgdd6la5uc66ek2c8nmg3', '2025-09-06 08:09:01'),
                                                                                      (6, 'hoei7f5ruh8f1787iujiep5p2k', '2025-09-04 14:32:48'),
                                                                                      (6, 'iqpfamo9lqceg72d599utbbepm', '2025-09-11 17:47:20'),
                                                                                      (6, 'm8qsgh56nuce6ibf33i0e45ec2', '2025-09-06 04:12:26'),
                                                                                      (6, 'p0j7m8rte9lh4b05le0e07uauk', '2025-09-08 07:42:55'),
                                                                                      (6, 'pglj9ovjijarh6kv1bhtn1hk7v', '2025-09-08 07:21:10'),
                                                                                      (6, 'q68pts618n3tkla7sbb6b98dl0', '2025-09-07 13:32:20'),
                                                                                      (6, 'rmuog6fbd12fe5rhm63g2s7uds', '2025-08-25 05:47:14'),
                                                                                      (7, '4dgdaagrjbgjosn9rgsmeld17f', '2025-08-22 05:02:33'),
                                                                                      (7, '4evm1tln9kd756idpd3kjmcrmh', '2025-08-22 07:00:32'),
                                                                                      (7, '6nb1r5mraq35ah2qnk4r8e071n', '2025-08-24 04:02:21'),
                                                                                      (7, 'i36hr61j04lplugm57ej15c7ec', '2025-08-22 05:27:58'),
                                                                                      (7, 'mr07ig97qvcibbdoafliq16fm6', '2025-08-22 05:25:00'),
                                                                                      (8, '13o97977urecpucer8j6i4vav9', '2025-08-25 04:54:01'),
                                                                                      (8, '6nb1r5mraq35ah2qnk4r8e071n', '2025-08-24 06:26:52'),
                                                                                      (8, 'ddb5kuco9gb90g3odh5744ipou', '2025-08-24 12:40:21'),
                                                                                      (8, 'hk0p74hlhbreqplp8okkrs95dk', '2025-08-24 12:54:10'),
                                                                                      (8, 'ri9di5c9cqi9ha45r3vq3c35ba', '2025-08-25 04:45:36'),
                                                                                      (8, 't19gk6a8s544dspidejpt5rhe1', '2025-08-24 12:54:20'),
                                                                                      (8, 'tojal0944iv6bik11hcismnunk', '2025-08-25 04:18:57'),
                                                                                      (9, '4dgdaagrjbgjosn9rgsmeld17f', '2025-08-22 05:02:33'),
                                                                                      (9, '4evm1tln9kd756idpd3kjmcrmh', '2025-08-22 07:00:32'),
                                                                                      (9, '6nb1r5mraq35ah2qnk4r8e071n', '2025-08-24 04:02:21'),
                                                                                      (9, 'i36hr61j04lplugm57ej15c7ec', '2025-08-22 05:27:58'),
                                                                                      (9, 'mr07ig97qvcibbdoafliq16fm6', '2025-08-22 05:25:00'),
                                                                                      (10, '13o97977urecpucer8j6i4vav9', '2025-08-25 04:54:01'),
                                                                                      (10, '6nb1r5mraq35ah2qnk4r8e071n', '2025-08-24 06:26:52'),
                                                                                      (10, 'ddb5kuco9gb90g3odh5744ipou', '2025-08-24 12:40:21'),
                                                                                      (10, 'hk0p74hlhbreqplp8okkrs95dk', '2025-08-24 12:54:10'),
                                                                                      (10, 'ri9di5c9cqi9ha45r3vq3c35ba', '2025-08-25 04:45:36'),
                                                                                      (10, 't19gk6a8s544dspidejpt5rhe1', '2025-08-24 12:54:20'),
                                                                                      (10, 'tojal0944iv6bik11hcismnunk', '2025-08-25 04:18:57'),
                                                                                      (11, '036qneuqcmlmb17go2h301l6s3', '2025-09-17 13:25:45'),
                                                                                      (11, '0q7k0f10mc625p0tho5fskpv21', '2025-09-08 14:32:13'),
                                                                                      (11, '0rs4kauq45f3em8j863qgna23l', '2025-09-01 08:50:01'),
                                                                                      (11, '12b1lt7j0j9n5sc2q04cu5e5t2', '2025-09-18 03:56:41'),
                                                                                      (11, '1asmr6k7vqhajeif0ugol6b825', '2025-09-08 03:30:10'),
                                                                                      (11, '7p4ngrvpot69vtv4ranmtqjprf', '2025-09-02 16:39:35'),
                                                                                      (11, 'f2p8bp3d2idnme0b860kkfebj1', '2025-09-03 05:14:04'),
                                                                                      (11, 'f752vcgdd6la5uc66ek2c8nmg3', '2025-09-06 08:09:01'),
                                                                                      (11, 'hoei7f5ruh8f1787iujiep5p2k', '2025-09-04 14:32:48'),
                                                                                      (11, 'iqpfamo9lqceg72d599utbbepm', '2025-09-11 17:47:20'),
                                                                                      (11, 'm8qsgh56nuce6ibf33i0e45ec2', '2025-09-06 04:12:26'),
                                                                                      (11, 'p0j7m8rte9lh4b05le0e07uauk', '2025-09-08 07:42:55'),
                                                                                      (11, 'pglj9ovjijarh6kv1bhtn1hk7v', '2025-09-08 07:21:10'),
                                                                                      (11, 'q68pts618n3tkla7sbb6b98dl0', '2025-09-07 13:32:20'),
                                                                                      (11, 'rmuog6fbd12fe5rhm63g2s7uds', '2025-08-25 05:47:14'),
                                                                                      (12, '4dgdaagrjbgjosn9rgsmeld17f', '2025-08-22 05:02:33'),
                                                                                      (12, '4evm1tln9kd756idpd3kjmcrmh', '2025-08-22 07:00:32'),
                                                                                      (12, '6nb1r5mraq35ah2qnk4r8e071n', '2025-08-24 04:02:21'),
                                                                                      (12, 'i36hr61j04lplugm57ej15c7ec', '2025-08-22 05:27:58'),
                                                                                      (12, 'mr07ig97qvcibbdoafliq16fm6', '2025-08-22 05:25:00'),
                                                                                      (13, '13o97977urecpucer8j6i4vav9', '2025-08-25 04:54:01'),
                                                                                      (13, '6nb1r5mraq35ah2qnk4r8e071n', '2025-08-24 06:26:52'),
                                                                                      (13, 'ddb5kuco9gb90g3odh5744ipou', '2025-08-24 12:40:21'),
                                                                                      (13, 'hk0p74hlhbreqplp8okkrs95dk', '2025-08-24 12:54:10'),
                                                                                      (13, 'ri9di5c9cqi9ha45r3vq3c35ba', '2025-08-25 04:45:36'),
                                                                                      (13, 't19gk6a8s544dspidejpt5rhe1', '2025-08-24 12:54:20'),
                                                                                      (13, 'tojal0944iv6bik11hcismnunk', '2025-08-25 04:18:57'),
                                                                                      (14, '036qneuqcmlmb17go2h301l6s3', '2025-09-17 13:25:45'),
                                                                                      (14, '0q7k0f10mc625p0tho5fskpv21', '2025-09-08 14:32:13'),
                                                                                      (14, '0rs4kauq45f3em8j863qgna23l', '2025-09-01 08:50:01'),
                                                                                      (14, '12b1lt7j0j9n5sc2q04cu5e5t2', '2025-09-18 03:56:41'),
                                                                                      (14, '1asmr6k7vqhajeif0ugol6b825', '2025-09-08 03:30:10'),
                                                                                      (14, '7p4ngrvpot69vtv4ranmtqjprf', '2025-09-02 16:39:35'),
                                                                                      (14, 'f2p8bp3d2idnme0b860kkfebj1', '2025-09-03 05:14:04'),
                                                                                      (14, 'f752vcgdd6la5uc66ek2c8nmg3', '2025-09-06 08:09:01'),
                                                                                      (14, 'hoei7f5ruh8f1787iujiep5p2k', '2025-09-04 14:32:48'),
                                                                                      (14, 'iqpfamo9lqceg72d599utbbepm', '2025-09-11 17:47:20'),
                                                                                      (14, 'm8qsgh56nuce6ibf33i0e45ec2', '2025-09-06 04:12:26'),
                                                                                      (14, 'p0j7m8rte9lh4b05le0e07uauk', '2025-09-08 07:42:55'),
                                                                                      (14, 'pglj9ovjijarh6kv1bhtn1hk7v', '2025-09-08 07:21:10'),
                                                                                      (14, 'q68pts618n3tkla7sbb6b98dl0', '2025-09-07 13:32:20'),
                                                                                      (14, 'rmuog6fbd12fe5rhm63g2s7uds', '2025-08-25 05:47:14');

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

-- --------------------------------------------------------

--
-- Table structure for table `websocket_connections`
--

CREATE TABLE `websocket_connections` (
                                         `id` int(11) NOT NULL,
                                         `user_id` int(11) NOT NULL,
                                         `connection_id` varchar(255) NOT NULL,
                                         `last_ping` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                                         `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `approved_project_details_view`
--
DROP TABLE IF EXISTS `approved_project_details_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `approved_project_details_view`  AS SELECT `ap`.`id` AS `id`, `ap`.`user_id` AS `user_id`, `r`.`name` AS `user_name`, `r`.`email` AS `user_email`, `r`.`enrollment_number` AS `enrollment_number`, `r`.`department` AS `department`, `ap`.`project_name` AS `project_name`, `ap`.`project_type` AS `project_type`, `ap`.`classification` AS `classification`, `ap`.`project_category` AS `project_category`, `ap`.`difficulty_level` AS `difficulty_level`, `ap`.`description` AS `description`, `ap`.`language` AS `language`, `ap`.`development_time` AS `development_time`, `ap`.`team_size` AS `team_size`, `ap`.`target_audience` AS `target_audience`, `ap`.`project_goals` AS `project_goals`, `ap`.`challenges_faced` AS `challenges_faced`, `ap`.`future_enhancements` AS `future_enhancements`, `ap`.`github_repo` AS `github_repo`, `ap`.`live_demo_url` AS `live_demo_url`, `ap`.`project_license` AS `project_license`, `ap`.`keywords` AS `keywords`, `ap`.`contact_email` AS `contact_email`, `ap`.`social_links` AS `social_links`, `ap`.`image_path` AS `image_path`, `ap`.`video_path` AS `video_path`, `ap`.`code_file_path` AS `code_file_path`, `ap`.`instruction_file_path` AS `instruction_file_path`, `ap`.`presentation_file_path` AS `presentation_file_path`, `ap`.`additional_files_path` AS `additional_files_path`, `ap`.`submission_date` AS `submission_date`, `ap`.`status` AS `status` FROM (`admin_approved_projects` `ap` left join `register` `r` on(`ap`.`user_id` = `r`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `project_details_view`
--
DROP TABLE IF EXISTS `project_details_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `project_details_view`  AS SELECT `p`.`id` AS `id`, `p`.`user_id` AS `user_id`, `r`.`name` AS `user_name`, `r`.`email` AS `user_email`, `r`.`enrollment_number` AS `enrollment_number`, `r`.`department` AS `department`, `p`.`project_name` AS `project_name`, `p`.`project_type` AS `project_type`, `p`.`classification` AS `classification`, `p`.`project_category` AS `project_category`, `p`.`difficulty_level` AS `difficulty_level`, `p`.`description` AS `description`, `p`.`language` AS `language`, `p`.`development_time` AS `development_time`, `p`.`team_size` AS `team_size`, `p`.`target_audience` AS `target_audience`, `p`.`project_goals` AS `project_goals`, `p`.`challenges_faced` AS `challenges_faced`, `p`.`future_enhancements` AS `future_enhancements`, `p`.`github_repo` AS `github_repo`, `p`.`live_demo_url` AS `live_demo_url`, `p`.`project_license` AS `project_license`, `p`.`keywords` AS `keywords`, `p`.`contact_email` AS `contact_email`, `p`.`social_links` AS `social_links`, `p`.`image_path` AS `image_path`, `p`.`video_path` AS `video_path`, `p`.`code_file_path` AS `code_file_path`, `p`.`instruction_file_path` AS `instruction_file_path`, `p`.`presentation_file_path` AS `presentation_file_path`, `p`.`additional_files_path` AS `additional_files_path`, `p`.`submission_date` AS `submission_date`, `p`.`status` AS `status` FROM (`projects` `p` left join `register` `r` on(`p`.`user_id` = `r`.`id`)) ;

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
  ADD KEY `idx_blog_submission_date` (`submission_datetime`);

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
-- Indexes for table `denial_projects`
--
ALTER TABLE `denial_projects`
    ADD PRIMARY KEY (`id`);

--
-- Indexes for table `github_analytics_cache`
--
ALTER TABLE `github_analytics_cache`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_cache` (`user_id`,`cache_key`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `github_contributions`
--
ALTER TABLE `github_contributions`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`contribution_date`),
  ADD KEY `idx_user_date` (`user_id`,`contribution_date`),
  ADD KEY `idx_github_username` (`github_username`);

--
-- Indexes for table `github_language_stats`
--
ALTER TABLE `github_language_stats`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_language` (`user_id`,`language`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `github_repo_analytics`
--
ALTER TABLE `github_repo_analytics`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_repo` (`user_id`,`repo_name`),
  ADD KEY `idx_user_id` (`user_id`);

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
-- Indexes for table `mentor_email_templates`
--
ALTER TABLE `mentor_email_templates`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mentor_template` (`mentor_id`,`template_type`);

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
  ADD KEY `idx_project_type` (`project_type`);

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
  ADD UNIQUE KEY `google_id` (`google_id`);

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
-- Indexes for table `websocket_connections`
--
ALTER TABLE `websocket_connections`
    ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_connection` (`user_id`,`connection_id`);

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
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT for table `blog`
--
ALTER TABLE `blog`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `bookmark`
--
ALTER TABLE `bookmark`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=780;

--
-- AUTO_INCREMENT for table `comment_likes`
--
ALTER TABLE `comment_likes`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `denial_projects`
--
ALTER TABLE `denial_projects`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `github_analytics_cache`
--
ALTER TABLE `github_analytics_cache`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `github_contributions`
--
ALTER TABLE `github_contributions`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `github_language_stats`
--
ALTER TABLE `github_language_stats`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `github_repo_analytics`
--
ALTER TABLE `github_repo_analytics`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `idea_comments`
--
ALTER TABLE `idea_comments`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `idea_likes`
--
ALTER TABLE `idea_likes`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mentoring_sessions`
--
ALTER TABLE `mentoring_sessions`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mentors`
--
ALTER TABLE `mentors`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mentor_activity_logs`
--
ALTER TABLE `mentor_activity_logs`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mentor_email_logs`
--
ALTER TABLE `mentor_email_logs`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT for table `mentor_email_templates`
--
ALTER TABLE `mentor_email_templates`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentor_project_access`
--
ALTER TABLE `mentor_project_access`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mentor_requests`
--
ALTER TABLE `mentor_requests`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mentor_student_pairs`
--
ALTER TABLE `mentor_student_pairs`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notification_counters`
--
ALTER TABLE `notification_counters`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
    MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_comments`
--
ALTER TABLE `project_comments`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `project_likes`
--
ALTER TABLE `project_likes`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `realtime_notifications`
--
ALTER TABLE `realtime_notifications`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `removed_user`
--
ALTER TABLE `removed_user`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_email_preferences`
--
ALTER TABLE `student_email_preferences`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `subadmins`
--
ALTER TABLE `subadmins`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subadmin_classification_requests`
--
ALTER TABLE `subadmin_classification_requests`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
    MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
-- AUTO_INCREMENT for table `websocket_connections`
--
ALTER TABLE `websocket_connections`
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
-- Constraints for table `github_analytics_cache`
--
ALTER TABLE `github_analytics_cache`
    ADD CONSTRAINT `github_analytics_cache_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `github_contributions`
--
ALTER TABLE `github_contributions`
    ADD CONSTRAINT `github_contributions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `github_language_stats`
--
ALTER TABLE `github_language_stats`
    ADD CONSTRAINT `github_language_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `github_repo_analytics`
--
ALTER TABLE `github_repo_analytics`
    ADD CONSTRAINT `github_repo_analytics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `mentor_email_templates`
--
ALTER TABLE `mentor_email_templates`
    ADD CONSTRAINT `mentor_email_templates_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

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