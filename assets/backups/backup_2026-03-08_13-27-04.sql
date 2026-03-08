-- Backup Perpustakaan
-- Date: 2026-03-08 13:27:04

SET FOREIGN_KEY_CHECKS=0;

-- Table structure for table `activity_logs`
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `activity_logs`
INSERT INTO `activity_logs` VALUES ('54', '1', 'admin', 'login', 'User logged in', '2026-03-08 13:19:37');
INSERT INTO `activity_logs` VALUES ('55', '1', 'admin', 'logout', 'User logged out', '2026-03-08 13:20:55');
INSERT INTO `activity_logs` VALUES ('56', '1', 'admin', 'login', 'User logged in', '2026-03-08 13:21:48');
INSERT INTO `activity_logs` VALUES ('57', '1', 'admin', 'login', 'User logged in', '2026-03-08 13:26:09');
INSERT INTO `activity_logs` VALUES ('58', '1', 'admin', 'logout', 'User logged out', '2026-03-08 13:26:23');
INSERT INTO `activity_logs` VALUES ('59', '1', 'admin', 'login', 'User logged in', '2026-03-08 13:26:26');
INSERT INTO `activity_logs` VALUES ('60', '1', 'admin', 'logout', 'User logged out', '2026-03-08 13:26:33');
INSERT INTO `activity_logs` VALUES ('61', '1', 'admin', 'login', 'User logged in', '2026-03-08 13:26:37');

-- Table structure for table `books`
DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `isbn` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '',
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `author` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `year` int DEFAULT '0',
  `cover_url` text COLLATE utf8mb4_general_ci,
  `cover_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `book_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `book_url` text COLLATE utf8mb4_general_ci,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `views` int DEFAULT '0',
  `downloads` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `books`
