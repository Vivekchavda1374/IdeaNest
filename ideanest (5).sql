-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 15, 2025 at 11:21 AM
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
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_approved_projects`
--

INSERT INTO `admin_approved_projects` (`id`, `project_name`, `project_type`, `classification`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `submission_date`, `status`) VALUES
(1, 'FleetLedger', 'hardware', 'electronics', 'fs', 'fb', NULL, NULL, NULL, NULL, '2025-04-15 05:49:02', 'approved'),
(2, 'title', 'software', 'web', 'ca', 'd', NULL, NULL, NULL, NULL, '2025-04-15 05:48:04', 'approved');

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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog`
--

INSERT INTO `blog` (`id`, `er_number`, `project_name`, `project_type`, `classification`, `description`, `submission_datetime`, `status`, `priority1`, `assigned_to`, `completion_date`, `created_at`, `updated_at`) VALUES
(1, '92200133026', 'few', 'software', 'mobileapp', 'absjbhjavks', '2025-03-22 15:19:23', 'in_progress', 'medium', 'ad', '2025-03-22', '2025-03-22 03:10:26', '2025-03-22 04:19:23'),
(2, '92200133027', 'j', 'hardware', 'iotdevice', 'vvj', '2025-03-22 14:17:25', 'pending', 'medium', NULL, NULL, '2025-03-22 03:17:25', '2025-03-22 03:17:25'),
(3, '92200133052', '00000', 'software', 'webapp', 'vra', '2025-03-22 16:46:22', 'in_progress', 'high', 'ad', '2025-03-22', '2025-03-22 03:38:30', '2025-03-22 05:46:22'),
(4, '92200133027', 'j', 'software', 'webapp', 'z', '2025-03-22 14:39:32', 'completed', 'medium', 'ad', '2025-03-22', '2025-03-22 03:39:32', '2025-03-22 03:39:32'),
(5, '92200133027', 'viv', 'hardware', 'robotics', 'sfbsb', '2025-03-22 15:29:19', 'pending', 'medium', 'sB', '2025-03-29', '2025-03-22 04:29:19', '2025-03-22 04:29:19'),
(6, '92200133027', 'viv', 'hardware', 'robotics', 'sfbsb', '2025-03-22 15:34:27', 'pending', 'medium', 'sB', '2025-03-29', '2025-03-22 04:34:27', '2025-03-22 04:34:27'),
(7, 'f', 'vsd', 'hardware', 'iotdevice', 'dsvvs', '2025-03-22 15:34:54', 'in_progress', 'medium', 'sd', '2025-03-27', '2025-03-22 04:34:54', '2025-03-22 04:34:54'),
(8, '92200133026', 'few', 'hardware', 'iotdevice', 'sfz', '2025-03-22 15:39:18', 'rejected', 'low', 'wvc', NULL, '2025-03-22 04:39:18', '2025-03-22 04:39:18'),
(9, '92200133026', 'viv', 'software', 'mobileapp', 'fs', '2025-03-22 15:48:36', 'in_progress', 'low', 'cd', '2025-03-21', '2025-03-22 04:48:36', '2025-03-22 04:48:36'),
(10, '92200133027', 'viv', 'software', 'webapp', 'a', '2025-03-22 16:39:37', 'pending', 'medium', '', NULL, '2025-03-22 05:39:37', '2025-03-22 05:39:37'),
(11, '92200133026', 'dcs', 'software', 'desktopapp', 'sd', '2025-03-22 16:42:23', 'in_progress', 'low', '', NULL, '2025-03-22 05:42:23', '2025-03-22 05:42:23'),
(12, '92310133019', 'impossible task', 'software', 'mobileapp', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2025-04-06 21:45:31', 'in_progress', 'high', 'dfgfdm', '2025-04-06', '2025-04-06 16:15:31', '2025-04-06 16:15:31'),
(13, '92200133027', 'invisible cloth', 'hardware', 'electroniccircuit', 'thi is a projecy', '2025-04-14 11:29:48', 'pending', 'high', '', '2025-04-15', '2025-04-14 05:59:48', '2025-04-14 05:59:48');

-- --------------------------------------------------------

--
-- Table structure for table `bookmark`
--

CREATE TABLE `bookmark` (
  `id` int(10) NOT NULL,
  `user_id` int(15) NOT NULL,
  `project_id` int(15) NOT NULL,
  `idea_id` int(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 0, 'HCD', 'hardware', 'robotics', 'this project will done in hdc subject', 'python', '', '', '', '', '2025-04-13 02:49:50', 'pending', NULL, 'test'),
(2, 0, 'HDD', 'hardware', 'robotics', 'as', 'df', '', '', '', '', '2025-04-13 03:12:15', 'pending', NULL, 'test'),
(3, 58, 'idea nest', 'hardware', 'robotics', 'test1', 'test 1', NULL, NULL, NULL, NULL, '2025-04-15 05:04:48', 'rejected', '2025-04-15 14:05:01', 'not working ');

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
(1, 58, 'title', 'software', 'web', 'ca', 'd', NULL, NULL, NULL, NULL, '2025-04-15 05:48:04', 'approved'),
(2, 58, 'FleetLedger', 'hardware', 'electronics', 'fs', 'fb', NULL, NULL, NULL, NULL, '2025-04-15 05:49:02', 'approved');

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
(58, 'Vivek Chavda', 'vivek.chavda119486@marwadiuniversity.ac.in', '92200133026', '119486', '$2y$10$Z26VWvc3mmrXb33CK.CbcuSvttmjci3r4M9eIz3hyBS4CwMavUzjO', '', '');

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_approved_projects`
--
ALTER TABLE `admin_approved_projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blog`
--
ALTER TABLE `blog`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_er_number` (`er_number`),
  ADD KEY `idx_project_type` (`project_type`),
  ADD KEY `idx_classification` (`classification`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `bookmark`
--
ALTER TABLE `bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `denial_projects`
--
ALTER TABLE `denial_projects`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `er_number` (`enrollment_number`),
  ADD UNIQUE KEY `gr_number` (`gr_number`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_approved_projects`
--
ALTER TABLE `admin_approved_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `blog`
--
ALTER TABLE `blog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `bookmark`
--
ALTER TABLE `bookmark`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `denial_projects`
--
ALTER TABLE `denial_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `removed_user`
--
ALTER TABLE `removed_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
