<?php
require_once 'config/config.php';
require_login();
$pdo = db();

clean_old_visitors();

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
    'week' => (int)$pdo->query("SELECT COUNT(*) FROM visitors WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn(),
    'unique' => (int)$pdo->query("SELECT COUNT(DISTINCT name) FROM visitors")->fetchColumn(),
    'views' => (int)$pdo->query("SELECT COUNT(*) FROM visitors WHERE purpose LIKE 'Melihat Buku:%'")->fetchColumn(),
    'downloads' => (int)$pdo->query("SELECT COUNT(*) FROM visitors WHERE purpose LIKE 'Mengunduh Buku:%'")->fetchColumn(),
];

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

$pageTitle = "Data Pengunjung";
$pageSubtitle = "Pantau aktivitas kunjungan dan interaksi katalog digital";
$activePage = 'visitors';

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Kunjungan</p>
            </div>
            <i class="small-box-icon bi bi-people"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?php echo $stats['today']; ?></h3>
                <p>Kunjungan Hari Ini</p>
            </div>
            <i class="small-box-icon bi bi-calendar-check"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?php echo $stats['views']; ?></h3>
                <p>Total Dilihat</p>
            </div>
            <i class="small-box-icon bi bi-eye"></i>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?php echo $stats['downloads']; ?></h3>
                <p>Total Diunduh</p>
            </div>
            <i class="small-box-icon bi bi-download"></i>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-clock-history me-1"></i> Riwayat Kunjungan Terbaru</h3>
                <div class="card-tools">
                    <span class="badge text-bg-light text-dark"><?php echo $stats['unique']; ?> pengunjung unik</span>
                    <span class="badge text-bg-light text-dark ms-1"><?php echo $stats['week']; ?> minggu ini</span>
                </div>
            </div>
            <div class="card-body p-3" style="max-height:520px; overflow-y:auto;">
                <?php if (empty($visitors)): ?>
                    <p class="text-center text-muted py-4 mb-0">Belum ada data pengunjung.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php
                        $lastDate = null;
                        $shown = 0;
                        foreach ($visitors as $row):
                            if ($shown >= 30) break;
                            $shown++;
                            $meta = visitor_purpose_meta($row['purpose']);
                            $logDate = format_date_id($row['visit_date']);
                            if ($logDate !== $lastDate):
                                $lastDate = $logDate;
                        ?>
                        <div class="time-label">
                            <span class="text-bg-primary"><?php echo $logDate; ?></span>
                        </div>
                        <?php endif; ?>
                        <div>
                            <i class="timeline-icon bi <?php echo $meta['icon']; ?> <?php echo $meta['badge']; ?>"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="bi bi-clock-fill"></i>
                                    <?php echo date('H:i', strtotime($row['visit_date'])); ?>
                                    &middot; <?php echo time_ago($row['visit_date']); ?>
                                </span>
                                <h3 class="timeline-header no-border mb-1">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['name']); ?>&background=random&color=fff&size=32"
                                         class="rounded-circle me-1" width="28" height="28" alt="">
                                    <span class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></span>
                                    <span class="badge <?php echo $meta['badge']; ?> ms-1"><?php echo $meta['label']; ?></span>
                                </h3>
                                <div class="timeline-body pt-0">
                                    <?php if (!empty($meta['book'])): ?>
                                        <i class="bi bi-book me-1 text-muted"></i>
                                        <strong><?php echo htmlspecialchars($meta['book']); ?></strong>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($meta['detail'] ?? $row['purpose']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div>
                            <i class="timeline-icon bi bi-clock-fill text-bg-secondary"></i>
                        </div>
                    </div>
                    <?php if (count($visitors) > 30): ?>
                    <p class="text-muted text-center small mb-0 mt-2">Menampilkan 30 kunjungan terbaru. Lihat tabel lengkap di bawah.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-bar-chart me-1"></i> Buku Paling Diminati</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topBooks)): ?>
                    <p class="text-center text-muted p-4 mb-0">Belum ada data buku.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topBooks as $i => $book): ?>
                        <li class="list-group-item">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge text-bg-primary rounded-pill mt-1"><?php echo $i + 1; ?></span>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold lh-sm"><?php echo htmlspecialchars($book['book_title']); ?></div>
                                    <div class="mt-2 d-flex flex-wrap gap-1">
                                        <span class="badge text-bg-info"><i class="bi bi-eye me-1"></i><?php echo (int)$book['views']; ?> dilihat</span>
                                        <span class="badge text-bg-success"><i class="bi bi-download me-1"></i><?php echo (int)$book['downloads']; ?> unduh</span>
                                    </div>
                                </div>
                                <span class="badge text-bg-secondary"><?php echo (int)$book['total']; ?>x</span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Rasio lihat : unduh</span>
                    <span class="fw-bold"><?php echo $stats['views']; ?> : <?php echo $stats['downloads']; ?></span>
                </div>
                <?php
                $viewPct = $stats['total'] > 0 ? round(($stats['views'] / $stats['total']) * 100) : 0;
                $dlPct = $stats['total'] > 0 ? round(($stats['downloads'] / $stats['total']) * 100) : 0;
                ?>
                <div class="progress mb-1" style="height:8px;">
                    <div class="progress-bar bg-info" style="width:<?php echo $viewPct; ?>%"></div>
                    <div class="progress-bar bg-success" style="width:<?php echo $dlPct; ?>%"></div>
                </div>
                <small class="text-muted">
                    <span class="text-info"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>Dilihat <?php echo $viewPct; ?>%</span>
                    &nbsp;&middot;&nbsp;
                    <span class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>Diunduh <?php echo $dlPct; ?>%</span>
                </small>
            </div>
        </div>
    </div>
</div>

<div class="card">
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
            <table id="visitorsTable" class="table table-hover align-middle custom-table w-100">
                <thead class="table-light">
                    <tr>
                        <th>Pengunjung</th>
                        <th>Aktivitas</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visitors as $row):
                        $meta = visitor_purpose_meta($row['purpose']);
                        $ts = strtotime($row['visit_date']);
                    ?>
                    <tr data-type="<?php echo $meta['type']; ?>">
                        <td data-order="<?php echo htmlspecialchars($row['name']); ?>">
                            <div class="d-flex align-items-center gap-2">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['name']); ?>&background=random&color=fff&size=40"
                                     class="rounded-circle" width="40" height="40" alt="">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <small class="text-muted">Pengunjung katalog</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?php echo $meta['badge']; ?> me-1">
                                <i class="bi <?php echo $meta['icon']; ?> me-1"></i><?php echo $meta['label']; ?>
                            </span>
                            <?php if (!empty($meta['book'])): ?>
                                <span class="text-body"><?php echo htmlspecialchars($meta['book']); ?></span>
                            <?php else: ?>
                                <span class="text-muted"><?php echo htmlspecialchars($meta['detail'] ?? $row['purpose']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-order="<?php echo $ts; ?>">
                            <div class="fw-medium"><?php echo format_date_id($ts); ?></div>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?php echo date('H:i', $ts); ?>
                                &middot; <?php echo time_ago($row['visit_date']); ?>
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
        order: [[2, 'desc']],
        columnDefs: [
            { targets: 1, orderable: false }
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
