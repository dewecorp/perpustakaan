<?php
require_once __DIR__ . '/../config/config.php';
require_login();

header('Content-Type: application/json');

$year = (int)($_GET['year'] ?? date('Y'));
$type = strtoupper(trim((string)($_GET['type'] ?? 'BK')));

echo json_encode([
    'code' => generate_next_book_code(db(), $year, $type),
]);
