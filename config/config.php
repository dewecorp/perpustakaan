<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');
session_start();

// Kredensial database: lokal & hosting terpisah (lihat config/database.loader.php)
$DB_HOST = '127.0.0.1';
$DB_NAME = 'perpustakaan';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHARSET = 'utf8mb4';

require_once __DIR__ . '/database.loader.php';
load_database_config(__DIR__);
require_once __DIR__ . '/github_update.php';

// Detect Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = '/';
if ($host === 'localhost' || $host === '127.0.0.1') {
    $path = '/perpustakaan/';
}
define('BASE_URL', $protocol . "://" . $host . $path);
define('BOOK_COVER_PLACEHOLDER', 'assets/images/book-placeholder.svg');
define('SESSION_IDLE_SECONDS', 2 * 60 * 60);

function touch_session_activity(): void
{
    $_SESSION['last_activity'] = time();
}

function lock_session(): void
{
    $_SESSION['locked'] = true;
}

function unlock_session(): void
{
    unset($_SESSION['locked']);
}

function is_session_locked(): bool
{
    return !empty($_SESSION['locked']);
}

function is_lockscreen_request(): bool
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    return str_ends_with($script, '/auth/lockscreen.php');
}

function is_public_request(): bool
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $publicScripts = [
        '/index.php',
        '/preview_book_viewer.php',
        '/preview_book_get.php',
        '/track_book.php',
        '/track_download.php',
        '/auth/login.php',
    ];

    foreach ($publicScripts as $suffix) {
        if (str_ends_with($script, $suffix)) {
            return true;
        }
    }

    return false;
}

function is_staff_user(): bool
{
    if (empty($_SESSION['user'])) {
        return false;
    }

    return in_array(current_user_role(), ['admin', 'pustakawan'], true);
}

function enforce_session_idle_lock(): void
{
    if (!is_staff_user() || is_lockscreen_request() || is_public_request()) {
        return;
    }

    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
    if ($lastActivity > 0 && (time() - $lastActivity) > SESSION_IDLE_SECONDS) {
        lock_session();
    }

    if (is_session_locked()) {
        $_SESSION['redirect_after_unlock'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . 'dashboard.php');
        header('Location: ' . BASE_URL . 'auth/lockscreen.php');
        exit;
    }

    touch_session_activity();
}

function require_login() {
  if (empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
  }
  enforce_session_idle_lock();
}

function db() {
  static $pdo = null;
  if ($pdo) return $pdo;
  global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET;
  try {
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo->exec("SET time_zone = '+07:00'");
    return $pdo;
  } catch (PDOException $e) {
    http_response_code(500);
    echo "Koneksi DB gagal: " . htmlspecialchars($e->getMessage());
    exit;
  }
}

/**
 * Get system setting
 * @param string $key
 * @param string $default
 * @return string
 */
function get_setting($key, $default = ''): string {
  $pdo = db();
  $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
  $stmt->execute([$key]);
  $res = $stmt->fetchColumn();
  return ($res !== false && $res !== null) ? (string)$res : (string)$default;
}

function save_setting($key, $value) {
  $pdo = db();
  $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
  return $stmt->execute([$key, $value]);
}

function current_user_role(): string {
  return isset($_SESSION['user']['role']) ? (string)$_SESSION['user']['role'] : 'admin';
}

function require_admin() {
  require_login();
  if (current_user_role() !== 'admin') {
    http_response_code(403);
    echo 'Akses ditolak.';
    exit;
  }
}

/**
 * URL sampul buku — fallback ke placeholder gambar buku jika tidak ada/invalid.
 */
function book_cover_url(array $book): string {
    $cover = trim((string)($book['cover_path'] ?? ''));
    if ($cover === '') {
        $cover = trim((string)($book['cover_url'] ?? ''));
    }
    if ($cover === '') {
        return BOOK_COVER_PLACEHOLDER;
    }
    if (filter_var($cover, FILTER_VALIDATE_URL)) {
        return $cover;
    }
    $root = dirname(__DIR__);
    $localPath = $root . '/' . str_replace(['..', '\\'], ['', '/'], ltrim($cover, '/'));
    if (file_exists($localPath)) {
        return $cover;
    }
    return BOOK_COVER_PLACEHOLDER;
}

function book_cover_placeholder(): string {
    return BOOK_COVER_PLACEHOLDER;
}

/**
 * Nama pengunjung untuk log kunjungan buku.
 */
function visitor_display_name(): string {
    if (isset($_SESSION['user']['username'])) {
        return (string)$_SESSION['user']['username'];
    }
    return 'Tamu (' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ')';
}

