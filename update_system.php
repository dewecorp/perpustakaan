<?php
require_once __DIR__ . '/config/config.php';
require_login();
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

if (!class_exists('ZipArchive')) {
    echo json_encode(['success' => false, 'message' => 'Ekstensi PHP ZipArchive tidak tersedia di server.']);
    exit;
}

const GITHUB_ZIP_URL = 'https://github.com/dewecorp/perpustakaan/archive/refs/heads/main.zip';

$skipFiles = [
    'config/database.local.php',
    'config/database.production.php',
    'config/database.php',
];

$skipDirs = [
    'assets/uploads',
    'assets/backups',
    'node_modules',
    '.git',
];

$skipPatterns = [
    'assets/images/logo.png',
    'assets/images/book-hero.png',
    'assets/images/login-bg.png',
];

function update_should_skip(string $relativePath, array $skipFiles, array $skipDirs, array $skipPatterns): bool
{
    $relativePath = str_replace('\\', '/', ltrim($relativePath, '/'));
    foreach ($skipFiles as $file) {
        if (strcasecmp($relativePath, $file) === 0) {
            return true;
        }
    }
    foreach ($skipPatterns as $pattern) {
        if (strcasecmp($relativePath, $pattern) === 0) {
            return true;
        }
    }
    foreach ($skipDirs as $dir) {
        $dir = rtrim(str_replace('\\', '/', $dir), '/');
        if ($relativePath === $dir || str_starts_with($relativePath, $dir . '/')) {
            return true;
        }
    }
    return false;
}

function update_remove_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            update_remove_dir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function update_copy_tree(string $source, string $dest, array $skipFiles, array $skipDirs, array $skipPatterns): int
{
    $copied = 0;
    $source = rtrim(str_replace('\\', '/', $source), '/');
    $dest = rtrim(str_replace('\\', '/', $dest), '/');
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $fullPath = str_replace('\\', '/', $item->getPathname());
        $relative = ltrim(substr($fullPath, strlen($source)), '/');
        if (update_should_skip($relative, $skipFiles, $skipDirs, $skipPatterns)) {
            continue;
        }

        $target = $dest . '/' . $relative;
        if ($item->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                throw new RuntimeException('Gagal membuat folder: ' . $relative);
            }
            continue;
        }

        $targetDir = dirname($target);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Gagal membuat folder tujuan: ' . $targetDir);
        }
        if (!copy($item->getPathname(), $target)) {
            throw new RuntimeException('Gagal menyalin file: ' . $relative);
        }
        $copied++;
    }

    return $copied;
}

set_time_limit(300);

$tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pusdigi_update_' . bin2hex(random_bytes(8));
$zipFile = $tempRoot . '.zip';

try {
    if (!is_dir(sys_get_temp_dir()) || !is_writable(sys_get_temp_dir())) {
        throw new RuntimeException('Folder temporary server tidak dapat ditulis.');
    }

    $zipData = false;
    if (function_exists('curl_init')) {
        $ch = curl_init(GITHUB_ZIP_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_USERAGENT => 'PUSDIGI-Updater/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $zipData = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        if ($zipData === false || $httpCode !== 200) {
            throw new RuntimeException('Gagal mengunduh paket update dari GitHub.' . ($curlError ? ' ' . $curlError : ''));
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'timeout' => 120,
                'header' => "User-Agent: PUSDIGI-Updater/1.0\r\n",
            ],
        ]);
        $zipData = @file_get_contents(GITHUB_ZIP_URL, false, $context);
        if ($zipData === false) {
            throw new RuntimeException('Gagal mengunduh paket update dari GitHub.');
        }
    }

    if (file_put_contents($zipFile, $zipData) === false) {
        throw new RuntimeException('Gagal menyimpan file update sementara.');
    }
    unset($zipData);

    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        throw new RuntimeException('File update tidak valid atau rusak.');
    }

    if (!mkdir($tempRoot, 0755, true) && !is_dir($tempRoot)) {
        $zip->close();
        throw new RuntimeException('Gagal menyiapkan folder ekstraksi.');
    }

    if (!$zip->extractTo($tempRoot)) {
        $zip->close();
        throw new RuntimeException('Gagal mengekstrak paket update.');
    }
    $zip->close();
    @unlink($zipFile);

    $extractedDirs = array_values(array_filter(scandir($tempRoot) ?: [], function ($item) use ($tempRoot) {
        return $item !== '.' && $item !== '..' && is_dir($tempRoot . DIRECTORY_SEPARATOR . $item);
    }));

    if (empty($extractedDirs)) {
        throw new RuntimeException('Struktur paket update tidak ditemukan.');
    }

    $sourceRoot = $tempRoot . DIRECTORY_SEPARATOR . $extractedDirs[0];
    $targetRoot = realpath(__DIR__);
    if ($targetRoot === false) {
        throw new RuntimeException('Folder aplikasi tidak ditemukan.');
    }

    $copied = update_copy_tree($sourceRoot, $targetRoot, $skipFiles, $skipDirs, $skipPatterns);
    if ($copied === 0) {
        throw new RuntimeException('Tidak ada file yang diperbarui.');
    }

    log_activity('update', activity_user_label() . ' memperbarui sistem dari GitHub');
    mark_github_update_installed();

    echo json_encode([
        'success' => true,
        'message' => 'Sistem berhasil diperbarui dari GitHub. ' . $copied . ' file diperbarui.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
} finally {
    if (is_file($zipFile)) {
        @unlink($zipFile);
    }
    if (is_dir($tempRoot)) {
        update_remove_dir($tempRoot);
    }
}
