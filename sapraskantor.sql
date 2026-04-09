-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 02:52 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sapraskantor`
--

-- --------------------------------------------------------

--
-- Table structure for table `galeri`
--

CREATE TABLE `galeri` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `foto_before` varchar(255) NOT NULL,
  `foto_after` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `galeri`
--

INSERT INTO `galeri` (`id`, `user_id`, `judul`, `foto_before`, `foto_after`, `deskripsi`, `created_at`, `updated_at`) VALUES
(4, 1, 'Toilet Wanita Aula Utama', '1774917252_before_69cb1684cab55.gif', '1773286181_after_69b23325ef729.jpg', 'Toilet wanita lantai 1 ruang B sudah di perbaiki dan layak di gunakan', '2026-03-12 03:29:41', '2026-03-31 00:34:12');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `created_at`) VALUES
(1, 'Bangunan', '2026-02-10 04:56:08'),
(2, 'Elektronik', '2026-02-10 04:56:08'),
(3, 'Furnitur', '2026-02-10 04:56:08'),
(4, 'Listrik', '2026-02-10 04:56:08'),
(5, 'Plumbing', '2026-02-10 04:56:08'),
(6, 'Lainnya', '2026-02-10 04:56:08'),
(25, 'lapangan', '2026-03-30 04:02:51'),
(26, 'kendaraan', '2026-03-30 04:14:06');

-- --------------------------------------------------------

--
-- Table structure for table `komentar_galeri`
--

CREATE TABLE `komentar_galeri` (
  `id` int(11) NOT NULL,
  `galeri_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `komentar_galeri`
--

INSERT INTO `komentar_galeri` (`id`, `galeri_id`, `user_id`, `komentar`, `created_at`, `updated_at`) VALUES
(19, 4, 11, 'TERIMAKASIH', '2026-03-31 00:39:19', '2026-03-31 00:39:19');

-- --------------------------------------------------------

--
-- Table structure for table `pengaduan`
--

