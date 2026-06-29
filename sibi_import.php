<?php
require_once __DIR__ . '/config/config.php';
require_login();
require_admin();

$pdo = db();
$pageTitle = "Impor Buku";
$activePage = 'sibi_import';

$lastHttpError = '';
$lastHttpInfo = [];

function http_get_last_error(): string {
    global $lastHttpError;
    return $lastHttpError;
}

function http_get_last_info(): array {
    global $lastHttpInfo;
    return is_array($lastHttpInfo) ? $lastHttpInfo : [];
}

function http_get($url) {
    global $lastHttpError, $lastHttpInfo;
    $lastHttpError = '';
    $lastHttpInfo = [];
    $url = trim((string)$url);
    if ($url === '') {
        $lastHttpError = 'URL kosong';
        return '';
    }

    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
        'Cache-Control: no-cache',
        'Connection: keep-alive',
        'Referer: ' . preg_replace('#\?.*$#', '', $url),
    ];

    $attempts = [
        ['verify' => true],
        ['verify' => false],
    ];

    if (function_exists('curl_init')) {
        foreach ($attempts as $attempt) {
            $ch = curl_init($url);
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 8,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_USERAGENT => $userAgent,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => $attempt['verify'],
                CURLOPT_SSL_VERIFYHOST => $attempt['verify'] ? 2 : 0,
                CURLOPT_ENCODING => '',
            ];
            if (defined('CURL_IPRESOLVE_V4')) {
                $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }
            curl_setopt_array($ch, $opts);
            $html = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $effectiveUrl = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $curlError = curl_error($ch);
            curl_close($ch);

            $lastHttpInfo = [
                'http_code' => $httpCode,
                'effective_url' => $effectiveUrl !== '' ? $effectiveUrl : $url,
                'ssl_verify' => $attempt['verify'] ? 1 : 0,
                'engine' => 'curl',
            ];

            if ($html !== false && $html !== '' && $httpCode >= 200 && $httpCode < 400) {
                return $html;
            }
            $lastHttpError = $curlError !== '' ? $curlError : ('HTTP ' . $httpCode);
        }
    }

    $headerLines = "User-Agent: {$userAgent}\r\n" . implode("\r\n", $headers);
    foreach ($attempts as $attempt) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $headerLines . "\r\n",
                'timeout' => 60,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => $attempt['verify'],
                'verify_peer_name' => $attempt['verify'],
            ],
        ]);
        $html = @file_get_contents($url, false, $context);
        if (is_string($html) && $html !== '') {
            $lastHttpInfo = [
                'http_code' => 200,
                'effective_url' => $url,
                'ssl_verify' => $attempt['verify'] ? 1 : 0,
                'engine' => 'file_get_contents',
            ];
            return $html;
        }
    }

    if ($lastHttpError === '') {
        $lastHttpError = 'Gagal mengambil halaman (curl/file_get_contents tidak tersedia atau diblokir hosting)';
    }
    return '';
}

function url_host($url): string {
    return strtolower((string)(parse_url((string)$url, PHP_URL_HOST) ?? ''));
}

function same_host($url, $baseUrl): bool {
    $host = url_host($url);
    $baseHost = url_host($baseUrl);
    return $host !== '' && $host === $baseHost;
}

function is_book_listing_url($url): bool {
    $url = strtolower((string)$url);
    $path = strtolower((string)(parse_url($url, PHP_URL_PATH) ?? ''));

    $listingPatterns = [
        '#/kategori/#i',
        '#/category/#i',
        '#/categories/#i',
        '#/tag/#i',
        '#/tags/#i',
        '#/search#i',
        '#/cari#i',
        '#/video/#i',
        '#/mading/#i',
        '#/publik/?$#i',
        '#/(katalog|buku|ebook|modul|perpustakaan)/?$#i',
    ];
    foreach ($listingPatterns as $pattern) {
        if (preg_match($pattern, $url) || preg_match($pattern, $path)) {
            return true;
        }
    }

    return false;
}

