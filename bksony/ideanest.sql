-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 01, 2025 at 10:27 AM
-- Server version: 11.6.2-MariaDB
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
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

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
  `project_status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `project_type`, `classification`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `submission_date`, `project_status`) VALUES
(1, 'vivek', 'hardware', 'web', 'fasba', 'sf', '', '', '', '', '2025-03-01 09:13:30', 'Pending'),
(2, 'title ', 'software', 'desktop', 'rgab', 'sf', 'uploads/images/unnamed.png', '', '', '', '2025-03-01 09:18:30', 'Pending'),
(3, 'title ', 'software', 'web', 'ddvssvsd', 'cWEEVE', 'uploads/images/WhatsApp Image 2025-03-01 at 13.13.29_3991b76d.jpg', '', 'uploads/code_files/AWT_MU_2425-master (1).zip', 'uploads/instructions/OT (1).pdf', '2025-03-01 09:22:10', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `er_number` varchar(50) NOT NULL,
  `gr_number` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`id`, `name`, `email`, `er_number`, `gr_number`, `password`) VALUES
(7, 'Vivek Chavda', 'vivek.chavda119486@marwadiuniversity.ac.in', '92200133026', '119486', '$2y$10$X6u9dGCmkn/gXMsUDJdjaeCWyNT.of8Rpi/V47eWKMqJqMPnAjG/K');

--
-- Indexes for dumped tables
--

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
  ADD UNIQUE KEY `er_number` (`er_number`),
  ADD UNIQUE KEY `gr_number` (`gr_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
