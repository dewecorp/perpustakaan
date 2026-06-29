<?php
// Minimal copy of parser functions for local testing
$_SERVER['REQUEST_METHOD'] = 'CLI';

function url_host($url): string {
    return strtolower((string)(parse_url((string)$url, PHP_URL_HOST) ?? ''));
}
function same_host($url, $baseUrl): bool {
    return url_host($url) !== '' && url_host($url) === url_host($baseUrl);
}
function is_book_listing_url($url): bool {
    $url = strtolower((string)$url);
    $path = strtolower((string)(parse_url($url, PHP_URL_PATH) ?? ''));
    $listingPatterns = ['#/kategori/#i','#/category/#i','#/publik/?$#i','#/(katalog|buku|ebook|modul|perpustakaan)/?$#i'];
    foreach ($listingPatterns as $pattern) {
        if (preg_match($pattern, $url) || preg_match($pattern, $path)) return true;
    }
    return false;
}
function is_book_detail_url($url): bool {
    $url = (string)$url;
    if ($url === '' || !preg_match('#^https?://#i', $url) || is_book_listing_url($url)) return false;
    $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
    $detailPatterns = ['#/publik/buku_detail/\d+#i','#/buku_detail/\d+#i','#/(?:katalog|buku|ebook|modul|baca|read)/[^/?#]+#i'];
    foreach ($detailPatterns as $pattern) {
        if (preg_match($pattern, $url) || preg_match($pattern, $path)) return true;
    }
    return false;
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

// Include parse_listing_previews from sibi_import by reading file... use eval of functions only
$code = file_get_contents(dirname(__DIR__) . '/sibi_import.php');
// Extract function bodies - simpler: require with mocked auth
define('SKIP_SIBI_IMPORT_BOOT', true);

$htmlFile = dirname(__DIR__) . '/tmp_cendikia_live.html';
if (!is_file($htmlFile)) $htmlFile = dirname(__DIR__) . '/tmp_cendikia.html';
$html = file_get_contents($htmlFile);
$url = 'https://cendikia.kemenag.go.id/publik/kategori/1';

preg_match_all('#buku_detail/\d+#', $html, $m);
echo 'buku_detail in HTML: ' . count(array_unique($m[0])) . PHP_EOL;
preg_match_all('#bs-shelf-image#', $html, $m2);
echo 'bs-shelf-image in HTML: ' . count($m2[0]) . PHP_EOL;

// Test shelf regex
$pat = '#<div[^>]*class=["\'][^"\']*\bbs-shelf-image\b[^"\']*["\'][^>]*>\s*<a[^>]+href=["\']([^"\']+)["\'][^>]*>.*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>.*?</a>.*?<div[^>]*class=["\'][^"\']*\bbs-textbox\b[^"\']*["\'][^>]*>\s*<p>\s*(.*?)\s*</p>#is';
$n = preg_match_all($pat, $html, $matches, PREG_SET_ORDER);
echo 'shelf regex matches: ' . $n . PHP_EOL;
if ($n > 0) {
    echo 'First title: ' . trim(preg_split('/\R|Dilihat/i', strip_tags($matches[0][3]))[0]) . PHP_EOL;
}
