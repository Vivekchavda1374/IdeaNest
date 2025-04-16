-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 22, 2025 at 11:40 PM
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
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `er_number` varchar(50) NOT NULL,
  `gr_number` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `about` varchar(500) NOT NULL,
  `user_image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`id`, `name`, `email`, `er_number`, `gr_number`, `password`, `about`, `user_image`) VALUES
(7, 'Vivek Chavda', 'vivek.chavda119486@marwadiuniversity.ac.in', '92200133026', '119486', '$2y$10$X6u9dGCmkn/gXMsUDJdjaeCWyNT.of8Rpi/V47eWKMqJqMPnAjG/K', '', ''),
(8, 'Vivek Chavda', 'bhavik@marwadiuniversity.ac.in', '92200133027', '253163', '$2y$10$ufcxPIuvhe0LYqMNtw6n0eFTLlfBbbGh9oVnqI.IDF7nYHyIOH2Ka', '', ''),
(10, 'ViveChavda', 'vive6@marwadiuniversity.ac.in', '922001330244', '526321', '$2y$10$yhbg99gU4ApxumEuK0niwuw3xruw0Vh/539csDaoYJKr69XDUQq32', '', ''),
(11, 'bhavik kaladiya', 'bhavik.kaladiya@marwadiuniversity.ac.in', '92310133008', '121187', '$2y$10$Qu8HbKhhUWAESV0iOsaqPe.z8fKPhJ92elXSabRVNEmH5R03L38Z2', '', ''),
(12, 'github', 'vivek@marwadiuniversity.edu.in', '565656', '123456', '$2y$10$PBOpuEAEgFUcFDj2gMhji.oJIthBZpfD9cubUZ9b/kevyi5LnCJQu', '', ''),
(13, 'Rohan', 'rohanroy.121022@marwadiuniversity.ac.in', '92310133003', '111111', '$2y$10$.bk2fgklT//M5sdxRrzy0e8pjDW5.bBUZxOe6sh3UvK.M8B.rIgXa', '', ''),
(14, 'Neel kumar', 'neel.rayani123452@marwadiuniversity.ac.in', '92310133019', '123452', '$2y$10$DEMahNeMkP4TpQBBoQJuTOE4InSpNwa.scW0A8xjXBNfknKct.m0O', 'i am in sem 6 ', '');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
