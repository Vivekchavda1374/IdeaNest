-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2025 at 06:48 AM
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
-- Database: `ideanest`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_approved_projects`
--

CREATE TABLE `admin_approved_projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_type` varchar(50) NOT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `language` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `code_file_path` varchar(255) DEFAULT NULL,
  `instruction_file_path` varchar(255) DEFAULT NULL,
  `submission_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_approved_projects`
--

INSERT INTO `admin_approved_projects` (`id`, `project_name`, `project_type`, `classification`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `submission_date`, `status`, `user_id`) VALUES
(2, '1', 'software', 'mobile', '1', '1', NULL, NULL, NULL, NULL, '2025-05-21 00:14:23', 'approved', 61),
(3, 'IdeaNest', 'software', 'data_science', 'deployment', 'HTML, CSS, JS, PHP, MYSQL', NULL, NULL, NULL, NULL, '2025-04-19 11:21:06', 'approved', 61),
(4, 'IdeaNest', 'software', 'data_science', 'deployment', 'HTML, CSS, JS, PHP, MYSQL', NULL, NULL, NULL, NULL, '2025-04-19 11:21:06', 'approved', 61),
(5, '1', 'software', 'mobile', '1', '1', NULL, NULL, NULL, NULL, '2025-05-21 00:14:23', 'approved', 61);

-- --------------------------------------------------------

--
-- Table structure for table `blog`
--

CREATE TABLE `blog` (
  `id` int(11) NOT NULL,
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
  `register_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog`
--

INSERT INTO `blog` (`id`, `er_number`, `project_name`, `project_type`, `classification`, `description`, `submission_datetime`, `status`, `priority1`, `assigned_to`, `completion_date`, `created_at`, `updated_at`, `register_id`) VALUES
(1, '92200133041', 'Dhruvi', 'hardware', 'robotics', 'this is just a test case for each user', '2025-05-21 13:56:39', 'pending', 'high', 'me and priyanshi', '1999-12-09', '2025-05-21 08:26:39', '2025-05-21 08:26:39', 62),
(2, '92200133002', 'harsh', 'software', 'game', 'this is just a test case for each user', '2025-05-21 13:58:52', 'pending', 'medium', 'me alon', '2025-01-11', '2025-05-21 08:28:52', '2025-05-21 08:28:52', 63),
(3, '92200133040', 'this is just a test case for each user', 'software', 'ai_ml', 'this is just a test case for each user', '2025-05-21 14:00:34', 'rejected', 'low', 'umang', '2025-01-22', '2025-05-21 08:30:34', '2025-05-21 08:30:34', 64),
(4, '92200133016', 'test case', 'hardware', 'automation', 'this is just a test case for each user', '2025-05-21 14:02:21', 'in_progress', 'medium', 'this is just a test case for each user', '2025-05-05', '2025-05-21 08:32:22', '2025-05-21 08:32:22', 65),
(5, '92200133027', 'tesing this function', 'hardware', 'wearable', 'this is just a test case for each user', '2025-05-21 14:04:12', 'rejected', 'medium', 'me', '2024-06-06', '2025-05-21 08:34:12', '2025-05-21 08:34:12', 66);

-- --------------------------------------------------------

--
-- Table structure for table `denial_projects`
--

CREATE TABLE `denial_projects` (
  `id` int(11) NOT NULL,
  `user_id` int(15) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_type` varchar(50) NOT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `language` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `code_file_path` varchar(255) DEFAULT NULL,
  `instruction_file_path` varchar(255) DEFAULT NULL,
  `submission_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_date` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `denial_projects`
--

INSERT INTO `denial_projects` (`id`, `user_id`, `project_name`, `project_type`, `classification`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `submission_date`, `status`, `rejection_date`, `rejection_reason`) VALUES
(1, 65, 'mallahar', 'hardware', 'power', 'this is just a test case for each user', 'this is just a test case for each user', NULL, NULL, NULL, NULL, '2025-05-21 05:01:39', 'rejected', '2025-05-21 14:16:15', 'just because this is mallhar\'s project ');

-- --------------------------------------------------------

--
-- Table structure for table `idea_bookmarks`
--

CREATE TABLE `idea_bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `bookmarked_at` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `user_id` int(15) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_type` varchar(50) NOT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `language` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `code_file_path` varchar(255) DEFAULT NULL,
  `instruction_file_path` varchar(255) DEFAULT NULL,
  `submission_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `user_id`, `project_name`, `project_type`, `classification`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `submission_date`, `status`) VALUES
(2, 61, 'IdeaNest', 'software', 'data_science', 'deployment', 'HTML, CSS, JS, PHP, MYSQL', NULL, NULL, NULL, NULL, '2025-04-19 11:21:06', 'approved'),
(3, 61, '1', 'software', 'mobile', '1', '1', NULL, NULL, NULL, NULL, '2025-05-21 00:14:23', 'approved'),
(4, 62, 'business', 'software', 'mobile', 'test', 'test', NULL, NULL, NULL, NULL, '2025-05-21 04:55:11', 'pending'),
(5, 63, 'Harsh', 'hardware', 'power', 'this is just a test case for each user', 'test', NULL, NULL, NULL, NULL, '2025-05-21 04:58:00', 'pending'),
(6, 64, 'this is just a test case for each user', 'hardware', 'sensor', 'this is just a test case for each user', 'this is just a test case for each user', NULL, NULL, NULL, NULL, '2025-05-21 04:59:47', 'pending'),
(7, 65, 'mallahar', 'hardware', 'power', 'this is just a test case for each user', 'this is just a test case for each user', NULL, NULL, NULL, NULL, '2025-05-21 05:01:39', 'rejected'),
(8, 66, 'rishit', 'software', 'cybersecurity', 'this is just a test case for each user', 'tase case', NULL, NULL, NULL, NULL, '2025-05-21 05:03:26', 'pending');

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
  `user_image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`id`, `name`, `email`, `enrollment_number`, `gr_number`, `password`, `about`, `user_image`) VALUES
