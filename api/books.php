<?php
header('Content-Type: application/json');

$dataFile = __DIR__ . '/../data/books.json';

function loadBooks($dataFile) {
  if (!file_exists($dataFile)) {
    return [];
  }
  $json = file_get_contents($dataFile);
  $data = json_decode($json, true);
  return is_array($data) ? $data : [];
}

function saveBooks($dataFile, $books) {
  $dir = dirname($dataFile);
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
  }
  $fp = fopen($dataFile, 'c+');
  if (!$fp) {
    http_response_code(500);
    echo json_encode(['error' => 'Tidak bisa membuka file data']);
    exit;
  }
  if (!flock($fp, LOCK_EX)) {
    http_response_code(500);
    echo json_encode(['error' => 'Tidak bisa mengunci file data']);
    fclose($fp);
    exit;
  }
  ftruncate($fp, 0);
  fwrite($fp, json_encode($books, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  fflush($fp);
  flock($fp, LOCK_UN);
  fclose($fp);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $books = loadBooks($dataFile);
  // optional filters: category, year, q
  $category = isset($_GET['category']) ? trim($_GET['category']) : '';
  $year = isset($_GET['year']) ? intval($_GET['year']) : 0;
  $q = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
  $filtered = array_values(array_filter($books, function($b) use ($category, $year, $q) {
    $ok = true;
    if ($category !== '') {
      $ok = $ok && (isset($b['category']) && strtolower($b['category']) === strtolower($category));
    }
    if ($year !== 0) {
      $ok = $ok && (isset($b['year']) && intval($b['year']) === $year);
    }
    if ($q !== '') {
      $hay = strtolower(($b['title'] ?? '') . ' ' . ($b['author'] ?? '') . ' ' . ($b['description'] ?? ''));
      $ok = $ok && strpos($hay, $q) !== false;
    }
    return $ok;
  }));
  echo json_encode(['data' => $filtered]);
  exit;
}

if ($method === 'POST') {
  $action = $_POST['action'] ?? '';
  $books = loadBooks($dataFile);

  if ($action === 'create') {
    $book = [
      'id' => $_POST['id'] ?? ('BK-' . str_pad(strval(rand(1, 9999)), 4, '0', STR_PAD_LEFT)),
      'title' => $_POST['title'] ?? '',
      'author' => $_POST['author'] ?? '',
      'category' => $_POST['category'] ?? '',
      'year' => intval($_POST['year'] ?? 0),
      'cover_url' => $_POST['cover_url'] ?? '',
      'description' => $_POST['description'] ?? ''
    ];
    // prevent duplicate id
    foreach ($books as $b) {
      if (($b['id'] ?? '') === $book['id']) {
        http_response_code(400);
        echo json_encode(['error' => 'ID buku sudah ada']);
        exit;
      }
    }
    $books[] = $book;
    saveBooks($dataFile, $books);
    echo json_encode(['message' => 'Buku ditambahkan', 'data' => $book]);
    exit;
  }

  if ($action === 'update') {
    $id = $_POST['id'] ?? '';
    if ($id === '') {
      http_response_code(400);
      echo json_encode(['error' => 'ID buku wajib diisi']);
      exit;
    }
    $updated = false;
    foreach ($books as &$b) {
      if (($b['id'] ?? '') === $id) {
        $b['title'] = $_POST['title'] ?? $b['title'];
        $b['author'] = $_POST['author'] ?? $b['author'];
        $b['category'] = $_POST['category'] ?? $b['category'];
        $b['year'] = isset($_POST['year']) ? intval($_POST['year']) : $b['year'];
        $b['cover_url'] = $_POST['cover_url'] ?? $b['cover_url'];
        $b['description'] = $_POST['description'] ?? $b['description'];
        $updated = true;
        break;
      }
    }
    if (!$updated) {
      http_response_code(404);
      echo json_encode(['error' => 'Buku tidak ditemukan']);
      exit;
    }
    saveBooks($dataFile, $books);
    echo json_encode(['message' => 'Buku diperbarui']);
    exit;
  }

  if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if ($id === '') {
      http_response_code(400);
      echo json_encode(['error' => 'ID buku wajib diisi']);
      exit;
    }
    $before = count($books);
    $books = array_values(array_filter($books, function($b) use ($id) {
      return ($b['id'] ?? '') !== $id;
    }));
    if ($before === count($books)) {
      http_response_code(404);
      echo json_encode(['error' => 'Buku tidak ditemukan']);
      exit;
    }
    saveBooks($dataFile, $books);
    echo json_encode(['message' => 'Buku dihapus']);
    exit;
  }

  http_response_code(400);
  echo json_encode(['error' => 'Aksi tidak dikenal']);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metode tidak didukung']);