INSERT INTO `books` VALUES ('6', 'SIBI-689ffe6d', '', 'Kelas-III-tema-6-Energi-Dan-Perubahanya', 'SIBI', 'BUKU PELAJARAN', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Kelas-III-tema-6-Energi-Dan-Perubahanya', 'Kelas-III-tema-6-Energi-Dan-Perubahanya', '2026-02-18 08:37:29', '0', '0');
INSERT INTO `books` VALUES ('8', 'SIBI-689ffe', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'BUKU PELAJARAN', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', '2026-02-18 08:41:42', '0', '0');
INSERT INTO `books` VALUES ('9', 'SIBI-689ffe-1', '', 'ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Tidak diketahui', 'BUKU PELAJARAN', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'SIBI: https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 09:57:28', '0', '0');
INSERT INTO `books` VALUES ('10', 'SIBI-689ffe-2', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', '2026-02-18 09:58:23', '0', '0');
INSERT INTO `books` VALUES ('11', 'SIBI-689ffe-3', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('12', 'SIBI-689ffe-4', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('13', 'SIBI-689ffe-5', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('14', 'SIBI-689ffe-6', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-VII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-VII', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('15', 'SIBI-689ffe-7', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-X', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-X', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('16', 'SIBI-689ffe-8', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-VIII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-VIII', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('17', 'SIBI-689ffe-9', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-IX', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-IX', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('18', 'SIBI-689ffe-10', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Buku-Guru-Pendidikan-Jasmani-Olahraga-Kesehatan-Kelas-VII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Buku-Guru-Pendidikan-Jasmani-Olahraga-Kesehatan-Kelas-VII', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('19', 'SIBI-689ffe-11', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('20', 'SIBI-689ffe-12', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('21', 'SIBI-689ffe-13', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('22', 'SIBI-689ffe-14', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('23', 'SIBI-689ffe-15', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', '2026-02-18 10:01:25', '0', '0');
INSERT INTO `books` VALUES ('24', 'SIBI-689ffe-16', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('25', 'SIBI-689ffe-17', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'BUKU PELAJARAN', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('26', 'SIBI-689ffe-18', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('27', 'SIBI-689ffe-19', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-VII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-VII', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('28', 'SIBI-689ffe-20', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-X', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-X', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('29', 'SIBI-689ffe-21', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-VIII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-VIII', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('30', 'SIBI-689ffe-22', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-IX', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-IX', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('31', 'SIBI-689ffe-23', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Buku-Guru-Pendidikan-Jasmani-Olahraga-Kesehatan-Kelas-VII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Buku-Guru-Pendidikan-Jasmani-Olahraga-Kesehatan-Kelas-VII', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('32', 'SIBI-689ffe-24', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('33', 'SIBI-689ffe-25', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('34', 'SIBI-689ffe-26', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('35', 'SIBI-689ffe-27', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('36', 'SIBI-689ffe-28', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', '2026-02-18 10:11:06', '0', '0');
INSERT INTO `books` VALUES ('37', 'SIBI-689ffe-29', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', '2026-02-18 10:12:03', '0', '0');
INSERT INTO `books` VALUES ('38', 'SIBI-689ffe-30', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', '2026-02-18 10:12:03', '0', '0');
INSERT INTO `books` VALUES ('39', 'SIBI-689ffe-31', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', '2026-02-18 10:12:03', '0', '0');
INSERT INTO `books` VALUES ('40', 'SIBI-689ffe-32', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-VII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-VII', '2026-02-18 10:12:03', '0', '0');
INSERT INTO `books` VALUES ('41', 'SIBI-689ffe-33', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-X', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-X', '2026-02-18 10:12:03', '0', '0');
INSERT INTO `books` VALUES ('42', 'SIBI-689ffe-34', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-VIII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-VIII', '2026-02-18 10:12:03', '0', '0');
INSERT INTO `books` VALUES ('43', 'SIBI-689ffe-35', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-IX', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-IX', '2026-02-18 10:12:04', '0', '0');
INSERT INTO `books` VALUES ('44', 'SIBI-689ffe-36', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Buku-Guru-Pendidikan-Jasmani-Olahraga-Kesehatan-Kelas-VII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Buku-Guru-Pendidikan-Jasmani-Olahraga-Kesehatan-Kelas-VII', '2026-02-18 10:12:04', '0', '0');
INSERT INTO `books` VALUES ('45', 'SIBI-689ffe-37', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', '2026-02-18 10:12:04', '0', '0');
INSERT INTO `books` VALUES ('46', 'SIBI-689ffe-38', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:12:04', '0', '0');
INSERT INTO `books` VALUES ('47', 'SIBI-689ffe-39', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:12:04', '0', '0');
INSERT INTO `books` VALUES ('48', 'SIBI-689ffe-40', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:12:04', '0', '0');
INSERT INTO `books` VALUES ('49', 'SIBI-689ffe-41', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', '2026-02-18 10:12:04', '0', '0');
INSERT INTO `books` VALUES ('50', 'SIBI-689ffe-42', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('51', 'SIBI-689ffe-43', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('52', 'SIBI-689ffe-44', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('53', 'SIBI-689ffe-45', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-VII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-VII', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('54', 'SIBI-689ffe-46', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-X', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-dan-Kesehatan-Kelas-X', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('55', 'SIBI-689ffe-47', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-VIII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-VIII', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('56', 'SIBI-689ffe-48', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-IX', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Pendidikan-Jasmani-Olahraga-Dan-Kesehatan-Kelas-IX', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('57', 'SIBI-689ffe-49', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/Buku-Guru-Pendidikan-Jasmani-Olahraga-Kesehatan-Kelas-VII', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/Buku-Guru-Pendidikan-Jasmani-Olahraga-Kesehatan-Kelas-VII', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('58', 'SIBI-689ffe-50', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('59', 'SIBI-689ffe-51', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('60', 'SIBI-689ffe-52', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('61', 'SIBI-689ffe-53', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('62', 'SIBI-689ffe-54', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'SD/MI - Kurikulum Merdeka', '0', 'https://ik.imagekit.io/pusatperbukuan/Assets/SIBI.png', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', 'Imported from SIBI: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', '2026-02-18 10:19:18', '0', '0');
INSERT INTO `books` VALUES ('64', 'IMP-689ffe', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'BUKU PELAJARAN', '2023', '', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', 'Imported from: https://buku.kemendikdasmen.go.id/katalog/buku-kurikulum-merdeka', '2026-02-18 11:40:58', '0', '0');
INSERT INTO `books` VALUES ('65', 'IMP-689ffe-1', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'Ebook', '0', '', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', 'Imported from: https://buku.kemendikdasmen.go.id/katalog/buku-teks-k13', '2026-02-18 11:40:58', '0', '0');
INSERT INTO `books` VALUES ('66', 'IMP-689ffe-2', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'Ebook', '0', '', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', 'Imported from: https://buku.kemendikdasmen.go.id/katalog/buku-non-teks', '2026-02-18 11:40:58', '0', '0');
INSERT INTO `books` VALUES ('67', 'IMP-689ffe-3', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'Ebook', '0', '', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', 'Imported from: https://buku.kemendikdasmen.go.id/katalog/pendidikan-jasmani-olahraga-dan-kesehatan-untuk-sdmi-kelas-ii', '2026-02-18 11:40:58', '0', '0');
INSERT INTO `books` VALUES ('68', 'IMP-689ffe-4', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'Ebook', '0', '', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from: https://buku.kemendikdasmen.go.id/katalog/ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 11:40:58', '0', '0');
INSERT INTO `books` VALUES ('69', 'IMP-689ffe-5', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'Ebook', '0', '', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-ilmu-pengetahuan-alam-dan-sosial-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 11:40:58', '0', '0');
INSERT INTO `books` VALUES ('70', 'IMP-689ffe-6', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'Ebook', '0', '', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', 'Imported from: https://buku.kemendikdasmen.go.id/katalog/bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi-edisi-revisi', '2026-02-18 11:40:58', '0', '0');
INSERT INTO `books` VALUES ('71', 'IMP-689ffe-7', '', 'SIBI - Sistem Informasi Perbukuan Indonesia', 'Tidak diketahui', 'Ebook', '0', '', NULL, NULL, 'https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', 'Imported from: https://buku.kemendikdasmen.go.id/katalog/panduan-guru-bahasa-indonesia-anak-anak-yang-mengubah-dunia-untuk-sdmi-kelas-vi', '2026-02-18 11:40:58', '0', '0');

-- Table structure for table `categories`
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`nama_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `categories`
INSERT INTO `categories` VALUES ('1', 'SAINS', NULL, '2026-02-03 15:33:47', '2026-02-03 15:33:47');
INSERT INTO `categories` VALUES ('2', 'BUKU PELAJARAN', NULL, '2026-02-03 15:34:06', '2026-02-03 15:34:06');
INSERT INTO `categories` VALUES ('3', 'CERITA', NULL, '2026-02-03 15:50:03', '2026-02-03 15:50:03');
INSERT INTO `categories` VALUES ('4', 'BUDAYA', NULL, '2026-02-03 15:50:13', '2026-02-03 19:48:49');
INSERT INTO `categories` VALUES ('5', 'SEJARAH', NULL, '2026-02-03 15:50:34', '2026-02-03 15:50:34');
INSERT INTO `categories` VALUES ('6', 'AGAMA', NULL, '2026-02-03 15:50:44', '2026-02-03 15:50:44');
INSERT INTO `categories` VALUES ('7', 'TEKNOLOGI', NULL, '2026-02-03 15:58:02', '2026-02-03 15:58:02');
INSERT INTO `categories` VALUES ('8', 'KESENIAN', NULL, '2026-02-03 19:49:00', '2026-02-03 19:49:00');

-- Table structure for table `settings`
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `settings`
INSERT INTO `settings` VALUES ('hero_description', '<h3>Akses koleksi buku digital perpustakaan kami dengan mudah. Mulai petualangan literasimu hari ini.</h3>
');
INSERT INTO `settings` VALUES ('hero_title', 'Temukan Buku Favoritmu');
INSERT INTO `settings` VALUES ('school_name', 'MI SULTAN FATTAH JEPARA');

-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Admin',
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES ('1', 'admin', '$2y$10$ttpq9TMQIYdCOF5RXcgYu.6PEdM68joTvlzBOrcFTE90q9jtQvjq.', '2026-02-03 13:40:32', 'Admin', NULL, 'admin');
INSERT INTO `users` VALUES ('2', 'admin2', '$2y$10$eTIuiNiSiTl5HkGI4xfVNO8VKLbyyhwJBIQcGLEk98hQ7XfdHYO3m', '2026-02-03 17:51:53', 'Pustakawan', NULL, 'pustakawan');

-- Table structure for table `visitors`
DROP TABLE IF EXISTS `visitors`;
CREATE TABLE `visitors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `visit_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `visitors`
INSERT INTO `visitors` VALUES ('1', 'admin', 'Melihat Buku: Pemrograman Web Modern', '2026-02-03 16:52:55');
INSERT INTO `visitors` VALUES ('2', 'admin', 'Mengunduh Buku: Pemrograman Web Modern', '2026-02-03 16:53:01');
INSERT INTO `visitors` VALUES ('3', 'admin', 'Mengunduh Buku: Pemrograman Web Modern', '2026-02-03 16:53:01');
INSERT INTO `visitors` VALUES ('4', 'admin', 'Melihat Buku: Pengantar Manajemen', '2026-02-03 17:28:30');
INSERT INTO `visitors` VALUES ('5', 'admin', 'Melihat Buku: Dasar-Dasar Data Science', '2026-02-03 17:48:15');
INSERT INTO `visitors` VALUES ('6', 'admin', 'Melihat Buku: Desain Antarmuka Pengguna', '2026-02-03 19:34:51');
INSERT INTO `visitors` VALUES ('7', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:35:07');
INSERT INTO `visitors` VALUES ('8', 'admin', 'Melihat Buku: Pengantar Manajemen', '2026-02-03 19:35:21');
INSERT INTO `visitors` VALUES ('9', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:35:27');
INSERT INTO `visitors` VALUES ('10', 'admin', 'Melihat Buku: Dasar-Dasar Data Science', '2026-02-03 19:35:35');
INSERT INTO `visitors` VALUES ('11', 'admin', 'Melihat Buku: Desain Antarmuka Pengguna', '2026-02-03 19:35:44');
INSERT INTO `visitors` VALUES ('12', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:35:52');
INSERT INTO `visitors` VALUES ('13', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('14', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('15', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('16', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('17', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('18', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('19', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('20', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('21', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('22', 'admin', 'Mengunduh Buku: Sejarah Nusantara', '2026-02-03 19:36:01');
INSERT INTO `visitors` VALUES ('23', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('24', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('25', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('26', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('27', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('28', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('29', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('30', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('31', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('32', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:39:12');
INSERT INTO `visitors` VALUES ('33', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:41:08');
INSERT INTO `visitors` VALUES ('34', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:42:49');
INSERT INTO `visitors` VALUES ('35', 'admin', 'Melihat Buku: Pengantar Manajemen', '2026-02-03 19:45:10');
INSERT INTO `visitors` VALUES ('36', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:45:14');
INSERT INTO `visitors` VALUES ('37', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:49:48');
INSERT INTO `visitors` VALUES ('38', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:53:05');
INSERT INTO `visitors` VALUES ('39', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:54:49');
INSERT INTO `visitors` VALUES ('40', 'admin', 'Melihat Buku: Pengantar Manajemen', '2026-02-03 19:55:10');
INSERT INTO `visitors` VALUES ('41', 'admin', 'Melihat Buku: Pengantar Manajemen', '2026-02-03 19:57:41');
INSERT INTO `visitors` VALUES ('42', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-03 19:57:49');
INSERT INTO `visitors` VALUES ('43', 'admin', 'Melihat Buku: Desain Antarmuka Pengguna', '2026-02-03 19:58:16');
INSERT INTO `visitors` VALUES ('44', 'admin', 'Melihat Buku: Pengantar Manajemen', '2026-02-04 05:48:56');
INSERT INTO `visitors` VALUES ('45', 'admin', 'Melihat Buku: Pengantar Manajemen', '2026-02-04 08:35:51');
INSERT INTO `visitors` VALUES ('46', 'admin', 'Melihat Buku: Sejarah Nusantara', '2026-02-09 11:53:11');
INSERT INTO `visitors` VALUES ('47', 'admin', 'Melihat Buku: Pemrograman Web Modern', '2026-02-17 09:57:17');
INSERT INTO `visitors` VALUES ('48', 'admin', 'Melihat Buku: Dasar-Dasar Data Science', '2026-02-17 10:11:57');

SET FOREIGN_KEY_CHECKS=1;
