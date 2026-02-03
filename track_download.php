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
    // Log visit (Mengunduh Buku)
    try {
        // Get book title
        $stmt = $pdo->prepare("SELECT title FROM books WHERE book_path = ?");
        $stmt->execute([$path]);
        $book = $stmt->fetch();
        $title = $book['title'] ?? basename($path);

        // Determine visitor name
        $visitorName = isset($_SESSION['user']) ? $_SESSION['user']['username'] : 'Tamu (' . $_SERVER['REMOTE_ADDR'] . ')';

        // Insert log
        $stmtLog = $pdo->prepare("INSERT INTO visitors (name, purpose) VALUES (?, ?)");
        $stmtLog->execute([$visitorName, "Mengunduh Buku: " . $title]);

        // Increment downloads
        $stmtDl = $pdo->prepare("UPDATE books SET downloads = downloads + 1 WHERE book_path = ?");
        $stmtDl->execute([$path]);
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
