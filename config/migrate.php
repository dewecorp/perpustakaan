<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$DB_NAME = 'perpustakaan';
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$charset = 'utf8mb4';

try {
  $pdo = new PDO("mysql:host=$DB_HOST;charset=$charset", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET $charset COLLATE {$charset}_general_ci");
  $pdo->exec("USE `$DB_NAME`");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(50) UNIQUE NOT NULL,
      password VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS books (
      id INT AUTO_INCREMENT PRIMARY KEY,
      code VARCHAR(20) UNIQUE NOT NULL,
      title VARCHAR(255) NOT NULL,
      author VARCHAR(255) DEFAULT '',
      category VARCHAR(100) DEFAULT '',
      year INT DEFAULT 0,
      cover_url TEXT,
      description TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) UNIQUE NOT NULL,
      description TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS visitors (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      purpose VARCHAR(255) NOT NULL,
      visit_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

  // Add views and downloads columns if not exist
  $cols = $pdo->query("DESCRIBE books")->fetchAll(PDO::FETCH_COLUMN);
  if (!in_array('views', $cols)) {
      $pdo->exec("ALTER TABLE books ADD COLUMN views INT DEFAULT 0");
  }
  if (!in_array('downloads', $cols)) {
      $pdo->exec("ALTER TABLE books ADD COLUMN downloads INT DEFAULT 0");
  }

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
      setting_key VARCHAR(50) PRIMARY KEY,
      setting_value TEXT
    ) ENGINE=InnoDB");

  $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  if ($count === 0) {
    $hash = password_hash('admin', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)")->execute(['admin', $hash]);
  }

  $countB = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
  if ($countB === 0) {
    $books = [
      ['BK-001','Pemrograman Web Modern','Andi Pratama','Teknologi',2024,'https://picsum.photos/seed/bk001/400/600','Panduan lengkap membangun aplikasi web modern.'],
      ['BK-002','Dasar-Dasar Data Science','Siti Rahma','Sains',2023,'https://picsum.photos/seed/bk002/400/600','Konsep dasar data science dengan studi kasus.'],
      ['BK-003','Pengantar Manajemen','Budi Santoso','Bisnis',2022,'https://picsum.photos/seed/bk003/400/600','Konsep manajemen modern untuk organisasi.'],
      ['BK-004','Sejarah Nusantara','Dewi Kartika','Sejarah',2021,'https://picsum.photos/seed/bk004/400/600','Perjalanan sejarah nusantara.'],
      ['BK-005','Desain Antarmuka Pengguna','Rizky Maulana','Desain',2025,'https://picsum.photos/seed/bk005/400/600','Prinsip UI/UX untuk pengalaman pengguna.'],
    ];
    $stmt = $pdo->prepare("INSERT INTO books (code,title,author,category,year,cover_url,description) VALUES (?,?,?,?,?,?,?)");
    foreach ($books as $b) { $stmt->execute($b); }
  }

  echo "Migrasi selesai. DB dan tabel siap.\n";
} catch (PDOException $e) {
  http_response_code(500);
  echo "Error migrasi: " . htmlspecialchars($e->getMessage());
  exit;
}
