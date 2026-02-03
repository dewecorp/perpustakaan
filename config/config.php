<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');
session_start();

$DB_NAME = 'perpustakaan';
$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHARSET = 'utf8mb4';

// Detect Base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = '/perpustakaan/'; 
define('BASE_URL', $protocol . "://" . $host . $path);

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

function require_login() {
  if (empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
  }
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
    // Delete logs older than 24 hours
    $pdo->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
}

/**
 * Time ago helper
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $current = time();
    $diff = $current - $time;
    
    $second = 1;
    $minute = 60;
    $hour = 3600;
    $day = 86400;
    $month = 2629743;
    $year = 31556926;
    
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
    
    return date('d M Y H:i', $time);
}

