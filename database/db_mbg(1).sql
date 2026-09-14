-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2026 at 09:22 AM
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
-- Database: `db_mbg`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` bigint(20) NOT NULL,
  `dataset_id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `comment` text NOT NULL,
  `case_folding` text DEFAULT NULL,
  `cleaning` text DEFAULT NULL,
  `normalization` text DEFAULT NULL,
  `tokenizing` text DEFAULT NULL,
  `stopword` text DEFAULT NULL,
  `stemming` text DEFAULT NULL,
  `sentiment` enum('Positif','Negatif','Netral') DEFAULT NULL,
  `prediction` enum('Positif','Negatif','Netral') DEFAULT NULL,
  `probability` decimal(8,5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `dataset_id`, `username`, `comment`, `case_folding`, `cleaning`, `normalization`, `tokenizing`, `stopword`, `stemming`, `sentiment`, `prediction`, `probability`, `created_at`) VALUES
(1, 5, 'amar', 'Makanannya enak sekali', 'makanannya enak sekali', 'makanannya enak sekali', 'makanannya enak sekali', '[\"makanannya\",\"enak\",\"sekali\"]', 'makanannya enak sekali', 'makan enak sekali', 'Positif', NULL, NULL, '2026-07-30 03:15:39'),
(2, 5, 'budi', 'Pelayanannya buruk sekali', 'pelayanannya buruk sekali', 'pelayanannya buruk sekali', 'pelayanannya buruk sekali', '[\"pelayanannya\",\"buruk\",\"sekali\"]', 'pelayanannya buruk sekali', 'layan buruk sekali', 'Negatif', NULL, NULL, '2026-07-30 03:15:39'),
(3, 5, 'ani', 'Sangat membantu', 'sangat membantu', 'sangat membantu', 'sangat membantu', '[\"sangat\",\"membantu\"]', 'sangat membantu', 'sangat bantu', 'Positif', NULL, NULL, '2026-07-30 03:15:39'),
(4, 5, 'eko', 'Tidak bagus', 'tidak bagus', 'tidak bagus', 'tidak bagus', '[\"tidak\",\"bagus\"]', 'bagus', 'bagus', 'Negatif', NULL, NULL, '2026-07-30 03:15:39'),
(5, 5, 'rina', 'Lumayan saja', 'lumayan saja', 'lumayan saja', 'lumayan saja', '[\"lumayan\",\"saja\"]', 'lumayan', 'lumayan', 'Netral', NULL, NULL, '2026-07-30 03:15:39'),
(6, 6, '@akbaralmunawir', 'SEDANGKAN MBG GUA🍗🍳🍗🍳🍗🍳', 'sedangkan mbg gua🍗🍳🍗🍳🍗🍳', 'sedangkan mbg gua', 'sedangkan mbg gua', '[\"sedangkan\",\"mbg\",\"gua\"]', 'mbg gua', 'mbg gua', 'Negatif', NULL, NULL, '2026-07-30 16:08:14'),
(7, 6, '@tiakkk_3', 'ko bda,buahku ko jruk ma smngka Mulu sih', 'ko bda,buahku ko jruk ma smngka mulu sih', 'ko bdabuahku ko jruk ma smngka mulu sih', 'ko bdabuahku ko jruk ma smngka mulu sih', '[\"ko\",\"bdabuahku\",\"ko\",\"jruk\",\"ma\",\"smngka\",\"mulu\",\"sih\"]', 'ko bdabuahku ko jruk ma smngka mulu sih', 'ko bdabuahku ko jruk ma smngka mulu sih', 'Negatif', NULL, NULL, '2026-07-30 16:08:14'),
(8, 6, '@_rismaoplnoberbau', 'MBG GW MAH TIAP HARI TELOR DADAR MULU MALES GW😭😭', 'mbg gw mah tiap hari telor dadar mulu males gw😭😭', 'mbg gw mah tiap hari telor dadar mulu males gw', 'mbg gw mah tiap hari telor dadar mulu males gw', '[\"mbg\",\"gw\",\"mah\",\"tiap\",\"hari\",\"telor\",\"dadar\",\"mulu\",\"males\",\"gw\"]', 'mbg gw mah tiap hari telor dadar mulu males gw', 'mbg gw mah tiap hari telor dadar mulu males gw', 'Negatif', NULL, NULL, '2026-07-30 16:08:14'),
(9, 6, '@anas_al171', 'tag mbg kalian', 'tag mbg kalian', 'tag mbg kalian', 'tag mbg kalian', '[\"tag\",\"mbg\",\"kalian\"]', 'tag mbg kalian', 'tag mbg kalian', 'Netral', NULL, NULL, '2026-07-30 16:08:14'),
(10, 6, '@nuclrear_mineyy', 'mbg gw bakso', 'mbg gw bakso', 'mbg gw bakso', 'mbg gw bakso', '[\"mbg\",\"gw\",\"bakso\"]', 'mbg gw bakso', 'mbg gw bakso', 'Netral', NULL, NULL, '2026-07-30 16:08:14'),
(11, 6, '@dicky.ardana3', 'knp mbg daerah lain enak enak didaerah gwe mbg sawi putih kalo gak ya buncis [angry]', 'knp mbg daerah lain enak enak didaerah gwe mbg sawi putih kalo gak ya buncis [angry]', 'knp mbg daerah lain enak enak didaerah gwe mbg sawi putih kalo gak ya buncis angry', 'knp mbg daerah lain enak enak didaerah gwe mbg sawi putih kalo gak ya buncis angry', '[\"knp\",\"mbg\",\"daerah\",\"lain\",\"enak\",\"enak\",\"didaerah\",\"gwe\",\"mbg\",\"sawi\",\"putih\",\"kalo\",\"gak\",\"ya\",\"buncis\",\"angry\"]', 'knp mbg daerah enak enak didaerah gwe mbg sawi putih kalo gak buncis angry', 'knp mbg daerah enak enak daerah gwe mbg sawi putih kalo gak buncis angry', 'Netral', NULL, NULL, '2026-07-30 16:08:14'),
(12, 6, '@nanac_09', 'tag sppg kalian 🗿😭', 'tag sppg kalian 🗿😭', 'tag sppg kalian', 'tag sppg kalian', '[\"tag\",\"sppg\",\"kalian\"]', 'tag sppg kalian', 'tag sppg kalian', 'Netral', NULL, NULL, '2026-07-30 16:08:14'),
(13, 6, '@almira_nidia', 'tahu ny 2 segede gede ujung jempoL buah ny salak 😁', 'tahu ny 2 segede gede ujung jempol buah ny salak 😁', 'tahu ny segede gede ujung jempol buah ny salak', 'tahu ny segede gede ujung jempol buah ny salak', '[\"tahu\",\"ny\",\"segede\",\"gede\",\"ujung\",\"jempol\",\"buah\",\"ny\",\"salak\"]', 'tahu ny segede gede ujung jempol buah ny salak', 'tahu ny gede gede ujung jempol buah ny salak', 'Negatif', NULL, NULL, '2026-07-30 16:08:14'),
(14, 6, '@user701360362673', 'mbg anak ku selalu buahnya pisang kpok engak kelengkeng lima biji', 'mbg anak ku selalu buahnya pisang kpok engak kelengkeng lima biji', 'mbg anak ku selalu buahnya pisang kpok engak kelengkeng lima biji', 'mbg anak ku selalu buahnya pisang kpok engak kelengkeng lima biji', '[\"mbg\",\"anak\",\"ku\",\"selalu\",\"buahnya\",\"pisang\",\"kpok\",\"engak\",\"kelengkeng\",\"lima\",\"biji\"]', 'mbg anak ku selalu buahnya pisang kpok engak kelengkeng lima biji', 'mbg anak ku selalu buah pisang kpok engak kelengkeng lima biji', 'Negatif', NULL, NULL, '2026-07-30 16:08:14'),
(15, 6, '@givelysia', '𝖡𝗎𝖺𝗁 𝖼𝗎𝗆𝖺 𝗌𝖾𝖻𝗂𝗃𝗂', '𝖡𝗎𝖺𝗁 𝖼𝗎𝗆𝖺 𝗌𝖾𝖻𝗂𝗃𝗂', '', '', '[]', '', '', 'Negatif', NULL, NULL, '2026-07-30 16:08:14');

