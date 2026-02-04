-- Backup Perpustakaan
-- Date: 2026-02-04 05:50:40

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `activity_logs`
INSERT INTO `activity_logs` VALUES ('1', '1', 'admin', 'update', 'Mengubah kategori: TEKNOLOGI', '2026-02-03 18:46:50');
INSERT INTO `activity_logs` VALUES ('2', '1', 'admin', 'update', 'Mengubah data buku: Desain Antarmuka Pengguna', '2026-02-03 18:46:58');
INSERT INTO `activity_logs` VALUES ('3', '1', 'admin', 'update', 'Mengubah data buku: Pengantar Manajemen', '2026-02-03 18:47:06');
INSERT INTO `activity_logs` VALUES ('4', '1', 'admin', 'update', 'Mengubah data buku: Sejarah Nusantara', '2026-02-03 19:34:28');
INSERT INTO `activity_logs` VALUES ('5', '1', 'admin', 'update', 'Mengubah data buku: Desain Antarmuka Pengguna', '2026-02-03 19:34:44');
INSERT INTO `activity_logs` VALUES ('6', '1', 'admin', 'update', 'Mengubah kategori: BUDAYA', '2026-02-03 19:48:49');
INSERT INTO `activity_logs` VALUES ('7', '1', 'admin', 'create', 'Menambah kategori: KESENIAN', '2026-02-03 19:49:00');

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
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `views` int DEFAULT '0',
  `downloads` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `books`
INSERT INTO `books` VALUES ('1', 'BK-001', '978-789-456-123', 'Pemrograman Web Modern', 'Andi Pratama', 'TEKNOLOGI', '2024', 'https://picsum.photos/seed/bk001/400/600', NULL, 'assets/uploads/books/book_1770109130_6025.pdf', 'Panduan lengkap membangun aplikasi web modern.', '2026-02-03 13:40:32', '0', '0');
INSERT INTO `books` VALUES ('2', 'BK-002', '123-123-123-123', 'Dasar-Dasar Data Science', 'Siti Rahma', 'SAINS', '2023', 'https://picsum.photos/seed/bk002/400/600', NULL, 'assets/uploads/books/book_1770110296_4289.pdf', 'Konsep dasar data science dengan studi kasus.', '2026-02-03 13:40:32', '2', '0');
INSERT INTO `books` VALUES ('3', 'BK-003', '', 'Pengantar Manajemen', 'Budi Santoso', 'SAINS', '2022', 'https://picsum.photos/seed/bk003/400/600', NULL, 'assets/uploads/books/book_1770112675_6751.pdf', 'Konsep manajemen modern untuk organisasi.', '2026-02-03 13:40:32', '6', '0');
INSERT INTO `books` VALUES ('4', 'BK-004', '', 'Sejarah Nusantara', 'Dewi Kartika', 'SEJARAH', '2021', 'https://picsum.photos/seed/bk004/400/600', NULL, 'assets/uploads/books/book_1770122068_6762.pdf', 'Perjalanan sejarah nusantara.', '2026-02-03 13:40:32', '20', '10');
INSERT INTO `books` VALUES ('5', 'BK-005', '', 'Desain Antarmuka Pengguna', 'Rizky Maulana', 'TEKNOLOGI', '2025', 'https://picsum.photos/seed/bk005/400/600', NULL, 'assets/uploads/books/book_1770122084_4995.pdf', 'Prinsip UI/UX untuk pengalaman pengguna.', '2026-02-03 13:40:32', '3', '0');

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
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES ('1', 'admin', '$2y$10$ttpq9TMQIYdCOF5RXcgYu.6PEdM68joTvlzBOrcFTE90q9jtQvjq.', '2026-02-03 13:40:32', 'Admin', NULL);
INSERT INTO `users` VALUES ('2', 'admin2', '$2y$10$Xlg/iTY.jZ7/.cbBn65bvejXwc3EwuMGcUxfBoeYmZg1uU2xgquNW', '2026-02-03 17:51:53', 'NUR HUDA', NULL);

-- Table structure for table `visitors`
DROP TABLE IF EXISTS `visitors`;
CREATE TABLE `visitors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `purpose` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `visit_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

SET FOREIGN_KEY_CHECKS=1;
