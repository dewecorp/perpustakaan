<?php
/**
 * Test seluruh rantai fetch (listing-only) untuk melihat di mana hasilnya kosong.
 */
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/perpustakaan/sibi_import.php';

// Bootstrap minimal
define('BASE_URL', 'http://localhost/perpustakaan/');

// Muat konfigurasi DB (skip koneksi untuk testing parser saja)
require_once __DIR__ . '/../config/config.php';

// Ambil seluruh fungsi parser dari sibi_import.php
$src = file_get_contents(__DIR__ . '/../sibi_import.php');

// Cari batas: dari awal sampai sebelum "$results = [];"
$cut = strpos($src, '$results = [];');
$funcBlock = substr($src, 0, $cut);

// Hapus baris bootstrap yang sudah dilakukan
$funcBlock = str_replace("<?php\n", "", $funcBlock);
$funcBlock = str_replace("require_once __DIR__ . '/config/config.php';\n", "", $funcBlock);
$funcBlock = str_replace("require_login();\n", "", $funcBlock);
$funcBlock = str_replace("require_admin();\n", "", $funcBlock);
$funcBlock = preg_replace("/\\\$pdo = db\(\);\n/", "", $funcBlock);

// Override set_time_limit supaya tidak error di CLI
$funcBlock = str_replace("@set_time_limit(", "// @set_time_limit(", $funcBlock);

eval($funcBlock);

// Sekarang test
echo "=== TEST 1: http_get listing ===\n";
$url = 'https://cendikia.kemenag.go.id/publik/kategori/1';
$html = http_get($url);
echo "HTML length: " . strlen($html) . "\n";
echo "Error: " . http_get_last_error() . "\n";

echo "\n=== TEST 2: is_book_listing_url ===\n";
echo "is_book_listing_url(kategori/1): " . (is_book_listing_url($url) ? 'Y' : 'N') . "\n";

echo "\n=== TEST 3: parse_listing_links ===\n";
$links = parse_listing_links($url, $html);
echo "Links found: " . count($links) . "\n";
if (count($links) > 0) echo "First: " . $links[0] . "\n";

echo "\n=== TEST 4: parse_listing_previews ===\n";
$previews = parse_listing_previews($url, $html);
echo "Preview books: " . count($previews) . "\n";
if (count($previews) > 0) {
    echo "First title: [" . $previews[0]['title'] . "]\n";
    echo "First detail_url: " . $previews[0]['detail_url'] . "\n";
    echo "is_generic(first title): " . (is_generic_page_title($previews[0]['title']) ? 'Y' : 'N') . "\n";
}

echo "\n=== TEST 5: fetch_listing_books ===\n";
$books = fetch_listing_books($url, 50);
echo "fetch_listing_books count: " . count($books) . "\n";
$emptyTitle = 0;
$genericTitle = 0;
foreach ($books as $i => $b) {
    $t = trim($b['title'] ?? '');
    if ($t === '') $emptyTitle++;
    elseif (is_generic_page_title($t)) $genericTitle++;
}
echo "Empty titles: $emptyTitle\n";
echo "Generic titles: $genericTitle\n";
echo "Valid titles: " . (count($books) - $emptyTitle - $genericTitle) . "\n";

echo "\n=== TEST 6: Simulate fetch handler dedup loop ===\n";
$seen = [];
$results = [];
foreach ($books as $preview) {
    $detailUrl = trim((string)($preview['detail_url'] ?? ''));
    if ($detailUrl === '' || isset($seen[$detailUrl])) continue;
    $seen[$detailUrl] = true;
    $preview['detail_url'] = $detailUrl;
    if (empty($preview['read_url'])) $preview['read_url'] = $detailUrl;

    // INI YANG PERLU DICEK — filter generic
    if (!empty($preview['title']) && !is_generic_page_title($preview['title'])) {
        $results[] = $preview;
    }
}
echo "Results after filter: " . count($results) . " (vs " . count($books) . " input)\n";
echo "Dropped by filter: " . (count($books) - count($results)) . "\n";
