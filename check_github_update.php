<?php
require_once __DIR__ . '/config/config.php';
require_login();
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dismiss'])) {
    $sha = trim((string)($_POST['sha'] ?? ''));
    if ($sha !== '') {
        github_dismiss_update_popup($sha);
    }
    echo json_encode(['success' => true]);
    exit;
}

$force = isset($_GET['force']) && $_GET['force'] === '1';
$result = check_github_update($force);

echo json_encode([
    'success' => $result['error'] === null,
    'has_update' => $result['has_update'],
    'installed_sha' => $result['installed_sha'],
    'latest' => $result['latest'],
    'checked_at' => $result['checked_at'],
    'message' => $result['error'] ?? ($result['has_update']
        ? 'Pembaruan baru tersedia di GitHub.'
        : 'Sistem Anda sudah menggunakan versi terbaru.'),
]);
