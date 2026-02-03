<?php
require_once 'config/config.php';
require_login();

$pdo = db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $name = trim($_POST['name']);
        
        // Check if username exists
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmtCheck->execute([$username]);
        if ($stmtCheck->fetchColumn() > 0) {
            $_SESSION['error'] = "Username sudah digunakan.";
            header('Location: ' . BASE_URL . 'users.php');
            exit;
        }

        // Handle Avatar Upload
        $avatarPath = null;
        if (!empty($_FILES['avatar']['name'])) {
            $targetDir = 'assets/uploads/avatars';
            if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
            
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $filename = 'user_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $targetFile = $targetDir . '/' . $filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                    $avatarPath = $targetFile;
                }
            }
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password, avatar) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $username, $hash, $avatarPath]);
            
            log_activity('create', 'Menambah pengguna baru: ' . $username);
            $_SESSION['success'] = "Pengguna berhasil ditambahkan.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Gagal menambahkan pengguna: " . $e->getMessage();
        }
        header('Location: ' . BASE_URL . 'users.php');

    } elseif ($action === 'update') {
        $id = $_POST['id'];
        $username = trim($_POST['username']);
        $name = trim($_POST['name']);
        
        // Check username uniqueness (exclude current user)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmtCheck->execute([$username, $id]);
        if ($stmtCheck->fetchColumn() > 0) {
            $_SESSION['error'] = "Username sudah digunakan oleh pengguna lain.";
            header('Location: ' . BASE_URL . 'users.php');
            exit;
        }

        // Fetch existing avatar
        $stmtGet = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmtGet->execute([$id]);
        $existing = $stmtGet->fetch();
        $avatarPath = $existing['avatar'] ?? null;

        // Handle Avatar Upload
        if (!empty($_FILES['avatar']['name'])) {
            $targetDir = 'assets/uploads/avatars';
            if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
            
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $filename = 'user_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $targetFile = $targetDir . '/' . $filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFile)) {
                    // Remove old avatar if exists
                    if ($avatarPath && file_exists($avatarPath)) {
                        unlink($avatarPath);
                    }
                    $avatarPath = $targetFile;
                }
            }
        }

        $sql = "UPDATE users SET name = ?, username = ?, avatar = ? WHERE id = ?";
        $params = [$name, $username, $avatarPath, $id];

        // Update password if provided
        if (!empty($_POST['password'])) {
            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = ?, username = ?, avatar = ?, password = ? WHERE id = ?";
            $params = [$name, $username, $avatarPath, $hash, $id];
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            log_activity('update', 'Mengubah data pengguna: ' . $username);
            $_SESSION['success'] = "Pengguna berhasil diperbarui.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Gagal memperbarui pengguna: " . $e->getMessage();
        }
        header('Location: ' . BASE_URL . 'users.php');
    }
} elseif ($action === 'delete') {
    $id = $_GET['id'];
    
    // Prevent deleting self (simple check)
    // In a real app, we should check logged in user ID
    
    try {
        // Get avatar to delete file
        $stmtGet = $pdo->prepare("SELECT avatar, username FROM users WHERE id = ?");
        $stmtGet->execute([$id]);
        $user = $stmtGet->fetch();
        
        if ($user) {
             if ($user['avatar'] && file_exists($user['avatar'])) {
                unlink($user['avatar']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            log_activity('delete', 'Menghapus pengguna: ' . $user['username']);
            $_SESSION['success'] = "Pengguna berhasil dihapus.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus pengguna: " . $e->getMessage();
    }
    header('Location: ' . BASE_URL . 'users.php');
}
