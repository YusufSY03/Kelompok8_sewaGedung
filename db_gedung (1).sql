-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2025 at 09:53 AM
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
-- Database: `db_gedung`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_username` varchar(100) DEFAULT NULL,
  `tanggal_acara` date DEFAULT NULL,
  `waktu_mulai` time DEFAULT NULL,
  `waktu_selesai` time DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','dikonfirmasi','selesai','dibatalkan') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`id`, `customer_id`, `user_username`, `tanggal_acara`, `waktu_mulai`, `waktu_selesai`, `harga`, `status`, `created_at`) VALUES
(1, 4, 'yusuf', '2025-06-23', '04:00:00', '16:00:00', 20000000.00, 'selesai', '2025-06-22 03:43:14'),
(2, 2, 'yusuf', '2025-06-22', '13:46:00', '16:49:00', 20000000.00, 'selesai', '2025-06-22 03:44:20'),
(3, 2, 'yusuf', '2025-06-22', '13:46:00', '16:49:00', 20000000.00, 'dikonfirmasi', '2025-06-22 03:44:40'),
(4, 2, 'aldo', '2025-06-24', '04:00:00', '16:00:00', 17000000.00, 'selesai', '2025-06-22 04:08:55'),
(5, NULL, 'superadmin', '2025-06-23', '01:00:00', '23:59:00', 29650000.00, 'dikonfirmasi', '2025-06-22 06:33:21'),
(6, NULL, 'yusuf', '2025-06-23', '13:33:00', '23:59:00', 12650000.00, 'dibatalkan', '2025-06-22 06:34:52'),
(7, 7, 'superadmin', '2025-06-24', '05:00:00', '20:00:00', 20450000.00, 'dikonfirmasi', '2025-06-23 07:31:49'),
(8, 7, 'nopal', '2025-06-25', '07:00:00', '20:00:00', 18150000.00, 'dikonfirmasi', '2025-06-23 07:46:05');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id`, `nama`, `email`, `telepon`, `alamat`) VALUES
(1, 'Abdi', 'abdi@gmail.com', '081234567890', 'Jl. Merdeka No. 1, Jakarta'),
(2, 'aldo', 'aldo@gmail.com', '08152525612', 'padang'),
(3, 'Hayfa', 'hayfa@gmail.com', '087654321098', 'Jl. Pahlawan No. 3, Surabaya'),
(4, 'Yusuf Syamputra', 'yusuf@gmail.com', '081270726389', 'Jl. Rowosari no 12'),
(7, 'nopal', 'nopal@gmail.com', '089776654321', 'Perawang,siak,km 8');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`username`, `email`, `password`, `role`) VALUES
('admin', 'ucup@gmail.com', '$2y$10$mNGB3LfCmw9CKUCSn.1kY.zhpFcHxVhIxOGqx.SY.VaingFfm.O8S', 'admin'),
('aldo', 'rrr@fr', 'aldoaja', 'user'),
('nopal', 'nopalaja@gmail.com', 'nopal123', 'user'),
('superadmin', 'superadmin@example.com', 'super123', 'superadmin'),
('yusuf', 'yusuf@gmail.com', 'yusuf123', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `user_username` (`user_username`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`user_username`) REFERENCES `user` (`username`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
