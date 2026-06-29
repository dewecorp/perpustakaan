<?php
require_once __DIR__ . '/config/config.php';

$pdo = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = ($_GET['action'] ?? 'view') === 'download' ? 'download' : 'view';

if ($id <= 0) {
    http_response_code(400);
    die('ID buku tidak valid.');
}

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    die('Buku tidak ditemukan.');
}

log_book_visit($book, $action);

$hasLocal = !empty($book['book_path']);
$hasRemote = !empty($book['book_url']);

if ($action === 'download') {
    if ($hasLocal) {
        $path = str_replace(['..', '\\'], ['', '/'], $book['book_path']);
        $allowedPrefix = 'assets/uploads/books/';
        if (strpos($path, $allowedPrefix) !== 0) {
            http_response_code(403);
            die('Akses ditolak.');
        }
        $fullPath = __DIR__ . '/' . $path;
        if (!file_exists($fullPath)) {
            http_response_code(404);
            die('File tidak ditemukan.');
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
    if ($hasRemote) {
        header('Location: ' . $book['book_url']);
        exit;
    }
    http_response_code(404);
    die('File buku tidak tersedia.');
}

// action = view
if ($hasLocal) {
    $path = urlencode($book['book_path']);
    header('Location: ' . BASE_URL . 'preview_book_viewer.php?path=' . $path . '&id=' . $id . '&skip_log=1');
    exit;
}
if ($hasRemote) {
    header('Location: ' . $book['book_url']);
    exit;
}

http_response_code(404);
die('Buku tidak tersedia untuk dibaca.');
