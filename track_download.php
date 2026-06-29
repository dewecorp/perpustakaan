<?php
require_once 'config/config.php';

$pdo = db();
$path = $_GET['path'] ?? '';
$path = str_replace(['..', '\\'], ['', '/'], $path);
$allowedPrefix = 'assets/uploads/books/';

if (!$path || strpos($path, $allowedPrefix) !== 0) {
    http_response_code(403);
    die('Akses ditolak');
}

$fullPath = __DIR__ . '/' . $path;

if (file_exists($fullPath)) {
    $bookIdParam = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    try {
        if ($bookIdParam > 0) {
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
            $stmt->execute([$bookIdParam]);
            $book = $stmt->fetch();
        } else {
            $stmt = $pdo->prepare("SELECT * FROM books WHERE book_path = ?");
            $stmt->execute([$path]);
            $book = $stmt->fetch();
        }
        if ($book) {
            log_book_visit($book, 'download');
        }
    } catch (Exception $e) {
        // Ignore errors
    }

    // Serve file for download
    header('Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($fullPath).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit;
} else {
    http_response_code(404);
    die('File tidak ditemukan');
}