(61, 'vivek chavda', 'vivek.chavda119486@marwadiuniversity.ac.in', '92200133026', '119486', '$2y$10$n7v4FIYXewWPq3VZERRoVOZr20I4k4.xwygJr5DNUHS330j6ehpnW', '', '0'),
(62, 'dhruvi', 'dhruvi@marwadiuniversity.ac.in', '92200133041', '867132', '$2y$10$gYTahB4DwC5J8c2Y2KzkAeV/T6yofzLu2PuVMsnONUCDf3I8gSXr6', '', ''),
(63, 'harsh doshi', 'harsh@marwadiuniversity.ac.in', '92200133002', '126598', '$2y$10$SOLukju9cEXcg0bXaWL.BeL7djeE86uKnR5xM.bITXjYaLLCHpBZm', '', ''),
(64, 'jay', 'jay @marwadiuniversity.ac.in', '92200133040', '984651', '$2y$10$3icYsYU/xt7jaSm.Dh5/cuFydkIcNQdW4KDJpKY9oV/Msx8WBMkOC', '', ''),
(65, 'Malharkrishna', 'mahllar@marwadiuniversity.ac.in', '92200133016', '978465', '$2y$10$UjsPoOEsew5TnVEfuxkaiuBKnHEvtYVeM8GlOLGq3mwZOV8efcUaa', '', ''),
(66, 'rishit', 'rishit@marwadiuniversity.ac.in', '92200133027', '156489', '$2y$10$kSVO9FiTGCP0PzQ0mtcuz.bNf0iuA2oKXXzvwEfCcYvG/fnfPf84C', '', ''),
(67, 'harshvardhan', 'harshvardhan@marwadiuniversity.ac.in', '92200133028', '146851', '$2y$10$ONZpFfeAXDh/s9twb4/ofesHIKo2wSbB.utISc6AsrUNjoxW1d2Di', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `removed_user`
--

CREATE TABLE `removed_user` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` text NOT NULL,
  `enrollment_number` bigint(12) NOT NULL,
  `gr_number` int(7) NOT NULL,
  `password` varchar(255) NOT NULL,
  `about` varchar(254) NOT NULL,
  `user_image` int(11) NOT NULL
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
-- Table structure for table `user_bookmarks`
--

CREATE TABLE `user_bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `bookmarked_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_bookmarks`
--

INSERT INTO `user_bookmarks` (`id`, `user_id`, `project_id`, `bookmarked_at`) VALUES
(5, 61, 1, '2025-04-19 19:09:02'),
(6, 61, 5, '2025-05-21 09:19:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_approved_projects`
--
ALTER TABLE `admin_approved_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `blog`
--
ALTER TABLE `blog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_er_number` (`er_number`),
  ADD KEY `idx_project_type` (`project_type`),
  ADD KEY `idx_classification` (`classification`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_register_blog` (`register_id`);

--
-- Indexes for table `denial_projects`
--
ALTER TABLE `denial_projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `idea_bookmarks`
--
ALTER TABLE `idea_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bookmark` (`user_id`,`idea_id`),
  ADD KEY `idea_id` (`idea_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `er_number` (`enrollment_number`),
  ADD UNIQUE KEY `gr_number` (`gr_number`),
  ADD KEY `idx_user_search` (`name`,`email`);

--
-- Indexes for table `removed_user`
--
ALTER TABLE `removed_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enrollment_number_idx` (`enrollment_number`),
  ADD UNIQUE KEY `email` (`email`,`enrollment_number`,`gr_number`) USING HASH,
  ADD UNIQUE KEY `email_2` (`email`,`enrollment_number`,`gr_number`) USING HASH;

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`project_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_approved_projects`
--
ALTER TABLE `admin_approved_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `blog`
--
ALTER TABLE `blog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `denial_projects`
--
ALTER TABLE `denial_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `idea_bookmarks`
--
ALTER TABLE `idea_bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `removed_user`
--
ALTER TABLE `removed_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_approved_projects`
--
ALTER TABLE `admin_approved_projects`
  ADD CONSTRAINT `fk_approved_projects_user_id` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`),
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `blog`
--
ALTER TABLE `blog`
  ADD CONSTRAINT `fk_register_blog` FOREIGN KEY (`register_id`) REFERENCES `register` (`id`);

--
-- Constraints for table `idea_bookmarks`
--
ALTER TABLE `idea_bookmarks`
  ADD CONSTRAINT `idea_bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`),
  ADD CONSTRAINT `idea_bookmarks_ibfk_2` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`);

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