function is_book_detail_url($url): bool {
    $url = (string)$url;
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return false;
    }
    if (is_book_listing_url($url)) {
        return false;
    }

    $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
    $detailPatterns = [
        '#/publik/buku_detail/\d+#i',
        '#/buku_detail/\d+#i',
        '#/detail(?:/buku)?/\d+#i',
        '#/ebook/\d+#i',
        '#/modul/\d+#i',
        '#/item/\d+#i',
        '#/read/\d+#i',
        '#/(?:katalog|buku|ebook|modul|baca|read)/[^/?#]+#i',
        '#/download/[^/?#]+#i',
    ];
    foreach ($detailPatterns as $pattern) {
        if (preg_match($pattern, $url) || preg_match($pattern, $path)) {
            return true;
        }
    }

    return false;
}

function score_detail_link($url): int {
    $url = strtolower((string)$url);
    $score = 0;
    $keywords = [
        'buku_detail' => 12,
        'detail' => 8,
        'buku' => 6,
        'ebook' => 6,
        'katalog' => 5,
        'modul' => 5,
        'read' => 4,
        'download' => 3,
    ];
    foreach ($keywords as $word => $points) {
        if (strpos($url, $word) !== false) {
            $score += $points;
        }
    }
    if (preg_match('#/\d+/?$#', $url)) {
        $score += 3;
    }
    return $score;
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

function collect_href_links($baseUrl, $html): array {
    $links = [];
    if (preg_match_all('#\b(?:href|to)=["\']([^"\']+)["\']#i', $html, $matches)) {
        foreach ($matches[1] as $href) {
            $href = trim(html_entity_decode($href));
            if ($href === '' || $href === '#' || stripos($href, 'javascript:') === 0) {
                continue;
            }
            $links[] = absolute_url($baseUrl, $href);
        }
    }
    if (preg_match_all('#/(?:publik/)?buku_detail/\d+#i', $html, $inline)) {
        foreach ($inline[0] as $path) {
            $links[] = absolute_url($baseUrl, $path);
        }
    }
    return array_values(array_unique($links));
}

function parse_listing_links($baseUrl, $html) {
    $links = [];
    foreach (collect_href_links($baseUrl, $html) as $abs) {
        if (!same_host($abs, $baseUrl)) {
            continue;
        }
        if (is_book_detail_url($abs)) {
            $links[] = $abs;
        }
    }

    $links = array_values(array_unique($links));
    usort($links, function ($a, $b) {
        return score_detail_link($b) <=> score_detail_link($a);
    });
    return $links;
}

function parse_listing_previews($baseUrl, $html): array {
    $books = [];

    // Rak buku digital (contoh: bookshelf / bs-shelf-image): detail link + cover + judul di textbox
    if (preg_match_all(
        '#<div[^>]*class=["\'][^"\']*\bbs-shelf-image\b[^"\']*["\'][^>]*>\s*<a[^>]+href=["\']([^"\']+)["\'][^>]*>.*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>.*?</a>.*?<div[^>]*class=["\'][^"\']*\bbs-textbox\b[^"\']*["\'][^>]*>\s*<p>\s*(.*?)\s*</p>#is',
        $html,
        $matches,
        PREG_SET_ORDER
    )) {
        foreach ($matches as $m) {
            $detailUrl = absolute_url($baseUrl, html_entity_decode($m[1]));
            if (!same_host($detailUrl, $baseUrl) || !is_book_detail_url($detailUrl)) {
                continue;
            }
            $rawTitle = trim(html_entity_decode(strip_tags($m[3])));
            // Biasanya judul ada di baris pertama sebelum "Dilihat ..."
            $rawTitle = preg_split('/\R|Dilihat/i', $rawTitle)[0] ?? $rawTitle;
            $title = trim($rawTitle);
            if ($title === '' || preg_match('#^(dilihat|lihat|baca|unduh)\b#i', $title)) {
                continue;
            }
            $books[$detailUrl] = [
                'title' => $title,
                'author' => '',
                'isbn' => '',
                'year' => '',
                'jenjang' => '',
                'kurikulum' => '',
                'cover_url' => absolute_url($baseUrl, html_entity_decode($m[2])),
                'read_url' => $detailUrl,
                'download_url' => '',
                'detail_url' => $detailUrl,
            ];
        }
    }

    $patterns = [
        '#<a[^>]+href=["\']([^"\']+)["\'][^>]*>\s*<img[^>]+src=["\']([^"\']+)["\'][^>]*>.*?<(?:p|h\d|span|div)[^>]*>\s*([^<]{3,})#is',
        '#<div[^>]+class="[^"]*(?:shelf|course|book|card|item)[^"]*"[^>]*>.*?<a[^>]+href=["\']([^"\']+)["\'][^>]*>.*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>.*?<p[^>]*>\s*([^<]+)#is',
    ];
    foreach ($patterns as $pattern) {
        if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            continue;
        }
        foreach ($matches as $match) {
            $detailUrl = absolute_url($baseUrl, html_entity_decode($match[1]));
            if (!same_host($detailUrl, $baseUrl) || !is_book_detail_url($detailUrl)) {
                continue;
            }
            $title = trim(html_entity_decode(strip_tags($match[3])));
            if ($title === '' || preg_match('#^(dilihat|lihat|baca|unduh)\b#i', $title)) {
                continue;
            }
            $books[$detailUrl] = [
                'title' => $title,
                'author' => '',
                'isbn' => '',
                'year' => '',
                'jenjang' => '',
                'kurikulum' => '',
                'cover_url' => absolute_url($baseUrl, html_entity_decode($match[2])),
                'read_url' => $detailUrl,
                'download_url' => '',
                'detail_url' => $detailUrl,
            ];
        }
    }

    if (preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $jsonBlocks)) {
        foreach ($jsonBlocks[1] as $rawJson) {
            $decoded = json_decode(trim($rawJson), true);
            if (!is_array($decoded)) {
                continue;
            }
            $items = isset($decoded['@graph']) ? $decoded['@graph'] : [$decoded];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $type = $item['@type'] ?? '';
                if (!in_array($type, ['Book', 'PublicationIssue', 'CreativeWork'], true)) {
                    continue;
                }
                $detailUrl = (string)($item['url'] ?? $item['@id'] ?? '');
                if ($detailUrl === '' || !same_host($detailUrl, $baseUrl) || !is_book_detail_url($detailUrl)) {
                    continue;
                }
                $books[$detailUrl] = [
                    'title' => trim((string)($item['name'] ?? $item['headline'] ?? '')),
                    'author' => trim((string)($item['author']['name'] ?? $item['author'] ?? '')),
                    'isbn' => trim((string)($item['isbn'] ?? '')),
                    'year' => trim((string)($item['datePublished'] ?? '')),
                    'jenjang' => '',
                    'kurikulum' => '',
                    'cover_url' => trim((string)($item['image'] ?? '')),
                    'read_url' => $detailUrl,
                    'download_url' => '',
                    'detail_url' => $detailUrl,
                ];
            }
        }
    }

    return array_values($books);
}

