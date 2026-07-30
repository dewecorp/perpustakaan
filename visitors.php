<?php
require_once 'config/config.php';
require_login();
$pdo = db();

clean_old_visitors();

$vcols = $pdo->query("DESCRIBE visitors")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('ip_address', $vcols)) {
    $pdo->exec("ALTER TABLE visitors ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER book_id");
}
if (!in_array('country', $vcols)) {
    $pdo->exec("ALTER TABLE visitors ADD COLUMN country VARCHAR(100) DEFAULT NULL AFTER ip_address");
    $pdo->exec("ALTER TABLE visitors ADD INDEX idx_visitors_country (country)");
}

function visitor_avatar_html(string $name, int $size = 36): string {
    $s = min(max($size, 24), 80);
    if (strpos($name, 'Tamu') === 0) {
        return '<span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white" style="width:' . $s . 'px;height:' . $s . 'px;background:#6c757d;font-size:' . round($s * 0.55) . 'px;"><i class="bi bi-person-fill"></i></span>';
    }
    return '<img src="https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=random&color=fff&size=' . $s . '" class="rounded-circle" width="' . $s . '" height="' . $s . '" alt="">';
}

function visitor_purpose_meta(string $purpose): array {
    if (preg_match('/^Melihat Buku:\s*(.+)$/iu', $purpose, $m)) {
        return [
            'type' => 'view',
            'label' => 'Melihat Buku',
            'book' => trim($m[1]),
            'icon' => 'bi-eye',
            'badge' => 'text-bg-info',
        ];
    }
    if (preg_match('/^Mengunduh Buku:\s*(.+)$/iu', $purpose, $m)) {
        return [
            'type' => 'download',
            'label' => 'Mengunduh Buku',
            'book' => trim($m[1]),
            'icon' => 'bi-download',
            'badge' => 'text-bg-success',
        ];
    }
    return [
        'type' => 'other',
        'label' => 'Kunjungan',
        'book' => null,
        'detail' => $purpose,
        'icon' => 'bi-journal-bookmark',
        'badge' => 'text-bg-secondary',
    ];
}