/**
 * Catat kunjungan lihat/unduh buku ke tabel visitors.
 */
function log_book_visit(array $book, string $action = 'view'): void {
    $pdo = db();
    $bookId = (int)($book['id'] ?? 0);
    if ($bookId <= 0) {
        return;
    }

    $title = (string)($book['title'] ?? 'Buku');
    $purpose = $action === 'download' ? "Mengunduh Buku: $title" : "Melihat Buku: $title";

    try {
        $stmt = $pdo->prepare("INSERT INTO visitors (name, purpose, book_id) VALUES (?, ?, ?)");
        $stmt->execute([visitor_display_name(), $purpose, $bookId]);

        $column = $action === 'download' ? 'downloads' : 'views';
        $pdo->prepare("UPDATE books SET {$column} = {$column} + 1 WHERE id = ?")->execute([$bookId]);

        if (empty($_SESSION['visitors_cleaned_at']) || time() - (int)$_SESSION['visitors_cleaned_at'] > 86400) {
            clean_old_visitors();
            $_SESSION['visitors_cleaned_at'] = time();
        }
    } catch (Exception $e) {
        // Jangan ganggu pengalaman pengunjung jika logging gagal
    }
}

/**
 * Label pengguna untuk log aktivitas (Admin/Pustakawan + nama).
 */
function activity_user_label(?int $user_id = null, ?string $username = null): string {
    $pdo = db();

    if ($user_id === null && isset($_SESSION['user']['id'])) {
        $user_id = (int)$_SESSION['user']['id'];
    }

    if ($user_id) {
        $stmt = $pdo->prepare("SELECT username, name, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user) {
            $roleLabel = ($user['role'] ?? 'admin') === 'pustakawan' ? 'Pustakawan' : 'Admin';
            $display = trim((string)($user['name'] ?? ''));
            if ($display === '') {
                $display = (string)$user['username'];
            }
            if (strcasecmp($display, $roleLabel) === 0) {
                return $roleLabel;
            }
            return $roleLabel . ' ' . $display;
        }
    }

    if ($username) {
        if (strcasecmp($username, 'admin') === 0) {
            return 'Admin';
        }
        return 'Pengguna ' . $username;
    }

    return 'Sistem';
}

/**
 * Hilangkan duplikasi label peran (mis. "Admin Admin" -> "Admin").
 */
function normalize_activity_text(string $text): string {
    $text = preg_replace('/^(Admin|Pustakawan)(\s+\1)+/iu', '$1', $text);
    return trim(preg_replace('/\s+/', ' ', $text));
}

/**
 * Ambil teks aksi tanpa nama pengguna (untuk timeline).
 */
function activity_action_text(array $log): string {
    $message = format_activity_description($log);
    $actor = activity_user_label(
        isset($log['user_id']) ? (int)$log['user_id'] : null,
        $log['username'] ?? null
    );

    if (str_starts_with($message, $actor . ' ')) {
        return trim(substr($message, strlen($actor) + 1));
    }

    return normalize_activity_text(preg_replace('/^' . preg_quote($actor, '/') . '\s+/iu', '', $message, 1));
}

/**
 * Meta ikon timeline berdasarkan jenis aksi.
 */
function activity_timeline_meta(string $action_type): array {
    return match ($action_type) {
        'login' => ['icon' => 'bi-box-arrow-in-right', 'bg' => 'text-bg-success'],
        'logout' => ['icon' => 'bi-box-arrow-left', 'bg' => 'text-bg-danger'],
        'create' => ['icon' => 'bi-plus-lg', 'bg' => 'text-bg-primary'],
        'update' => ['icon' => 'bi-pencil', 'bg' => 'text-bg-warning'],
        'delete' => ['icon' => 'bi-trash', 'bg' => 'text-bg-danger'],
        default => ['icon' => 'bi-info-circle', 'bg' => 'text-bg-secondary'],
    };
}

/**
 * Format deskripsi aktivitas untuk ditampilkan (Bahasa Indonesia).
 */
