<?php
require_once 'config.php';
$pdo = db();

try {
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_kategori VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Tabel categories berhasil dibuat/diperiksa.";
} catch (PDOException $e) {
    echo "Gagal membuat tabel: " . $e->getMessage();
}
?>