<?php
require_once __DIR__ . '/config.php';

$pdo = db();

$cols = $pdo->query("DESCRIBE visitors")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('book_id', $cols)) {
    $pdo->exec("ALTER TABLE visitors ADD COLUMN book_id INT DEFAULT NULL AFTER purpose");
    $pdo->exec("ALTER TABLE visitors ADD INDEX idx_visitors_book_id (book_id)");
    echo "Kolom book_id ditambahkan ke tabel visitors.\n";
}

// Backfill book_id dari judul buku yang masih ada
$updated = $pdo->exec("
    UPDATE visitors v
    INNER JOIN books b ON b.title = TRIM(SUBSTRING(v.purpose, LOCATE(':', v.purpose) + 1))
    SET v.book_id = b.id
    WHERE v.book_id IS NULL
      AND (v.purpose LIKE 'Melihat Buku:%' OR v.purpose LIKE 'Mengunduh Buku:%')
");
echo "Backfill book_id: $updated baris diperbarui.\n";
