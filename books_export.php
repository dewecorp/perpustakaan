<?php
/**
 * Ekspor data buku ke file JSON untuk dipindahkan dari lokal ke hosting.
 *
 * Jalankan di LOKAL (yang berhasil mengambil data dari server ebook), unduh
 * file .json-nya, lalu unggah file tersebut di hosting via menu Impor.
 *
 * Catatan: file PDF/sampul hasil upload (book_path/cover_path lokal) TIDAK
 * diekspor — hanya metadata + URL (cover_url/book_url) yang dipindahkan,
 * karena yang diekspor biasanya buku impor dari web (memakai URL eksternal).
 */
require_once 'config/config.php';
require_login();
require_admin();

$pdo = db();

// Hanya kolom metadata yang relevan untuk dipindahkan antar-instansi.
$columns = ['code', 'isbn', 'title', 'author', 'category', 'year',
            'cover_url', 'book_url', 'description'];

$books = $pdo->query(
    "SELECT " . implode(', ', $columns) . " FROM books ORDER BY id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$payload = [
    'app' => 'PUSDIGI',
    'type' => 'books_export',
    'version' => 1,
    'exported_at' => date('c'),
    'count' => count($books),
    'books' => $books,
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$filename = 'perpustakaan_buku_' . date('Y-m-d_His') . '.json';

// Jika ini request AJAX/biasa yang ingin preview, tampilkan di halaman.
$inline = isset($_GET['view']) && $_GET['view'] === '1';
if ($inline) {
    header('Content-Type: application/json; charset=utf-8');
    echo $json;
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($json));
echo $json;
