<?php
/**
 * Impor data buku dari file JSON (hasil ekspor dari lokal).
 *
 * Dipakai di HOSTING: unggah file .json yang diekspor dari lokal, lalu sistem
 * memasukkan metadata buku tersebut. Duplikat dilewati (berdasarkan ISBN atau
 * judul+penulis).
 */
require_once 'config/config.php';
require_login();
require_admin();

$pdo = db();

$pageTitle = 'Impor Buku dari File';
$pageSubtitle = 'Pindahkan data buku dari lokal ke hosting melalui file ekspor (.json)';
$activePage = 'books';

$message = '';
$messageType = 'info';
$importStats = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_file') {
    @set_time_limit(300);

    if (empty($_FILES['import_file']['tmp_name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Gagal mengunggah file. Pastikan file dipilih dan ukurannya tidak melebihi batas server.';
        $messageType = 'danger';
    } else {
        $raw = file_get_contents($_FILES['import_file']['tmp_name']);
        $data = json_decode($raw, true);

        if (!is_array($data) || ($data['type'] ?? '') !== 'books_export' || !isset($data['books'])) {
            $message = 'File tidak valid. Pastikan ini file ekspor buku (.json) dari menu Ekspor.';
            $messageType = 'danger';
        } elseif (!is_array($data['books'])) {
            $message = 'File valid tetapi tidak berisi data buku.';
            $messageType = 'danger';
        } else {
            $books = $data['books'];
            $inserted = 0;
            $skipped = 0;
            $failed = 0;

            // Siapkan statement duplikat sekali.
            $stDupIsbn = $pdo->prepare("SELECT COUNT(*) FROM books WHERE isbn <> '' AND isbn = ?");
            $stDupTitle = $pdo->prepare("SELECT COUNT(*) FROM books WHERE title = ? AND author = ?");

            $stmt = $pdo->prepare(
                "INSERT INTO books
                    (code, isbn, title, author, category, year, cover_url, cover_path, book_path, book_url, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?)"
            );

            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM books WHERE code = ?");

            foreach ($books as $b) {
                $title = trim((string)($b['title'] ?? ''));
                if ($title === '') {
                    $skipped++;
                    continue;
                }
                $author = trim((string)($b['author'] ?? ''));
                $isbn = trim((string)($b['isbn'] ?? ''));
                $year = (int)($b['year'] ?? 0);
                $cover_url = trim((string)($b['cover_url'] ?? ''));
                $book_url = trim((string)($b['book_url'] ?? ''));
                $category = trim((string)($b['category'] ?? '')) ?: 'Ebook';
                $description = trim((string)($b['description'] ?? ''));

                // Cek duplikat.
                $exists = false;
                if ($isbn !== '') {
                    $stDupIsbn->execute([$isbn]);
                    if ((int)$stDupIsbn->fetchColumn() > 0) $exists = true;
                }
                if (!$exists) {
                    $stDupTitle->execute([$title, $author]);
                    if ((int)$stDupTitle->fetchColumn() > 0) $exists = true;
                }
                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Buat kode baru (BI = hasil impor) sesuai tahun, hindari bentrok kode.
                $code = generate_next_book_code($pdo, $year > 0 ? $year : (int)date('Y'), 'BI');

                try {
                    $stmt->execute([
                        $code, $isbn, $title,
                        $author !== '' ? $author : 'Tidak diketahui',
                        $category,
                        $year > 0 ? $year : 0,
                        $cover_url, $book_url, $description,
                    ]);
                    $inserted++;
                } catch (PDOException $e) {
                    $failed++;
                }
            }

            $importStats = [
                'total' => count($books),
                'inserted' => $inserted,
                'skipped' => $skipped,
                'failed' => $failed,
            ];

            if ($inserted > 0) {
                log_activity('create', activity_user_label() . ' mengimpor ' . $inserted . ' buku dari file ekspor');
            }

            $message = "Impor selesai. Ditambahkan {$inserted} buku, {$skipped} dilewati (sudah ada), {$failed} gagal.";
            $messageType = $inserted > 0 ? 'success' : 'warning';

            $_SESSION['success'] = $message;
        }
    }
}

include __DIR__ . '/template/header.php';
include __DIR__ . '/template/sidebar.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-file-earmark-arrow-up me-1"></i> Impor Buku dari File Ekspor</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Alur:</strong> Ekspor data buku di <b>lokal</b> (menu Data Buku &rarr; Ekspor) &rarr;
            unduh file <code>.json</code> &rarr; unggah file tersebut di sini (di <b>hosting</b>).
            <br>Duplikat otomatis dilewati (berdasarkan ISBN atau judul+penulis). File PDF/sampul lokal tidak dipindahkan — hanya URL eksternal.
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
                <?php if ($importStats): ?>
                    <a href="<?php echo BASE_URL; ?>books.php" class="alert-link ms-2">Lihat daftar buku &rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import_file">
            <div class="mb-3">
                <label class="form-label">File Ekspor (.json)</label>
                <input type="file" name="import_file" class="form-control" accept=".json,application/json" required>
                <small class="text-muted">File JSON hasil dari menu Ekspor Data Buku.</small>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i> Impor Sekarang</button>
            <a href="<?php echo BASE_URL; ?>books.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
<?php
include __DIR__ . '/template/footer.php';
