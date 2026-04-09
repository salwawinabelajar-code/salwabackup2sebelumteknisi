-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 01:55 AM
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `teknisi_nama` varchar(100) DEFAULT NULL,
  `teknisi_yang_memperbaiki` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `galeri`
--

INSERT INTO `galeri` (`id`, `user_id`, `judul`, `foto_before`, `foto_after`, `deskripsi`, `created_at`, `updated_at`, `teknisi_nama`, `teknisi_yang_memperbaiki`) VALUES
(17, 13, 'Perbaikan: Ban Mobil Bocor', '1775621873_OIP.jpg', '1775627718_banteknisi.jpg', 'Pengaduan dari Reza Habibi telah selesai diperbaiki.\n\nDeskripsi perbaikan: Ban mobil dinas dengan plat nomor D 786 XYA sudah di perbaiki oleh teknisi', '2026-04-08 05:55:18', '2026-04-08 05:55:18', NULL, NULL),
(18, 12, 'Perbaikan: Komputer Tidak Menyala', '1775626008_komputer rusak.jpg', '1775631115_after_69d5fb0b2057c.jpg', 'Pengaduan dari Wina Yuliani telah selesai diperbaiki.\r\n\r\nDeskripsi perbaikan: KOMPUTER TELAH DI GANTI', '2026-04-08 06:38:51', '2026-04-08 06:51:55', NULL, NULL),
(19, 11, 'Perbaikan: Printer Error', '1775624997_printer.jpg', '1775714323_printerbaru.jpg', 'Pengaduan dari Risa Aulia Pratiwi telah selesai diperbaiki.\n\nDeskripsi perbaikan: Printer di Gedung utama lantai 1 ruang Tata Usaha sudah di ganti dengan yang  baru', '2026-04-09 05:58:43', '2026-04-09 05:58:43', NULL, NULL),
(20, 15, 'Perbaikan: Wastafel Tersumbat', '1775719440_wastafel.jpg', '1775722724_wstfl.jpg', 'Pengaduan dari Salwa Nur telah selesai diperbaiki.\n\nDeskripsi perbaikan: sudah di perbaiki oleh teknisi', '2026-04-09 08:18:44', '2026-04-09 08:18:44', NULL, NULL),
(21, 19, 'Perbaikan: Atap ambruk', '1775715195_ATAP.jpg', '1775723004_atapp.jpg', 'Pengaduan dari Ajeng Kartini telah selesai diperbaiki.\n\nDeskripsi perbaikan: sudah di perbaiki teknisi', '2026-04-09 08:23:24', '2026-04-09 08:23:24', NULL, NULL);

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
(26, 'kendaraan', '2026-03-30 04:14:06'),
(28, 'lapangan', '2026-04-06 02:09:58');

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
(20, 18, 12, 'bagus, admin sama teknisinya gercep banget good', '2026-04-08 06:53:07', '2026-04-08 06:53:07'),
(21, 18, 15, 'good', '2026-04-09 07:46:54', '2026-04-09 07:46:54');

-- --------------------------------------------------------

--
-- Table structure for table `log_teknisi`
--