CREATE TABLE `pengaduan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tanggal_kejadian` date NOT NULL,
  `judul` varchar(200) NOT NULL,
  `kategori` varchar(255) DEFAULT NULL,
  `prioritas` enum('Rendah','Sedang','Tinggi') NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `deskripsi` text NOT NULL,
  `status` enum('Menunggu','Diproses','Selesai','Ditolak') DEFAULT 'Menunggu',
  `catatan_admin` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaduan`
--

INSERT INTO `pengaduan` (`id`, `user_id`, `tanggal_kejadian`, `judul`, `kategori`, `prioritas`, `lampiran`, `deskripsi`, `status`, `catatan_admin`, `created_at`, `updated_at`) VALUES
(37, 11, '2026-03-31', 'LAPANGAN APEL ', 'lapangan', 'Tinggi', '1774917497_LAPANG.jpg', 'LAPANGAN APEL RUSAK ', 'Ditolak', '-', '2026-03-31 00:38:17', '2026-03-31 00:43:20'),
(38, 11, '2026-03-31', 'BAGUNAN GEDUNG A ', 'Bangunan', 'Tinggi', '1774917678_ATAP.jpg', 'GEDUNG A BLOK C ATAPNYA AMBRUK', 'Diproses', 'TEKNISI SEDANG MEMPERBAIKI', '2026-03-31 00:41:18', '2026-03-31 00:42:57');

-- --------------------------------------------------------

--
-- Table structure for table `rating_galeri`
--

CREATE TABLE `rating_galeri` (
  `id` int(11) NOT NULL,
  `galeri_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rating_galeri`
--

INSERT INTO `rating_galeri` (`id`, `galeri_id`, `user_id`, `rating`, `created_at`) VALUES
(7, 4, 11, 5, '2026-03-31 00:39:03');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'telepon', '(021) 1234-56789', '2026-02-24 04:58:48'),
(2, 'email', 'helpdesk@assetcare.com', '2026-02-24 04:55:17'),
(3, 'alamat', 'Gedung Utama Lt. 2, Ruang IT Support', '2026-03-12 03:16:57'),
(4, 'jam_kerja', 'Senin-Jumat, 08:00-17:00 WIB', '2026-02-24 04:55:17'),
(13, 'petugas1', 'Arif (123)', '2026-03-12 03:16:57'),
(14, 'petugas2', 'Firman (234)', '2026-03-12 03:16:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('pegawai','admin') DEFAULT 'pegawai',
  `status` varchar(10) DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin AssetCare', 'admin@assetcare.com', '$2y$10$ylYA0Y2KRY2B7OVB4OYRHeUVmnXyfYzcRYHayefH8C3iAvL31tAwW', 'admin', 'aktif', '2026-02-05 01:41:58', '2026-02-10 05:02:17'),
(11, 'Nur AnisaA', 'nur@gmail.com', '$2y$10$5p4a0.9lK572vVDV2eDBX.NzSaKTACwJoOe4uiZHIZlpxnfP2zzIy', 'pegawai', 'aktif', '2026-03-31 00:35:11', '2026-03-31 00:41:49');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_galeri_rating`
-- (See below for the actual view)
--
CREATE TABLE `v_galeri_rating` (
`id` int(11)
,`user_id` int(11)
,`judul` varchar(255)
,`foto_before` varchar(255)
,`foto_after` varchar(255)
,`deskripsi` text
,`created_at` timestamp
,`updated_at` timestamp
,`uploader_nama` varchar(100)
,`avg_rating` decimal(14,4)
,`total_rating` bigint(21)
,`total_komentar` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_komentar_galeri`
-- (See below for the actual view)
--
CREATE TABLE `v_komentar_galeri` (
`id` int(11)
,`galeri_id` int(11)
,`user_id` int(11)
,`komentar` text
,`created_at` timestamp
,`updated_at` timestamp
,`user_nama` varchar(100)
,`user_role` enum('pegawai','admin')
,`galeri_judul` varchar(255)
);

-- --------------------------------------------------------

--
-- Structure for view `v_galeri_rating`
--
DROP TABLE IF EXISTS `v_galeri_rating`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_galeri_rating`  AS SELECT `g`.`id` AS `id`, `g`.`user_id` AS `user_id`, `g`.`judul` AS `judul`, `g`.`foto_before` AS `foto_before`, `g`.`foto_after` AS `foto_after`, `g`.`deskripsi` AS `deskripsi`, `g`.`created_at` AS `created_at`, `g`.`updated_at` AS `updated_at`, `u`.`nama` AS `uploader_nama`, coalesce(avg(`r`.`rating`),0) AS `avg_rating`, count(distinct `r`.`id`) AS `total_rating`, count(distinct `k`.`id`) AS `total_komentar` FROM (((`galeri` `g` left join `users` `u` on(`g`.`user_id` = `u`.`id`)) left join `rating_galeri` `r` on(`g`.`id` = `r`.`galeri_id`)) left join `komentar_galeri` `k` on(`g`.`id` = `k`.`galeri_id`)) GROUP BY `g`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_komentar_galeri`
--
DROP TABLE IF EXISTS `v_komentar_galeri`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_komentar_galeri`  AS SELECT `k`.`id` AS `id`, `k`.`galeri_id` AS `galeri_id`, `k`.`user_id` AS `user_id`, `k`.`komentar` AS `komentar`, `k`.`created_at` AS `created_at`, `k`.`updated_at` AS `updated_at`, `u`.`nama` AS `user_nama`, `u`.`role` AS `user_role`, `g`.`judul` AS `galeri_judul` FROM ((`komentar_galeri` `k` join `users` `u` on(`k`.`user_id` = `u`.`id`)) join `galeri` `g` on(`k`.`galeri_id` = `g`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `galeri`
--
ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_galeri_user` (`user_id`),
  ADD KEY `idx_galeri_created` (`created_at`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `komentar_galeri`
--
ALTER TABLE `komentar_galeri`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_komentar_galeri` (`galeri_id`),
  ADD KEY `idx_komentar_galeri_user` (`user_id`),
  ADD KEY `idx_komentar_galeri_created` (`created_at`);

--
-- Indexes for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rating_galeri`
--
ALTER TABLE `rating_galeri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`galeri_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_rating_galeri` (`galeri_id`,`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `galeri`
--
ALTER TABLE `galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `komentar_galeri`
--
ALTER TABLE `komentar_galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `pengaduan`
--
ALTER TABLE `pengaduan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `rating_galeri`
--
ALTER TABLE `rating_galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `galeri`
--
ALTER TABLE `galeri`
  ADD CONSTRAINT `galeri_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `komentar_galeri`
--
ALTER TABLE `komentar_galeri`
  ADD CONSTRAINT `komentar_galeri_ibfk_1` FOREIGN KEY (`galeri_id`) REFERENCES `galeri` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `komentar_galeri_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD CONSTRAINT `pengaduan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rating_galeri`
--
ALTER TABLE `rating_galeri`
  ADD CONSTRAINT `rating_galeri_ibfk_1` FOREIGN KEY (`galeri_id`) REFERENCES `galeri` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rating_galeri_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
