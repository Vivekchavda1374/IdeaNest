-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 30, 2025 at 06:39 PM
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_approved_projects`
--

INSERT INTO `admin_approved_projects` (`id`, `user_id`, `project_name`, `project_type`, `classification`, `project_category`, `difficulty_level`, `development_time`, `team_size`, `target_audience`, `project_goals`, `challenges_faced`, `future_enhancements`, `github_repo`, `live_demo_url`, `project_license`, `keywords`, `contact_email`, `social_links`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `presentation_file_path`, `additional_files_path`, `submission_date`, `status`) VALUES
(1, '2', 'Automated Attendance System with Face Recognition', 'software', 'ai_ml', 'education', 'intermediate', '2-3 months', '2', 'Schools, colleges, corporate training centers, and educational administrators', 'Automate attendance tracking, reduce manual errors, save time for educators, provide accurate attendance analytics, and improve overall administrative efficiency.', 'Ensuring accuracy in different lighting conditions, handling privacy concerns, preventing spoofing attacks, optimizing for real-time processing, and managing large datasets of student faces.', 'Mobile app integration, cloud-based deployment, integration with learning management systems, advanced analytics dashboard, and support for mask detection.', 'https://github.com/viveksinhchavda/face-recognition-attendance', 'https://face-attendance-demo.herokuapp.com', 'BSD-3-Clause', 'face recognition, attendance system, computer vision, automation, education management', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda', 'An intelligent attendance management system using computer vision and facial recognition technology. The system automatically marks attendance when students enter the classroom, generates detailed reports, and integrates with existing student management systems. Features include real-time monitoring, anti-spoofing measures, and privacy protection.', 'Python, OpenCV, TensorFlow, Flask, SQLite, HTML/CSS/JavaScript', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-09 04:52:14', 'approved'),
(2, '2', 'Automated Attendance System with Face Recognition', 'software', 'ai_ml', 'education', 'intermediate', '2-3 months', '2', 'Schools, colleges, corporate training centers, and educational administrators', 'Automate attendance tracking, reduce manual errors, save time for educators, provide accurate attendance analytics, and improve overall administrative efficiency.', 'Ensuring accuracy in different lighting conditions, handling privacy concerns, preventing spoofing attacks, optimizing for real-time processing, and managing large datasets of student faces.', 'Mobile app integration, cloud-based deployment, integration with learning management systems, advanced analytics dashboard, and support for mask detection.', 'https://github.com/viveksinhchavda/face-recognition-attendance', 'https://face-attendance-demo.herokuapp.com', 'BSD-3-Clause', 'face recognition, attendance system, computer vision, automation, education management', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda', 'An intelligent attendance management system using computer vision and facial recognition technology. The system automatically marks attendance when students enter the classroom, generates detailed reports, and integrates with existing student management systems. Features include real-time monitoring, anti-spoofing measures, and privacy protection.', 'Python, OpenCV, TensorFlow, Flask, SQLite, HTML/CSS/JavaScript', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-21 09:42:52', 'approved');

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
(1, 'mentor_removed', 'Removed mentor: vivek chavda (vivek.chavda119486@marwadiuniversity.ac.in)', 1, '2025-11-30 16:50:52', '2025-11-30 16:50:52');

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

--
-- Dumping data for table `blog`
--

INSERT INTO `blog` (`id`, `user_id`, `er_number`, `project_name`, `project_type`, `classification`, `description`, `submission_datetime`, `status`, `priority1`, `assigned_to`, `completion_date`, `created_at`, `updated_at`, `title`, `content`) VALUES
(1, 2, 'ER001', 'Smart Home Automation System', 'software', 'IoT', 'An intelligent home automation system using IoT sensors and machine learning to optimize energy consumption and enhance security.', '2025-11-28 21:43:28', 'pending', 'high', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'Smart Home Automation with AI', 'This project aims to create a comprehensive smart home system that learns from user behavior patterns and automatically adjusts lighting, temperature, and security settings. The system will use various IoT sensors, machine learning algorithms for pattern recognition, and a mobile app for remote control.'),
(2, 2, 'ER002', 'E-Learning Platform', 'software', 'Web Development', 'A modern e-learning platform with interactive courses, real-time collaboration, and AI-powered personalized learning paths.', '2025-11-28 21:43:28', 'in_progress', 'high', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'Next-Gen E-Learning Platform', 'Building a comprehensive online learning platform that features video lectures, interactive quizzes, peer-to-peer collaboration, and AI-driven course recommendations. The platform will support multiple learning styles and provide detailed analytics for both students and instructors.'),
(3, 2, 'ER003', 'Health & Fitness Tracker', 'software', 'Mobile App', 'A comprehensive health and fitness tracking application with workout plans, nutrition tracking, and social features.', '2025-11-28 21:43:28', 'pending', 'medium', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'Personal Health & Fitness Companion', 'Developing a mobile application that helps users track their fitness journey, including workout routines, calorie intake, water consumption, and sleep patterns. The app will provide personalized workout plans, nutrition advice, and allow users to connect with friends for motivation.'),
(4, 2, 'ER004', 'Predictive Analytics Dashboard', 'software', 'Data Science', 'A data analytics platform that uses machine learning to predict trends and provide actionable insights for businesses.', '2025-11-28 21:43:28', 'pending', 'high', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'Business Intelligence Dashboard', 'Creating a powerful analytics dashboard that processes large datasets, identifies patterns, and provides predictive insights. The system will use various ML algorithms for forecasting, anomaly detection, and trend analysis, with an intuitive visualization interface.'),
(5, 2, 'ER005', 'Decentralized Supply Chain', 'software', 'Blockchain', 'A blockchain-based supply chain management system ensuring transparency and traceability of products.', '2025-11-28 21:43:28', 'pending', 'medium', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'Blockchain Supply Chain Solution', 'Implementing a decentralized supply chain tracking system using blockchain technology. This ensures complete transparency, prevents counterfeiting, and provides real-time tracking of products from manufacturer to consumer. Smart contracts will automate various supply chain processes.'),
(6, 2, 'ER006', 'Network Security Monitor', 'software', 'Cybersecurity', 'An advanced network security monitoring tool that detects and prevents cyber threats in real-time.', '2025-11-28 21:43:28', 'in_progress', 'high', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'Real-Time Threat Detection System', 'Developing a comprehensive network security solution that monitors network traffic, detects anomalies, identifies potential threats, and automatically responds to security incidents. The system uses AI/ML for threat intelligence and pattern recognition.'),
(7, 2, 'ER007', 'Educational VR Game', 'software', 'Game Development', 'An immersive virtual reality educational game that makes learning science concepts fun and interactive.', '2025-11-28 21:43:28', 'pending', 'medium', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'VR Science Learning Experience', 'Creating an engaging VR game that teaches complex science concepts through interactive simulations and gamification. Students can explore virtual laboratories, conduct experiments, and learn through hands-on experience in a safe virtual environment.'),
(8, 2, 'ER008', 'Autonomous Drone System', 'hardware', 'Robotics', 'An autonomous drone system for agricultural monitoring and crop health analysis using computer vision.', '2025-11-28 21:43:28', 'pending', 'high', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'Agricultural Monitoring Drone', 'Building an autonomous drone equipped with multispectral cameras and AI-powered image analysis to monitor crop health, detect diseases, and optimize irrigation. The system will provide farmers with actionable insights through a web dashboard.'),
(9, 2, 'ER009', 'Serverless Microservices Platform', 'software', 'Cloud Computing', 'A scalable serverless architecture platform for deploying and managing microservices applications.', '2025-11-28 21:43:28', 'pending', 'medium', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'Cloud-Native Application Platform', 'Developing a platform that simplifies the deployment and management of microservices using serverless architecture. The platform will handle auto-scaling, load balancing, and provide monitoring tools for distributed applications.'),
(10, 2, 'ER010', 'Community Help Network', 'software', 'Social Platform', 'A social platform connecting volunteers with community service opportunities and NGOs.', '2025-11-28 21:43:28', 'pending', 'medium', NULL, NULL, '2025-11-28 16:13:28', '2025-11-28 16:13:28', 'Volunteer Matching Platform', 'Creating a platform that connects volunteers with meaningful community service opportunities. The system matches volunteers based on their skills, interests, and availability with NGOs and community organizations that need help. Includes features for event management, impact tracking, and volunteer recognition.');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `denial_projects`
--

INSERT INTO `denial_projects` (`id`, `user_id`, `project_name`, `project_type`, `classification`, `project_category`, `difficulty_level`, `development_time`, `team_size`, `target_audience`, `project_goals`, `challenges_faced`, `future_enhancements`, `github_repo`, `live_demo_url`, `project_license`, `keywords`, `contact_email`, `social_links`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `presentation_file_path`, `additional_files_path`, `submission_date`, `status`, `rejection_date`, `rejection_reason`) VALUES
(1, 2, 'AI-Powered Student Performance Predictor', 'software', 'ai_ml', 'education', 'expert', '2-3 months', '2', 'Educational institutions, academic counselors, teachers, and students', 'Identify at-risk students early, provide personalized learning recommendations, improve overall academic performance, and assist educators in making data-driven decisions.', 'Handling sensitive student data with privacy concerns, ensuring model accuracy across diverse student populations, dealing with incomplete or biased data, and creating interpretable AI recommendations.', 'Real-time performance monitoring, integration with learning management systems, mobile application for students and parents, advanced visualization dashboards, and multi-language support.', 'https://github.com/viveksinhchavda/ai-student-predictor', 'https://student-predictor-ai.herokuapp.com', 'MIT', 'artificial intelligence, machine learning, education, student performance, predictive analytics', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda, https://github.com/viveksinhchavda', 'A machine learning application that analyzes student performance data to predict academic outcomes and identify students at risk of falling behind. The system uses various algorithms including regression analysis, decision trees, and neural networks to provide accurate predictions and personalized recommendations for improvement.', 'Python, TensorFlow, Scikit-learn, Pandas, Flask, PostgreSQL', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 10:22:14', 'rejected', '2025-11-28 15:03:26', 'not good'),
(2, 2, 'AI-Powered Student Performance Predictor', 'software', 'ai_ml', 'education', 'expert', '2-3 months', '2', 'Educational institutions, academic counselors, teachers, and students', 'Identify at-risk students early, provide personalized learning recommendations, improve overall academic performance, and assist educators in making data-driven decisions.', 'Handling sensitive student data with privacy concerns, ensuring model accuracy across diverse student populations, dealing with incomplete or biased data, and creating interpretable AI recommendations.', 'Real-time performance monitoring, integration with learning management systems, mobile application for students and parents, advanced visualization dashboards, and multi-language support.', 'https://github.com/viveksinhchavda/ai-student-predictor', 'https://student-predictor-ai.herokuapp.com', 'MIT', 'artificial intelligence, machine learning, education, student performance, predictive analytics', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda, https://github.com/viveksinhchavda', 'A machine learning application that analyzes student performance data to predict academic outcomes and identify students at risk of falling behind. The system uses various algorithms including regression analysis, decision trees, and neural networks to provide accurate predictions and personalized recommendations for improvement.', 'Python, TensorFlow, Scikit-learn, Pandas, Flask, PostgreSQL', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25 15:12:52', 'rejected', '2025-11-28 15:25:04', 'not good');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `email_type` varchar(100) DEFAULT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `recipient_email`, `subject`, `email_type`, `status`, `error_message`, `sent_at`, `created_at`) VALUES
(1, 'chavdaviveksinh1374@gmail.com', 'Welcome to IdeaNest - Subadmin Access', 'subadmin_welcome', 'sent', NULL, '2025-11-28 02:41:51', '2025-11-28 07:11:51'),
(2, 'vivek.chavda119486@marwadiuniversity.ac.in', 'Welcome to IdeaNest - Mentor Account Created', 'mentor_welcome', 'sent', NULL, '2025-11-28 02:53:04', '2025-11-28 07:23:04'),
(3, 'vivekcchavda@gmail.com', 'Welcome to IdeaNest - Mentor Account Created', 'mentor_welcome', 'sent', NULL, '2025-11-30 12:21:19', '2025-11-30 16:51:19'),
(4, 'viveksinhchavda639@gmail.com', 'Welcome to IdeaNest - Subadmin Access', 'subadmin_welcome', 'sent', NULL, '2025-11-30 12:28:00', '2025-11-30 16:58:00');

-- --------------------------------------------------------

--
-- Table structure for table `idea_activity_log`
--

CREATE TABLE `idea_activity_log` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('created','updated','deleted','liked','unliked','commented','bookmarked','unbookmarked','shared','viewed','rated') NOT NULL,
  `activity_data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idea_bookmarks`
--

CREATE TABLE `idea_bookmarks` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `idea_bookmarks`
--

INSERT INTO `idea_bookmarks` (`id`, `idea_id`, `user_id`, `created_at`) VALUES
(1, 1, 2, '2025-11-29 07:37:45');

-- --------------------------------------------------------

--
-- Table structure for table `idea_collaborations`
--

CREATE TABLE `idea_collaborations` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `role` varchar(50) DEFAULT 'contributor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idea_comments`
--

