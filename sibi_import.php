<?php
require_once __DIR__ . '/config/config.php';
require_login();

$pdo = db();
$pageTitle = "Impor Buku";
$activePage = 'sibi_import';

function http_get($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PUSDIGI/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $html = curl_exec($ch);
    return $html ?: '';
}

function generate_sibi_code(PDO $pdo, $title, $author) {
    $base = 'IMP-' . substr(md5($title . '|' . $author), 0, 6);
    $code = $base;
    $suffix = 1;
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM books WHERE code = ?");
    while (true) {
        $stmtCheck->execute([$code]);
        if ($stmtCheck->fetchColumn() == 0) {
            return $code;
        }
        $code = $base . '-' . $suffix;
        $suffix++;
    }
}

function absolute_url($base, $rel) {
    if (preg_match('#^https?://#i', $rel)) return $rel;
    $p = parse_url($base);
    $scheme = $p['scheme'] ?? 'https';
    $host = $p['host'] ?? '';
    $path = rtrim(dirname($p['path'] ?? '/'), '/');
    if (strpos($rel, '/') === 0) return $scheme . '://' . $host . $rel;
    return $scheme . '://' . $host . $path . '/' . $rel;
}

function parse_listing_links($baseUrl, $html) {
    $links = [];
    // Cari anchor ke detail buku, contoh pola /katalog/slug-buku atau /buku/...
    if (preg_match_all('#<a[^>]+href=["\']([^"\']+/(katalog|buku)/[^"\']+)["\']#i', $html, $m)) {
        foreach ($m[1] as $href) {
            $links[] = absolute_url($baseUrl, html_entity_decode($href));
        }
    }
    // Jaga-jaga: cari juga link dengan teks berisi "Buku" atau "Detail"
    if (preg_match_all('#href=["\']([^"\']+)[\'"][^>]*>(?:[^<]*Buku|Detail)#i', $html, $m2)) {
        foreach ($m2[1] as $href) {
            if (stripos($href, 'buku') !== false || stripos($href, 'katalog') !== false) {
                $links[] = absolute_url($baseUrl, html_entity_decode($href));
            }
        }
    }
    // Dukungan untuk SPA (Nuxt/Vue/React): pola to="/katalog/..." atau "/buku/..."
    if (preg_match_all('#\bto=["\']([^"\']*/(katalog|buku)/[^"\']+)["\']#i', $html, $m3)) {
        foreach ($m3[1] as $href) {
            $links[] = absolute_url($baseUrl, html_entity_decode($href));
        }
    }
    if (preg_match_all('#/(katalog|buku)/[^"\'<>\s]+#i', $html, $m4)) {
        foreach ($m4[0] as $href) {
            $links[] = absolute_url($baseUrl, html_entity_decode($href));
        }
    }
    return array_values(array_unique($links));
}

function meta_content($html, $name) {
    $pattern = '#<meta[^>]+property=["\']' . preg_quote($name, '#') . '["\'][^>]+content=["\']([^"\']+)["\']#i';
    if (preg_match($pattern, $html, $m)) return trim($m[1]);
    $pattern2 = '#<meta[^>]+name=["\']' . preg_quote($name, '#') . '["\'][^>]+content=["\']([^"\']+)["\']#i';
    if (preg_match($pattern2, $html, $m)) return trim($m[1]);
    return '';
}

function extract_field($html, $label) {
    // Cari pola: <label>Penulis</label> <div>Nama</div> atau "Penulis : Nama"
    if (preg_match('#' . preg_quote($label, '#') . '\s*[:\-]\s*([^<\n\r]+)#i', strip_tags($html), $m)) {
        return trim($m[1]);
    }
    return '';
}