function listing_has_next_page($html, $page): bool {
    $next = $page + 1;
    return (bool)preg_match(
        '#rel=["\']next["\']|page=' . $next . '(?:&|"|\'|$)|/page/' . $next . '(?:/|"|\'|$)#i',
        $html
    );
}

function build_listing_page_url($listingUrl, $page): string {
    $baseListing = preg_replace('#\?.*$#', '', rtrim((string)$listingUrl, '/'));
    if ($page <= 1) {
        return $baseListing;
    }

    if (preg_match('#/page/\d+/?$#i', $baseListing)) {
        return preg_replace('#/page/\d+/?$#i', '/page/' . $page, $baseListing);
    }

    return $baseListing . '?page=' . $page;
}

function fetch_listing_book_urls($listingUrl, $limit) {
    $urls = [];
    $page = 1;
    $maxPages = 60;

    while (count($urls) < $limit && $page <= $maxPages) {
        $pageUrl = build_listing_page_url($listingUrl, $page);
        $html = http_get($pageUrl);
        if ($html === '') {
            break;
        }

        $found = parse_listing_links($pageUrl, $html);
        if (empty($found)) {
            $listingBooks = parse_listing_previews($pageUrl, $html);
            $found = array_values(array_filter(array_map(function ($book) {
                return $book['detail_url'] ?? '';
            }, $listingBooks)));
        }

        $before = count($urls);
        foreach ($found as $u) {
            if ($u === '' || in_array($u, $urls, true)) {
                continue;
            }
            $urls[] = $u;
            if (count($urls) >= $limit) {
                break;
            }
        }

        if (empty($found)) {
            break;
        }
        if ($page > 1 && count($urls) === $before) {
            break;
        }
        if (!listing_has_next_page($html, $page)) {
            break;
        }
        $page++;
    }

    return array_slice($urls, 0, $limit);
}