$stats = [
    'total' => (int)$pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn(),
    'today' => (int)$pdo->query("SELECT COUNT(*) FROM visitors WHERE DATE(visit_date) = CURDATE()")->fetchColumn(),
    'yesterday' => (int)$pdo->query("SELECT COUNT(*) FROM visitors WHERE DATE(visit_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn(),
    'week' => (int)$pdo->query("SELECT COUNT(*) FROM visitors WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn(),
    'last_week' => (int)$pdo->query("SELECT COUNT(*) FROM visitors WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND visit_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn(),
    'unique' => (int)$pdo->query("SELECT COUNT(DISTINCT name) FROM visitors")->fetchColumn(),
    'views' => (int)$pdo->query("SELECT COUNT(*) FROM visitors WHERE purpose LIKE 'Melihat Buku:%'")->fetchColumn(),
    'downloads' => (int)$pdo->query("SELECT COUNT(*) FROM visitors WHERE purpose LIKE 'Mengunduh Buku:%'")->fetchColumn(),
];

$todayTrend = $stats['yesterday'] > 0 ? round((($stats['today'] - $stats['yesterday']) / $stats['yesterday']) * 100) : ($stats['today'] > 0 ? 100 : 0);
$weekTrend = $stats['last_week'] > 0 ? round((($stats['week'] - $stats['last_week']) / $stats['last_week']) * 100) : ($stats['week'] > 0 ? 100 : 0);

$dailyData = $pdo->query("
    SELECT DATE(visit_date) as date, COUNT(*) as count,
           SUM(CASE WHEN purpose LIKE 'Melihat Buku:%' THEN 1 ELSE 0 END) as views,
           SUM(CASE WHEN purpose LIKE 'Mengunduh Buku:%' THEN 1 ELSE 0 END) as downloads
    FROM visitors
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(visit_date)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

$dailyMap = [];
foreach ($dailyData as $d) {
    $dailyMap[$d['date']] = $d;
}
$dailyChart = [];
$start = new DateTime('-13 days');
$end = new DateTime();
for ($d = clone $start; $d <= $end; $d->modify('+1 day')) {
    $dateStr = $d->format('Y-m-d');
    $dayData = $dailyMap[$dateStr] ?? ['count' => 0, 'views' => 0, 'downloads' => 0];
    $dailyChart[] = [
        'label' => $d->format('j'),
        'day' => $d->format('D'),
        'count' => (int)$dayData['count'],
        'views' => (int)$dayData['views'],
        'downloads' => (int)$dayData['downloads'],
    ];
}
$maxDaily = max(array_column($dailyChart, 'count'));
$maxDaily = $maxDaily > 0 ? $maxDaily : 1;

$topBooks = $pdo->query("
    SELECT
        TRIM(SUBSTRING(purpose, LOCATE(':', purpose) + 1)) AS book_title,
        COUNT(*) AS total,
        SUM(CASE WHEN purpose LIKE 'Melihat Buku:%' THEN 1 ELSE 0 END) AS views,
        SUM(CASE WHEN purpose LIKE 'Mengunduh Buku:%' THEN 1 ELSE 0 END) AS downloads
    FROM visitors
    WHERE purpose LIKE 'Melihat Buku:%' OR purpose LIKE 'Mengunduh Buku:%'
    GROUP BY book_title
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

$visitors = $pdo->query("SELECT * FROM visitors ORDER BY visit_date DESC")->fetchAll();
$totalVisitors = count($visitors);

$countryStats = $pdo->query("
    SELECT COALESCE(NULLIF(country, ''), 'Tidak diketahui') as country, COUNT(*) as total
    FROM visitors
    GROUP BY country
    ORDER BY total DESC
    LIMIT 10
")->fetchAll();
$countryMax = !empty($countryStats) ? max(array_column($countryStats, 'total')) : 1;

$viewPct = $stats['total'] > 0 ? round(($stats['views'] / $stats['total']) * 100) : 0;
$dlPct = $stats['total'] > 0 ? round(($stats['downloads'] / $stats['total']) * 100) : 0;

function trend_arrow(int $pct): string {
    if ($pct > 0) return '<i class="bi bi-arrow-up-short text-success"></i>';
    if ($pct < 0) return '<i class="bi bi-arrow-down-short text-danger"></i>';
    return '<i class="bi bi-dash text-muted"></i>';
}
$pageTitle = "Data Pengunjung";
$pageSubtitle = "Pantau aktivitas kunjungan dan interaksi katalog digital";
$activePage = 'visitors';
$extra_css = "
<style>
.stat-card-gradient {
    border: none;
    border-radius: 0.75rem;
    overflow: hidden;
    transition: transform .15s ease, box-shadow .15s ease;
    position: relative;
}
.stat-card-gradient:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,.12) !important;
}
.stat-card-gradient .card-body {
    position: relative;
    z-index: 1;
    padding: 1.25rem 1.5rem;
}
.stat-card-gradient .stat-icon {
    position: absolute;
    right: 1rem;
    bottom: 1rem;
    font-size: 2.75rem;
    opacity: .18;
    line-height: 1;
}
.stat-card-gradient .stat-number {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: .15rem;
}
.stat-card-gradient .stat-label {
    font-size: .85rem;
    opacity: .85;
    margin-bottom: .35rem;
}
.bg-gradient-primary { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
.bg-gradient-success { background: linear-gradient(135deg, #198754, #157347); }
.bg-gradient-info { background: linear-gradient(135deg, #0dcaf0, #0ab1d6); }
.bg-gradient-warning { background: linear-gradient(135deg, #ffc107, #e6a800); }
.bg-gradient-danger { background: linear-gradient(135deg, #dc3545, #c12a36); }
.bg-gradient-purple { background: linear-gradient(135deg, #6f42c1, #59359d); }

.trend-badge {
    font-size: .75rem;
    padding: .15em .55em;
    border-radius: 1rem;
}

.visitor-bar-chart {
    display: flex;
    align-items: flex-end;
    gap: 3px;
    height: 120px;
    padding-top: 8px;
}
.visitor-bar-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    min-width: 0;
}
.visitor-bar {
    width: 100%;
    border-radius: 3px 3px 0 0;
    min-height: 2px;
    transition: height .3s ease;
    position: relative;
}
.visitor-bar-item .bar-label {
    font-size: .6rem;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
.visitor-bar-item .bar-tooltip {
    position: absolute;
    top: -22px;
    left: 50%;
    transform: translateX(-50%);
    font-size: .65rem;
    background: #212529;
    color: #fff;
    padding: 1px 6px;
    border-radius: 3px;
    opacity: 0;
    transition: opacity .15s;
    white-space: nowrap;
    pointer-events: none;
    z-index: 5;
}
.visitor-bar-item:hover .bar-tooltip {
    opacity: 1;
}
.visitor-bar-item:hover .visitor-bar {
    filter: brightness(1.1);
}

.visitor-timeline-modern .tl-item {
    display: flex;
    gap: .75rem;
    padding: .75rem 0;
    border-bottom: 1px solid rgba(0,0,0,.06);
}
.visitor-timeline-modern .tl-item:last-child {
    border-bottom: none;
}
.visitor-timeline-modern .tl-avatar {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
}
.visitor-timeline-modern .tl-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}
.visitor-timeline-modern .tl-body {
    flex: 1;
    min-width: 0;
}
.visitor-timeline-modern .tl-name {
    font-weight: 600;
    font-size: .9rem;
}
.visitor-timeline-modern .tl-meta {
    font-size: .78rem;
    color: #6c757d;
    display: flex;
    flex-wrap: wrap;
    gap: .35rem .75rem;
}
.visitor-timeline-modern .tl-book {
    font-size: .85rem;
    color: #212529;
    display: flex;
    align-items: center;
    gap: .35rem;
    margin-top: .15rem;
}
.visitor-timeline-modern .tl-book i {
    color: #6c757d;
}
.visitor-table tbody tr {
    transition: background .12s ease;
}
.visitor-table tbody tr:hover {
    background: rgba(13,110,253,.04);
}
.top-book-item .progress {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}
.top-book-item .progress-bar {
    border-radius: 2px;
}
@media (max-width: 575.98px) {
    .stat-card-gradient .stat-number {
        font-size: 1.5rem;
    }
}
</style>
";

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="row g-3 mb-4">
    <div class="col-lg-4 col-6">
        <div class="card stat-card-gradient bg-gradient-primary text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-people stat-icon"></i>
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Kunjungan</div>
                <span class="trend-badge bg-white text-dark bg-opacity-25 d-inline-flex align-items-center gap-1 px-2 py-0 rounded-pill small">
                    <?php echo $stats['unique']; ?> pengunjung unik
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="card stat-card-gradient bg-gradient-success text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-calendar-check stat-icon"></i>
                <div class="stat-number"><?php echo $stats['today']; ?></div>
                <div class="stat-label">Kunjungan Hari Ini</div>
                <span class="trend-badge d-inline-flex align-items-center gap-1 px-2 py-0 rounded-pill small" style="background:rgba(255,255,255,.2)">
                    <?php echo trend_arrow($todayTrend); ?>
                    <?php echo $todayTrend >= 0 ? '+' : ''; ?><?php echo $todayTrend; ?>%
                    <span class="opacity-75">vs kemarin</span>
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="card stat-card-gradient bg-gradient-info text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-graph-up stat-icon"></i>
                <div class="stat-number"><?php echo $stats['week']; ?></div>
                <div class="stat-label">Kunjungan 7 Hari</div>
                <span class="trend-badge d-inline-flex align-items-center gap-1 px-2 py-0 rounded-pill small" style="background:rgba(255,255,255,.2)">
                    <?php echo trend_arrow($weekTrend); ?>
                    <?php echo $weekTrend >= 0 ? '+' : ''; ?><?php echo $weekTrend; ?>%
                    <span class="opacity-75">vs pekan lalu</span>
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="card stat-card-gradient bg-gradient-warning text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-eye stat-icon"></i>
                <div class="stat-number"><?php echo $stats['views']; ?></div>
                <div class="stat-label">Total Dilihat</div>
                <span class="trend-badge d-inline-flex align-items-center gap-1 px-2 py-0 rounded-pill small" style="background:rgba(255,255,255,.2)">
                    <i class="bi bi-book"></i> Aktivitas baca
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="card stat-card-gradient bg-gradient-danger text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-download stat-icon"></i>
                <div class="stat-number"><?php echo $stats['downloads']; ?></div>
                <div class="stat-label">Total Diunduh</div>
                <span class="trend-badge d-inline-flex align-items-center gap-1 px-2 py-0 rounded-pill small" style="background:rgba(255,255,255,.2)">
                    <i class="bi bi-file-earmark-arrow-down"></i> Aktivitas unduh
                </span>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="card stat-card-gradient bg-gradient-purple text-white shadow-sm">
            <div class="card-body">
                <i class="bi bi-person-badge stat-icon"></i>
                <div class="stat-number"><?php echo $stats['unique']; ?></div>
                <div class="stat-label">Pengunjung Unik</div>
                <span class="trend-badge d-inline-flex align-items-center gap-1 px-2 py-0 rounded-pill small" style="background:rgba(255,255,255,.2)">
                    <i class="bi bi-people"></i> Total pengunjung
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-bar-chart-line me-1"></i> Tren Harian (14 Hari)</h3>
                <div class="card-tools">
                    <span class="badge text-bg-info me-1"><i class="bi bi-eye me-1"></i>Dilihat</span>
                    <span class="badge text-bg-success"><i class="bi bi-download me-1"></i>Diunduh</span>
                </div>
            </div>
            <div class="card-body">
                <?php if ($maxDaily <= 1 && $dailyChart[count($dailyChart)-1]['count'] == 0): ?>
                    <p class="text-center text-muted py-3 mb-0">Belum cukup data untuk menampilkan grafik.</p>
                <?php else: ?>
                <div class="visitor-bar-chart">
                    <?php foreach ($dailyChart as $bar):
                        $barH = max(2, round(($bar['count'] / $maxDaily) * 100));
                        $barHv = max(2, $bar['views'] > 0 ? round(($bar['views'] / $maxDaily) * 100) : 0);
                        $barHd = max(2, $bar['downloads'] > 0 ? round(($bar['downloads'] / $maxDaily) * 100) : 0);
                        $isToday = $bar['day'] === date('D') && $bar['label'] === date('j');
                    ?>
                    <div class="visitor-bar-item" title="<?php echo $bar['count']; ?> kunjungan">
                        <div class="bar-tooltip"><?php echo $bar['count']; ?></div>
                        <div style="width:100%;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end;gap:1px;">
                            <div class="visitor-bar bg-success" style="height:<?php echo $barHd; ?>%;max-width:12px;"></div>
                            <div class="visitor-bar bg-info" style="height:<?php echo $barHv; ?>%;max-width:16px;"></div>
                        </div>
                        <span class="bar-label"><?php echo $isToday ? 'Sekarang' : $bar['label']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <small class="text-muted d-block text-center mt-2">
                    Bar biru = dilihat, bar hijau = diunduh. Arahkan kursor ke bar untuk detail.
                </small>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-clock-history me-1"></i> Riwayat Kunjungan Terbaru</h3>
                <div class="card-tools">
                    <span class="badge text-bg-light text-dark border"><?php echo $stats['unique']; ?> pengunjung unik</span>
                    <span class="badge text-bg-light text-dark border ms-1"><?php echo $stats['week']; ?> minggu ini</span>
                </div>
            </div>
            <div class="card-body p-3" style="max-height:480px; overflow-y:auto;">
                <?php if (empty($visitors)): ?>
                    <p class="text-center text-muted py-4 mb-0">Belum ada data pengunjung.</p>
                <?php else: ?>
                    <div class="visitor-timeline-modern">
                        <?php
                        $shown = 0;
                        foreach ($visitors as $row):
                            if ($shown >= 20) break;
                            $shown++;
                            $meta = visitor_purpose_meta($row['purpose']);
                            $ts = strtotime($row['visit_date']);
                        ?>
                        <div class="tl-item">
                            <div class="tl-avatar">
                                <?php echo visitor_avatar_html($row['name'], 40); ?>
                            </div>
                            <div class="tl-body">
                                <div class="tl-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="tl-meta">
                                    <span class="badge <?php echo $meta['badge']; ?>"><?php echo $meta['label']; ?></span>
                                    <span><i class="bi bi-clock me-1"></i><?php echo date('H:i', $ts); ?></span>
                                    <span>&middot; <?php echo time_ago($row['visit_date']); ?></span>
                                </div>
                                <?php if (!empty($meta['book'])): ?>
                                <div class="tl-book">
                                    <i class="bi bi-book"></i>
                                    <span><?php echo htmlspecialchars($meta['book']); ?></span>
                                </div>
                                <?php elseif (!empty($meta['detail'])): ?>
                                <div class="tl-book">
                                    <i class="bi bi-info-circle"></i>
                                    <span><?php echo htmlspecialchars($meta['detail']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($totalVisitors > 20): ?>
                    <p class="text-muted text-center small mb-0 mt-2">Menampilkan 20 kunjungan terbaru. Lihat tabel lengkap di bawah.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-trophy me-1"></i> Buku Paling Diminati</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topBooks)): ?>
                    <p class="text-center text-muted p-4 mb-0">Belum ada data buku.</p>
                <?php else:
                    $topMax = max(array_column($topBooks, 'total'));
                ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topBooks as $i => $book):
                            $pct = round(($book['total'] / $topMax) * 100);
                        ?>
                        <li class="list-group-item top-book-item">
                            <div class="d-flex align-items-start gap-2 mb-1">
                                <span class="badge rounded-pill <?php echo $i === 0 ? 'text-bg-warning' : ($i === 1 ? 'text-bg-secondary' : ($i === 2 ? 'text-bg-danger' : 'text-bg-primary')); ?> flex-shrink-0 mt-1"><?php echo $i + 1; ?></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold lh-sm small text-truncate"><?php echo htmlspecialchars($book['book_title']); ?></div>
                                    <div class="progress mt-1">
                                        <div class="progress-bar" style="width:<?php echo $pct; ?>%;"></div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <small class="text-info"><i class="bi bi-eye me-1"></i><?php echo (int)$book['views']; ?></small>
                                        <small class="text-success"><i class="bi bi-download me-1"></i><?php echo (int)$book['downloads']; ?></small>
                                        <small class="text-muted"><?php echo (int)$book['total']; ?> total</small>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-pie-chart me-1"></i> Rasio Aktivitas</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Dilihat : Diunduh</span>
                    <span class="fw-bold"><?php echo $stats['views']; ?> : <?php echo $stats['downloads']; ?></span>
                </div>
                <div class="progress mb-2" style="height:12px;border-radius:6px;">
                    <div class="progress-bar bg-info rounded-start" style="width:<?php echo $viewPct; ?>%;" role="progressbar" aria-label="Dilihat"></div>
                    <div class="progress-bar bg-success" style="width:<?php echo $dlPct; ?>%;" role="progressbar" aria-label="Diunduh"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span><i class="bi bi-circle-fill text-info me-1" style="font-size:.5rem;"></i>Dilihat <?php echo $viewPct; ?>%</span>
                    <span><i class="bi bi-circle-fill text-success me-1" style="font-size:.5rem;"></i>Diunduh <?php echo $dlPct; ?>%</span>
                </div>

                <hr class="my-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Total Kunjungan</span>
                    <span class="fw-bold"><?php echo $stats['total']; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="text-muted">Kunjungan Hari Ini</span>
                    <span class="fw-bold"><?php echo $stats['today']; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="text-muted">Minggu Ini</span>
                    <span class="fw-bold"><?php echo $stats['week']; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="text-muted">Pengunjung Unik</span>
                    <span class="fw-bold"><?php echo $stats['unique']; ?></span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-globe me-1"></i> Negara Pengunjung</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($countryStats) || (count($countryStats) === 1 && $countryStats[0]['country'] === 'Tidak diketahui')): ?>
                    <p class="text-center text-muted p-4 mb-0 small">Data negara belum tersedia.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($countryStats as $c):
                            $cPct = round(($c['total'] / $countryMax) * 100);
                            $flag = strtolower(substr($c['country'], 0, 2));
                        ?>
                        <li class="list-group-item d-flex align-items-center gap-2 py-2">
                            <span class="text-muted small flex-shrink-0" style="width:20px;"><?php echo $c['country'] === 'Lokal' ? '<i class="bi bi-house-door"></i>' : '<i class="bi bi-globe2"></i>'; ?></span>
                            <span class="small flex-shrink-0" style="width:100px;"><?php echo htmlspecialchars($c['country']); ?></span>
                            <div class="flex-grow-1">
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar bg-secondary" style="width:<?php echo $cPct; ?>%;"></div>
                                </div>
                            </div>
                            <span class="small fw-semibold flex-shrink-0" style="width:40px;text-align:right;"><?php echo (int)$c['total']; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-table me-1"></i> Data Lengkap Pengunjung</h3>
        <div class="card-tools">
            <div class="btn-group btn-group-sm" role="group" id="visitorFilter">
                <button type="button" class="btn btn-outline-secondary active" data-filter="">Semua</button>
                <button type="button" class="btn btn-outline-info" data-filter="view">Dilihat</button>
                <button type="button" class="btn btn-outline-success" data-filter="download">Diunduh</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="visitorsTable" class="table table-hover align-middle visitor-table custom-table w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width:30px;">#</th>
                        <th>Pengunjung</th>
                        <th>Aktivitas</th>
                        <th>Buku</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $idx = 1; foreach ($visitors as $row):
                        $meta = visitor_purpose_meta($row['purpose']);
                        $ts = strtotime($row['visit_date']);
                    ?>
                    <tr data-type="<?php echo $meta['type']; ?>">
                        <td class="text-muted text-center small"><?php echo $idx++; ?></td>
                        <td data-order="<?php echo htmlspecialchars($row['name']); ?>">
                            <div class="d-flex align-items-center gap-2">
                                <?php echo visitor_avatar_html($row['name'], 36); ?>
                                <div>
                                    <div class="fw-semibold small"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <small class="text-muted"><?php echo time_ago($row['visit_date']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?php echo $meta['badge']; ?>">
                                <i class="bi <?php echo $meta['icon']; ?> me-1"></i><?php echo $meta['label']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($meta['book'])): ?>
                                <span class="small"><?php echo htmlspecialchars($meta['book']); ?></span>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td data-order="<?php echo $ts; ?>">
                            <div class="small fw-medium"><?php echo format_date_id($ts); ?></div>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?php echo date('H:i', $ts); ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extra_js = "
<script>
$(document).ready(function() {
    var table = $('#visitorsTable').DataTable({
        language: { url: '" . BASE_URL . "assets/lang/datatables-id.json' },
        order: [[4, 'desc']],
        columnDefs: [
            { targets: 0, orderable: false, className: 'text-center text-nowrap', width: '1%' },
            { targets: 2, orderable: false },
            { targets: 3, orderable: false }
        ],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']]
    });

    var filterFn = null;
    $('#visitorFilter button').on('click', function() {
        var filter = $(this).data('filter');
        $('#visitorFilter button').removeClass('active');
        $(this).addClass('active');

        if (filterFn) {
            $.fn.dataTable.ext.search.pop();
            filterFn = null;
        }

        if (filter) {
            filterFn = function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'visitorsTable') return true;
                var type = table.row(dataIndex).node().getAttribute('data-type');
                return type === filter;
            };
            $.fn.dataTable.ext.search.push(filterFn);
        }

        table.draw();
    });
});
</script>
";
include 'template/footer.php';