function parse_detail($url) {
    $html = http_get($url);
    if (!$html) return null;
    $data = [
        'title' => '',
        'author' => '',
        'isbn' => '',
        'year' => '',
        'jenjang' => '',
        'kurikulum' => '',
        'cover_url' => '',
        'read_url' => '',
        'download_url' => '',
        'detail_url' => $url
    ];
    $data['title'] = meta_content($html, 'og:title') ?: meta_content($html, 'twitter:title');
    if (!$data['title'] && preg_match('#<title>([^<]+)</title>#i', $html, $m)) {
        $data['title'] = trim(html_entity_decode($m[1]));
    }
    // Abaikan meta image SIBI karena biasanya berupa banner global, bukan cover buku.
    // Biarkan cover_url kosong; sampul bisa diatur manual dari aplikasi jika diperlukan.
    $data['cover_url'] = '';
    $data['author'] = extract_field($html, 'Penulis') ?: extract_field($html, 'Pengarang');
    $data['isbn'] = extract_field($html, 'ISBN');
    if (preg_match('#\b(19|20)\d{2}\b#', $html, $mYear)) $data['year'] = $mYear[0];
    // Jenjang & Kurikulum
    $flat = strip_tags($html);
    if (preg_match('#(SD|MI)\b#i', $flat, $mJen)) $data['jenjang'] = strtoupper($mJen[1]);
    if (stripos($flat, 'Kurikulum Merdeka') !== false) $data['kurikulum'] = 'Kurikulum Merdeka';
    // Cari tombol/anchor Unduh/Baca
    if (preg_match('#<a[^>]+href=["\']([^"\']+)["\'][^>]*>(?:\s*Unduh|\s*Download)[^<]*</a>#i', $html, $mDl)) {
        $data['download_url'] = absolute_url($url, html_entity_decode($mDl[1]));
    }
    if (preg_match('#<a[^>]+href=["\']([^"\']+)["\'][^>]*>(?:\s*Baca|\s*Lihat|\s*Read)[^<]*</a>#i', $html, $mRead)) {
        $data['read_url'] = absolute_url($url, html_entity_decode($mRead[1]));
    }
    // Dukungan untuk atribut data-link atau window.open('...')
    if (!$data['download_url'] && preg_match('#data-download-url=["\']([^"\']+)["\']#i', $html, $mDl2)) {
        $data['download_url'] = absolute_url($url, html_entity_decode($mDl2[1]));
    }
    if (!$data['read_url'] && preg_match('#window\.open\([\'"]([^\'"]+)[\'"]#i', $html, $mRd2)) {
        $data['read_url'] = absolute_url($url, html_entity_decode($mRd2[1]));
    }

    // Khusus domain mis-almourky.sch.id: abaikan link yang hanya mengarah ke halaman /download/
    $hostDetail = parse_url($url, PHP_URL_HOST);
    if ($hostDetail === 'mis-almourky.sch.id') {
        foreach (['download_url', 'read_url'] as $k) {
            if (!empty($data[$k])) {
                $p = parse_url($data[$k]);
                $path = isset($p['path']) ? trim($p['path'], '/') : '';
                if (($p['host'] ?? '') === 'mis-almourky.sch.id' && $path === 'download') {
                    $data[$k] = '';
                }
            }
        }
    }

    // Jika tidak ada read_url gunakan download_url atau detail url
    if (!$data['read_url']) $data['read_url'] = $data['download_url'] ?: $url;
    return $data;
}

$results = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'fetch') {
        $listingUrl = trim($_POST['listing_url'] ?? '');
        $detailUrls = trim($_POST['detail_urls'] ?? '');
        $limit = max(1, min(100, intval($_POST['limit'] ?? 20)));

        $urls = [];
        if ($listingUrl) {
            $html = http_get($listingUrl);
            $urls = parse_listing_links($listingUrl, $html);
            // Perlakukan URL katalog sebagai kandidat detail juga (untuk kasus 1 buku)
            $urls[] = $listingUrl;
        }
        if ($detailUrls) {
            foreach (preg_split("/\r\n|\n|\r/", $detailUrls) as $u) {
                $u = trim($u);
                if ($u) $urls[] = $u;
            }
        }
        $urls = array_slice(array_values(array_unique($urls)), 0, $limit);

        foreach ($urls as $u) {
            $d = parse_detail($u);
            if (!$d) continue;
            $results[] = $d;
        }
        if (!$results) {
            if ($listingUrl && !$detailUrls) {
                $message = 'Tidak ada buku yang berhasil diambil dari URL katalog. Halaman daftar ini kemungkinan memuat buku menggunakan JavaScript, sehingga tidak bisa diambil langsung dari server. Silakan gunakan kolom "Daftar URL Detail Buku" untuk memasukkan URL detail buku (bisa banyak sekaligus).';
            } else {
                $message = 'Tidak ada buku yang berhasil diambil dari URL yang diberikan.';
            }
        }
    } elseif ($action === 'import') {
        $payload = json_decode($_POST['payload'] ?? '[]', true);
        if (is_array($payload)) {
            $imported = 0;
            foreach ($payload as $b) {
                $title = trim($b['title'] ?? '');
                if (!$title) continue;
                $author = trim($b['author'] ?? '');
                $isbn = trim($b['isbn'] ?? '');
                $year = intval($b['year'] ?? 0);
                $cover_url = trim($b['cover_url'] ?? '');
                $book_url = trim($b['read_url'] ?? $b['download_url'] ?? $b['detail_url'] ?? '');
                // Check duplicate by ISBN or title+author
                $exists = false;
                if ($isbn) {
                    $st = $pdo->prepare("SELECT COUNT(*) FROM books WHERE isbn = ?");
                    $st->execute([$isbn]);
                    $exists = $st->fetchColumn() > 0;
                }
                if (!$exists) {
                    $st = $pdo->prepare("SELECT COUNT(*) FROM books WHERE title = ? AND author = ?");
                    $st->execute([$title, $author]);
                    $exists = $st->fetchColumn() > 0;
                }
                if ($exists) continue;
                $code = generate_sibi_code($pdo, $title, $author);
                $stmt = $pdo->prepare("INSERT INTO books (code, isbn, title, author, category, year, cover_url, cover_path, book_path, book_url, description) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?)");
                try {
                    $category = 'Ebook';
                    if (!empty($b['jenjang']) && !empty($b['kurikulum'])) {
                        $category = $b['jenjang'] . ' - ' . $b['kurikulum'];
                    }
                    $stmt->execute([
                        $code,
                        $isbn,
                        $title,
                        $author ?: 'Tidak diketahui',
                        $category,
                        $year ?: 0,
                        $cover_url,
                        $book_url,
                        'Imported from: ' . ($b['detail_url'] ?? '')
                    ]);
                    $imported++;
                } catch (PDOException $e) {
                    // Lewati buku yang gagal diinsert agar tidak menghentikan proses keseluruhan
                    continue;
                }
            }
            $_SESSION['success'] = "Import selesai. Berhasil menambahkan {$imported} buku.";
            header('Location: ' . BASE_URL . 'books.php');
            exit;
        }
    }
}