function fetch_listing_books($listingUrl, $limit): array {
    $books = [];
    $page = 1;
    $maxPages = 60;

    while (count($books) < $limit && $page <= $maxPages) {
        $pageUrl = build_listing_page_url($listingUrl, $page);
        $html = http_get($pageUrl);
        if ($html === '') {
            break;
        }

        $pageBooks = parse_listing_previews($pageUrl, $html);
        if (empty($pageBooks)) {
            foreach (parse_listing_links($pageUrl, $html) as $detailUrl) {
                $pageBooks[] = [
                    'title' => '',
                    'author' => '',
                    'isbn' => '',
                    'year' => '',
                    'jenjang' => '',
                    'kurikulum' => '',
                    'cover_url' => '',
                    'read_url' => $detailUrl,
                    'download_url' => '',
                    'detail_url' => $detailUrl,
                ];
            }
        }

        $before = count($books);
        foreach ($pageBooks as $book) {
            $detailUrl = $book['detail_url'] ?? '';
            if ($detailUrl === '') {
                continue;
            }
            if (!isset($books[$detailUrl])) {
                $books[$detailUrl] = $book;
            }
            if (count($books) >= $limit) {
                break;
            }
        }

        if (empty($pageBooks)) {
            break;
        }
        if ($page > 1 && count($books) === $before) {
            break;
        }
        if (!listing_has_next_page($html, $page)) {
            break;
        }
        $page++;
    }

    return array_slice(array_values($books), 0, $limit);
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

function empty_book_data($url, array $fallback = []): array {
    return [
        'title' => trim((string)($fallback['title'] ?? '')),
        'author' => trim((string)($fallback['author'] ?? '')),
        'isbn' => trim((string)($fallback['isbn'] ?? '')),
        'year' => trim((string)($fallback['year'] ?? '')),
        'jenjang' => trim((string)($fallback['jenjang'] ?? '')),
        'kurikulum' => trim((string)($fallback['kurikulum'] ?? '')),
        'cover_url' => trim((string)($fallback['cover_url'] ?? '')),
        'read_url' => trim((string)($fallback['read_url'] ?? '')),
        'download_url' => trim((string)($fallback['download_url'] ?? '')),
        'detail_url' => $url,
    ];
}

function is_generic_page_title(string $title): bool {
    $title = strtolower(trim($title));
    if ($title === '') {
        return true;
    }
    $generic = [
        'ebook kemenag',
        'ebook',
        'beranda',
        'home',
        'perpustakaan digital',
        'perpustakaan',
        'katalog buku',
        'katalog',
        'digital library',
        'cari buku',
    ];
    foreach ($generic as $word) {
        if ($title === $word) {
            return true;
        }
    }
    return false;
}

function extract_title_from_html($html): string {
    $title = meta_content($html, 'og:title') ?: meta_content($html, 'twitter:title');
    if ($title !== '' && !is_generic_page_title($title)) {
        return trim(html_entity_decode($title));
    }
    $patterns = [
        '#<h3[^>]*class="[^"]*(?:pb-10|title|judul)[^"]*"[^>]*>([^<]+)</h3>#i',
        '#<h1[^>]*class="[^"]*(?:title|judul)[^"]*"[^>]*>([^<]+)</h1>#i',
        '#<h2[^>]*class="[^"]*(?:title|judul)[^"]*"[^>]*>([^<]+)</h2>#i',
        '#<h1[^>]*>([^<]{4,})</h1>#i',
        '#<h2[^>]*>([^<]{4,})</h2>#i',
        '#<h3[^>]*>([^<]{4,})</h3>#i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $html, $m)) {
            $candidate = trim(html_entity_decode(strip_tags($m[1])));
            if ($candidate !== '' && !is_generic_page_title($candidate)) {
                return $candidate;
            }
        }
    }
    if (preg_match('#<title>([^<]+)</title>#i', $html, $m)) {
        $candidate = trim(html_entity_decode(strip_tags($m[1])));
        if ($candidate !== '' && !is_generic_page_title($candidate)) {
            return $candidate;
        }
    }
    return '';
}

