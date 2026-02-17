<?php
require_once __DIR__ . '/config.php';
$pdo = db();

try {
    // Add ISBN column if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM books LIKE 'isbn'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE books ADD isbn VARCHAR(20) DEFAULT '' AFTER code");
        echo "Kolom 'isbn' ditambahkan.\n";
    }

    // Add cover_path column if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM books LIKE 'cover_path'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE books ADD cover_path VARCHAR(255) DEFAULT NULL AFTER cover_url");
        echo "Kolom 'cover_path' ditambahkan.\n";
    }

    // Add book_path column if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM books LIKE 'book_path'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE books ADD book_path VARCHAR(255) DEFAULT NULL AFTER cover_path");
        echo "Kolom 'book_path' ditambahkan.\n";
    }

    // Add book_url column if not exists (untuk link file buku, misal Google Drive)
    $stmt = $pdo->query("SHOW COLUMNS FROM books LIKE 'book_url'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE books ADD book_url TEXT NULL AFTER book_path");
        echo "Kolom 'book_url' ditambahkan.\n";
    }

    echo "Migrasi tabel books selesai.\n";
} catch (PDOException $e) {
    echo "Error migrasi: " . $e->getMessage() . "\n";
}
?>
