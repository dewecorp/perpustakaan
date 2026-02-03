<?php
require_once 'config/config.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama_kategori = $_POST['nama_kategori'] ?? '';
        if (!empty($nama_kategori)) {
            $stmt = $pdo->prepare("INSERT INTO categories (nama_kategori) VALUES (?)");
            $stmt->execute([$nama_kategori]);
            log_activity('create', 'Menambah kategori: ' . $nama_kategori);
            $_SESSION['success'] = "Kategori berhasil ditambahkan";
        } else {
            $_SESSION['error'] = "Nama kategori tidak boleh kosong";
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $nama_kategori = $_POST['nama_kategori'] ?? '';
        if (!empty($id) && !empty($nama_kategori)) {
            $stmt = $pdo->prepare("UPDATE categories SET nama_kategori = ? WHERE id = ?");
            $stmt->execute([$nama_kategori, $id]);
            log_activity('update', 'Mengubah kategori: ' . $nama_kategori);
            $_SESSION['success'] = "Kategori berhasil diperbarui";
        } else {
            $_SESSION['error'] = "Data tidak valid";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            // Get name for log
            $stmtGet = $pdo->prepare("SELECT nama_kategori FROM categories WHERE id=?");
            $stmtGet->execute([$id]);
            $name = $stmtGet->fetchColumn() ?: 'Unknown';

            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            log_activity('delete', 'Menghapus kategori: ' . $name);
            $_SESSION['success'] = "Kategori berhasil dihapus";
        } else {
            $_SESSION['error'] = "ID tidak valid";
        }
    }
}

header('Location: ' . BASE_URL . 'categories.php');
exit;
?>