function extract_cover_from_html($html, $url): string {
    $candidates = [];
    if (preg_match('#<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']#i', $html, $m)) {
        $candidates[] = $m[1];
    }
    if (preg_match_all('#<img[^>]+src=["\']([^"\']+)["\'][^>]*>#i', $html, $imgs)) {
        foreach ($imgs[1] as $src) {
            if (preg_match('#(cover|sampul|thumbnail|image_cover|poster)#i', $src)) {
                $candidates[] = $src;
            }
        }
    }
    if (preg_match('#class="[^"]*(?:info-img|cover|sampul)[^"]*"[^>]*src="([^"]+)"#i', $html, $m)) {
        $candidates[] = $m[1];
    }
    foreach ($candidates as $src) {
        $cover = absolute_url($url, html_entity_decode($src));
        if (!preg_match('#(logo|banner|favicon|avatar|icon)#i', $cover)) {
            return $cover;
        }
    }
    return '';
}

function extract_file_links_from_html($html, $url, array &$data): void {
    if (preg_match('#pdfUrl:\s*["\']([^"\']+)["\']#i', $html, $m)) {
        $pdf = absolute_url($url, html_entity_decode($m[1]));
        $data['read_url'] = $pdf;
        $data['download_url'] = $pdf;
    }
    if (preg_match_all('#https?://[^"\'\s>]+\.pdf#i', $html, $pdfs)) {
        foreach ($pdfs[0] as $pdf) {
            $pdf = absolute_url($url, html_entity_decode($pdf));
            if ($data['read_url'] === '') {
                $data['read_url'] = $pdf;
            }
            if ($data['download_url'] === '') {
                $data['download_url'] = $pdf;
            }
            break;
        }
    }
    if (preg_match('#<a[^>]+href=["\']([^"\']+)["\'][^>]*>(?:\s*Unduh|\s*Download)[^<]*</a>#i', $html, $mDl)) {
        $data['download_url'] = absolute_url($url, html_entity_decode($mDl[1]));
    }
    if (preg_match('#<a[^>]+href=["\']([^"\']+)["\'][^>]*>(?:\s*Baca|\s*Lihat|\s*Read)[^<]*</a>#i', $html, $mRead)) {
        $data['read_url'] = absolute_url($url, html_entity_decode($mRead[1]));
    }
    if ($data['download_url'] === '' && preg_match('#data-download-url=["\']([^"\']+)["\']#i', $html, $mDl2)) {
        $data['download_url'] = absolute_url($url, html_entity_decode($mDl2[1]));
    }
    if ($data['read_url'] === '' && preg_match('#window\.open\([\'"]([^\'"]+)[\'"]#i', $html, $mRd2)) {
        $data['read_url'] = absolute_url($url, html_entity_decode($mRd2[1]));
    }
}