-- --------------------------------------------------------

--
-- Table structure for table `datasets`
--

CREATE TABLE `datasets` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `total_comments` int(11) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `status` enum('Uploaded','Preprocessed','Trained') DEFAULT 'Uploaded',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `datasets`
--

INSERT INTO `datasets` (`id`, `filename`, `total_comments`, `uploaded_by`, `status`, `created_at`) VALUES
(5, 'dataset.csv', 5, 1, 'Trained', '2026-07-30 03:15:39'),
(6, 'scrape_1785427694076.csv', 10, 1, 'Preprocessed', '2026-07-30 16:08:14');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `models`
--

CREATE TABLE `models` (
  `id` int(11) NOT NULL,
  `dataset_id` int(11) NOT NULL,
  `algorithm` varchar(100) DEFAULT NULL,
  `model_file` varchar(255) DEFAULT NULL,
  `vectorizer_file` varchar(255) DEFAULT NULL,
  `accuracy` decimal(5,2) DEFAULT NULL,
  `precision_score` decimal(5,2) DEFAULT NULL,
  `recall_score` decimal(5,2) DEFAULT NULL,
  `f1_score` decimal(5,2) DEFAULT NULL,
  `trained_rows` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `models`
--

INSERT INTO `models` (`id`, `dataset_id`, `algorithm`, `model_file`, `vectorizer_file`, `accuracy`, `precision_score`, `recall_score`, `f1_score`, `trained_rows`, `created_at`) VALUES
(1, 5, 'Naive Bayes + TF-IDF', 'model.pkl', 'tfidf.pkl', 0.00, 0.00, 0.00, 0.00, 5, '2026-07-30 12:50:49'),
(2, 6, 'Naive Bayes + TF-IDF', 'model.pkl', 'tfidf.pkl', 1.00, 1.00, 1.00, 1.00, 9, '2026-07-30 16:09:40'),
(3, 5, 'Naive Bayes + TF-IDF', 'model.pkl', 'tfidf.pkl', 0.00, 0.00, 0.00, 0.00, 5, '2026-07-30 20:02:21');

-- --------------------------------------------------------

--
-- Table structure for table `predictions`
--

CREATE TABLE `predictions` (
  `id` bigint(20) NOT NULL,
  `model_id` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `preprocessing` text DEFAULT NULL,
  `prediction` enum('Positif','Negatif','Netral') DEFAULT NULL,
  `probability` decimal(8,5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `predictions`
--

INSERT INTO `predictions` (`id`, `model_id`, `comment`, `preprocessing`, `prediction`, `probability`, `created_at`) VALUES
(1, 1, 'Makanannya enak sekali', 'makan enak sekali', 'Positif', 0.52431, '2026-07-30 12:51:22'),
(2, 2, 'enak bang', 'enak bang', 'Netral', 0.56146, '2026-07-30 16:10:14'),
(3, 2, 'kontol', 'kontol', 'Negatif', 0.55556, '2026-07-30 16:10:22'),
(4, 2, 'apasih bambang', 'apasih bambang', 'Negatif', 0.55556, '2026-07-30 16:10:33'),
(5, 2, 'anjay keren bang', 'anjay keren bang', 'Negatif', 0.55556, '2026-07-30 16:10:45'),
(6, 2, 'kok gini', 'kok gin', 'Negatif', 0.55556, '2026-07-30 16:11:30'),
(7, 2, 'yah keren', 'yah keren', 'Negatif', 0.55556, '2026-07-30 16:11:39'),
(8, 2, 'yah keren', 'yah keren', 'Negatif', 0.55556, '2026-07-30 16:11:40'),
(9, 2, 'mbg cantik', 'mbg cantik', 'Negatif', 0.50693, '2026-07-30 20:01:02'),
(10, 2, 'mbg cantik\nmbg mas bahlil ganteng', 'mbg cantik mbg mas bahlil ganteng', 'Negatif', 0.50693, '2026-07-30 20:01:19'),
(11, 3, 'kontol', 'kontol', 'Negatif', 0.40000, '2026-07-30 20:04:31'),
(12, 3, 'bajingan', 'bajing', 'Negatif', 0.40000, '2026-07-30 20:04:44'),
(13, 3, 'bajingan enak', 'bajing enak', 'Positif', 0.49569, '2026-07-30 20:04:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Operator') DEFAULT 'Operator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin', 'admin123', 'Admin', '2026-07-30 03:15:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dataset_id` (`dataset_id`);

--
-- Indexes for table `datasets`
--
ALTER TABLE `datasets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `models`
--
ALTER TABLE `models`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dataset_id` (`dataset_id`);

--
-- Indexes for table `predictions`
--
ALTER TABLE `predictions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `model_id` (`model_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `datasets`
--
ALTER TABLE `datasets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `models`
--
ALTER TABLE `models`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `predictions`
--
ALTER TABLE `predictions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `datasets`
--
ALTER TABLE `datasets`
  ADD CONSTRAINT `datasets_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `models`
--
ALTER TABLE `models`
  ADD CONSTRAINT `models_ibfk_1` FOREIGN KEY (`dataset_id`) REFERENCES `datasets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `predictions`
--
ALTER TABLE `predictions`
  ADD CONSTRAINT `predictions_ibfk_1` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
