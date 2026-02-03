<?php
require_once __DIR__ . '/config/config.php';
require_login();

$path = $_GET['path'] ?? '';
if (!$path) {
  http_response_code(400);
  echo 'Path tidak ditemukan';
  exit;
}

// Normalize path
$path = str_replace(['..', '\\'], ['', '/'], $path);
// Hanya izinkan file di dalam folder uploads/books
$allowedPrefix = 'assets/uploads/books/';
if (strpos($path, $allowedPrefix) !== 0) {
  http_response_code(403);
  echo 'Akses ditolak';
  exit;
}

$fullPath = __DIR__ . '/' . $path;
if (!file_exists($fullPath)) {
  http_response_code(404);
  echo 'File tidak ditemukan';
  exit;
}

header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
readfile($fullPath);
exit;
?>