function enrich_book_metadata(array &$data, $html): void {
    if ($data['author'] === '') {
        $data['author'] = extract_field($html, 'Penulis') ?: extract_field($html, 'Pengarang');
    }
    if ($data['isbn'] === '') {
        $data['isbn'] = extract_field($html, 'ISBN');
    }
    if ($data['year'] === '' && preg_match('#\b(19|20)\d{2}\b#', $html, $mYear)) {
        $data['year'] = $mYear[0];
    }
    if ($data['year'] === '' && preg_match('#(?:cover|file)_\d{2}-\d{2}-(\d{4})#', $html, $m)) {
        $data['year'] = $m[1];
    }

    $flat = strip_tags($html);
    if ($data['jenjang'] === '' && preg_match('#\b(SD|MI|SMP|MTs|SMA|MA)\b#i', $flat, $mJen)) {
        $data['jenjang'] = strtoupper($mJen[1]);
    }
    if ($data['kurikulum'] === '' && stripos($flat, 'Kurikulum Merdeka') !== false) {
        $data['kurikulum'] = 'Kurikulum Merdeka';
    }
}

function parse_detail($url, $fallback = []) {
    $data = empty_book_data($url, $fallback);
    $html = http_get($url);

    if ($html !== '') {
        $scrapedTitle = extract_title_from_html($html);
        if ($scrapedTitle !== '') {
            $data['title'] = $scrapedTitle;
        }
        if ($data['cover_url'] === '') {
            $cover = extract_cover_from_html($html, $url);
            if ($cover !== '') {
                $data['cover_url'] = $cover;
            }
        }
        extract_file_links_from_html($html, $url, $data);
        enrich_book_metadata($data, $html);
    }

    if ($data['title'] === '' && !empty($fallback['title'])) {
        $data['title'] = trim((string)$fallback['title']);
    }
    if ($data['cover_url'] === '' && !empty($fallback['cover_url'])) {
        $data['cover_url'] = trim((string)$fallback['cover_url']);
    }
    if (!$data['read_url'] && !empty($fallback['read_url'])) {
        $data['read_url'] = trim((string)$fallback['read_url']);
    }
    if (!$data['download_url'] && !empty($fallback['download_url'])) {
        $data['download_url'] = trim((string)$fallback['download_url']);
    }

    if (!$data['read_url']) {
        $data['read_url'] = $data['download_url'] ?: $url;
    }
    if ($data['title'] === '' || is_generic_page_title($data['title'])) {
        return null;
    }

    return $data;
}

