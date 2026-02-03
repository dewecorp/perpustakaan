<?php
require_once 'config/config.php';
require_login();
$pdo = db();

// Clean old activities
clean_old_activities();

// Get total activity count
$totalActivities = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();

// Fetch recent activities
$activities = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100")->fetchAll();

// Statistics
$stats = [
    'categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'books' => $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
    'visitors' => $pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
];

// Visitor Chart Data (Last 12 Months)
// 1. Fetch raw data keyed by period (YYYY-MM)
$rawData = $pdo->query("
    SELECT DATE_FORMAT(visit_date, '%Y-%m') as period, COUNT(*) as count 
    FROM visitors 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) 
    GROUP BY period 
")->fetchAll(PDO::FETCH_KEY_PAIR);

// 2. Generate complete 12 months list filling missing months with 0
$chartData = [];
// Use first day of current month as base to avoid "31st" edge cases
$baseDate = strtotime(date('Y-m-01')); 
for ($i = 11; $i >= 0; $i--) {
    $timestamp = strtotime("-$i months", $baseDate);
    $date = date('Y-m', $timestamp);
    $chartData[] = [
        'period' => $date,
        'label' => date('M Y', $timestamp),
        'count' => isset($rawData[$date]) ? (int)$rawData[$date] : 0
    ];
}

