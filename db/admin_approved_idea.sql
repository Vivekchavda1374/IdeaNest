-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 16, 2025 at 05:42 PM
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
-- Table structure for table `admin_approved_idea`
--

CREATE TABLE `admin_approved_idea` (
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
-- Dumping data for table `admin_approved_idea`
--

INSERT INTO `admin_approved_idea` (`id`, `er_number`, `project_name`, `project_type`, `classification`, `description`, `submission_datetime`, `status`, `priority1`, `assigned_to`, `completion_date`, `created_at`, `updated_at`) VALUES
(13, '92200133027', 'invisible cloth', 'hardware', 'electroniccircuit', 'thi is a projecy', '2025-04-14 11:29:48', 'pending', 'high', '', '2025-04-15', '2025-04-14 05:59:48', '2025-04-14 05:59:48'),
(14, '92310133019', 'test 20', 'software', 'system', 'test 20', '2025-04-16 15:05:46', 'in_progress', 'medium', '', NULL, '2025-04-16 09:43:54', '2025-04-16 09:43:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_approved_idea`
--
ALTER TABLE `admin_approved_idea`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `er_number` (`er_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_approved_idea`
--
ALTER TABLE `admin_approved_idea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