include __DIR__ . '/template/header.php';
include __DIR__ . '/template/sidebar.php';
?>
<div class="pcoded-content">
    <div class="pcoded-inner-content">
        <div class="main-body">
            <div class="page-wrapper">
                <div class="page-header card">
                    <div class="row align-items-end">
                        <div class="col-lg-8">
                            <div class="page-header-title">
                                <i class="icofont icofont-cloud-download bg-c-blue"></i>
                                <div class="d-inline">
                                    <h4>Impor Buku</h4>
                                    <span>Tarik data buku dari berbagai situs ebook (SIBI, Kemdikbud, madrasah, dan lainnya).</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-body">
                    <div class="card">
                        <div class="card-header">
                            <h5>Ambil Data</h5>
                        </div>
                        <div class="card-block">
                            <?php if ($message): ?>
                                <div class="alert alert-warning"><?php echo htmlspecialchars($message); ?></div>
                            <?php endif; ?>
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="action" value="fetch">
                                <div class="form-group">
                                    <label>URL Daftar Buku (opsional)</label>
                                    <input type="url" name="listing_url" class="form-control" placeholder="https://buku.kemendikdasmen.go.id/...">
                                    <small class="text-muted">Isi dengan halaman daftar buku dari situs mana pun. Jika tidak berhasil, gunakan daftar URL detail buku di bawah.</small>
                                </div>
                                <div class="form-group">
                                    <label>Daftar URL Detail Buku (opsional) - satu per baris</label>
                                    <textarea name="detail_urls" class="form-control" rows="4" placeholder="https://buku.kemendikdasmen.go.id/buku/..."></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-3 form-group">
                                        <label>Batas Maksimal</label>
                                        <input type="number" name="limit" class="form-control" value="20" min="1" max="100">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Tarik Data</button>
                            </form>

                            <?php if (!empty($results)): ?>
                                <h5 class="mt-4">Pratinjau Hasil (<?php echo count($results); ?>)</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Judul</th>
                                                <th>Penulis</th>
                                                <th>ISBN</th>
                                                <th>Jenjang</th>
                                                <th>Tahun</th>
                                                <th>Sampul</th>
                                                <th>Lihat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results as $r): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($r['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['author'] ?: '—'); ?></td>
                                                    <td><?php echo htmlspecialchars($r['isbn'] ?: '—'); ?></td>
                                                    <td><?php echo htmlspecialchars($r['jenjang'] ?: '—'); ?></td>
                                                    <td><?php echo htmlspecialchars($r['year'] ?: '—'); ?></td>
                                                    <td><?php if($r['cover_url']): ?><img src="<?php echo htmlspecialchars($r['cover_url']); ?>" style="height:48px"><?php endif; ?></td>
                                                    <td><a href="<?php echo htmlspecialchars($r['read_url'] ?: $r['detail_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info">Lihat</a></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="action" value="import">
                                    <input type="hidden" name="payload" value="<?php echo htmlspecialchars(json_encode($results, JSON_UNESCAPED_SLASHES)); ?>">
                                    <button type="submit" class="btn btn-success">Import Semua</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include __DIR__ . '/template/footer.php';
?>
