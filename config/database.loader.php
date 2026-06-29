<?php
/**
 * Memuat konfigurasi database sesuai lingkungan (lokal vs hosting).
 * File database.*.php tidak ditimpa saat update sistem.
 */

function is_local_environment(): bool
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));

    if ($host === '' || $host === 'localhost' || $host === '127.0.0.1') {
        return true;
    }

    if (str_starts_with($host, '127.0.0.1:')) {
        return true;
    }

    foreach (['.local', '.test', '.localhost'] as $suffix) {
        if (str_ends_with($host, $suffix)) {
            return true;
        }
    }

    return false;
}

function resolve_database_config_file(string $configDir): string
{
    $localFile = $configDir . '/database.local.php';
    $productionFile = $configDir . '/database.production.php';
    $legacyFile = $configDir . '/database.php';
    $exampleFile = $configDir . '/database.example.php';

    if (PHP_SAPI === 'cli') {
        $targetFile = is_file($localFile) ? $localFile : $productionFile;
    } else {
        $targetFile = is_local_environment() ? $localFile : $productionFile;
    }

    if (is_file($legacyFile)) {
        if ($targetFile === $localFile && !is_file($localFile)) {
            @copy($legacyFile, $localFile);
        } elseif ($targetFile === $productionFile && !is_file($productionFile)) {
            @copy($legacyFile, $productionFile);
        }
    }

    if (!is_file($targetFile) && is_file($exampleFile)) {
        @copy($exampleFile, $targetFile);
    }

    return $targetFile;
}

function load_database_config(string $configDir): void
{
    $databaseFile = resolve_database_config_file($configDir);

    if (!is_file($databaseFile)) {
        http_response_code(500);
        echo 'Konfigurasi database tidak ditemukan. Buat '
            . (is_local_environment() || PHP_SAPI === 'cli' ? 'database.local.php' : 'database.production.php')
            . ' dari database.example.php';
        exit;
    }

    require $databaseFile;
}
