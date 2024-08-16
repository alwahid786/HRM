-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2024 at 11:07 AM
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
-- Database: `hrm`
--

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration` decimal(10,2) DEFAULT NULL,
  `leave_type` varchar(50) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Requested','Approved','Rejected','Cancelled') DEFAULT 'Requested',
  `action_by` int(11) DEFAULT NULL,
  `action_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leaves`
--

INSERT INTO `leaves` (`id`, `user_id`, `username`, `start_date`, `end_date`, `duration`, `leave_type`, `reason`, `created_at`, `status`, `action_by`, `action_date`) VALUES
(98, 14, 'Wahid Ahmad', '2024-08-15', '2024-08-18', 3.50, 'sickleave', 'fdfj', '2024-08-15 07:46:29', 'Approved', 14, '2024-08-15 00:54:31'),
(99, 13, 'Hammas Munir', '2024-08-15', '2024-08-19', 4.50, 'sickleave', 'i need leave', '2024-08-15 07:50:13', 'Approved', 1, '2024-08-15 09:55:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `login` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','hradmin','user') NOT NULL,
  `hiring_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','blocked') NOT NULL DEFAULT 'active',
  `leave_limit` int(11) DEFAULT 0,
  `leave_start_date` date DEFAULT NULL,
  `leave_end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `login`, `email`, `password`, `user_type`, `hiring_date`, `created_at`, `status`, `leave_limit`, `leave_start_date`, `leave_end_date`) VALUES
(1, 'admin', 'admin', 'admin@gmail.com', '$2y$10$ie5Rf5BTFM0qjJ5xMZibROUTCtwPBgxNu8RlyEFYTfpRDmjLSJynW', 'admin', '1993-10-03', '2024-08-09 21:20:34', 'active', 20, '2024-01-01', '2024-11-30'),
(13, 'Hammas Munir', 'hammas', 'hammas@gmail.com', '$2y$10$/YAA2BJxaTEMVliw6NtR4.qPvNUvoC82CGz/HUllJSt0u3XpKPC8y', 'hradmin', '2024-08-13', '2024-08-13 12:09:17', 'active', 10, '2024-01-01', '2024-12-01'),
(14, 'Wahid Ahmad', 'wahid', 'wahid@gmail.com', '$2y$10$p0XeAoO0tQA71AdxUCrVsedIR8gwdzfD3MWemhelGMx8UjESa6ze.', 'user', '2024-08-13', '2024-08-13 12:10:00', 'active', 5, '2024-01-01', '2024-12-30'),
(15, 'testuser', 'testuser', 'test@gmail.com', '$2y$10$ZhEMIT3Z3W5W29CChHLVyeqx5ru4dSRNGeRY/pZau8.bXctxEd4a6', 'user', '2024-08-13', '2024-08-13 12:10:31', 'active', 6, '2024-01-01', '2024-12-01'),
(16, 'seconduserfromhammas', 'seconduserfromhammas', 'seconduserfromhammas@gmail.com', '$2y$10$5B.eoVr9e1DiXuwUbh9OHuuVGjuhVpW2CdBV7N1iyPjmLrCJHh.Sa', 'user', '2024-08-13', '2024-08-13 12:11:20', 'active', 3, '2024-08-13', '2024-12-03'),
(17, 'batux', 'Tempor voluptas fugi', 'fivyc@mailinator.com', '$2y$10$XJF5ZJLdavIo1Q8LAIPJiu8ddTj9DFrS/z.AIZ6redDddtd6uaZDq', 'user', '1985-07-04', '2024-08-13 12:32:36', 'active', 0, NULL, NULL),
(18, 'noxubuquze', 'Aliquam vel laudanti', 'pinul@mailinator.com', '$2y$10$O9sWusupPEisOc0v5xl/TOzGQgN9iRMYcmCcNdksadKpSgvFEYFS6', 'hradmin', '2003-09-20', '2024-08-13 12:32:58', 'active', 0, NULL, NULL),
(19, 'xazaq', 'Recusandae Aut eos', 'naqad@mailinator.com', '$2y$10$/Q3ZCj/fQl.lwsJ2bz92O.7L3pLKclLVOgFCZUyI7ryBTZcRbU3.K', 'hradmin', '2021-08-01', '2024-08-13 12:33:03', 'active', 0, NULL, NULL),
(20, 'bacykam', 'Animi esse nulla el', 'wirawew@mailinator.com', '$2y$10$ox1tzCDjtFfPLvav9YtNme39rWuNyXnDxlStUOhAR5jMd9FzBb5nG', 'user', '2015-05-10', '2024-08-15 06:08:07', 'active', 0, NULL, NULL),
(21, 'fypyfi', 'Non est adipisci con', 'muzi@mailinator.com', '$2y$10$344LD.4tNK5lr/nhKp.fQut0U5Z6s.sq74nb5JFYrVA2I8HBzrZzm', 'admin', '1981-02-24', '2024-08-15 06:08:22', 'active', 0, NULL, NULL),
(22, 'cokajij', 'Est ut incidunt sit', 'covo@mailinator.com', '$2y$10$IF37K0ItPbY2db7B7qZnfurH00w6j.QV3JhHwlRV1AUJh5n8D05X.', 'admin', '1970-11-23', '2024-08-15 06:09:24', 'active', 0, NULL, NULL),
(23, 'caqovolyz', 'Sed culpa modi cons', 'fywujazy@mailinator.com', '$2y$10$dTzo7Mysh4MwTdlYMXFbSu0vKizyt/qk3EitzqVX4Au7cAJuL31s.', 'user', '2001-11-23', '2024-08-15 06:09:30', 'active', 0, NULL, NULL),
(24, 'puxofukite', 'Doloremque qui cum e', 'hozewepa@mailinator.com', '$2y$10$gkLegTU96eIX19v3Ctqjfu11w6MpHrMTy.2WOXkaj2nnvixhulOLq', 'hradmin', '1982-09-04', '2024-08-15 06:57:43', 'active', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE attendance (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `department` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `no` INT(11) NOT NULL,
    `date_time` DATETIME NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `location_id` INT(11) NOT NULL,
    `id_number` VARCHAR(100) NOT NULL,
    `verify_code` VARCHAR(255) NOT NULL,
    `card_no` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (`id`)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `leaves`
--
ALTER TABLE `leaves`
  ADD CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
