-- phpMyAdmin SQL Dump
-- version 4.6.6deb5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 18, 2019 at 01:19 PM
-- Server version: 5.7.26-0ubuntu0.18.04.1
-- PHP Version: 7.2.19-0ubuntu0.18.04.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sites`
--

-- --------------------------------------------------------

--
-- Table structure for table `sites`
--

CREATE TABLE `sites` (
  `site_id` int(10) UNSIGNED NOT NULL,
  `site_name` varchar(16) NOT NULL,
  `site_path` varchar(16) NOT NULL,
  `timezone` varchar(24) NOT NULL,
  `auth_type` enum('ldap','local') NOT NULL,
  `session_expire_hours` int(10) UNSIGNED NOT NULL,
  `local_password_expire_days` int(10) UNSIGNED NOT NULL,
  `local_password_min_length` int(10) UNSIGNED NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `max_login_attempt_count` int(10) UNSIGNED NOT NULL,
  `failed_login_lockout_minutes` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sites`
--

INSERT INTO `sites` (`site_id`, `site_name`, `site_path`, `timezone`, `auth_type`, `session_expire_hours`, `local_password_expire_days`, `local_password_min_length`, `enabled`, `max_login_attempt_count`, `failed_login_lockout_minutes`) VALUES
(1, 'pertech', 'pertech/', 'America/Denver', 'local', 8, 365, 6, 1, 5, 10);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sites`
--
ALTER TABLE `sites`
  ADD PRIMARY KEY (`site_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sites`
--
ALTER TABLE `sites`
  MODIFY `site_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
