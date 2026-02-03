<?php
require_once 'config/config.php';
$pdo = db();

try {
    // Check if column 'nama_kategori' exists
    $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'nama_kategori'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Check if 'name' exists to rename it
        $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'name'");
        $nameExists = $stmt->fetch();

        if ($nameExists) {
            // Rename 'name' to 'nama_kategori'
            $pdo->exec("ALTER TABLE categories CHANGE name nama_kategori VARCHAR(255) NOT NULL");
            echo "Kolom 'name' diubah menjadi 'nama_kategori'.\n";
        } else {
            // Add 'nama_kategori'
            $pdo->exec("ALTER TABLE categories ADD nama_kategori VARCHAR(255) NOT NULL AFTER id");
            echo "Kolom 'nama_kategori' ditambahkan.\n";
        }
    }

    // Check if 'updated_at' exists
    $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'updated_at'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE categories ADD updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "Kolom 'updated_at' ditambahkan.\n";
    }

    echo "Migrasi database selesai.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>