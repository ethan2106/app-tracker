-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 29, 2025 at 06:12 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `app_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `apps`
--

CREATE TABLE `apps` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `version` varchar(50) NOT NULL,
  `last_checked` datetime DEFAULT NULL,
  `latest_version` varchar(50) DEFAULT NULL,
  `update_source` varchar(100) DEFAULT NULL,
  `latest_version_norm` varchar(50) DEFAULT NULL,
  `update_available` tinyint DEFAULT NULL,
  `last_error` text,
  `update_type` varchar(20) DEFAULT NULL,
  `update_url` varchar(500) DEFAULT NULL,
  `update_regex` varchar(255) DEFAULT NULL,
  `latest_download_url` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `apps`
--

INSERT INTO `apps` (`id`, `name`, `version`, `last_checked`, `latest_version`, `update_source`, `latest_version_norm`, `update_available`, `last_error`, `update_type`, `update_url`, `update_regex`, `latest_download_url`) VALUES
(1, 'Firefox', '146.0.1', '2025-12-29 07:11:37', '146.0.1', 'mozilla', '146..1', 0, NULL, NULL, NULL, NULL, 'https://www.mozilla.org/firefox/download/'),
(2, 'Mozilla Firefox (x64 fr)', '146.0.1', '2025-12-29 07:06:53', '146.0.1', 'mozilla', '146..1', 0, NULL, NULL, NULL, NULL, NULL),
(3, 'Microsoft Visual Studio Code (User)', '1.107.1', '2025-12-29 07:06:52', '1.107.1', 'microsoft/vscode', '1.107.1', 0, NULL, NULL, NULL, NULL, NULL),
(4, 'VLC media player', '3.0.21', '2025-12-29 07:06:57', '3.0.21', 'vlc', '3..21', 0, NULL, NULL, NULL, NULL, NULL),
(5, 'Notepad++ (64-bit x64)', '8.9', '2025-12-29 07:06:55', 'v8.9', 'notepad-plus-plus/notepad-plus-plus', '8.9', 0, NULL, NULL, NULL, NULL, NULL),
(6, '7-Zip 25.01 (x64)', '25.01', '2025-12-29 07:11:37', '25.01', '7zip', '25.1', 0, NULL, NULL, NULL, NULL, 'https://www.7-zip.org/download.html'),
(7, 'Git', '2.52.0', '2025-12-29 07:11:47', 'v2.52.0.windows.1', 'git', '2.52.0', 0, NULL, NULL, NULL, NULL, 'https://gitforwindows.org/'),
(8, 'Node.js', '24.12.0', '2025-12-29 07:06:54', 'v25.2.1', 'nodejs', '25.2.1', 1, NULL, NULL, NULL, NULL, NULL),
(9, 'Python 3.12.7 (64-bit)', '3.12.7', '2025-12-29 07:06:56', '2.0.1', 'python', '2..1', 0, NULL, NULL, NULL, NULL, NULL),
(10, 'Mullvad VPN 2025.14.0', '2025.14.0', '2025-12-29 07:06:54', '2025.14', 'mullvad/mullvadvpn-app', '2025.14', 0, NULL, NULL, NULL, NULL, NULL),
(11, 'ShareX', '18.0.1', '2025-12-29 07:06:56', 'v18.0.1', 'ShareX/ShareX', '18..1', 0, NULL, NULL, NULL, NULL, NULL),
(12, 'ImageGlass', '9.4.0.1120', '2025-12-29 07:06:52', '9.4.0.1120', 'd2phap/ImageGlass', '9.4.0.1120', 0, NULL, NULL, NULL, NULL, NULL),
(13, 'SumatraPDF', '3.5.2', '2025-12-29 07:06:57', '3.5.2rel', 'sumatrapdfreader/sumatrapdf', '3.5.2', 0, NULL, NULL, NULL, NULL, NULL),
(14, 'bcuninstaller', '5.9.0', '2025-12-29 07:11:37', 'v5.9', 'bcuninstaller', '5.9', 0, 'Impossible de récupérer la dernière version.', '', NULL, NULL, NULL),
(15, 'Mullvad Browser', '15.0.3', '2025-12-29 07:06:53', '15.0.3', 'mullvad/mullvad-browser', '15..3', 0, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `update_providers`
--

CREATE TABLE `update_providers` (
  `provider_key` varchar(100) NOT NULL,
  `latest_url` text NOT NULL,
  `latest_regex` varchar(255) NOT NULL,
  `download_url` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `download_regex` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `update_providers`
--

INSERT INTO `update_providers` (`provider_key`, `latest_url`, `latest_regex`, `download_url`, `created_at`, `download_regex`) VALUES
('7zip', 'https://www.7-zip.org/', '/Download\\s+7-Zip\\s+([0-9\\.]+)/', 'https://www.7-zip.org/download.html', '2025-12-29 06:04:51', NULL),
('git', 'https://api.github.com/repos/git-for-windows/git/releases/latest', '/\"tag_name\"\\s*:\\s*\"([^\"]+)\"/', 'https://gitforwindows.org/', '2025-12-29 06:04:51', NULL),
('mozilla', 'https://product-details.mozilla.org/1.0/firefox_versions.json', '/\"LATEST_FIREFOX_VERSION\"\\s*:\\s*\"([^\"]+)\"/', 'https://www.mozilla.org/firefox/download/', '2025-12-29 06:04:51', NULL),
('nodejs', 'https://nodejs.org/dist/index.json', '/\"version\"\\s*:\\s*\"(v[^\"]+)\"/', 'https://nodejs.org/', '2025-12-29 06:04:51', NULL),
('python', 'https://www.python.org/api/v2/downloads/release/?is_published=yes&is_prerelease=false', '/\"name\"\\s*:\\s*\"Python\\s+([0-9\\.]+)\"/', 'https://www.python.org/downloads/', '2025-12-29 06:04:51', NULL),
('vlc', 'https://www.videolan.org/vlc/download-windows.html', '/vlc-([\\d\\.]+)-win32\\.exe/', 'https://www.videolan.org/vlc/download-windows.html', '2025-12-29 06:04:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `update_provider_aliases`
--

CREATE TABLE `update_provider_aliases` (
  `alias` varchar(100) NOT NULL,
  `provider_key` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `apps`
--
ALTER TABLE `apps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `update_providers`
--
ALTER TABLE `update_providers`
  ADD PRIMARY KEY (`provider_key`);

--
-- Indexes for table `update_provider_aliases`
--
ALTER TABLE `update_provider_aliases`
  ADD PRIMARY KEY (`alias`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `apps`
--
ALTER TABLE `apps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
