<?php
require_once 'config/config.php';
require_login();

$backupDir = __DIR__ . '/assets/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'backup') {
    try {
        $pdo = db();
        $tables = [];
        $query = $pdo->query('SHOW TABLES');
        while($row = $query->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $content = "-- Backup Perpustakaan\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach($tables as $table) {
            $content .= "-- Table structure for table `$table`\n";
            $content .= "DROP TABLE IF EXISTS `$table`;\n";
            $row2 = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
            $content .= $row2[1] . ";\n\n";

            $content .= "-- Dumping data for table `$table`\n";
            $rows = $pdo->query("SELECT * FROM `$table`");
            while($row = $rows->fetch(PDO::FETCH_NUM)) {
                $values = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $values[] = "NULL";
                    } else {
                        $values[] = "'" . addslashes($val) . "'";
                    }
                }
                $content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $content .= "\n";
        }
        $content .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        file_put_contents($backupDir . $filename, $content);

        // Return JSON if AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['status' => 'success', 'message' => 'Backup berhasil dibuat']);
            exit;
        }

        $_SESSION['success'] = "Backup berhasil dibuat: $filename";
        header("Location: " . BASE_URL . "backup.php");
        exit;

    } catch (Exception $e) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
        $_SESSION['error'] = "Gagal backup: " . $e->getMessage();
        header("Location: " . BASE_URL . "backup.php");
        exit;
    }

} elseif ($action === 'restore') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['backup_file'])) {
        $file = $_FILES['backup_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'sql') {
                $_SESSION['error'] = "Format file harus .sql";
                header("Location: " . BASE_URL . "backup.php");
                exit;
            }

            $sql = file_get_contents($file['tmp_name']);
            
            try {
                $pdo = db();
                // Disable foreign key checks for restore
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                
                // Split queries (basic split by semicolon at end of line)
                // This is a simple parser, might fail on complex stored procedures but good for basic dumps
                $queries = preg_split("/;\s*[\r\n]+/", $sql);
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $pdo->exec($query);
                    }
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                $_SESSION['success'] = "Database berhasil direstore";
            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal restore: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Gagal upload file";
        }
    }
    header("Location: " . BASE_URL . "backup.php");
    exit;

} elseif ($action === 'delete') {
    $filename = $_GET['file'] ?? '';
    if ($filename && file_exists($backupDir . $filename)) {
        unlink($backupDir . $filename);
        $_SESSION['success'] = "File backup berhasil dihapus";
    } else {
        $_SESSION['error'] = "File tidak ditemukan";
    }
    header("Location: " . BASE_URL . "backup.php");
    exit;

} elseif ($action === 'download') {
    $filename = $_GET['file'] ?? '';
    $filepath = $backupDir . $filename;
    
    if ($filename && file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $_SESSION['error'] = "File tidak ditemukan";
        header("Location: " . BASE_URL . "backup.php");
        exit;
    }
} else {
    header("Location: " . BASE_URL . "backup.php");
    exit;
}
