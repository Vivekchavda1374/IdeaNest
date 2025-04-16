-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2025 at 04:16 AM
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
(0, 'Arduino Project', 'hardware', 'web', 'this is my project', 'C++', 'uploads/images/WhatsApp Image 2025-03-01 at 13.13.29_3991b76d.jpg', '', '', '', '2025-03-01 23:50:01', 'approved'),
(0, 'bhaviik', 'hardware', 'web', 'vasd', 'vda', '', '', '', '', '2025-03-01 06:41:51', 'approved'),
(0, 'IdeaNest', 'software', 'web', 'Collaboration with your Mentors ', 'HTML, CSS, JS, PHP, MYSQL', 'uploads/images/Screenshot 2025-03-01 125741.png', 'uploads/videos/2278095-hd_1920_1080_30fps.mp4', 'uploads/code_files/.gitignore', 'uploads/instructions/11.pdf', '2025-03-01 05:37:32', 'approved');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