CREATE TABLE `idea_comments` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `idea_comments`
--

INSERT INTO `idea_comments` (`id`, `idea_id`, `user_id`, `parent_id`, `comment`, `created_at`, `updated_at`) VALUES
(1, 0, 2, NULL, 'hello', '2025-11-28 16:14:51', '2025-11-28 16:14:51'),
(2, 0, 2, NULL, 'hi', '2025-11-28 16:15:09', '2025-11-28 16:15:09'),
(3, 0, 2, NULL, 'hi', '2025-11-28 16:25:12', '2025-11-28 16:25:12'),
(4, 0, 3, NULL, 'hi', '2025-11-29 07:36:15', '2025-11-29 07:36:15'),
(5, 0, 3, NULL, 'hello', '2025-11-30 13:54:17', '2025-11-30 13:54:17'),
(6, 0, 3, NULL, 'hi', '2025-11-30 13:54:28', '2025-11-30 13:54:28'),
(7, 0, 3, NULL, 'hello', '2025-11-30 13:56:25', '2025-11-30 13:56:25'),
(8, 0, 3, NULL, 'hello', '2025-11-30 13:58:53', '2025-11-30 13:58:53'),
(9, 0, 3, NULL, 'helllo', '2025-11-30 14:03:01', '2025-11-30 14:03:01'),
(10, 0, 3, NULL, 'hello', '2025-11-30 14:07:56', '2025-11-30 14:07:56'),
(11, 0, 3, NULL, 'hi', '2025-11-30 14:09:27', '2025-11-30 14:09:27'),
(12, 0, 3, NULL, 'hi', '2025-11-30 14:13:14', '2025-11-30 14:13:14'),
(13, 0, 3, NULL, 'hell', '2025-11-30 14:13:20', '2025-11-30 14:13:20'),
(14, 1, 3, NULL, 'hi', '2025-11-30 14:15:45', '2025-11-30 14:15:45'),
(15, 1, 3, 14, 'hello', '2025-11-30 14:15:51', '2025-11-30 14:15:51');

-- --------------------------------------------------------

--
-- Table structure for table `idea_followers`
--

CREATE TABLE `idea_followers` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notify_comments` tinyint(1) DEFAULT 1,
  `notify_updates` tinyint(1) DEFAULT 1,
  `followed_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(3, 1, 2, '2025-11-29 07:37:27');

-- --------------------------------------------------------

--
-- Table structure for table `idea_ratings`
--

CREATE TABLE `idea_ratings` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `idea_ratings`
--

