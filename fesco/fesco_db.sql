-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2025 at 08:57 PM
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
-- Database: `fesco_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '12345');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `consumer_id` int(11) NOT NULL,
  `units` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bill_date` date DEFAULT curdate(),
  `late_fee` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `late_fee_percent` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `consumer_id`, `units`, `amount`, `bill_date`, `late_fee`, `due_date`, `late_fee_percent`) VALUES
(1, 2, 123, 1845.00, '2025-09-01', 0.00, NULL, 0.00),
(2, 1, 526, 7890.00, '2025-09-01', 0.00, NULL, 0.00),
(3, 4, 12345, 185175.00, '2025-09-01', 0.00, NULL, 0.00),
(4, 4, 12345, 185175.00, '2025-09-01', 0.00, NULL, 0.00),
(5, 5, 345, 5175.00, '2025-09-01', 0.00, NULL, 0.00),
(6, 5, 345, 5175.00, '2025-09-01', 0.00, NULL, 0.00),
(7, 6, 12, 180.00, '2025-09-01', 0.00, NULL, 0.00),
(9, 6, 12, 180.00, '2025-09-01', 0.00, NULL, 0.00),
(10, 7, 123456, 1851840.00, '2025-09-01', 0.00, NULL, 0.00),
(11, 7, 123456, 1851840.00, '2025-09-01', 0.00, NULL, 0.00),
(12, 9, 10, 150.00, '2025-09-02', 0.00, NULL, 0.00),
(13, 9, 30, 450.00, '2025-09-02', 0.00, NULL, 0.00),
(14, 9, 30, 450.00, '2025-09-02', 0.00, NULL, 0.00),
(15, 10, 1234, 18510.00, '2025-09-02', 0.00, '2025-09-17', 2.00),
(16, 11, 12345, 185175.00, '2025-09-02', 0.00, '2025-09-17', 2.00),
(17, 11, 12345, 185175.00, '2025-09-02', 0.00, '2025-09-17', 2.00),
(18, 13, 234, 3510.00, '2025-09-24', 0.00, '2025-10-09', 2.00);

-- --------------------------------------------------------

--
-- Table structure for table `consumers`
--

CREATE TABLE `consumers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `consumer_number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consumers`
--

INSERT INTO `consumers` (`id`, `name`, `email`, `password`, `consumer_number`, `created_at`) VALUES
(1, 'muhammad qasim nizam', 'qasimnizam9@gmail.com', '$2y$10$parVKgwgYLJlbHTonyho7uxj4Ga4M3mQKYOHQmLSDoWxX4p6rnbcq', '03071464131', '2025-09-01 20:24:58'),
(2, 'ahmad hassan', 'ahmadhassan@gmail.com', '$2y$10$LWPcyoWSls9N7OFgypmhs.oED7br8qz6bdJIAT1DAqvr1l2/edSpa', '03074085889', '2025-09-01 20:28:08'),
(3, 'muhammad qasim nizam', 'hussain@gmail.com', '$2y$10$RTrySmviVz3ytrtbihwY3epWTsoy9UJ7aAbMqImcJNdQejfAb.tha', '03043191531', '2025-09-01 21:12:54'),
(4, 'muhammad waqas', 'waqasahmad@gmail.com', '$2y$10$KU1vyHllza4vuuk5b7aNxuWA0P21FnIKS.sIcnoUnib76frYFHe1.', '03067899546', '2025-09-01 21:18:33'),
(5, 'asher', 'asher@gmail.com', '$2y$10$QUIp9ZFcOX4ByPkpfEBnJOGpGJliqBw23qvuWk5POYtZsj91GM3jC', '0305677893', '2025-09-01 21:26:07'),
(6, 'zayyan', 'zayyan@gmail.com', '$2y$10$tEIFbmpOut0CYKtYOphdTOB9QTVEB9pLGJC/rycvWMYnLNejRla/O', '03007234567', '2025-09-01 21:40:49'),
(7, 'sarim', 'sarim@gmail.com', '$2y$10$74UEpGCEvTr5Qg7MVHc2dOfqUk.9d8zzB563RnJxcEsqDaEKwHOuK', '0205790', '2025-09-01 21:49:57'),
(8, 'abx', 'abx@gmail.com', '$2y$10$uru5TzWUVRS8KMx7O6A5ouYQY9CX3cWu3JEWQseEnU9g6xeMkhnby', '03087890654', '2025-09-01 22:03:27'),
(9, 'azar', 'azar@gmail.com', '$2y$10$TrTRpuuJyGK8lveenDglT.o7EBhqzGHwjoBHfth6Spgekxe9dTbzW', '030872891821', '2025-09-02 16:21:36'),
(10, 'muhammad ahad', 'ahad@gmail.com', '$2y$10$Dcxz8dPfJuoZGhiaRFoBsOYR.S.AaUOIvcRuKUJkhvjPWX1xoIkg.', '03035641728', '2025-09-02 16:49:01'),
(11, 'xyz', 'xyz@gmail.com', '$2y$10$t59i1RfYJ5ynYnLmMNIucew0FxclkDk07RT.tJaQNcVirF5SRLklG', '023034847256272', '2025-09-02 17:45:32'),
(12, 'shahmeer', 'shameer@gmail.com', '$2y$10$9/LbX5pVTCEIz1eq.Ew6OO.vt/02DKrwL7B6bs/C8tBVdXvo10akC', '11223', '2025-09-24 16:22:40'),
(13, 'asifa', 'asifa@gmail.com', '$2y$10$vt0qTdlKRsyYVhJOShtjP.FgdjkUAOfoFzBep/J3WOJnWQKZlmFMS', '1122356', '2025-09-24 16:23:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consumer_id` (`consumer_id`);

--
-- Indexes for table `consumers`
--
ALTER TABLE `consumers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `consumer_number` (`consumer_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `consumers`
--
ALTER TABLE `consumers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `bills_ibfk_1` FOREIGN KEY (`consumer_id`) REFERENCES `consumers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
