<?php
/**
 * Impor data buku dari file JSON (hasil ekspor dari lokal).
 *
 * Dipakai di HOSTING: unggah file .json yang diekspor dari lokal, lalu sistem
 * memasukkan metadata buku tersebut. Duplikat dilewati berdasarkan ISBN yang
 * valid, lalu judul/penulis yang dinormalisasi.
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

function import_normalize_text(string $value): string {
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', trim($value));
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function import_clean_isbn(string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $lower = str_replace([html_entity_decode('&#8212;', ENT_QUOTES, 'UTF-8'), html_entity_decode('&#8211;', ENT_QUOTES, 'UTF-8')], '-', import_normalize_text($value));
    $placeholders = ['-', '--', '---', 'n/a', 'na', 'null', 'tidak ada', 'tidak tersedia', 'belum ada', 'tanpa isbn'];
    if (in_array($lower, $placeholders, true)) {
        return '';
    }

    $clean = strtoupper(preg_replace('/[^0-9X]/i', '', $value));
    $length = strlen($clean);
    if (preg_match('/^(\d)\1+$/', $clean)) {
        return '';
    }
    return ($length === 10 || $length === 13) ? $clean : '';
}

function import_normalize_url(string $value): string {
    $value = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($value === '') {
        return '';
    }
    $value = preg_replace('/\s+/', '', $value);
    $value = preg_replace('/#.*$/', '', $value);
    $value = preg_replace('/^http:\/\//i', 'https://', $value);
    return rtrim(strtolower($value), '/');
}

function import_url_is_specific_book(string $url): bool {
    $url = import_normalize_url($url);
    if ($url === '') {
        return false;
    }

    $path = strtolower((string)(parse_url($url, PHP_URL_PATH) ?? ''));
    $query = strtolower((string)(parse_url($url, PHP_URL_QUERY) ?? ''));
    if ($path === '' || $path === '/') {
        return false;
    }

    // URL daftar/kategori/pencarian sering sama untuk banyak buku. Jangan jadikan
    // kunci duplikat, karena bisa membuat semua hasil ekspor dilewati.
    $listingPatterns = [
        '#/(kategori|category|categories|tag|tags|search|cari|pencarian)(/|$)#i',
        '#/(katalog|catalog|catalogue|koleksi|collections?|perpustakaan|ebook|buku|modul)/?$#i',
        '#/konten/list/detail/#i',
    ];
    foreach ($listingPatterns as $pattern) {
        if (preg_match($pattern, $path)) {
            return false;
        }
    }
    if ($query !== '' && preg_match('#\b(q|query|keyword|search|cari|kategori|category|page)=#i', $query)) {
        return false;
    }

    $detailPatterns = [
        '#\.pdf$#i',
        '#/(publik/)?buku_detail/[^/]+#i',
        '#/(detail|detail-buku|book/detail|books/detail|buku/.+/detail|koleksi/.+/detail)/#i',
        '#/(download|viewer|reader|read|baca)/[^/]+#i',
    ];
    foreach ($detailPatterns as $pattern) {
        if (preg_match($pattern, $path)) {
            return true;
        }
    }

    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    if (count($segments) < 2) {
        return false;
    }

    $last = end($segments);
    return is_string($last) && preg_match('#[a-z0-9]#i', $last) && strlen($last) >= 4;
}

function import_source_url_from_description(string $description): string {
    if (preg_match('#Imported from:\s*(https?://\S+)#i', $description, $m)) {
        $url = import_normalize_url($m[1]);
        return import_url_is_specific_book($url) ? $url : '';
    }
    return '';
}

function import_title_key(string $title, string $author, int $year, string $category): string {
    $parts = [import_normalize_text($title)];
    if (!import_author_is_placeholder($author)) {
        $parts[] = import_normalize_text($author);
    }
    if ($year > 0) {
        $parts[] = (string)$year;
    }
    $category = import_normalize_text($category);
    if ($category !== '') {
        $parts[] = $category;
    }
    return implode('|', $parts);
}

function import_author_is_placeholder(string $author): bool {
    $author = str_replace([html_entity_decode('&#8212;', ENT_QUOTES, 'UTF-8'), html_entity_decode('&#8211;', ENT_QUOTES, 'UTF-8')], '-', import_normalize_text($author));
    return $author === '' || in_array($author, ['-', '--', 'tidak diketahui', 'unknown', 'anonim', 'n/a'], true);
}

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
            $skippedEmptyTitle = 0;
            $skippedByUrl = 0;
            $skippedByIsbn = 0;
            $skippedByTitle = 0;
            $importUrlCounts = [];

            foreach ($books as $b) {
                $candidateUrls = [
                    import_normalize_url((string)($b['book_url'] ?? '')),
                    import_source_url_from_description((string)($b['description'] ?? '')),
                ];
                foreach ($candidateUrls as $candidateUrl) {
                    if ($candidateUrl === '' || !import_url_is_specific_book($candidateUrl)) {
                        continue;
                    }
                    $importUrlCounts[$candidateUrl] = ($importUrlCounts[$candidateUrl] ?? 0) + 1;
                }
            }

            // Ambil kunci buku yang sudah ada. ISBN placeholder seperti "-" diabaikan
            // agar tidak membuat semua data impor dianggap duplikat.
            $existingIsbns = [];
            $existingUrls = [];
            $existingTitleKeys = [];
            $existingRows = $pdo->query("SELECT isbn, title, author, category, year, book_url, description FROM books")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($existingRows as $row) {
                $existingTitle = import_normalize_text((string)($row['title'] ?? ''));
                if ($existingTitle === '') {
                    continue;
                }

                $existingBookUrl = import_normalize_url((string)($row['book_url'] ?? ''));
                if (!import_url_is_specific_book($existingBookUrl)) {
                    $existingBookUrl = '';
                }
                if ($existingBookUrl !== '') {
                    $existingUrls[$existingBookUrl] = true;
                }
                $existingSourceUrl = import_source_url_from_description((string)($row['description'] ?? ''));
                if ($existingSourceUrl !== '') {
                    $existingUrls[$existingSourceUrl] = true;
                }

                $existingIsbn = import_clean_isbn((string)($row['isbn'] ?? ''));
                if ($existingIsbn !== '') {
                    $existingIsbns[$existingIsbn] = true;
                }

                $existingTitleKeys[$existingTitle] = true;
            }

            $stmt = $pdo->prepare(
                "INSERT INTO books
                    (code, isbn, title, author, category, year, cover_url, cover_path, book_path, book_url, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?)"
            );

            foreach ($books as $b) {
                $title = trim((string)($b['title'] ?? ''));
                if ($title === '') {
                    $skipped++;
                    $skippedEmptyTitle++;
                    continue;
                }
                $author = trim((string)($b['author'] ?? ''));
                $isbn = import_clean_isbn((string)($b['isbn'] ?? ''));
                $year = (int)($b['year'] ?? 0);
                $cover_url = trim((string)($b['cover_url'] ?? ''));
                $book_url = trim((string)($b['book_url'] ?? ''));
                $category = trim((string)($b['category'] ?? '')) ?: 'Ebook';
                $description = trim((string)($b['description'] ?? ''));
                $bookUrlKey = import_normalize_url($book_url);
                if (!import_url_is_specific_book($bookUrlKey)) {
                    $bookUrlKey = '';
                }
                $sourceUrlKey = import_source_url_from_description($description);
                $titleKey = import_normalize_text($title);

                // Cek duplikat: untuk data hasil ekspor lokal, URL buku/sumber adalah
                // identitas paling kuat. ISBN dan judul dipakai sebagai fallback.
                $exists = false;
                $skipReason = '';
                if ($bookUrlKey !== '' && ($importUrlCounts[$bookUrlKey] ?? 0) === 1) {
                    if (isset($existingUrls[$bookUrlKey])) {
                        $exists = true;
                        $skipReason = 'url';
                    }
                }
                if (!$exists && $sourceUrlKey !== '' && ($importUrlCounts[$sourceUrlKey] ?? 0) === 1) {
                    if (isset($existingUrls[$sourceUrlKey])) {
                        $exists = true;
                        $skipReason = 'url';
                    }
                }
                if (!$exists && $isbn !== '' && isset($existingIsbns[$isbn])) {
                    $exists = true;
                    $skipReason = 'isbn';
                }
                if (!$exists && isset($existingTitleKeys[$titleKey])) {
                    $exists = true;
                    $skipReason = 'title';
                }
                if ($exists) {
                    $skipped++;
                    if ($skipReason === 'url') {
                        $skippedByUrl++;
                    } elseif ($skipReason === 'isbn') {
                        $skippedByIsbn++;
                    } else {
                        $skippedByTitle++;
                    }
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
                    if ($isbn !== '') {
                        $existingIsbns[$isbn] = true;
                    }
                    if ($bookUrlKey !== '') {
                        $existingUrls[$bookUrlKey] = true;
                    }
                    if ($sourceUrlKey !== '') {
                        $existingUrls[$sourceUrlKey] = true;
                    }
                    $existingTitleKeys[$titleKey] = true;
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
                'skipped_empty_title' => $skippedEmptyTitle,
                'skipped_by_url' => $skippedByUrl,
                'skipped_by_isbn' => $skippedByIsbn,
                'skipped_by_title' => $skippedByTitle,
            ];

            if ($inserted > 0) {
                log_activity('create', activity_user_label() . ' mengimpor ' . $inserted . ' buku dari file ekspor');
            }

            $message = "Impor selesai. Ditambahkan {$inserted} buku, {$skipped} dilewati (URL: {$skippedByUrl}, ISBN: {$skippedByIsbn}, judul: {$skippedByTitle}, judul kosong: {$skippedEmptyTitle}), {$failed} gagal.";
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
            <br>Duplikat otomatis dilewati berdasarkan ISBN valid atau judul/penulis. File PDF/sampul lokal tidak dipindahkan — hanya URL eksternal.
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