function format_activity_description(array $log): string {
    $desc = trim((string)($log['description'] ?? ''));
    $actor = activity_user_label(
        isset($log['user_id']) ? (int)$log['user_id'] : null,
        $log['username'] ?? null
    );

    $legacyMap = [
        'User logged in' => $actor . ' berhasil masuk ke sistem',
        'User logged out' => $actor . ' keluar dari sistem',
    ];

    if (isset($legacyMap[$desc])) {
        return normalize_activity_text($legacyMap[$desc]);
    }

    $patterns = [
        '/^Menambah buku baru: (.+)$/' => $actor . ' menambahkan buku baru: $1',
        '/^Mengubah data buku: (.+)$/' => $actor . ' memperbarui data buku: $1',
        '/^Menghapus buku: (.+)$/' => $actor . ' menghapus buku: $1',
        '/^Menambah kategori: (.+)$/' => $actor . ' menambahkan kategori: $1',
        '/^Mengubah kategori: (.+)$/' => $actor . ' memperbarui kategori: $1',
        '/^Menghapus kategori: (.+)$/' => $actor . ' menghapus kategori: $1',
        '/^Menambah pengguna baru: (.+)$/' => $actor . ' menambahkan pengguna baru: $1',
        '/^Mengubah data pengguna: (.+)$/' => $actor . ' memperbarui data pengguna: $1',
        '/^Menghapus pengguna: (.+)$/' => $actor . ' menghapus pengguna: $1',
    ];

    foreach ($patterns as $pattern => $replacement) {
        if (preg_match($pattern, $desc)) {
            return normalize_activity_text(preg_replace($pattern, $replacement, $desc));
        }
    }

    if ($desc !== '' && (str_starts_with($desc, 'Admin ') || str_starts_with($desc, 'Pustakawan ') || str_starts_with($desc, 'Pengguna '))) {
        return normalize_activity_text($desc);
    }

    if ($desc !== '') {
        return normalize_activity_text($actor . ' — ' . $desc);
    }

    return normalize_activity_text(match ($log['action_type'] ?? '') {
        'login' => $actor . ' berhasil masuk ke sistem',
        'logout' => $actor . ' keluar dari sistem',
        'create' => $actor . ' menambahkan data baru',
        'update' => $actor . ' memperbarui data',
        'delete' => $actor . ' menghapus data',
        default => $actor . ' melakukan aktivitas pada sistem',
    });
}

/**
 * Log user activity
 * @param string $action_type
 * @param string $description
 * @param int|null $user_id Optional, defaults to current session user
 */
function log_activity($action_type, $description, $user_id = null) {
    $pdo = db();
    
    if ($user_id === null && isset($_SESSION['user']['id'])) {
        $user_id = $_SESSION['user']['id'];
        $username = $_SESSION['user']['username'];
    } else {
        // If user_id is provided, fetch username
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $username = $stmt->fetchColumn() ?: 'Unknown';
        } else {
            $username = 'System';
        }
    }

    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, username, action_type, description, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $username, $action_type, $description]);
}

/**
 * Clean old activity logs (older than 24 hours)
 */
function clean_old_activities() {
    $pdo = db();
    $pdo->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
}

/**
 * Hapus data pengunjung lebih dari 1 tahun.
 */
function clean_old_visitors(): void {
    $pdo = db();
    $pdo->query("DELETE FROM visitors WHERE visit_date < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
}

/**
 * Nama bulan dalam Bahasa Indonesia.
 */
function indo_month_name(int $month, bool $short = false): string {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $shortMonths = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];
    if ($month < 1 || $month > 12) {
        return '';
    }
    return $short ? $shortMonths[$month] : $months[$month];
}

/**
 * Format tanggal: 29 Juni 2026
 */
function format_date_id(int|string $datetime, bool $withTime = false): string {
    $ts = is_numeric($datetime) ? (int)$datetime : strtotime((string)$datetime);
    if ($ts === false) {
        return (string)$datetime;
    }
    $result = (int)date('j', $ts) . ' ' . indo_month_name((int)date('n', $ts)) . ' ' . date('Y', $ts);
    if ($withTime) {
        $result .= ' ' . date('H:i', $ts);
    }
    return $result;
}

/**
 * Format bulan-tahun untuk grafik: Juni 2026
 */
function format_month_year_id(int|string $datetime): string {
    $ts = is_numeric($datetime) ? (int)$datetime : strtotime((string)$datetime);
    if ($ts === false) {
        return (string)$datetime;
    }
    return indo_month_name((int)date('n', $ts)) . ' ' . date('Y', $ts);
}

/**
 * Time ago helper
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    if ($time === false) {
        return (string)$datetime;
    }
    $current = time();
    $diff = $current - $time;
    
    $minute = 60;
    $hour = 3600;
    $day = 86400;
    $month = 2629743;
    
    if ($diff < $minute) {
        return "baru saja";
    }
    
    if ($diff < $hour) {
        return floor($diff / $minute) . " menit yang lalu";
    }
    
    if ($diff < $day) {
        return floor($diff / $hour) . " jam yang lalu";
    }
    
    if ($diff < $month) {
        return floor($diff / $day) . " hari yang lalu";
    }
    
    return format_date_id($time, true);
}
