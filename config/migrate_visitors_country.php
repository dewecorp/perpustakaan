<?php
require_once __DIR__ . '/config.php';

$pdo = db();

$cols = $pdo->query("DESCRIBE visitors")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('ip_address', $cols)) {
    $pdo->exec("ALTER TABLE visitors ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER book_id");
    echo "Kolom ip_address ditambahkan ke tabel visitors.\n";
}

if (!in_array('country', $cols)) {
    $pdo->exec("ALTER TABLE visitors ADD COLUMN country VARCHAR(100) DEFAULT NULL AFTER ip_address");
    $pdo->exec("ALTER TABLE visitors ADD INDEX idx_visitors_country (country)");
    echo "Kolom country ditambahkan ke tabel visitors.\n";
}

echo "Migrasi selesai.\n";