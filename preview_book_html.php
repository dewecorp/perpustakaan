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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="assets/images/favicon_library.svg?v=<?php echo time(); ?>" type="image/svg+xml">
  <title>Pratinjau Buku - PUSDIGI</title>
  <style>
    html, body { height: 100%; margin: 0; }
    .container { height: 100%; }
    .viewer { width: 100%; height: 100%; border: 0; }
  </style>
</head>
<body>
  <div class="container">
    <embed class="viewer" src="<?php echo htmlspecialchars($path); ?>#toolbar=1&navpanes=1&zoom=page-width" type="application/pdf">
  </div>
</body>
</html>