CREATE TABLE `log_teknisi` (
  `id` int(11) NOT NULL,
  `teknisi_id` int(11) NOT NULL,
  `pengaduan_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaduan`
--

CREATE TABLE `pengaduan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `teknisi_id` int(11) DEFAULT NULL,
  `tanggal_kejadian` date NOT NULL,
  `judul` varchar(200) NOT NULL,
  `kategori` varchar(255) DEFAULT NULL,
  `prioritas` enum('Rendah','Sedang','Tinggi') NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `foto_admin` varchar(255) DEFAULT NULL,
  `deskripsi` text NOT NULL,
  `status` enum('Menunggu','Diproses','Selesai','Ditolak') DEFAULT 'Menunggu',
  `catatan_admin` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deadline` date DEFAULT NULL,
  `assigned_teknisi_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaduan`
--

INSERT INTO `pengaduan` (`id`, `user_id`, `teknisi_id`, `tanggal_kejadian`, `judul`, `kategori`, `prioritas`, `lampiran`, `foto_admin`, `deskripsi`, `status`, `catatan_admin`, `created_at`, `updated_at`, `deadline`, `assigned_teknisi_id`) VALUES
(49, 13, NULL, '2026-04-02', 'Ban Mobil Bocor', 'kendaraan', 'Sedang', '1775621873_OIP.jpg', '1775627718_banteknisi.jpg', 'mobil dinas dengan plat nomor D 786 XYA ban depan sebelah kiri bocor(lokasinya di parkiran gedung aula)', 'Selesai', 'Ban mobil dinas dengan plat nomor D 786 XYA sudah di perbaiki oleh teknisi', '2026-04-08 04:17:53', '2026-04-08 05:55:18', NULL, NULL),
(50, 11, NULL, '2026-04-02', 'AC Tidak Rusak Dingin', 'Elektronik', 'Tinggi', '1775624665_ac.jpg', NULL, 'AC di Gedung utama lantai 2 ruang Administrasi tidak mengeluarkan udara dingin, hanya angin biasa meskipun sudah disetel suhu rendah.', 'Menunggu', NULL, '2026-04-08 05:04:25', '2026-04-08 05:33:12', NULL, NULL),
(51, 11, NULL, '2026-04-02', 'Printer Error', 'Elektronik', 'Sedang', '1775624997_printer.jpg', '1775714323_printerbaru.jpg', 'Printer di Gedung utama lantai 1 ruang Tata Usaha tidak bisa mencetak, muncul notifikasi paper jam padahal tidak ada kertas tersangkut.', 'Selesai', 'Printer di Gedung utama lantai 1 ruang Tata Usaha sudah di ganti dengan yang  baru', '2026-04-08 05:09:57', '2026-04-09 05:58:43', NULL, NULL),
(52, 11, NULL, '2026-04-03', 'Kursi Kantor Rusak', 'Furnitur', 'Rendah', '1775625146_kursi.jpg', NULL, 'Kursi di Gedung utama lantai 3 ruang Staff IT tidak bisa diatur ketinggiannya dan roda copot', 'Menunggu', NULL, '2026-04-08 05:12:26', '2026-04-08 05:32:54', NULL, NULL),
(53, 12, NULL, '2026-04-02', 'Komputer Tidak Menyala', 'Elektronik', 'Tinggi', '1775626008_komputer rusak.jpg', '1775630331_INTERNET.jpg', 'Komputer di Gedung Teknologi Informasi lantai 2 ruang Lab IT tidak dapat dinyalakan karena layar lcd rusak\r\n', 'Selesai', 'KOMPUTER TELAH DI GANTI', '2026-04-08 05:26:48', '2026-04-08 06:38:51', NULL, NULL),
(54, 12, NULL, '2026-04-02', 'Koneksi Internet Tidak Stabil', 'Lainnya', 'Tinggi', '1775626300_INTERNET.jpg', NULL, 'Jaringan di Gedung Teknologi Informasi lantai 3 ruang Server sering terputus.', 'Menunggu', NULL, '2026-04-08 05:31:40', '2026-04-08 05:31:40', NULL, NULL),
(55, 15, NULL, '2026-04-08', 'CCTV Tidak Aktif', 'Elektronik', 'Tinggi', '1775626687_cctv.jpg', NULL, 'CCTV di Gedung Utama lantai 1 lorong utama tidak berfungsi.', 'Menunggu', NULL, '2026-04-08 05:38:07', '2026-04-08 05:38:07', NULL, NULL),
(56, 15, NULL, '2026-04-08', 'Lift Tidak Beroperasi', 'Bangunan', 'Tinggi', '1775626907_liftt.jpg', NULL, 'Lift di Gedung Utama area tengah berhenti di lantai 2 dan tidak bisa digunakan.', 'Diproses', 'Lift sedang di perbaiki oleh teknisi', '2026-04-08 05:41:47', '2026-04-09 05:59:32', NULL, NULL),
(57, 17, NULL, '2026-04-04', 'Toilet rusak', 'Bangunan', 'Tinggi', '1775627130_toilet rusak.gif', NULL, 'Toilet di Gedung Pelayanan lantai 1 toilet tidak bisa digunakan dengan normal karena rusak parah', 'Menunggu', NULL, '2026-04-08 05:45:30', '2026-04-08 05:45:30', NULL, NULL),
(58, 17, NULL, '2026-04-05', 'Kran Air Bocor', 'Plumbing', 'Sedang', '1775627409_kran.jpg', NULL, 'Kran di Gedung Pelayanan lantai 1 pantry bocor terus menerus.', 'Menunggu', NULL, '2026-04-08 05:50:09', '2026-04-08 05:50:09', NULL, NULL),
(59, 18, NULL, '2026-04-07', 'Pagar Rusak', 'Bangunan', 'Tinggi', '1775627607_pagar.jpg', NULL, 'Pagar di Gedung Pendukung area belakang roboh sebagian.', 'Diproses', 'pagar sedang proses perbaikan, mohon tunggu ', '2026-04-08 05:53:27', '2026-04-09 06:00:11', NULL, NULL),
(60, 12, NULL, '2026-04-08', 'jlbnliohnpi', 'Elektronik', 'Sedang', '1775631699_kmptr.jpg', '1775714198_kmptr.jpg', 'kjhviku', 'Ditolak', 'mohon maaf pengfaduan di tolak karena tidak jelas', '2026-04-08 07:01:39', '2026-04-09 05:56:38', NULL, NULL),
(61, 17, NULL, '2026-04-09', 'Karpet Bau dan Kotor', 'Lainnya', 'Sedang', '1775713625_karpet.jpg', NULL, 'Karpet di Gedung Pelayanan lantai 1 lobby basah dan berbau tidak sedap.', 'Menunggu', NULL, '2026-04-09 05:47:05', '2026-04-09 05:47:05', NULL, NULL),
(62, 20, NULL, '2026-04-09', 'Pintu Tidak Bisa Dikunci', 'Bangunan', 'Sedang', '1775713986_pintu.jpg', NULL, 'Pintu di Gedung Pendukung lantai 1 ruang Gudang tidak dapat dikunci.', 'Menunggu', NULL, '2026-04-09 05:53:06', '2026-04-09 05:53:06', NULL, NULL),
(63, 19, NULL, '2026-04-09', 'Jendela Rusak', 'Bangunan', 'Rendah', '1775714615_jendelarusak.jpg', NULL, 'Jendela di Gedung Pendukung lantai 2 ruang Arsip sudah satu minggu tidak bisa ditutup rapat.', 'Menunggu', NULL, '2026-04-09 06:03:35', '2026-04-09 06:03:35', NULL, NULL),
(64, 19, NULL, '2026-04-03', 'Atap ambruk', 'Bangunan', 'Tinggi', '1775715195_ATAP.jpg', '1775723004_atapp.jpg', 'Atap di Gedung Pendukung lantai 2 ruang Penyimpanan ambruk saat hujan.\r\n', 'Selesai', 'sudah di perbaiki teknisi', '2026-04-09 06:13:15', '2026-04-09 08:23:24', NULL, NULL),
(65, 15, NULL, '2026-04-09', 'Wastafel Tersumbat', 'Plumbing', 'Sedang', '1775719440_wastafel.jpg', '1775722724_wstfl.jpg', 'Wastafel di Gedung Pelayanan lantai 1 pantry tidak bisa mengalirkan air dengan lancar.', 'Selesai', 'sudah di perbaiki oleh teknisi', '2026-04-09 07:24:00', '2026-04-09 08:18:44', NULL, NULL);

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
(15, 17, 12, 4, '2026-04-08 06:52:20'),
(16, 18, 12, 5, '2026-04-08 06:52:26'),
(17, 18, 19, 5, '2026-04-09 06:06:31'),
(18, 17, 19, 4, '2026-04-09 06:06:34'),
(19, 19, 19, 5, '2026-04-09 06:06:37'),
(20, 19, 15, 4, '2026-04-09 07:45:24'),
(21, 18, 15, 5, '2026-04-09 07:45:32'),
(22, 20, 12, 5, '2026-04-09 23:42:17'),
(23, 21, 12, 5, '2026-04-09 23:42:21'),
(24, 19, 12, 5, '2026-04-09 23:42:25');

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
(1, 'telepon', '(021) 1234-567899', '2026-04-06 02:08:10'),
(2, 'email', 'helpdesk@assetcare.com', '2026-02-24 04:55:17'),
(3, 'alamat', 'Gedung Utama Lt. 2, Ruang IT Support', '2026-03-12 03:16:57'),
(4, 'jam_kerja', 'Senin-Jumat, 08:00-16:00 WIB', '2026-04-06 02:08:10'),
(13, 'petugas1', 'Budianto (083456728579)', '2026-04-08 03:46:24'),
(14, 'petugas2', 'Firman (08167893525)', '2026-04-06 02:08:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('pegawai','admin','teknisi') NOT NULL DEFAULT 'pegawai',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin AssetCare', 'admin@assetcare.com', '$2y$10$ylYA0Y2KRY2B7OVB4OYRHeUVmnXyfYzcRYHayefH8C3iAvL31tAwW', 'admin', '2026-02-05 01:41:58', '2026-02-10 05:02:17'),
(11, 'Risa Aulia Pratiwi', 'risa@gmail.com', '$2y$10$2mFVUaWfVrkXWfZLrV4MOubzaqmcqMmh2eJhiGjrV3KVhD06gCNcq', 'pegawai', '2026-03-31 00:35:11', '2026-04-08 05:15:17'),
(12, 'Wina Yuliani', 'wina@gmail.com', '$2y$10$6iqYKYGuXzSNpGeC4Gli9edEph6VuWCHFJeWNaeSvn97yjsz121Hy', 'pegawai', '2026-04-02 06:32:08', '2026-04-08 05:18:08'),
(13, 'Reza Habibi', 'reza@gmail.com', '$2y$10$PYFukhkMF4L6D9ryw9QPluwHaF3nV86JB8ZUcRQw9kgrWHYDWVmca', 'pegawai', '2026-04-02 06:58:50', '2026-04-08 05:18:31'),
(14, 'Arif Wijayanto', 'teknisi1@gmail.com', '$2y$10$3Vj1a3WRIIvD2ful4tYO2OuYpGcyW1mO.LLLD1hddjJcQpc1mREI2', 'teknisi', '2026-04-02 07:24:16', '2026-04-02 07:24:16'),
(15, 'Salwa Nur', 'salwa@gmail.com', '$2y$10$m93o6K7buqZsxsW7FjPsx.kmyEZb7QVswjoGX81RC1jOT1s3XWVty', 'pegawai', '2026-04-02 09:30:53', '2026-04-08 04:21:57'),
(16, 'Budiman Abdullah', 'buditeknisi@gmail.com', '$2y$10$lVOohT7VTig9JvmQcRqynekxpKBUq4pGcw1ZmJoKBZrTPNG3B67K.', 'teknisi', '2026-04-03 08:17:03', '2026-04-08 06:17:46'),
(17, 'Tia Novianti', 'tia@gmail.com', '$2y$10$KE7rP8cWxWEYB8iYHrvmee28yN9PMEfGFZYooxu/nLv/Owi.1/K82', 'pegawai', '2026-04-08 05:19:34', '2026-04-08 05:42:39'),
(18, 'Haifa Hapsa Hanifah', 'haifa@gmail.com', '$2y$10$lF1n1U.EkAgVgQsNiGsdj.MZMEKkB74frPYKpnLB.bUV96ZURuRT.', 'pegawai', '2026-04-08 05:20:17', '2026-04-08 05:20:17'),
(19, 'Ajeng Kartini', 'ajeng@gmail.com', '$2y$10$G/Ta8cHDNcWfIjaz95UAROjD3ymwApN.AlzG/45CDiHXcSz6UUJ4G', 'pegawai', '2026-04-08 05:20:47', '2026-04-08 05:20:47'),
(20, 'Dewi Matahari', 'dewi@gmail.com', '$2y$10$DDo4a39DfyPkRrgQEPOyqO4vVqowDgbuxRKdS0b6Xi6al4VqOrtYq', 'pegawai', '2026-04-08 05:21:18', '2026-04-08 05:21:18');

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
,`user_role` enum('pegawai','admin','teknisi')
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
-- Indexes for table `log_teknisi`
--
ALTER TABLE `log_teknisi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teknisi` (`teknisi_id`),
  ADD KEY `idx_pengaduan` (`pengaduan_id`);

--
-- Indexes for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_teknisi` (`assigned_teknisi_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `komentar_galeri`
--
ALTER TABLE `komentar_galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `log_teknisi`
--
ALTER TABLE `log_teknisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengaduan`
--
ALTER TABLE `pengaduan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `rating_galeri`
--
ALTER TABLE `rating_galeri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
-- Constraints for table `log_teknisi`
--
ALTER TABLE `log_teknisi`
  ADD CONSTRAINT `log_teknisi_ibfk_1` FOREIGN KEY (`teknisi_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `log_teknisi_ibfk_2` FOREIGN KEY (`pengaduan_id`) REFERENCES `pengaduan` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD CONSTRAINT `fk_pengaduan_teknisi` FOREIGN KEY (`assigned_teknisi_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
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