$pageTitle = "Dashboard";
$activePage = 'dashboard';

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="pcoded-content">
    <div class="pcoded-inner-content">
        <div class="main-body">
            <div class="page-wrapper">
                <div class="page-header card">
                    <div class="row align-items-end">
                        <div class="col-lg-8">
                            <div class="page-header-title">
                                <i class="icofont icofont-home bg-c-blue"></i>
                                <div class="d-inline">
                                    <h4>Dashboard</h4>
                                    <span>Ringkasan statistik perpustakaan</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="page-body">
                    <div class="row">
                        <!-- Categories Card -->
                        <div class="col-md-6 col-xl-3">
                            <div class="card widget-card-1">
                                <div class="card-block-small">
                                    <i class="icofont icofont-layers bg-c-blue card1-icon"></i>
                                    <span class="text-c-blue f-w-600">Kategori Buku</span>
                                    <h4><?php echo $stats['categories']; ?></h4>
                                    <div>
                                        <span class="f-left m-t-10 text-muted">
                                            <i class="text-c-blue f-16 icofont icofont-warning m-r-10"></i>Total Kategori
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Books Card -->
                        <div class="col-md-6 col-xl-3">
                            <div class="card widget-card-1">
                                <div class="card-block-small">
                                    <i class="icofont icofont-book bg-c-pink card1-icon"></i>
                                    <span class="text-c-pink f-w-600">Data Buku</span>
                                    <h4><?php echo $stats['books']; ?></h4>
                                    <div>
                                        <span class="f-left m-t-10 text-muted">
                                            <i class="text-c-pink f-16 icofont icofont-calendar m-r-10"></i>Total Buku
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Visitors Card -->
                        <div class="col-md-6 col-xl-3">
                            <div class="card widget-card-1">
                                <div class="card-block-small">
                                    <i class="icofont icofont-users-alt-5 bg-c-green card1-icon"></i>
                                    <span class="text-c-green f-w-600">Pengunjung</span>
                                    <h4><?php echo $stats['visitors']; ?></h4>
                                    <div>
                                        <span class="f-left m-t-10 text-muted">
                                            <i class="text-c-green f-16 icofont icofont-tag m-r-10"></i>Total Pengunjung
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Users Card -->
                        <div class="col-md-6 col-xl-3">
                            <div class="card widget-card-1">
                                <div class="card-block-small">
                                    <i class="icofont icofont-ui-user bg-c-yellow card1-icon"></i>
                                    <span class="text-c-yellow f-w-600">Pengguna</span>
                                    <h4><?php echo $stats['users']; ?></h4>
                                    <div>
                                        <span class="f-left m-t-10 text-muted">
                                            <i class="text-c-yellow f-16 icofont icofont-refresh m-r-10"></i>Total User
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Chart -->
                        <div class="col-md-12 col-xl-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Statistik Pengunjung (1 Tahun Terakhir)</h5>
                                    <div class="card-header-right">
                                        <ul class="list-unstyled card-option">
                                            <li><i class="icofont icofont-simple-left "></i></li>
                                            <li><i class="icofont icofont-maximize full-card"></i></li>
                                            <li><i class="icofont icofont-minus minimize-card"></i></li>
                                            <li><i class="icofont icofont-refresh reload-card"></i></li>
                                            <li><i class="icofont icofont-error close-card"></i></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-block">
                                    <div id="visitorChart" style="height:400px;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Activity Log -->
                        <div class="col-md-12 col-xl-12">
                            <style>
                                .activity-log .row {
                                    position: relative;
                                }
                                .activity-log .row:not(:last-child)::before {
                                    content: "";
                                    position: absolute;
                                    top: 35px;
                                    left: 35px;
                                    width: 2px;
                                    height: calc(100% - 10px);
                                    background: #f1f1f1;
                                    z-index: 1;
                                }
                                .icon-circle {
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 50%;
                                    text-align: center;
                                    line-height: 40px;
                                    position: relative;
                                    z-index: 2;
                                    display: inline-block;
                                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                                }
                                .icon-circle i {
                                    color: #fff;
                                    font-size: 20px;
                                    line-height: 40px;
                                }
                            </style>
                            <div class="card">
                                <div class="card-header">
                                    <h5>Aktivitas Pengguna (Total: <?php echo $totalActivities; ?>)</h5>
                                </div>
                                <div class="card-block p-0">
                                    <div class="table-responsive" style="max-height: 460px; overflow-y: auto;">
                                        <div class="p-20 activity-log">
                                            <?php if (empty($activities)): ?>
                                                <p class="text-center text-muted">Belum ada aktivitas.</p>
                                            <?php else: ?>
                                                <?php foreach ($activities as $log): 
                                                    $icon = 'icofont-info-circle';
                                                    $bgClass = 'bg-primary';
                                                    
                                                    switch($log['action_type']) {
                                                        case 'login': 
                                                            $icon = 'icofont-login'; 
                                                            $bgClass = 'bg-c-green';
                                                            break;
                                                        case 'logout': 
                                                            $icon = 'icofont-logout'; 
                                                            $bgClass = 'bg-c-pink'; 
                                                            break;
                                                        case 'create': 
                                                            $icon = 'icofont-plus'; 
                                                            $bgClass = 'bg-c-blue';
                                                            break;
                                                        case 'update': 
                                                            $icon = 'icofont-edit'; 
                                                            $bgClass = 'bg-c-yellow';
                                                            break;
                                                        case 'delete': 
                                                            $icon = 'icofont-ui-delete'; 
                                                            $bgClass = 'bg-c-pink';
                                                            break;
                                                    }
                                                ?>
                                                <div class="row m-b-25">
                                                    <div class="col-auto p-r-0">
                                                        <div class="u-img" style="padding-left: 15px;">
                                                            <div class="icon-circle <?php echo $bgClass; ?>">
                                                                <i class="icofont <?php echo $icon; ?>"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <h6 class="m-b-5"><?php echo htmlspecialchars($log['description']); ?></h6>
                                                        <p class="text-muted m-b-0">
                                                            <i class="icofont icofont-clock-time"></i> 
                                                            <?php echo time_ago($log['created_at']); ?>
                                                            <span class="f-right text-muted f-10"><?php echo htmlspecialchars($log['username']); ?></span>
                                                        </p>
                                                        <p class="text-muted m-b-0 f-10"><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></p>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$extra_js = '
<!-- Morris Chart Dependencies -->
<script src="assets/js/raphael/raphael.min.js"></script>
<script src="assets/js/morris.js/morris.js"></script>
<script>
$(document).ready(function() {
    Morris.Bar({
        element: "visitorChart",
        data: ' . json_encode($chartData) . ',
        xkey: "label",
        ykeys: ["count"],
        labels: ["Pengunjung"],
        barColors: ["#FC6180"],
        hideHover: "auto",
        resize: true
    });
});
</script>
';
include 'template/footer.php'; 
?>
