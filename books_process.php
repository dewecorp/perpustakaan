<?php
require_once 'config/config.php';
require_login();

$pdo = db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        // Handle uploads
        $coverPath = null;
        $bookPath = null;
        // Ensure upload directories exist
        $coverDir = 'assets/uploads/covers';
        $bookDir  = 'assets/uploads/books';
        if (!is_dir($coverDir)) { mkdir($coverDir, 0777, true); }
        if (!is_dir($bookDir))  { mkdir($bookDir, 0777, true); }

        if (!empty($_FILES['cover_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
            $allowedImg = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowedImg)) {
                $filename = 'cover_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                $dest = $coverDir . '/' . $filename;
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $dest)) {
                    $coverPath = $dest;
                }
            }
        }
        if (!empty($_FILES['book_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['book_file']['name'], PATHINFO_EXTENSION));
            $allowedDoc = ['pdf'];
            if (in_array($ext, $allowedDoc)) {
                $filename = 'book_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                $dest = $bookDir . '/' . $filename;
                if (move_uploaded_file($_FILES['book_file']['tmp_name'], $dest)) {
                    $bookPath = $dest;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO books (code, isbn, title, author, category, year, cover_url, cover_path, book_path, book_url, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $_POST['code'],
                $_POST['isbn'] ?? '',
                $_POST['title'],
                $_POST['author'],
                $_POST['category'],
                (int)$_POST['year'],
                $_POST['cover_url'],
                $coverPath,
                $bookPath,
                $_POST['book_url'] ?? '',
                $_POST['description']
            ]);
            log_activity('create', 'Menambah buku baru: ' . $_POST['title']);
            $_SESSION['success'] = "Buku berhasil ditambahkan.";
            header('Location: ' . BASE_URL . 'books.php');
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    } elseif ($action === 'update') {
        // Fetch existing
        $stmtGet = $pdo->prepare("SELECT cover_path, book_path, book_url FROM books WHERE id=?");
        $stmtGet->execute([$_POST['id']]);
        $existing = $stmtGet->fetch();
        $coverPath = $existing['cover_path'] ?? null;
        $bookPath  = $existing['book_path'] ?? null;
        $bookUrl   = $existing['book_url'] ?? '';

        $coverDir = 'assets/uploads/covers';
        $bookDir  = 'assets/uploads/books';
        if (!is_dir($coverDir)) { mkdir($coverDir, 0777, true); }
        if (!is_dir($bookDir))  { mkdir($bookDir, 0777, true); }
        $useBookUrlOnly = isset($_POST['use_book_url_only']) && $_POST['use_book_url_only'] === '1';
        if (!empty($_FILES['cover_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
            $allowedImg = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowedImg)) {
                $filename = 'cover_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                $dest = $coverDir . '/' . $filename;
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $dest)) {
                    $coverPath = $dest;
                }
            }
        }
        if (!$useBookUrlOnly && !empty($_FILES['book_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['book_file']['name'], PATHINFO_EXTENSION));
            $allowedDoc = ['pdf'];
            if (in_array($ext, $allowedDoc)) {
                $filename = 'book_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
                $dest = $bookDir . '/' . $filename;
                if (move_uploaded_file($_FILES['book_file']['tmp_name'], $dest)) {
                    $bookPath = $dest;
                }
            }
        }

        if (isset($_POST['book_url'])) {
            $bookUrl = $_POST['book_url'];
        }
        if ($useBookUrlOnly) {
            $bookPath = null;
        }

        $stmt = $pdo->prepare("UPDATE books SET code=?, isbn=?, title=?, author=?, category=?, year=?, cover_url=?, cover_path=?, book_path=?, book_url=?, description=? WHERE id=?");
        try {
            $stmt->execute([
                $_POST['code'],
                $_POST['isbn'] ?? '',
                $_POST['title'],
                $_POST['author'],
                $_POST['category'],
                (int)$_POST['year'],
                $_POST['cover_url'],
                $coverPath,
                $bookPath,
                $bookUrl,
                $_POST['description'],
                $_POST['id']
            ]);
            log_activity('update', 'Mengubah data buku: ' . $_POST['title']);
            header('Location: ' . BASE_URL . 'books.php?msg=updated');
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    } elseif ($action === 'delete') {
        // Get title for log
        $stmtGet = $pdo->prepare("SELECT title FROM books WHERE id=?");
        $stmtGet->execute([$_POST['id']]);
        $title = $stmtGet->fetchColumn() ?: 'Unknown Book';

        $stmt = $pdo->prepare("DELETE FROM books WHERE id=?");
        $stmt->execute([$_POST['id']]);
        
        log_activity('delete', 'Menghapus buku: ' . $title);
        $_SESSION['success'] = "Buku berhasil dihapus.";
        header('Location: ' . BASE_URL . 'books.php');
    }
} else {
    header('Location: ' . BASE_URL . 'books.php');
}
