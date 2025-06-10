-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 06, 2025 at 09:18 AM
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
-- Database: `reimbursement_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `log_pengajuan`
--

CREATE TABLE `log_pengajuan` (
  `id_log` int(11) NOT NULL,
  `id_pengajuan` int(11) NOT NULL,
  `id_aksi` int(11) NOT NULL,
  `id_aktifitas` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `komentar` varchar(255) NOT NULL,
  `lampiran_komentar` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_pengajuan`
--

INSERT INTO `log_pengajuan` (`id_log`, `id_pengajuan`, `id_aksi`, `id_aktifitas`, `created_by`, `komentar`, `lampiran_komentar`, `created_at`, `updated_at`) VALUES
(1, 0, 1, 2, 0, 'okkkkkk', '', '2025-06-05 18:33:20', '2025-06-05 18:33:20'),
(2, 0, 1, 2, 0, 'yaaaa', '', '2025-06-06 06:59:02', '2025-06-06 06:59:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `log_pengajuan`
--
ALTER TABLE `log_pengajuan`
  ADD PRIMARY KEY (`id_log`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `log_pengajuan`
--
ALTER TABLE `log_pengajuan`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
