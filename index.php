<?php
require_once 'config/config.php';

$pdo = db();

// Filters
$cat = $_GET['cat'] ?? '';
$year = $_GET['year'] ?? '';
$q = $_GET['q'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Base query for counting
$countSql = "SELECT COUNT(*) FROM books WHERE 1=1";
$params = [];

if ($cat) {
    $countSql .= " AND category = ?";
    $params[] = $cat;
}
if ($year) {
    $countSql .= " AND year = ?";
    $params[] = $year;
}
if ($q) {
    $countSql .= " AND (title LIKE ? OR author LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

// Get total records
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalBooks = $stmtCount->fetchColumn();
$totalPages = ceil($totalBooks / $limit);

// Main query with pagination
$sql = "SELECT * FROM books WHERE 1=1";
if ($cat) $sql .= " AND category = ?";
if ($year) $sql .= " AND year = ?";
if ($q) $sql .= " AND (title LIKE ? OR author LIKE ?)";

$sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Get unique categories and years for filter dropdowns
$cats = $pdo->query("SELECT DISTINCT category FROM books ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query("SELECT DISTINCT year FROM books ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

// Get popular books
$popViews = $pdo->query("SELECT * FROM books ORDER BY views DESC LIMIT 4")->fetchAll();
$popDownloads = $pdo->query("SELECT * FROM books ORDER BY downloads DESC LIMIT 4")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Katalog Buku - PUSDIGI</title>
  <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" type="text/css" href="assets/css/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="assets/icon/themify-icons/themify-icons.css">
  <link rel="stylesheet" type="text/css" href="assets/icon/icofont/css/icofont.css">
  <link rel="stylesheet" type="text/css" href="assets/css/style.css">
  <style>
    body { background-color: #f4f7fa; }
    .hero {
      background: linear-gradient(135deg, #448aff 0%, #69b0ff 100%);
      color: #fff;
      padding: 48px 0;
      border-radius: 0 0 24px 24px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      margin-bottom: 32px;
    }
    .book-card {
      background: #fff;
      transition: transform .2s ease, box-shadow .2s ease;
      border: none;
      border-radius: 12px;
      overflow: hidden;
      height: 100%;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .book-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.12);
    }
    .book-cover-container {
        height: 240px;
        overflow: hidden;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .book-cover {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .card-body { padding: 1.5rem; }
    .book-title { font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; line-height: 1.3; color: #333; }
    .book-author { color: #666; font-size: 0.9rem; margin-bottom: 1rem; }
    .badge-group { margin-bottom: 1rem; }
    .filter-card { border-radius: 12px; border: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem; }
    
    /* Mobile Hero Adjustment */
    @media (max-width: 991px) {
        .hero {
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            text-align: center !important;
            padding: 40px 0 !important;
            height: auto !important;
            min-height: 100vh !important; /* Full viewport height for impact */
        }
        .hero .container {
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .hero .row {
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            margin: 0 !important;
            width: 100% !important;
        }
        .hero .col-lg-8 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 20px !important; /* Safe padding for text */
            margin: 0 !important;
            text-align: center !important;
        }
        .hero h1 {
            font-size: 2.5rem !important;
            line-height: 1.2 !important;
            margin-bottom: 20px !important;
            text-align: center !important;
            width: 100% !important;
        }
        .hero p.lead {
            font-size: 1.1rem !important;
            margin-bottom: 30px !important;
            text-align: center !important;
            width: 100% !important;
        }
        .hero .btn {
            display: inline-block !important;
            margin: 0 auto !important;
        }
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top" style="background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center text-white" href="index.php">
        <img src="assets/images/logo.png?v=<?php echo file_exists('assets/images/logo.png') ? filemtime('assets/images/logo.png') : time(); ?>" alt="Logo" height="40" class="mr-2 bg-white rounded p-1">
        <span class="font-weight-bold ml-2 d-none d-md-block">PERPUSTAKAAN DIGITAL | <?php echo htmlspecialchars(get_setting('school_name', 'PUSDIGI')); ?></span>
        <span class="font-weight-bold ml-2 d-block d-md-none" style="font-size: 1.2rem;">PUSDIGI</span>
      </a>
      <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navContent">
        <ul class="navbar-nav ml-auto align-items-center">
          <li class="nav-item mr-3 text-white font-weight-bold" id="live-clock" style="font-size: 0.9rem;"></li>
          <li class="nav-item"><a class="nav-link text-white font-weight-bold" href="index.php">Katalog</a></li>
          <li class="nav-item">
            <a class="nav-link text-white" href="<?php echo isset($_SESSION['user']) ? 'books.php' : 'auth/login.php'; ?>" target="_blank" title="<?php echo isset($_SESSION['user']) ? 'Dashboard Admin' : 'Login Admin'; ?>">
              <i class="ti-settings" style="font-size: 1.3rem;"></i>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <section class="hero position-relative overflow-hidden d-flex align-items-center" style="min-height: 400px; background: url('assets/images/book-hero.png') no-repeat center center / cover;">
    <div class="position-absolute" style="top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6);"></div>
    <div class="container position-relative z-index-1 text-center text-white">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <h1 class="display-4 font-weight-bold mb-3" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.6);">Temukan Buku Favoritmu</h1>
          <p class="lead mb-4 opacity-90" style="text-shadow: 1px 1px 2px rgba(0,0,0,0.6);">Akses ribuan koleksi buku digital dan fisik perpustakaan kami dengan mudah. Mulai petualangan literasimu hari ini.</p>
          <a href="#katalog" class="btn btn-light btn-lg rounded-pill px-5 text-primary font-weight-bold shadow">Mulai Mencari</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Popular Books Section -->
  <section class="container mb-5">
    <div class="text-center mb-4">
        <h2 class="font-weight-bold" style="color: #0d47a1;">Buku Terpopuler</h2>
        <p class="text-muted">Koleksi buku yang paling sering diakses oleh pengunjung</p>
    </div>

    <ul class="nav nav-pills justify-content-center mb-4" id="pills-tab" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="pills-view-tab" data-toggle="pill" href="#pills-view" role="tab" aria-controls="pills-view" aria-selected="true">Paling Banyak Dilihat</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="pills-download-tab" data-toggle="pill" href="#pills-download" role="tab" aria-controls="pills-download" aria-selected="false">Paling Banyak Diunduh</a>
      </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
      <div class="tab-pane fade show active" id="pills-view" role="tabpanel" aria-labelledby="pills-view-tab">
        <div class="row">
            <?php foreach($popViews as $book): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="book-card h-100 d-flex flex-column">
                    <div class="book-cover-container">
                        <?php 
                            $cover = $book['cover_path'] ?: $book['cover_url'];
                            if ($cover && file_exists(__DIR__ . '/' . $cover)) {
                                $cover = $cover;
                            } elseif ($cover && filter_var($cover, FILTER_VALIDATE_URL)) {
                                $cover = $cover;
                            } else {
                                $cover = 'assets/images/logo.png';
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($cover); ?>" class="book-cover" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <div class="position-absolute bg-primary text-white px-2 py-1 small rounded-left" style="bottom: 0; right: 0;">
                            <i class="ti-eye"></i> <?php echo $book['views']; ?>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column flex-grow-1">
                        <h6 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h6>
                        <p class="book-author mb-2">oleh <?php echo htmlspecialchars($book['author']); ?></p>
                        <div class="badge-group">
                            <span class="badge badge-primary bg-primary"><?php echo htmlspecialchars($book['category']); ?></span>
                        </div>
                        <p class="text-muted small mb-3 flex-grow-1"><?php echo htmlspecialchars(substr($book['description'], 0, 80)) . (strlen($book['description'])>80?'...':''); ?></p>
                        
                        <?php if(!empty($book['book_path'])): ?>
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-info text-white" onclick="previewBook('<?php echo htmlspecialchars($book['book_path']); ?>')">
                                <i class="ti-eye"></i> Lihat
                            </button>
                            <a href="track_download.php?path=<?php echo urlencode($book['book_path']); ?>" class="btn btn-sm btn-outline-success" target="_blank">
                                <i class="ti-download"></i> Unduh
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
      </div>
      <div class="tab-pane fade" id="pills-download" role="tabpanel" aria-labelledby="pills-download-tab">
        <div class="row">
            <?php foreach($popDownloads as $book): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="book-card h-100 d-flex flex-column">
                    <div class="book-cover-container">
                        <?php 
                            $cover = $book['cover_path'] ?: $book['cover_url'];
                            if ($cover && file_exists(__DIR__ . '/' . $cover)) {
                                $cover = $cover;
                            } elseif ($cover && filter_var($cover, FILTER_VALIDATE_URL)) {
                                $cover = $cover;
                            } else {
                                $cover = 'assets/images/logo.png';
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($cover); ?>" class="book-cover" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <div class="position-absolute bg-success text-white px-2 py-1 small rounded-left" style="bottom: 0; right: 0;">
                            <i class="ti-download"></i> <?php echo $book['downloads']; ?>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column flex-grow-1">
                        <h6 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h6>
                        <p class="book-author mb-2">oleh <?php echo htmlspecialchars($book['author']); ?></p>
                        <div class="badge-group">
                            <span class="badge badge-primary bg-primary"><?php echo htmlspecialchars($book['category']); ?></span>
                        </div>
                        <p class="text-muted small mb-3 flex-grow-1"><?php echo htmlspecialchars(substr($book['description'], 0, 80)) . (strlen($book['description'])>80?'...':''); ?></p>
                        
                        <?php if(!empty($book['book_path'])): ?>
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-info text-white" onclick="previewBook('<?php echo htmlspecialchars($book['book_path']); ?>')">
                                <i class="ti-eye"></i> Lihat
                            </button>
                            <a href="track_download.php?path=<?php echo urlencode($book['book_path']); ?>" class="btn btn-sm btn-outline-success" target="_blank">
                                <i class="ti-download"></i> Unduh
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <main class="container mb-5" id="katalog" style="position: relative; z-index: 2;">
    <div class="card filter-card shadow-lg border-0">
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-3">
            <label class="form-label small text-muted font-weight-bold">KATEGORI</label>
            <select name="cat" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                <?php foreach($cats as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $cat==$c?'selected':''; ?>><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted font-weight-bold">TAHUN</label>
            <select name="year" class="form-control" onchange="this.form.submit()">
                <option value="">Semua Tahun</option>
                <?php foreach($years as $y): ?>
                <option value="<?php echo htmlspecialchars($y); ?>" <?php echo $year==$y?'selected':''; ?>><?php echo htmlspecialchars($y); ?></option>
                <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label small text-muted font-weight-bold">PENCARIAN</label>
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Cari judul atau penulis..." value="<?php echo htmlspecialchars($q); ?>">
                <button type="submit" class="btn btn-primary"><i class="ti-search"></i></button>
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label small text-muted font-weight-bold d-block">&nbsp;</label>
            <a href="index.php" class="btn btn-outline-secondary w-100"><i class="ti-reload"></i> Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
        <?php if(empty($books)): ?>
        <div class="col-12 text-center py-5">
            <div class="py-5">
                <i class="ti-book text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5 class="mt-3 text-muted">Tidak ada buku yang ditemukan</h5>
                <p class="text-muted small">Coba ubah filter atau kata kunci pencarian.</p>
            </div>
        </div>
        <?php else: ?>
            <?php foreach($books as $book): ?>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="book-card h-100 d-flex flex-column">
                    <div class="book-cover-container">
                        <?php 
                            $cover = $book['cover_path'] ?: $book['cover_url'];
                            if(!$cover) $cover = 'assets/images/logo.png'; 
                        ?>
                        <img src="<?php echo htmlspecialchars($cover); ?>" class="book-cover" alt="Cover" onerror="this.src='assets/images/logo.png'">
                    </div>
                    <div class="card-body d-flex flex-column flex-grow-1">
                        <h6 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h6>
                        <p class="book-author mb-2">oleh <?php echo htmlspecialchars($book['author']); ?></p>
                        <div class="badge-group">
                            <span class="badge badge-primary bg-primary"><?php echo htmlspecialchars($book['category']); ?></span>
                            <span class="badge badge-secondary bg-secondary text-white"><?php echo htmlspecialchars($book['year']); ?></span>
                        </div>
                        <p class="text-muted small mb-3 flex-grow-1"><?php echo htmlspecialchars(substr($book['description'], 0, 100)) . (strlen($book['description'])>100?'...':''); ?></p>
                        
                        <?php if(!empty($book['book_path'])): ?>
                        <div class="mt-3 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-info text-white" onclick="previewBook('<?php echo htmlspecialchars($book['book_path']); ?>')">
                                <i class="ti-eye"></i> Lihat
                            </button>
                            <a href="track_download.php?path=<?php echo urlencode($book['book_path']); ?>" class="btn btn-sm btn-outline-success" target="_blank">
                                <i class="ti-download"></i> Unduh
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="row mt-4">
        <div class="col-12">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php 
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $queryString = http_build_query($queryParams);
                    ?>
                    
                    <!-- Previous -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo $queryString; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                            <span class="sr-only">Previous</span>
                        </a>
                    </li>

                    <!-- Page Numbers -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $queryString; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo $queryString; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                            <span class="sr-only">Next</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
  </main>

  <footer class="py-4 border-top mt-auto" style="background: linear-gradient(135deg, #e3f2fd 0%, #90caf9 100%);">
    <div class="container text-center">
      <span class="text-dark font-weight-bold">© <?php echo date('Y'); ?> Perpustakaan Digital - <?php echo htmlspecialchars((string)get_setting('school_name', 'Nama Sekolah')); ?></span>
    </div>
  </footer>

<!-- Modal Preview -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width:95vw;width:95vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pratinjau Buku</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding:0;height:90vh;overflow:auto;">
                <iframe id="previewFrame" src="" width="100%" height="100%" style="border:0"></iframe>
            </div>
        </div>
    </div>
</div>

  <script src="assets/js/jquery/jquery.min.js"></script>
  <script src="assets/js/popper.js/popper.min.js"></script>
  <script src="assets/js/bootstrap/js/bootstrap.min.js"></script>
  <script>
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        var clockElement = document.getElementById('live-clock');
        if (clockElement) {
            clockElement.innerText = now.toLocaleDateString('id-ID', options).replace('pukul', '');
        }
    }
    setInterval(updateClock, 1000);
    updateClock(); // initial call

    function previewBook(path) {
        var f = document.getElementById('previewFrame');
        if(f) {
            var url = 'preview_book_viewer.php?path=' + encodeURIComponent(path);
            f.src = url;
            $('#previewModal').modal('show');
        }
    }
  </script>
</body>
</html>