INSERT INTO `idea_ratings` (`id`, `idea_id`, `user_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 4, NULL, '2025-11-30 14:07:47', '2025-11-30 14:07:47');

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
-- Table structure for table `idea_shares`
--

CREATE TABLE `idea_shares` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `platform` enum('twitter','facebook','linkedin','whatsapp','copy','other') NOT NULL DEFAULT 'other',
  `shared_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `idea_shares`
--

INSERT INTO `idea_shares` (`id`, `idea_id`, `user_id`, `platform`, `shared_at`) VALUES
(1, 1, 2, 'linkedin', '2025-11-29 07:32:12'),
(2, 1, 2, 'whatsapp', '2025-11-29 07:32:23'),
(3, 1, 2, 'copy', '2025-11-29 07:32:28'),
(4, 1, 2, 'copy', '2025-11-29 07:41:03');

-- --------------------------------------------------------

--
-- Stand-in structure for view `idea_statistics`
-- (See below for the actual view)
--
CREATE TABLE `idea_statistics` (
`id` int(11)
,`project_name` varchar(100)
,`user_id` int(11)
,`submission_datetime` datetime
,`total_likes` bigint(21)
,`total_comments` bigint(21)
,`total_views` bigint(21)
,`total_shares` bigint(21)
,`total_bookmarks` bigint(21)
,`average_rating` decimal(7,4)
,`total_ratings` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `idea_tags`
--

CREATE TABLE `idea_tags` (
  `id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL,
  `tag_slug` varchar(50) NOT NULL,
  `tag_color` varchar(7) DEFAULT '#6366f1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `idea_tags`
--

INSERT INTO `idea_tags` (`id`, `tag_name`, `tag_slug`, `tag_color`, `created_at`) VALUES
(1, 'Machine Learning', 'machine-learning', '#8b5cf6', '2025-10-16 13:50:03'),
(2, 'Web Development', 'web-development', '#3b82f6', '2025-10-16 13:50:03'),
(3, 'Mobile App', 'mobile-app', '#10b981', '2025-10-16 13:50:03'),
(4, 'IoT', 'iot', '#f59e0b', '2025-10-16 13:50:03'),
(5, 'Blockchain', 'blockchain', '#ef4444', '2025-10-16 13:50:03'),
(6, 'AI', 'ai', '#ec4899', '2025-10-16 13:50:03'),
(7, 'Data Science', 'data-science', '#06b6d4', '2025-10-16 13:50:03'),
(8, 'Cybersecurity', 'cybersecurity', '#f97316', '2025-10-16 13:50:03');

-- --------------------------------------------------------

--
-- Table structure for table `idea_tag_relations`
--

CREATE TABLE `idea_tag_relations` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idea_views`
--

CREATE TABLE `idea_views` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `idea_views`
--

INSERT INTO `idea_views` (`id`, `idea_id`, `user_id`, `ip_address`, `user_agent`, `viewed_at`) VALUES
(1, 5, 1, NULL, NULL, '2025-10-16 14:15:29'),
(2, 4, 1, NULL, NULL, '2025-10-16 14:15:29'),
(4, 2, 1, NULL, NULL, '2025-10-16 14:15:30'),
(5, 2, 1, NULL, NULL, '2025-10-16 14:15:30'),
(6, 5, 1, NULL, NULL, '2025-10-16 14:15:37'),
(7, 4, 1, NULL, NULL, '2025-10-16 14:15:37'),
(9, 2, 1, NULL, NULL, '2025-10-16 14:15:37'),
(10, 2, 1, NULL, NULL, '2025-10-16 14:15:37'),
(11, 5, 1, NULL, NULL, '2025-10-16 14:19:35'),
(13, 4, 1, NULL, NULL, '2025-10-16 14:19:35'),
(14, 2, 1, NULL, NULL, '2025-10-16 14:19:35'),
(15, 2, 1, NULL, NULL, '2025-10-16 14:19:35'),
(16, 5, 1, NULL, NULL, '2025-10-16 14:20:03'),
(18, 4, 1, NULL, NULL, '2025-10-16 14:20:03'),
(19, 2, 1, NULL, NULL, '2025-10-16 14:20:04'),
(20, 2, 1, NULL, NULL, '2025-10-16 14:20:04'),
(21, 5, 1, NULL, NULL, '2025-10-16 14:20:06'),
(22, 4, 1, NULL, NULL, '2025-10-16 14:20:06'),
(24, 2, 1, NULL, NULL, '2025-10-16 14:20:07'),
(25, 2, 1, NULL, NULL, '2025-10-16 14:20:07'),
(26, 5, 1, NULL, NULL, '2025-10-16 14:20:08'),
(27, 4, 1, NULL, NULL, '2025-10-16 14:20:08'),
(29, 2, 1, NULL, NULL, '2025-10-16 14:20:09'),
(30, 2, 1, NULL, NULL, '2025-10-16 14:20:09'),
(31, 5, 1, NULL, NULL, '2025-10-16 14:20:13'),
(32, 4, 1, NULL, NULL, '2025-10-16 14:20:13'),
(34, 2, 1, NULL, NULL, '2025-10-16 14:20:13'),
(35, 2, 1, NULL, NULL, '2025-10-16 14:20:13'),
(36, 5, 4, NULL, NULL, '2025-10-16 14:20:30'),
(37, 4, 4, NULL, NULL, '2025-10-16 14:20:30'),
(39, 2, 4, NULL, NULL, '2025-10-16 14:20:30'),
(40, 2, 4, NULL, NULL, '2025-10-16 14:20:30'),
(41, 4, 4, NULL, NULL, '2025-10-16 14:20:32'),
(42, 5, 4, NULL, NULL, '2025-10-16 14:20:32'),
(44, 2, 4, NULL, NULL, '2025-10-16 14:20:33'),
(45, 2, 4, NULL, NULL, '2025-10-16 14:20:33'),
(46, 5, 1, NULL, NULL, '2025-10-16 14:20:59'),
(47, 4, 1, NULL, NULL, '2025-10-16 14:20:59'),
(49, 2, 1, NULL, NULL, '2025-10-16 14:21:00'),
(50, 2, 1, NULL, NULL, '2025-10-16 14:21:00'),
(51, 5, 1, NULL, NULL, '2025-10-16 14:21:19'),
(52, 4, 1, NULL, NULL, '2025-10-16 14:21:19'),
(54, 2, 1, NULL, NULL, '2025-10-16 14:21:20'),
(55, 2, 1, NULL, NULL, '2025-10-16 14:21:20'),
(56, 5, 1, NULL, NULL, '2025-10-16 14:24:41'),
(57, 2, 1, NULL, NULL, '2025-10-16 14:24:41'),
(59, 2, 1, NULL, NULL, '2025-10-16 14:24:41'),
(60, 4, 1, NULL, NULL, '2025-10-16 14:24:41'),
(61, 5, 1, NULL, NULL, '2025-10-16 14:25:29'),
(62, 4, 1, NULL, NULL, '2025-10-16 14:25:29'),
(63, 2, 1, NULL, NULL, '2025-10-16 14:25:30'),
(64, 2, 1, NULL, NULL, '2025-10-16 14:25:30'),
(66, 5, 1, NULL, NULL, '2025-10-16 14:27:21'),
(67, 4, 1, NULL, NULL, '2025-10-16 14:27:22'),
(68, 2, 1, NULL, NULL, '2025-10-16 14:27:22'),
(70, 2, 1, NULL, NULL, '2025-10-16 14:27:22'),
(71, 5, 1, NULL, NULL, '2025-10-16 14:27:23'),
(72, 4, 1, NULL, NULL, '2025-10-16 14:27:23'),
(73, 2, 1, NULL, NULL, '2025-10-16 14:27:23'),
(75, 2, 1, NULL, NULL, '2025-10-16 14:27:23'),
(76, 5, 1, NULL, NULL, '2025-10-16 14:27:29'),
(77, 4, 1, NULL, NULL, '2025-10-16 14:27:29'),
(79, 2, 1, NULL, NULL, '2025-10-16 14:27:29'),
(80, 2, 1, NULL, NULL, '2025-10-16 14:27:29'),
(81, 5, 1, NULL, NULL, '2025-10-16 14:27:36'),
(83, 4, 1, NULL, NULL, '2025-10-16 14:27:36'),
(84, 5, 1, NULL, NULL, '2025-10-16 14:27:44'),
(85, 4, 1, NULL, NULL, '2025-10-16 14:27:44'),
(87, 2, 1, NULL, NULL, '2025-10-16 14:27:45'),
(88, 2, 1, NULL, NULL, '2025-10-16 14:27:45'),
(89, 5, 1, NULL, NULL, '2025-10-16 14:29:57'),
(90, 4, 1, NULL, NULL, '2025-10-16 14:29:57'),
(91, 2, 1, NULL, NULL, '2025-10-16 14:29:57'),
(93, 2, 1, NULL, NULL, '2025-10-16 14:29:57'),
(94, 5, 1, NULL, NULL, '2025-10-16 14:30:05'),
(95, 4, 1, NULL, NULL, '2025-10-16 14:30:05'),
(97, 2, 1, NULL, NULL, '2025-10-16 14:30:05'),
(98, 2, 1, NULL, NULL, '2025-10-16 14:30:05'),
(99, 5, 1, NULL, NULL, '2025-10-16 14:30:11'),
(100, 4, 1, NULL, NULL, '2025-10-16 14:30:11'),
(102, 2, 1, NULL, NULL, '2025-10-16 14:30:12'),
(103, 2, 1, NULL, NULL, '2025-10-16 14:30:12'),
(104, 5, 1, NULL, NULL, '2025-10-16 14:30:27'),
(105, 4, 1, NULL, NULL, '2025-10-16 14:30:27'),
(107, 2, 1, NULL, NULL, '2025-10-16 14:30:28'),
(108, 2, 1, NULL, NULL, '2025-10-16 14:30:28'),
(109, 5, 1, NULL, NULL, '2025-10-16 14:30:53'),
(111, 4, 1, NULL, NULL, '2025-10-16 14:30:53'),
(112, 2, 1, NULL, NULL, '2025-10-16 14:30:53'),
(113, 2, 1, NULL, NULL, '2025-10-16 14:30:53'),
(114, 5, 1, NULL, NULL, '2025-10-16 14:30:54'),
(116, 4, 1, NULL, NULL, '2025-10-16 14:30:54'),
(117, 2, 1, NULL, NULL, '2025-10-16 14:30:54'),
(118, 2, 1, NULL, NULL, '2025-10-16 14:30:55'),
(119, 5, 1, NULL, NULL, '2025-10-16 14:30:56'),
(120, 4, 1, NULL, NULL, '2025-10-16 14:30:56'),
(122, 2, 1, NULL, NULL, '2025-10-16 14:30:56'),
(123, 2, 1, NULL, NULL, '2025-10-16 14:30:57'),
(124, 5, 1, NULL, NULL, '2025-10-16 14:31:07'),
(125, 4, 1, NULL, NULL, '2025-10-16 14:31:07'),
(126, 2, 1, NULL, NULL, '2025-10-16 14:31:07'),
(128, 2, 1, NULL, NULL, '2025-10-16 14:31:07'),
(129, 5, 1, NULL, NULL, '2025-10-16 14:32:36'),
(130, 4, 1, NULL, NULL, '2025-10-16 14:32:36'),
(132, 2, 1, NULL, NULL, '2025-10-16 14:32:36'),
(133, 2, 1, NULL, NULL, '2025-10-16 14:32:37'),
(134, 5, 4, NULL, NULL, '2025-10-16 14:33:03'),
(135, 4, 4, NULL, NULL, '2025-10-16 14:33:03'),
(137, 2, 4, NULL, NULL, '2025-10-16 14:33:03'),
(138, 2, 4, NULL, NULL, '2025-10-16 14:33:04'),
(139, 5, 4, NULL, NULL, '2025-10-16 14:33:12'),
(140, 4, 4, NULL, NULL, '2025-10-16 14:33:12'),
(142, 2, 4, NULL, NULL, '2025-10-16 14:33:12'),
(143, 2, 4, NULL, NULL, '2025-10-16 14:33:12'),
(144, 5, 4, NULL, NULL, '2025-10-16 14:34:51'),
(146, 4, 4, NULL, NULL, '2025-10-16 14:34:51'),
(147, 2, 4, NULL, NULL, '2025-10-16 14:34:51'),
(148, 2, 4, NULL, NULL, '2025-10-16 14:34:51'),
(149, 5, 4, NULL, NULL, '2025-10-16 14:34:57'),
(150, 4, 4, NULL, NULL, '2025-10-16 14:34:57'),
(152, 2, 4, NULL, NULL, '2025-10-16 14:34:57'),
(153, 2, 4, NULL, NULL, '2025-10-16 14:34:58'),
(154, 4, 4, NULL, NULL, '2025-10-16 14:35:04'),
(155, 4, 4, NULL, NULL, '2025-10-16 14:35:05'),
(156, 5, 4, NULL, NULL, '2025-10-16 14:35:05'),
(158, 2, 4, NULL, NULL, '2025-10-16 14:35:05'),
(159, 2, 4, NULL, NULL, '2025-10-16 14:35:07'),
(160, 5, 4, NULL, NULL, '2025-10-16 14:36:28'),
(162, 4, 4, NULL, NULL, '2025-10-16 14:36:28'),
(163, 2, 4, NULL, NULL, '2025-10-16 14:36:28'),
(164, 2, 4, NULL, NULL, '2025-10-16 14:36:28'),
(165, 5, 4, NULL, NULL, '2025-10-16 14:37:12'),
(166, 4, 4, NULL, NULL, '2025-10-16 14:37:12'),
(168, 2, 4, NULL, NULL, '2025-10-16 14:37:12'),
(169, 2, 4, NULL, NULL, '2025-10-16 14:37:12'),
(170, 5, 4, NULL, NULL, '2025-10-16 14:38:12'),
(171, 4, 4, NULL, NULL, '2025-10-16 14:38:12'),
(173, 2, 4, NULL, NULL, '2025-10-16 14:38:12'),
(174, 2, 4, NULL, NULL, '2025-10-16 14:38:12'),
(175, 5, 4, NULL, NULL, '2025-10-16 14:38:14'),
(176, 4, 4, NULL, NULL, '2025-10-16 14:38:14'),
(178, 5, 4, NULL, NULL, '2025-10-16 14:38:15'),
(180, 4, 4, NULL, NULL, '2025-10-16 14:38:15'),
(181, 4, 4, NULL, NULL, '2025-10-16 14:38:16'),
(182, 5, 4, NULL, NULL, '2025-10-16 14:38:18'),
(183, 4, 4, NULL, NULL, '2025-10-16 14:38:18'),
(185, 2, 4, NULL, NULL, '2025-10-16 14:38:30'),
(186, 2, 4, NULL, NULL, '2025-10-16 14:38:30'),
(187, 5, 4, NULL, NULL, '2025-10-16 14:39:15'),
(188, 4, 4, NULL, NULL, '2025-10-16 14:39:15'),
(190, 2, 4, NULL, NULL, '2025-10-16 14:39:15'),
(191, 2, 4, NULL, NULL, '2025-10-16 14:39:15'),
(192, 4, 4, NULL, NULL, '2025-10-16 14:39:38'),
(193, 4, 4, NULL, NULL, '2025-10-16 14:39:39'),
(194, 4, 4, NULL, NULL, '2025-10-16 14:39:39'),
(195, 4, 4, NULL, NULL, '2025-10-16 14:39:40'),
(196, 5, 4, NULL, NULL, '2025-10-16 14:39:42'),
(197, 4, 4, NULL, NULL, '2025-10-16 14:39:42'),
(199, 5, 4, NULL, NULL, '2025-10-16 14:40:32'),
(201, 4, 4, NULL, NULL, '2025-10-16 14:40:32'),
(202, 2, 4, NULL, NULL, '2025-10-16 14:40:34'),
(203, 2, 4, NULL, NULL, '2025-10-16 14:40:34'),
(204, 5, 4, NULL, NULL, '2025-10-16 14:41:44'),
(205, 4, 4, NULL, NULL, '2025-10-16 14:41:44'),
(207, 2, 4, NULL, NULL, '2025-10-16 14:41:44'),
(208, 2, 4, NULL, NULL, '2025-10-16 14:41:44'),
(209, 5, 4, NULL, NULL, '2025-10-16 14:42:30'),
(210, 4, 4, NULL, NULL, '2025-10-16 14:42:30'),
(212, 2, 4, NULL, NULL, '2025-10-16 14:42:30'),
(213, 2, 4, NULL, NULL, '2025-10-16 14:42:30'),
(214, 4, 4, NULL, NULL, '2025-10-16 14:43:46'),
(215, 5, 4, NULL, NULL, '2025-10-16 14:43:46'),
(217, 2, 4, NULL, NULL, '2025-10-16 14:43:46'),
(218, 2, 4, NULL, NULL, '2025-10-16 14:43:56'),
(219, 5, 1, NULL, NULL, '2025-10-16 14:44:29'),
(220, 4, 1, NULL, NULL, '2025-10-16 14:44:29'),
(221, 5, 1, NULL, NULL, '2025-10-16 14:44:32'),
(222, 4, 1, NULL, NULL, '2025-10-16 14:44:32'),
(223, 4, 1, NULL, NULL, '2025-10-16 14:44:33'),
(224, 5, 1, NULL, NULL, '2025-10-16 14:44:33'),
(226, 2, 1, NULL, NULL, '2025-10-16 14:44:34'),
(227, 2, 1, NULL, NULL, '2025-10-16 14:44:34'),
(228, 5, 1, NULL, NULL, '2025-10-16 14:44:41'),
(229, 4, 1, NULL, NULL, '2025-10-16 14:44:41'),
(231, 2, 1, NULL, NULL, '2025-10-16 14:44:42'),
(232, 2, 1, NULL, NULL, '2025-10-16 14:44:42'),
(233, 9, 3, NULL, NULL, '2025-11-30 16:26:31'),
(234, 1, 3, NULL, NULL, '2025-11-30 16:26:38');

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
  `reminder_sent` tinyint(1) DEFAULT 0,
  `immediate_reminder_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentoring_sessions`
--

INSERT INTO `mentoring_sessions` (`id`, `pair_id`, `session_date`, `duration_minutes`, `notes`, `meeting_link`, `status`, `created_at`, `reminder_sent`, `immediate_reminder_sent`) VALUES
(1, 2, '2025-11-28 20:17:00', 60, 'gfbrhjkklsf jdKM B', '', 'scheduled', '2025-11-28 14:48:03', 0, 0),
(2, 2, '2025-11-28 20:17:00', 60, 'gfbrhjkklsf jdKM B', '', 'scheduled', '2025-11-28 14:50:42', 0, 0),
(3, 2, '2025-11-28 20:21:00', 90, 'hs kvjerwklVF  M', '', 'scheduled', '2025-11-28 14:51:11', 0, 0),
(4, 2, '2025-11-28 20:21:00', 90, 'hs kvjerwklVF  M', '', 'scheduled', '2025-11-28 14:54:09', 0, 0),
(5, 2, '2025-11-28 20:21:00', 90, 'hs kvjerwklVF  M', '', 'scheduled', '2025-11-28 14:56:37', 0, 0),
(6, 2, '2025-11-30 21:27:00', 90, 'hj erkfwqkldvf n', '', 'scheduled', '2025-11-28 15:57:26', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `mentoring_sessions_archive`
--

CREATE TABLE `mentoring_sessions_archive` (
  `id` int(11) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `session_date` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `meeting_link` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','missed') DEFAULT 'scheduled',
  `reminder_sent` tinyint(1) DEFAULT 0,
  `immediate_reminder_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp()
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

--
-- Dumping data for table `mentors`
--

INSERT INTO `mentors` (`id`, `user_id`, `specialization`, `experience_years`, `max_students`, `current_students`, `bio`, `linkedin_url`, `github_url`, `created_at`) VALUES
(3, 4, 'Web Development', 1, 5, 0, 'Web Development', NULL, NULL, '2025-11-30 16:51:13');

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

--
-- Dumping data for table `mentor_activity_logs`
--

INSERT INTO `mentor_activity_logs` (`id`, `mentor_id`, `activity_type`, `description`, `student_id`, `created_at`) VALUES
(1, 1, 'session_scheduled', 'Scheduled session with vivek chavda for Nov 28, 2025 8:17 PM', 2, '2025-11-28 14:48:03'),
(2, 1, 'session_scheduled', 'Scheduled session with vivek chavda for Nov 28, 2025 8:17 PM', 2, '2025-11-28 14:50:42'),
(3, 1, 'session_scheduled', 'Scheduled session with vivek chavda for Nov 28, 2025 8:21 PM', 2, '2025-11-28 14:51:11'),
(4, 1, 'session_scheduled', 'Scheduled session with vivek chavda for Nov 28, 2025 8:21 PM', 2, '2025-11-28 14:54:09'),
(5, 1, 'session_scheduled', 'Scheduled session with vivek chavda for Nov 28, 2025 8:21 PM', 2, '2025-11-28 14:56:37'),
(6, 1, 'session_scheduled', 'Scheduled session with vivek chavda for Nov 30, 2025 9:27 PM', 2, '2025-11-28 15:57:26');

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

--
-- Dumping data for table `mentor_project_access`
--

INSERT INTO `mentor_project_access` (`id`, `mentor_id`, `student_id`, `project_id`, `granted_at`) VALUES
(1, 1, 2, 1, '2025-11-30 16:44:21'),
(2, 1, 2, 2, '2025-11-30 16:44:21'),
(3, 1, 2, 3, '2025-11-30 16:44:21'),
(4, 1, 2, 4, '2025-11-30 16:44:21'),
(5, 1, 2, 5, '2025-11-30 16:44:21'),
(6, 1, 2, 6, '2025-11-30 16:44:21'),
(7, 1, 2, 7, '2025-11-30 16:44:21'),
(8, 1, 2, 8, '2025-11-30 16:44:21'),
(9, 1, 2, 9, '2025-11-30 16:44:21'),
(10, 1, 2, 10, '2025-11-30 16:44:21'),
(11, 1, 2, 11, '2025-11-30 16:44:21'),
(12, 1, 2, 12, '2025-11-30 16:44:21'),
(16, 4, 2, 6, '2025-11-30 16:55:55');

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
(1, 2, 1, NULL, 'Test mentor request - This is a sample request to verify the system is working', 'pending', '2025-11-28 16:07:45', '2025-11-28 16:07:45'),
(2, 2, 1, NULL, 'Test request', 'accepted', '2025-11-28 16:11:39', '2025-11-30 16:44:21'),
(3, 2, 4, 6, 'thakjkn dkn dsn', 'accepted', '2025-11-30 16:55:22', '2025-11-30 16:55:55');

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
(1, 1, 2, NULL, 'completed', '2025-11-28 10:02:00', '2025-11-28 10:02:13', 5, 'good', 0, NULL),
(2, 1, 2, NULL, 'active', '2025-11-28 10:02:25', NULL, NULL, NULL, 0, NULL),
(3, 1, 3, NULL, 'active', '2025-11-30 16:44:43', NULL, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `progress_milestones`
--

CREATE TABLE `progress_milestones` (
  `id` int(11) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_date` date NOT NULL,
  `completed_date` datetime DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `completion_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `progress_notes`
--

CREATE TABLE `progress_notes` (
  `id` int(11) NOT NULL,
  `pair_id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `category` enum('general','achievement','concern','feedback') DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `user_id`, `project_name`, `project_type`, `classification`, `project_category`, `difficulty_level`, `development_time`, `team_size`, `target_audience`, `project_goals`, `challenges_faced`, `future_enhancements`, `github_repo`, `live_demo_url`, `project_license`, `keywords`, `contact_email`, `social_links`, `description`, `language`, `image_path`, `video_path`, `code_file_path`, `instruction_file_path`, `presentation_file_path`, `additional_files_path`, `submission_date`, `status`, `title`) VALUES
(1, 2, 'Smart Learning Management System', 'software', 'web', 'education', 'advanced', '3-6 months', '3', 'Educational institutions, teachers, students, and academic administrators', 'Create an intuitive and scalable learning platform that enhances the educational experience through technology. Improve student engagement, streamline administrative tasks, and provide comprehensive analytics for better decision-making.', 'Implementing real-time collaboration features, ensuring scalability for large user bases, creating an intuitive user interface that works for all age groups, and integrating with existing educational systems.', 'AI-powered personalized learning recommendations, mobile application development, integration with popular video conferencing tools, advanced plagiarism detection, and blockchain-based certificate verification.', 'https://github.com/viveksinhchavda/smart-lms', 'https://smart-lms-demo.herokuapp.com', 'MIT', 'education, learning management, web application, PHP, MySQL, student tracking', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda, https://twitter.com/vivekchavda', 'A comprehensive web-based learning management system designed for educational institutions. Features include course management, student progress tracking, interactive assignments, real-time collaboration tools, and advanced analytics for educators. The system supports multiple learning formats including video lectures, interactive quizzes, and peer-to-peer discussions.', 'PHP, JavaScript, MySQL, HTML5, CSS3, Bootstrap', NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-31 04:52:14', 'approved', NULL),
(2, 2, 'IoT-Based Smart Classroom System', 'hardware', 'iot', 'education', 'expert', '6+ months', '5', 'Educational institutions, facility managers, teachers, and students', 'Enhance the learning environment through automated systems, reduce energy consumption, improve attendance tracking accuracy, and provide data-driven insights for classroom management.', 'Integrating multiple sensor types, ensuring reliable wireless communication, developing a user-friendly dashboard, managing power consumption, and ensuring system security and privacy.', 'Integration with facial recognition for attendance, AI-powered predictive maintenance, mobile app for remote monitoring, integration with existing school management systems, and expansion to multiple classrooms.', 'https://github.com/viveksinhchavda/iot-smart-classroom', 'https://smart-classroom-dashboard.netlify.app', 'Apache-2.0', 'IoT, smart classroom, Arduino, sensors, automation, education technology', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda', 'An innovative IoT-based smart classroom system that automates environmental controls, monitors student attendance using RFID technology, and provides real-time data analytics for classroom optimization. The system includes sensors for temperature, humidity, air quality, and noise levels, with automated responses to maintain optimal learning conditions.', 'Arduino C++, Python, Node.js, React, MongoDB', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-21 04:52:14', 'approved', NULL),
(3, 2, 'AI-Powered Student Performance Predictor', 'software', 'ai_ml', 'education', 'expert', '2-3 months', '2', 'Educational institutions, academic counselors, teachers, and students', 'Identify at-risk students early, provide personalized learning recommendations, improve overall academic performance, and assist educators in making data-driven decisions.', 'Handling sensitive student data with privacy concerns, ensuring model accuracy across diverse student populations, dealing with incomplete or biased data, and creating interpretable AI recommendations.', 'Real-time performance monitoring, integration with learning management systems, mobile application for students and parents, advanced visualization dashboards, and multi-language support.', 'https://github.com/viveksinhchavda/ai-student-predictor', 'https://student-predictor-ai.herokuapp.com', 'MIT', 'artificial intelligence, machine learning, education, student performance, predictive analytics', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda, https://github.com/viveksinhchavda', 'A machine learning application that analyzes student performance data to predict academic outcomes and identify students at risk of falling behind. The system uses various algorithms including regression analysis, decision trees, and neural networks to provide accurate predictions and personalized recommendations for improvement.', 'Python, TensorFlow, Scikit-learn, Pandas, Flask, PostgreSQL', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-07 04:52:14', 'rejected', NULL),
(4, 2, 'Virtual Reality Chemistry Lab', 'software', 'game', 'education', 'advanced', '3-6 months', '4', 'High school and college students, chemistry teachers, and educational institutions', 'Provide safe and accessible chemistry education, reduce laboratory costs, enhance student engagement through immersive experiences, and enable remote learning capabilities.', 'Creating realistic physics simulations for chemical reactions, optimizing VR performance for smooth user experience, designing intuitive VR interactions, and ensuring educational accuracy of all experiments.', 'Support for more VR headsets, multiplayer collaborative experiments, AI-powered virtual teaching assistant, integration with curriculum standards, and assessment tools for teachers.', 'https://github.com/viveksinhchavda/vr-chemistry-lab', 'https://vr-chem-lab-demo.com', 'GPL-3.0', 'virtual reality, chemistry, education, Unity3D, VR learning, simulation', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda', 'An immersive virtual reality application that allows students to conduct chemistry experiments in a safe, virtual environment. The VR lab includes realistic 3D models of laboratory equipment, accurate chemical reactions, and interactive tutorials. Students can practice dangerous or expensive experiments without real-world risks.', 'Unity3D, C#, Blender, Oculus SDK, SteamVR', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-22 04:52:14', 'approved', NULL),
(5, 2, 'Blockchain-Based Digital Certificate System', 'software', 'web', 'education', 'expert', '6+ months', '3', 'Educational institutions, employers, certification bodies, and students', 'Eliminate certificate fraud, provide instant verification, reduce administrative overhead, create a global standard for digital credentials, and enhance trust in educational qualifications.', 'Understanding blockchain technology complexities, managing gas fees for transactions, ensuring user-friendly interface for non-technical users, and integrating with existing educational systems.', 'Integration with major job portals, mobile application development, support for multiple blockchain networks, AI-powered skill verification, and international standards compliance.', 'https://github.com/viveksinhchavda/blockchain-certificates', 'https://blockchain-certs.netlify.app', 'MIT', 'blockchain, digital certificates, smart contracts, education, verification, Ethereum', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda, https://twitter.com/vivekchavda', 'A secure blockchain-based system for issuing, verifying, and managing digital certificates and credentials. The platform ensures tamper-proof certificates, instant verification, and eliminates the need for manual verification processes. Built with smart contracts for automated certificate issuance and verification.', 'Solidity, Web3.js, React, Node.js, Ethereum, IPFS', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-15 04:52:14', 'approved', NULL),
(6, 2, 'Automated Attendance System with Face Recognition', 'software', 'ai_ml', 'education', 'intermediate', '2-3 months', '2', 'Schools, colleges, corporate training centers, and educational administrators', 'Automate attendance tracking, reduce manual errors, save time for educators, provide accurate attendance analytics, and improve overall administrative efficiency.', 'Ensuring accuracy in different lighting conditions, handling privacy concerns, preventing spoofing attacks, optimizing for real-time processing, and managing large datasets of student faces.', 'Mobile app integration, cloud-based deployment, integration with learning management systems, advanced analytics dashboard, and support for mask detection.', 'https://github.com/viveksinhchavda/face-recognition-attendance', 'https://face-attendance-demo.herokuapp.com', 'BSD-3-Clause', 'face recognition, attendance system, computer vision, automation, education management', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda', 'An intelligent attendance management system using computer vision and facial recognition technology. The system automatically marks attendance when students enter the classroom, generates detailed reports, and integrates with existing student management systems. Features include real-time monitoring, anti-spoofing measures, and privacy protection.', 'Python, OpenCV, TensorFlow, Flask, SQLite, HTML/CSS/JavaScript', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-09 04:52:14', 'approved', NULL),
(7, 2, 'Smart Learning Management System', 'software', 'web', 'education', 'advanced', '3-6 months', '3', 'Educational institutions, teachers, students, and academic administrators', 'Create an intuitive and scalable learning platform that enhances the educational experience through technology. Improve student engagement, streamline administrative tasks, and provide comprehensive analytics for better decision-making.', 'Implementing real-time collaboration features, ensuring scalability for large user bases, creating an intuitive user interface that works for all age groups, and integrating with existing educational systems.', 'AI-powered personalized learning recommendations, mobile application development, integration with popular video conferencing tools, advanced plagiarism detection, and blockchain-based certificate verification.', 'https://github.com/viveksinhchavda/smart-lms', 'https://smart-lms-demo.herokuapp.com', 'MIT', 'education, learning management, web application, PHP, MySQL, student tracking', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda, https://twitter.com/vivekchavda', 'A comprehensive web-based learning management system designed for educational institutions. Features include course management, student progress tracking, interactive assignments, real-time collaboration tools, and advanced analytics for educators. The system supports multiple learning formats including video lectures, interactive quizzes, and peer-to-peer discussions.', 'PHP, JavaScript, MySQL, HTML5, CSS3, Bootstrap', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-23 09:42:52', 'approved', NULL),
(8, 2, 'IoT-Based Smart Classroom System', 'hardware', 'iot', 'education', 'expert', '6+ months', '5', 'Educational institutions, facility managers, teachers, and students', 'Enhance the learning environment through automated systems, reduce energy consumption, improve attendance tracking accuracy, and provide data-driven insights for classroom management.', 'Integrating multiple sensor types, ensuring reliable wireless communication, developing a user-friendly dashboard, managing power consumption, and ensuring system security and privacy.', 'Integration with facial recognition for attendance, AI-powered predictive maintenance, mobile app for remote monitoring, integration with existing school management systems, and expansion to multiple classrooms.', 'https://github.com/viveksinhchavda/iot-smart-classroom', 'https://smart-classroom-dashboard.netlify.app', 'Apache-2.0', 'IoT, smart classroom, Arduino, sensors, automation, education technology', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda', 'An innovative IoT-based smart classroom system that automates environmental controls, monitors student attendance using RFID technology, and provides real-time data analytics for classroom optimization. The system includes sensors for temperature, humidity, air quality, and noise levels, with automated responses to maintain optimal learning conditions.', 'Arduino C++, Python, Node.js, React, MongoDB', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-16 09:42:52', 'approved', NULL),
(9, 2, 'AI-Powered Student Performance Predictor', 'software', 'ai_ml', 'education', 'expert', '2-3 months', '2', 'Educational institutions, academic counselors, teachers, and students', 'Identify at-risk students early, provide personalized learning recommendations, improve overall academic performance, and assist educators in making data-driven decisions.', 'Handling sensitive student data with privacy concerns, ensuring model accuracy across diverse student populations, dealing with incomplete or biased data, and creating interpretable AI recommendations.', 'Real-time performance monitoring, integration with learning management systems, mobile application for students and parents, advanced visualization dashboards, and multi-language support.', 'https://github.com/viveksinhchavda/ai-student-predictor', 'https://student-predictor-ai.herokuapp.com', 'MIT', 'artificial intelligence, machine learning, education, student performance, predictive analytics', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda, https://github.com/viveksinhchavda', 'A machine learning application that analyzes student performance data to predict academic outcomes and identify students at risk of falling behind. The system uses various algorithms including regression analysis, decision trees, and neural networks to provide accurate predictions and personalized recommendations for improvement.', 'Python, TensorFlow, Scikit-learn, Pandas, Flask, PostgreSQL', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-25 09:42:52', 'rejected', NULL),
(10, 2, 'Virtual Reality Chemistry Lab', 'software', 'game', 'education', 'advanced', '3-6 months', '4', 'High school and college students, chemistry teachers, and educational institutions', 'Provide safe and accessible chemistry education, reduce laboratory costs, enhance student engagement through immersive experiences, and enable remote learning capabilities.', 'Creating realistic physics simulations for chemical reactions, optimizing VR performance for smooth user experience, designing intuitive VR interactions, and ensuring educational accuracy of all experiments.', 'Support for more VR headsets, multiplayer collaborative experiments, AI-powered virtual teaching assistant, integration with curriculum standards, and assessment tools for teachers.', 'https://github.com/viveksinhchavda/vr-chemistry-lab', 'https://vr-chem-lab-demo.com', 'GPL-3.0', 'virtual reality, chemistry, education, Unity3D, VR learning, simulation', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda', 'An immersive virtual reality application that allows students to conduct chemistry experiments in a safe, virtual environment. The VR lab includes realistic 3D models of laboratory equipment, accurate chemical reactions, and interactive tutorials. Students can practice dangerous or expensive experiments without real-world risks.', 'Unity3D, C#, Blender, Oculus SDK, SteamVR', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-10 09:42:52', 'approved', NULL),
(11, 2, 'Blockchain-Based Digital Certificate System', 'software', 'web', 'education', 'expert', '6+ months', '3', 'Educational institutions, employers, certification bodies, and students', 'Eliminate certificate fraud, provide instant verification, reduce administrative overhead, create a global standard for digital credentials, and enhance trust in educational qualifications.', 'Understanding blockchain technology complexities, managing gas fees for transactions, ensuring user-friendly interface for non-technical users, and integrating with existing educational systems.', 'Integration with major job portals, mobile application development, support for multiple blockchain networks, AI-powered skill verification, and international standards compliance.', 'https://github.com/viveksinhchavda/blockchain-certificates', 'https://blockchain-certs.netlify.app', 'MIT', 'blockchain, digital certificates, smart contracts, education, verification, Ethereum', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda, https://twitter.com/vivekchavda', 'A secure blockchain-based system for issuing, verifying, and managing digital certificates and credentials. The platform ensures tamper-proof certificates, instant verification, and eliminates the need for manual verification processes. Built with smart contracts for automated certificate issuance and verification.', 'Solidity, Web3.js, React, Node.js, Ethereum, IPFS', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 09:42:52', 'approved', NULL),
(12, 2, 'Automated Attendance System with Face Recognition', 'software', 'ai_ml', 'education', 'intermediate', '2-3 months', '2', 'Schools, colleges, corporate training centers, and educational administrators', 'Automate attendance tracking, reduce manual errors, save time for educators, provide accurate attendance analytics, and improve overall administrative efficiency.', 'Ensuring accuracy in different lighting conditions, handling privacy concerns, preventing spoofing attacks, optimizing for real-time processing, and managing large datasets of student faces.', 'Mobile app integration, cloud-based deployment, integration with learning management systems, advanced analytics dashboard, and support for mask detection.', 'https://github.com/viveksinhchavda/face-recognition-attendance', 'https://face-attendance-demo.herokuapp.com', 'BSD-3-Clause', 'face recognition, attendance system, computer vision, automation, education management', 'viveksinhchavda@gmail.com', 'https://linkedin.com/in/viveksinhchavda', 'An intelligent attendance management system using computer vision and facial recognition technology. The system automatically marks attendance when students enter the classroom, generates detailed reports, and integrates with existing student management systems. Features include real-time monitoring, anti-spoofing measures, and privacy protection.', 'Python, OpenCV, TensorFlow, Flask, SQLite, HTML/CSS/JavaScript', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-21 09:42:52', 'approved', NULL);

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
(2, 2, '3', '2025-11-30 15:23:52');

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
(1, 'vivek chavda', 'vivek.chavda119486@marwadiuniversity.ac.in', 'MEN618', 'MEN618', '$2y$10$JS6Xyh6YDx0onCrmU9u7/egT1zNHSPMpiwwKRoQgBiiRhIAHsm72G', 'Data Science', NULL, 'Mentor', '2024', '', NULL, 1, NULL, NULL, 'student', 'Data Science', 0.00, 1, NULL, NULL, 0, NULL),
(2, 'vivek chavda', 'viveksinhchavda@gmail.com', '92200133026', '119486', '$2y$10$gyy21yBLGu0T2fHLitVwmOb5sK1HxmaGNw6KZTZ4RASzz89YQK60a', 'New student at IdeaNest', NULL, 'ict', '2026', '', NULL, 1, NULL, NULL, 'student', NULL, 0.00, 1, NULL, NULL, 0, NULL),
(3, 'Bhavik kaldiya', 'vivek@gmail.com', '92200133025', '119485', '$2y$10$9i9v37qQ7QswVsheS3EyDuqWJCaxtCge0MLwtti2m6Dx3K053YP1S', 'New student at IdeaNest', NULL, 'ict', '2026', '', NULL, 1, NULL, NULL, 'student', NULL, 0.00, 1, NULL, NULL, 0, NULL),
(4, 'vivek chavda', 'vivekcchavda@gmail.com', 'MEN108', 'MEN108', '$2y$10$mJVRU09ZmDw6XPLXl8aiQOF/xlMckK785wgtaQiYR4zLpjnWBKfty', 'Web Development', NULL, 'Mentor', '2024', '', NULL, 1, NULL, NULL, 'mentor', 'Web Development', 0.00, 1, NULL, NULL, 0, NULL);

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
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `specialization` text DEFAULT NULL,
  `domains` text DEFAULT NULL,
  `experience_years` int(3) DEFAULT 0,
  `bio` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subadmins`
--

INSERT INTO `subadmins` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `department`, `position`, `specialization`, `domains`, `experience_years`, `bio`, `avatar`, `status`, `email_verified`, `last_login`, `created_at`, `updated_at`) VALUES
(4, 'chavdaviveksinh1374@gmail.com', '$2y$10$S4ao6auDmlziKiwnNDxj6.9OZWHyapZEANmlj5QmN8EjmNluyn3tu', 'vivek', 'chavda', '9104231590', 'ICT', 'Faculty', 'Web', 'Web Application', 0, 'I Am vivek chavda', NULL, 'active', 0, NULL, '2025-11-28 07:11:44', '2025-11-30 16:43:46'),
(5, 'viveksinhchavda639@gmail.com', '$2y$10$MNvGNGjm5a2OhXV7R88hHOD41y8T1fkDdQqP0wjx9Qo8VXy19jtOG', 'vivek', 'vivek', '1234567890', 'ict', 'Faculty', 'Web', 'AI & Machine Learning', 1, 'thank', NULL, 'active', 0, NULL, '2025-11-30 16:57:53', '2025-11-30 16:59:15');

-- --------------------------------------------------------

--
-- Table structure for table `subadmin_activities`
--

CREATE TABLE `subadmin_activities` (
  `id` int(11) NOT NULL,
  `subadmin_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subadmin_classification_requests`
--

CREATE TABLE `subadmin_classification_requests` (
  `id` int(11) NOT NULL,
  `subadmin_id` int(11) NOT NULL,
  `requested_domains` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subadmin_permissions`
--

CREATE TABLE `subadmin_permissions` (
  `id` int(11) NOT NULL,
  `subadmin_id` int(11) NOT NULL,
  `permission_type` enum('project_review','user_management','content_moderation','analytics','settings') NOT NULL,
  `permission_level` enum('read','write','admin') DEFAULT 'read',
  `granted_by` int(11) DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subadmin_sessions`
--

CREATE TABLE `subadmin_sessions` (
  `id` int(11) NOT NULL,
  `subadmin_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `subadmin_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_ticket_replies`
--

CREATE TABLE `support_ticket_replies` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_type` enum('admin','subadmin') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_credentials`
--

CREATE TABLE `temp_credentials` (
  `id` int(11) NOT NULL,
  `user_type` enum('mentor','subadmin') NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `plain_password` varchar(255) NOT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `email_attempts` int(11) DEFAULT 0,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 7 day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temp_credentials`
--

INSERT INTO `temp_credentials` (`id`, `user_type`, `user_id`, `email`, `plain_password`, `email_sent`, `email_sent_at`, `email_attempts`, `last_attempt_at`, `error_message`, `created_at`, `expires_at`) VALUES
(1, 'subadmin', 4, 'chavdaviveksinh1374@gmail.com', '9be50ef6', 1, '2025-11-28 07:11:51', 2, '2025-11-28 07:11:51', NULL, '2025-11-28 07:11:44', '2025-12-05 07:11:44'),
(2, 'mentor', 1, 'vivek.chavda119486@marwadiuniversity.ac.in', 'f6d76951', 1, '2025-11-28 07:23:04', 2, '2025-11-28 07:23:04', NULL, '2025-11-28 07:22:57', '2025-12-05 07:22:57'),
(3, 'mentor', 4, 'vivekcchavda@gmail.com', '8b2aae00', 1, '2025-11-30 16:51:19', 2, '2025-11-30 16:51:19', NULL, '2025-11-30 16:51:13', '2025-12-07 16:51:13'),
(4, 'subadmin', 5, 'viveksinhchavda639@gmail.com', '22365aeb', 1, '2025-11-30 16:58:00', 2, '2025-11-30 16:58:00', NULL, '2025-11-30 16:57:53', '2025-12-07 16:57:53');

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
-- Stand-in structure for view `trending_ideas`
-- (See below for the actual view)
--
CREATE TABLE `trending_ideas` (
`id` int(11)
,`project_name` varchar(100)
,`classification` varchar(50)
,`project_type` enum('software','hardware')
,`recent_likes` bigint(21)
,`recent_comments` bigint(21)
,`recent_views` bigint(21)
,`engagement_score` bigint(24)
);

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
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` enum('project_approved','project_rejected','project_submitted','mentor_assigned','mentor_request','session_scheduled','general') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL COMMENT 'Project ID, Mentor ID, etc.',
  `related_type` varchar(50) DEFAULT NULL COMMENT 'project, mentor, session, etc.',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `action_url` varchar(500) DEFAULT NULL COMMENT 'URL to view details',
  `icon` varchar(50) DEFAULT 'bi-bell' COMMENT 'Bootstrap icon class',
  `color` varchar(20) DEFAULT 'primary' COMMENT 'Badge color'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`id`, `user_id`, `notification_type`, `title`, `message`, `related_id`, `related_type`, `is_read`, `read_at`, `created_at`, `action_url`, `icon`, `color`) VALUES
(1, 2, 'project_approved', '🎉 Project Approved!', 'Congratulations! Your project \'Automated Attendance System with Face Recognition\' has been approved and is now live on IdeaNest.', 12, 'project', 0, NULL, '2025-11-28 13:43:40', '/user/my_projects.php', 'bi-check-circle-fill', 'success'),
(2, 2, 'session_scheduled', 'New Session Scheduled', 'Your mentor has scheduled a session for Nov 28, 2025 8:17 PM', 2, 'session', 0, NULL, '2025-11-28 14:50:42', '/user/sessions.php', 'bi-calendar-check', 'success'),
(3, 2, 'session_scheduled', 'New Session Scheduled', 'Your mentor has scheduled a session for Nov 28, 2025 8:21 PM', 3, 'session', 0, NULL, '2025-11-28 14:51:11', '/user/sessions.php', 'bi-calendar-check', 'success'),
(4, 2, 'session_scheduled', 'New Session Scheduled', 'Your mentor has scheduled a session for Nov 28, 2025 8:21 PM', 4, 'session', 0, NULL, '2025-11-28 14:54:09', '/user/sessions.php', 'bi-calendar-check', 'success'),
(5, 2, 'session_scheduled', 'New Session Scheduled', 'Your mentor has scheduled a session for Nov 28, 2025 8:21 PM', 5, 'session', 0, NULL, '2025-11-28 14:56:37', '/user/sessions.php', 'bi-calendar-check', 'success'),
(6, 2, 'session_scheduled', 'New Session Scheduled', 'Your mentor has scheduled a session for Nov 30, 2025 9:27 PM', 6, 'session', 0, NULL, '2025-11-28 15:57:26', '/user/sessions.php', 'bi-calendar-check', 'success'),
(7, 4, 'mentor_request', 'New Mentorship Request', 'You have received a new mentorship request from a student.', 3, 'mentor_request', 0, NULL, '2025-11-30 16:55:22', '/mentor/student_requests.php', 'bi-person-plus', 'info');

-- --------------------------------------------------------

--
-- Structure for view `idea_statistics`
--
DROP TABLE IF EXISTS `idea_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `idea_statistics`  AS SELECT `b`.`id` AS `id`, `b`.`project_name` AS `project_name`, `b`.`user_id` AS `user_id`, `b`.`submission_datetime` AS `submission_datetime`, count(distinct `il`.`id`) AS `total_likes`, count(distinct `ic`.`id`) AS `total_comments`, count(distinct `iv`.`id`) AS `total_views`, count(distinct `ish`.`id`) AS `total_shares`, count(distinct `ib`.`id`) AS `total_bookmarks`, avg(`ir`.`rating`) AS `average_rating`, count(distinct `ir`.`id`) AS `total_ratings` FROM ((((((`blog` `b` left join `idea_likes` `il` on(`b`.`id` = `il`.`idea_id`)) left join `idea_comments` `ic` on(`b`.`id` = `ic`.`idea_id`)) left join `idea_views` `iv` on(`b`.`id` = `iv`.`idea_id`)) left join `idea_shares` `ish` on(`b`.`id` = `ish`.`idea_id`)) left join `idea_bookmarks` `ib` on(`b`.`id` = `ib`.`idea_id`)) left join `idea_ratings` `ir` on(`b`.`id` = `ir`.`idea_id`)) GROUP BY `b`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `trending_ideas`
--
DROP TABLE IF EXISTS `trending_ideas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `trending_ideas`  AS SELECT `b`.`id` AS `id`, `b`.`project_name` AS `project_name`, `b`.`classification` AS `classification`, `b`.`project_type` AS `project_type`, count(distinct `il`.`id`) AS `recent_likes`, count(distinct `ic`.`id`) AS `recent_comments`, count(distinct `iv`.`id`) AS `recent_views`, count(distinct `il`.`id`) * 3 + count(distinct `ic`.`id`) * 5 + count(distinct `iv`.`id`) AS `engagement_score` FROM (((`blog` `b` left join `idea_likes` `il` on(`b`.`id` = `il`.`idea_id` and `il`.`created_at` >= current_timestamp() - interval 7 day)) left join `idea_comments` `ic` on(`b`.`id` = `ic`.`idea_id` and `ic`.`created_at` >= current_timestamp() - interval 7 day)) left join `idea_views` `iv` on(`b`.`id` = `iv`.`idea_id` and `iv`.`viewed_at` >= current_timestamp() - interval 7 day)) GROUP BY `b`.`id` HAVING `engagement_score` > 0 ORDER BY count(distinct `il`.`id`) * 3 + count(distinct `ic`.`id`) * 5 + count(distinct `iv`.`id`) DESC ;

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
  ADD KEY `idx_submission_status` (`submission_date`,`status`),
  ADD KEY `idx_approved_projects_user` (`user_id`);

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
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient` (`recipient_email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `idea_activity_log`
--
ALTER TABLE `idea_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_log_idea` (`idea_id`),
  ADD KEY `idx_activity_log_user` (`user_id`),
  ADD KEY `idx_activity_log_type` (`activity_type`),
  ADD KEY `idx_activity_log_date` (`created_at`);

--
-- Indexes for table `idea_bookmarks`
--
ALTER TABLE `idea_bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bookmark` (`idea_id`,`user_id`),
  ADD KEY `idx_idea_bookmarks_idea_id` (`idea_id`),
  ADD KEY `idx_idea_bookmarks_user_id` (`user_id`);

--
-- Indexes for table `idea_collaborations`
--
ALTER TABLE `idea_collaborations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_collaborations_idea` (`idea_id`),
  ADD KEY `idx_collaborations_requester` (`requester_id`),
  ADD KEY `idx_collaborations_owner` (`owner_id`),
  ADD KEY `idx_collaborations_status` (`status`);

--
-- Indexes for table `idea_comments`
--
ALTER TABLE `idea_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_idea_comments_idea_id` (`idea_id`),
  ADD KEY `idx_idea_comments_user_id` (`user_id`),
  ADD KEY `idx_idea_comments_parent_id` (`parent_id`);

--
-- Indexes for table `idea_followers`
--
ALTER TABLE `idea_followers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follower` (`idea_id`,`user_id`),
  ADD KEY `idx_idea_followers_idea` (`idea_id`),
  ADD KEY `idx_idea_followers_user` (`user_id`);

--
-- Indexes for table `idea_likes`
--
ALTER TABLE `idea_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`idea_id`,`user_id`),
  ADD KEY `idx_idea_likes_idea_id` (`idea_id`),
  ADD KEY `idx_idea_likes_user_id` (`user_id`),
  ADD KEY `idx_likes_count` (`idea_id`),
  ADD KEY `idx_likes_user_date` (`user_id`,`created_at`);

--
-- Indexes for table `idea_ratings`
--
ALTER TABLE `idea_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_rating` (`idea_id`,`user_id`),
  ADD KEY `idx_idea_ratings_idea` (`idea_id`),
  ADD KEY `idx_idea_ratings_user` (`user_id`);

--
-- Indexes for table `idea_reports`
--
ALTER TABLE `idea_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `idea_shares`
--
ALTER TABLE `idea_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_idea_shares_idea_id` (`idea_id`),
  ADD KEY `idx_idea_shares_user_id` (`user_id`),
  ADD KEY `idx_idea_shares_platform` (`platform`);

--
-- Indexes for table `idea_tags`
--
ALTER TABLE `idea_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tag_name` (`tag_name`),
  ADD UNIQUE KEY `unique_tag_slug` (`tag_slug`);

--
-- Indexes for table `idea_tag_relations`
--
ALTER TABLE `idea_tag_relations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_idea_tag` (`idea_id`,`tag_id`),
  ADD KEY `idx_idea_tag_relations_idea` (`idea_id`),
  ADD KEY `idx_idea_tag_relations_tag` (`tag_id`);

--
-- Indexes for table `idea_views`
--
ALTER TABLE `idea_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_idea_views_idea_id` (`idea_id`),
  ADD KEY `idx_idea_views_user_id` (`user_id`),
  ADD KEY `idx_idea_views_date` (`viewed_at`);

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
-- Indexes for table `mentoring_sessions_archive`
--
ALTER TABLE `mentoring_sessions_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pair_id` (`pair_id`),
  ADD KEY `session_date` (`session_date`),
  ADD KEY `archived_at` (`archived_at`);

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
  ADD KEY `idx_mentor_requests_mentor_status` (`mentor_id`,`status`),
  ADD KEY `idx_mentor_requests_status` (`mentor_id`,`status`);

--
-- Indexes for table `mentor_student_pairs`
--
ALTER TABLE `mentor_student_pairs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_mentor_pairs_status` (`mentor_id`,`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

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
-- Indexes for table `progress_milestones`
--
ALTER TABLE `progress_milestones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pair_id` (`pair_id`),
  ADD KEY `status` (`status`),
  ADD KEY `target_date` (`target_date`);

--
-- Indexes for table `progress_notes`
--
ALTER TABLE `progress_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pair_id` (`pair_id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `created_at` (`created_at`);

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
  ADD KEY `idx_projects_title` (`title`),
  ADD KEY `idx_projects_user_status` (`user_id`,`status`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `subadmin_activities`
--
ALTER TABLE `subadmin_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subadmin_id` (`subadmin_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `subadmin_classification_requests`
--
ALTER TABLE `subadmin_classification_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subadmin_id` (`subadmin_id`);

--
-- Indexes for table `subadmin_permissions`
--
ALTER TABLE `subadmin_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subadmin_id` (`subadmin_id`),
  ADD KEY `idx_permission_type` (`permission_type`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `subadmin_sessions`
--
ALTER TABLE `subadmin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_subadmin_id` (`subadmin_id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subadmin_id` (`subadmin_id`);

--
-- Indexes for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `temp_credentials`
--
ALTER TABLE `temp_credentials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_type_id` (`user_type`,`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_expires` (`expires_at`);

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
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_notification_type` (`notification_type`),
  ADD KEY `idx_user_unread` (`user_id`,`is_read`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_approved_projects`
--
ALTER TABLE `admin_approved_projects`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog`
--
ALTER TABLE `blog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `bookmark`
--
ALTER TABLE `bookmark`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deleted_ideas`
--
ALTER TABLE `deleted_ideas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `denial_projects`
--
ALTER TABLE `denial_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `idea_activity_log`
--
ALTER TABLE `idea_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `idea_bookmarks`
--
ALTER TABLE `idea_bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `idea_collaborations`
--
ALTER TABLE `idea_collaborations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `idea_comments`
--
ALTER TABLE `idea_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `idea_followers`
--
ALTER TABLE `idea_followers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `idea_likes`
--
ALTER TABLE `idea_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `idea_ratings`
--
ALTER TABLE `idea_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `idea_reports`
--
ALTER TABLE `idea_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `idea_shares`
--
ALTER TABLE `idea_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `idea_tags`
--
ALTER TABLE `idea_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `idea_tag_relations`
--
ALTER TABLE `idea_tag_relations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `idea_views`
--
ALTER TABLE `idea_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=235;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `mentors`
--
ALTER TABLE `mentors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mentor_activity_logs`
--
ALTER TABLE `mentor_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `mentor_requests`
--
ALTER TABLE `mentor_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mentor_student_pairs`
--
ALTER TABLE `mentor_student_pairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `progress_milestones`
--
ALTER TABLE `progress_milestones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `progress_notes`
--
ALTER TABLE `progress_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `project_likes`
--
ALTER TABLE `project_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `realtime_notifications`
--
ALTER TABLE `realtime_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `subadmin_activities`
--
ALTER TABLE `subadmin_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subadmin_classification_requests`
--
ALTER TABLE `subadmin_classification_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subadmin_permissions`
--
ALTER TABLE `subadmin_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subadmin_sessions`
--
ALTER TABLE `subadmin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT for table `temp_credentials`
--
ALTER TABLE `temp_credentials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `idea_activity_log`
--
ALTER TABLE `idea_activity_log`
  ADD CONSTRAINT `fk_activity_log_idea` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `idea_bookmarks`
--
ALTER TABLE `idea_bookmarks`
  ADD CONSTRAINT `fk_idea_bookmarks_idea` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_idea_bookmarks_user` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_collaborations`
--
ALTER TABLE `idea_collaborations`
  ADD CONSTRAINT `fk_collaborations_idea` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_collaborations_owner` FOREIGN KEY (`owner_id`) REFERENCES `register` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_collaborations_requester` FOREIGN KEY (`requester_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_followers`
--
ALTER TABLE `idea_followers`
  ADD CONSTRAINT `fk_idea_followers_idea` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_idea_followers_user` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_likes`
--
ALTER TABLE `idea_likes`
  ADD CONSTRAINT `idea_likes_ibfk_1` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `idea_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_ratings`
--
ALTER TABLE `idea_ratings`
  ADD CONSTRAINT `fk_idea_ratings_idea` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_idea_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_shares`
--
ALTER TABLE `idea_shares`
  ADD CONSTRAINT `fk_idea_shares_idea` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_idea_shares_user` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_tag_relations`
--
ALTER TABLE `idea_tag_relations`
  ADD CONSTRAINT `fk_idea_tag_relations_idea` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_idea_tag_relations_tag` FOREIGN KEY (`tag_id`) REFERENCES `idea_tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idea_views`
--
ALTER TABLE `idea_views`
  ADD CONSTRAINT `fk_idea_views_idea` FOREIGN KEY (`idea_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_idea_views_user` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `student_email_preferences`
--
ALTER TABLE `student_email_preferences`
  ADD CONSTRAINT `student_email_preferences_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subadmin_activities`
--
ALTER TABLE `subadmin_activities`
  ADD CONSTRAINT `subadmin_activities_ibfk_1` FOREIGN KEY (`subadmin_id`) REFERENCES `subadmins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subadmin_classification_requests`
--
ALTER TABLE `subadmin_classification_requests`
  ADD CONSTRAINT `subadmin_classification_requests_ibfk_1` FOREIGN KEY (`subadmin_id`) REFERENCES `subadmins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subadmin_permissions`
--
ALTER TABLE `subadmin_permissions`
  ADD CONSTRAINT `subadmin_permissions_ibfk_1` FOREIGN KEY (`subadmin_id`) REFERENCES `subadmins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subadmin_sessions`
--
ALTER TABLE `subadmin_sessions`
  ADD CONSTRAINT `subadmin_sessions_ibfk_1` FOREIGN KEY (`subadmin_id`) REFERENCES `subadmins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`subadmin_id`) REFERENCES `subadmins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `support_ticket_replies`
--
ALTER TABLE `support_ticket_replies`
  ADD CONSTRAINT `support_ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `register` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;