-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2025 at 04:41 AM
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
(5, 0, 'GYM', 'software', 'mobile', 'This is a mobile app application ', 'flutter , dart , firebase ', 'uploads/images/50.jpg', 'uploads/videos/2024-04-13 13-46-30.mp4', '', 'uploads/instructions/Using_Artificial_Intelligence_in_Source_Code_Summa.pdf', '2025-03-01 06:07:27', 'pending'),
(7, 0, 'AI base system', 'software', 'mobile', 'Its all about AI based system which will help to search anything without searching anywhere else so you can learn more and more ', 'python', 'uploads/images/image_1.jpg', '', '', '', '2025-03-01 08:04:01', 'pending'),
(5, 0, 'GYM', 'software', 'mobile', 'This is a mobile app application ', 'flutter , dart , firebase ', 'uploads/images/50.jpg', 'uploads/videos/2024-04-13 13-46-30.mp4', '', 'uploads/instructions/Using_Artificial_Intelligence_in_Source_Code_Summa.pdf', '2025-03-01 06:07:27', 'pending'),
(7, 0, 'AI base system', 'software', 'mobile', 'Its all about AI based system which will help to search anything without searching anywhere else so you can learn more and more ', 'python', 'uploads/images/image_1.jpg', '', '', '', '2025-03-01 08:04:01', 'pending'),
(0, 14, 'neel kumar', 'hardware', 'web', 'aosudhasdiufh', 'asg', '', '', '', '', '2025-03-22 15:30:34', 'pending');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
