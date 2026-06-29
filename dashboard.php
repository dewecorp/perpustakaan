<?php
require_once 'config/config.php';
require_login();
$pdo = db();

clean_old_activities();
clean_old_visitors();
$totalActivities = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$activities = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100")->fetchAll();

$stats = [
    'categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'books' => $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
    'visitors' => $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
];

$rawData = $pdo->query("
    SELECT DATE_FORMAT(visit_date, '%Y-%m') as period, COUNT(*) as count 
    FROM visitors 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) 
    GROUP BY period 
")->fetchAll(PDO::FETCH_KEY_PAIR);

$chartData = [];
$baseDate = strtotime(date('Y-m-01'));
for ($i = 11; $i >= 0; $i--) {
    $timestamp = strtotime("-$i months", $baseDate);
    $date = date('Y-m', $timestamp);
    $chartData[] = [
        'label' => format_month_year_id($timestamp),
        'count' => isset($rawData[$date]) ? (int)$rawData[$date] : 0
    ];
}

$categoryPieData = $pdo->query("
    SELECT
        COALESCE(NULLIF(b.category, ''), 'Tanpa Kategori') AS category,
        COUNT(*) AS total
    FROM visitors v
    INNER JOIN books b ON b.id = v.book_id
    WHERE v.book_id IS NOT NULL
      AND (v.purpose LIKE 'Melihat Buku:%' OR v.purpose LIKE 'Mengunduh Buku:%')
    GROUP BY COALESCE(NULLIF(b.category, ''), 'Tanpa Kategori')
    HAVING total > 0
    ORDER BY total DESC
    LIMIT 8
")->fetchAll();

if (empty($categoryPieData)) {
    $categoryPieData = $pdo->query("
        SELECT
            COALESCE(NULLIF(category, ''), 'Tanpa Kategori') AS category,
            SUM(COALESCE(views, 0) + COALESCE(downloads, 0)) AS total
        FROM books
        GROUP BY COALESCE(NULLIF(category, ''), 'Tanpa Kategori')
        HAVING total > 0
        ORDER BY total DESC
        LIMIT 8
    ")->fetchAll();
}

$pageTitle = "Dashboard";
$pageSubtitle = "Ringkasan statistik perpustakaan";
$activePage = 'dashboard';

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?php echo $stats['categories']; ?></h3>
                <p>Kategori Buku</p>
            </div>
            <i class="small-box-icon bi bi-layers"></i>
            <a href="<?php echo BASE_URL; ?>categories.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                Lihat detail <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3><?php echo $stats['books']; ?></h3>
                <p>Data Buku</p>
            </div>
            <i class="small-box-icon bi bi-book"></i>
            <a href="<?php echo BASE_URL; ?>books.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                Lihat detail <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?php echo $stats['visitors']; ?></h3>
                <p>Pengunjung</p>
            </div>
            <i class="small-box-icon bi bi-people"></i>
            <a href="<?php echo BASE_URL; ?>visitors.php" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                Lihat detail <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?php echo $stats['users']; ?></h3>
                <p>Pengguna</p>
            </div>
            <i class="small-box-icon bi bi-person-badge"></i>
            <a href="<?php echo BASE_URL; ?>users.php" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                Lihat detail <i class="bi bi-link-45deg"></i>
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Statistik Pengunjung (1 Tahun Terakhir)</h3>
            </div>
            <div class="card-body">
                <div id="visitorChart" style="height:400px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">Kategori Paling Dikunjungi</h3>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <?php if (empty($categoryPieData)): ?>
                    <p class="text-muted text-center mb-0 small">Belum ada kunjungan ke koleksi buku saat ini.<br>Buka katalog publik dan lihat/unduh buku untuk mengisi grafik.</p>
                <?php else: ?>
                    <div id="categoryPieChart" class="w-100" style="height:400px;"></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Aktivitas Pengguna (Total: <?php echo $totalActivities; ?>)</h3>
            </div>
            <div class="card-body p-3" style="max-height:460px; overflow-y:auto;">
                <?php if (empty($activities)): ?>
                    <p class="text-center text-muted p-4">Belum ada aktivitas.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php
                        $lastDate = null;
                        foreach ($activities as $log):
                            $logDate = format_date_id($log['created_at']);
                            if ($logDate !== $lastDate):
                                $lastDate = $logDate;
                        ?>
                        <div class="time-label">
                            <span class="text-bg-primary"><?php echo $logDate; ?></span>
                        </div>
                        <?php
                            endif;
                            $meta = activity_timeline_meta($log['action_type'] ?? '');
                            $actor = activity_user_label(
                                isset($log['user_id']) ? (int)$log['user_id'] : null,
                                $log['username'] ?? null
                            );
                            $action = activity_action_text($log);
                        ?>
                        <div>
                            <i class="timeline-icon bi <?php echo $meta['icon']; ?> <?php echo $meta['bg']; ?>"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="bi bi-clock-fill"></i>
                                    <?php echo date('H:i', strtotime($log['created_at'])); ?>
                                    &middot; <?php echo time_ago($log['created_at']); ?>
                                </span>
                                <h3 class="timeline-header no-border mb-0">
                                    <span class="fw-semibold"><?php echo htmlspecialchars($actor); ?></span>
                                    <?php echo ' ' . htmlspecialchars($action); ?>
                                </h3>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div>
                            <i class="timeline-icon bi bi-clock-fill text-bg-secondary"></i>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var chartData = ' . json_encode($chartData) . ';
    new ApexCharts(document.querySelector("#visitorChart"), {
        series: [{ name: "Pengunjung", data: chartData.map(function(d) { return d.count; }) }],
        chart: { type: "bar", height: 400, toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 4, columnWidth: "55%" } },
        colors: ["#0d6efd"],
        xaxis: { categories: chartData.map(function(d) { return d.label; }) },
        dataLabels: { enabled: false }
    }).render();

    var pieData = ' . json_encode($categoryPieData) . ';
    if (pieData.length && document.querySelector("#categoryPieChart")) {
        new ApexCharts(document.querySelector("#categoryPieChart"), {
            series: pieData.map(function(d) { return parseInt(d.total, 10); }),
            chart: { type: "donut", height: 400 },
            labels: pieData.map(function(d) { return d.category; }),
            colors: ["#0d6efd", "#20c997", "#ffc107", "#dc3545", "#6f42c1", "#fd7e14", "#198754", "#6c757d"],
            legend: { position: "bottom", fontSize: "12px" },
            plotOptions: {
                pie: {
                    donut: {
                        size: "65%",
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: "Total",
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce(function(a, b) { return a + b; }, 0);
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: true, formatter: function(val) { return Math.round(val) + "%"; } }
        }).render();
    }
});
</script>
';
include 'template/footer.php';