$results = [];
$message = '';
$debugInfo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'fetch') {
        @set_time_limit(300);
        $listingUrl = trim($_POST['listing_url'] ?? '');
        $detailUrls = trim($_POST['detail_urls'] ?? '');
        $limit = max(1, min(100, intval($_POST['limit'] ?? 20)));
        $debug = isset($_GET['debug']) && $_GET['debug'] === '1';

        $previewBooks = [];
        if ($listingUrl) {
            if (is_book_detail_url($listingUrl)) {
                $previewBooks[] = ['detail_url' => $listingUrl, 'title' => '', 'author' => '', 'isbn' => '', 'year' => '', 'jenjang' => '', 'kurikulum' => '', 'cover_url' => '', 'read_url' => $listingUrl, 'download_url' => ''];
            } else {
                $previewBooks = fetch_listing_books($listingUrl, $limit);
            }
        }
        if ($detailUrls) {
            foreach (preg_split("/\r\n|\n|\r/", $detailUrls) as $u) {
                $u = trim($u);
                if ($u === '') {
                    continue;
                }
                $previewBooks[] = [
                    'detail_url' => $u,
                    'title' => '',
                    'author' => '',
                    'isbn' => '',
                    'year' => '',
                    'jenjang' => '',
                    'kurikulum' => '',
                    'cover_url' => '',
                    'read_url' => $u,
                    'download_url' => '',
                ];
            }
        }

        $seen = [];
        foreach ($previewBooks as $preview) {
            $detailUrl = trim((string)($preview['detail_url'] ?? ''));
            if ($detailUrl === '' || isset($seen[$detailUrl])) {
                continue;
            }
            $seen[$detailUrl] = true;
            $d = parse_detail($detailUrl, $preview);
            if (!$d) {
                continue;
            }
            $results[] = $d;
            if (count($results) >= $limit) {
                break;
            }
        }

        if ($debug) {
            $detailListCount = 0;
            if ($detailUrls) {
                $detailListCount = count(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $detailUrls))));
            }
            $debugInfo = '<details class="mt-2"><summary><strong>Debug</strong></summary><pre style="white-space:pre-wrap;margin:0;">'
                . htmlspecialchars(json_encode([
                    'listing_url' => $listingUrl,
                    'detail_urls_count' => $detailListCount,
                    'preview_books_count' => count($previewBooks),
                    'unique_urls_count' => count($seen),
                    'results_count' => count($results),
                    'last_http_error' => http_get_last_error(),
                    'last_http_info' => http_get_last_info(),
                    'sample_preview' => array_slice($previewBooks, 0, 3),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8')
                . '</pre></details>';
        }
        if (!$results) {
            $httpError = http_get_last_error();
            if ($listingUrl && !$detailUrls) {
                if ($httpError !== '') {
                    $message = 'Gagal mengambil data dari URL katalog: ' . $httpError . '. Pastikan hosting mengizinkan koneksi HTTPS keluar (curl aktif).';
                } else {
                    $message = 'Tidak ada buku yang berhasil diambil dari URL katalog. Pastikan URL adalah halaman daftar/kategori buku. Jika situs memuat data lewat JavaScript saja, gunakan kolom "Daftar URL Detail Buku".';
                }
            } else {
                $message = $httpError !== ''
                    ? 'Tidak ada buku yang berhasil diambil. Error: ' . $httpError
                    : 'Tidak ada buku yang berhasil diambil dari URL yang diberikan.';
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
                $code = generate_next_book_code($pdo, $year, 'BI');
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

$pageTitle = 'Impor Buku';
$pageSubtitle = 'Tarik data buku dari berbagai situs ebook (SIBI, Kemdikbud, madrasah, dan lainnya)';
$activePage = 'sibi_import';
include __DIR__ . '/template/header.php';
include __DIR__ . '/template/sidebar.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Ambil Data</h3>
    </div>
    <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-warning"><?php echo htmlspecialchars($message); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($debugInfo)): ?>
                                <div class="alert alert-secondary"><?php echo $debugInfo; ?></div>
                            <?php endif; ?>
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="action" value="fetch">
                                <div class="mb-3">
                                    <label class="form-label">URL Daftar Buku (opsional)</label>
                                    <input type="url" name="listing_url" class="form-control" placeholder="https://contoh-perpustakaan.go.id/kategori/...">
                                    <small class="text-muted">URL halaman daftar/kategori buku dari situs perpustakaan digital mana pun. Sistem mendeteksi link detail buku secara otomatis (termasuk halaman berikutnya).</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Daftar URL Detail Buku (opsional) - satu per baris</label>
                                    <textarea name="detail_urls" class="form-control" rows="4" placeholder="https://contoh-perpustakaan.go.id/buku/...&#10;https://contoh-perpustakaan.go.id/buku_detail/123"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Batas Maksimal</label>
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
<?php
include __DIR__ . '/template/footer.php';
?>
