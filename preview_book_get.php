<?php
require_once __DIR__ . '/config/config.php';
require_login();

$encodedPath = $_GET['data'] ?? '';
if (!$encodedPath) {
    http_response_code(400);
    exit;
}

$path = base64_decode($encodedPath);
$path = str_replace(['..', '\\'], ['', '/'], $path);
$allowedPrefix = 'assets/uploads/books/';

if (!$path || strpos($path, $allowedPrefix) !== 0) {
    http_response_code(403);
    exit;
}

$fullPath = __DIR__ . '/' . $path;

if (!file_exists($fullPath)) {
    http_response_code(404);
    exit;
}

// Serve as text/plain containing Base64 data to bypass IDM sniffing
header('Content-Type: text/plain');
header('Cache-Control: private, max-age=0, must-revalidate');
// Disable content sniffing
header('X-Content-Type-Options: nosniff');

// Read file and output base64
$fileContent = file_get_contents($fullPath);
echo base64_encode($fileContent);
exit;
