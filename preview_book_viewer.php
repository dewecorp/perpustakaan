<?php
require_once __DIR__ . '/config/config.php';
// require_login(); // Allow public access

$pdo = db();
$path = $_GET['path'] ?? '';
$path = str_replace(['..', '\\'], ['', '/'], $path);
$allowedPrefix = 'assets/uploads/books/';

if (!$path || strpos($path, $allowedPrefix) !== 0) {
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

// Log visit (Melihat Buku)
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
    $stmtLog->execute([$visitorName, "Melihat Buku: " . $title]);

    // Increment views
    $stmtView = $pdo->prepare("UPDATE books SET views = views + 1 WHERE book_path = ?");
    $stmtView->execute([$path]);
} catch (Exception $e) {
    // Ignore logging errors to not break functionality
}

$base64 = base64_encode(file_get_contents($fullPath));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pratinjau Buku</title>
  <style>
    html, body { height: 100%; margin: 0; }
    .container { height: 100%; }
    .viewer { width: 100%; height: 100%; border: 0; display:block; }
  </style>
  </head>
<body>
  <div class="container">
    <embed class="viewer" src="data:application/pdf;base64,<?php echo $base64; ?>#toolbar=1&view=FitH&zoom=page-width" type="application/pdf">
  </div>
</body>
</html>
