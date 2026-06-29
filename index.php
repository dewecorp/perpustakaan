<?php
require_once 'config/config.php';

$pdo = db();

function render_book_card(array $book, array $opts = []): void
{
    $cover = book_cover_url($book);
    $isPlaceholder = ($cover === book_cover_placeholder());
    $showViews = !empty($opts['show_views']);
    $showDownloads = !empty($opts['show_downloads']);
    $hasLocalBook = !empty($book['book_path']);
    $hasRemoteBook = !empty($book['book_url']);
    $desc = htmlspecialchars(substr($book['description'] ?? '', 0, 90)) . (strlen($book['description'] ?? '') > 90 ? '...' : '');
    ?>
    <div class="col-md-6 col-lg-3 mb-4">
        <article class="book-card h-100">
            <div class="book-cover-wrap">
                <img src="<?php echo htmlspecialchars($cover); ?>" class="book-cover<?php echo $isPlaceholder ? ' is-placeholder' : ''; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?php echo book_cover_placeholder(); ?>';this.classList.add('is-placeholder');">
                <div class="book-cover-overlay">
                    <?php if ($showViews): ?>
                        <span class="overlay-stat"><i class="bi bi-eye"></i> <?php echo number_format((int)$book['views']); ?></span>
                    <?php elseif ($showDownloads): ?>
                        <span class="overlay-stat"><i class="bi bi-download"></i> <?php echo number_format((int)$book['downloads']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($book['year'])): ?>
                        <span class="overlay-year"><?php echo htmlspecialchars($book['year']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="book-card-body">
                <?php if (!empty($book['category'])): ?>
                    <span class="book-category"><?php echo htmlspecialchars($book['category']); ?></span>
                <?php endif; ?>
                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                <p class="book-author"><i class="bi bi-person"></i> <?php echo htmlspecialchars($book['author']); ?></p>
                <?php if ($desc): ?>
                    <p class="book-desc"><?php echo $desc; ?></p>
                <?php endif; ?>
                <div class="book-meta">
                    <span><i class="bi bi-eye"></i> <?php echo number_format((int)($book['views'] ?? 0)); ?></span>
                    <span><i class="bi bi-download"></i> <?php echo number_format((int)($book['downloads'] ?? 0)); ?></span>
                </div>
                <?php if ($hasLocalBook || $hasRemoteBook): ?>
                <div class="book-actions">
                    <?php if ($hasLocalBook): ?>
                        <button type="button" class="btn btn-primary btn-sm" onclick="previewBook(<?php echo (int)$book['id']; ?>, '<?php echo htmlspecialchars($book['book_path'], ENT_QUOTES, 'UTF-8'); ?>')">
                            <i class="bi bi-book-half"></i> Baca
                        </button>
                        <a href="track_book.php?id=<?php echo (int)$book['id']; ?>&action=download" class="btn btn-outline-success btn-sm" target="_blank" rel="noopener">
                            <i class="bi bi-download"></i> Unduh
                        </a>
                    <?php else: ?>
                        <a href="track_book.php?id=<?php echo (int)$book['id']; ?>&action=view" class="btn btn-primary btn-sm w-100" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right"></i> Buka Buku
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </article>
    </div>
    <?php
}

// Filters
$cat = $_GET['cat'] ?? '';
$year = $_GET['year'] ?? '';
$q = $_GET['q'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

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

$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$filteredTotal = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($filteredTotal / $limit));

$sql = "SELECT * FROM books WHERE 1=1";
if ($cat) $sql .= " AND category = ?";
if ($year) $sql .= " AND year = ?";
if ($q) $sql .= " AND (title LIKE ? OR author LIKE ?)";
$sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$cats = $pdo->query("SELECT DISTINCT category FROM books WHERE category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query("SELECT DISTINCT year FROM books WHERE year != '' ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

$popViews = $pdo->query("SELECT * FROM books ORDER BY views DESC LIMIT 4")->fetchAll();
$popDownloads = $pdo->query("SELECT * FROM books ORDER BY downloads DESC LIMIT 4")->fetchAll();
$newBooks = $pdo->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 4")->fetchAll();

$publicStats = [
    'total_books' => (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
    'total_categories' => (int)$pdo->query("SELECT COUNT(DISTINCT category) FROM books WHERE category != ''")->fetchColumn(),
    'total_views' => (int)$pdo->query("SELECT COALESCE(SUM(views), 0) FROM books")->fetchColumn(),
    'total_downloads' => (int)$pdo->query("SELECT COALESCE(SUM(downloads), 0) FROM books")->fetchColumn(),
];

$categoryStats = $pdo->query("
    SELECT category, COUNT(*) AS cnt
    FROM books
    WHERE category != ''
    GROUP BY category
    ORDER BY cnt DESC
    LIMIT 12
")->fetchAll();

$schoolName = htmlspecialchars(get_setting('school_name', 'PUSDIGI'));
$hasActiveFilter = $cat || $year || $q;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Katalog Buku - <?php echo $schoolName; ?></title>
  <link rel="icon" href="assets/images/favicon_library.svg?v=<?php echo time(); ?>" type="image/svg+xml">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <style>
    :root {
      --primary: #0d47a1;
      --primary-light: #1976d2;
      --accent: #448aff;
      --surface: #f4f7fa;
      --card-shadow: 0 4px 20px rgba(13, 71, 161, 0.08);
      --card-hover: 0 16px 40px rgba(13, 71, 161, 0.15);
    }
    body { background: var(--surface); font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
    .navbar-brand span { letter-spacing: 0.02em; }

    /* Hero */
    .hero {
      position: relative;
      min-height: 480px;
      display: flex;
      align-items: center;
      background: url('assets/images/book-hero.png') center/cover no-repeat;
    }
    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(13,71,161,0.92) 0%, rgba(25,118,210,0.85) 50%, rgba(68,138,255,0.75) 100%);
    }
    .hero-content { position: relative; z-index: 1; color: #fff; }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(255,255,255,0.15);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,0.25);
      border-radius: 50px;
      padding: 0.4rem 1rem;
      font-size: 0.85rem;
      margin-bottom: 1rem;
    }
    .hero-search {
      background: #fff;
      border-radius: 16px;
      padding: 0.5rem;
      box-shadow: 0 8px 32px rgba(0,0,0,0.15);
      max-width: 640px;
      margin: 0 auto;
    }
    .hero-search .form-control {
      border: none;
      box-shadow: none;
      padding: 0.75rem 1rem;
      font-size: 1rem;
    }
    .hero-search .form-control:focus { box-shadow: none; }
    .hero-search .btn {
      border-radius: 12px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
    }

    /* Stats strip */
    .stats-strip {
      margin-top: -48px;
      position: relative;
      z-index: 2;
      margin-bottom: 3rem;
    }
    .stat-card {
      background: #fff;
      border-radius: 16px;
      padding: 1.25rem 1.5rem;
      box-shadow: var(--card-shadow);
      display: flex;
      align-items: center;
      gap: 1rem;
      height: 100%;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--card-hover);
    }
    .stat-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      flex-shrink: 0;
    }
    .stat-icon.blue { background: #e3f2fd; color: var(--primary); }
    .stat-icon.green { background: #e8f5e9; color: #2e7d32; }
    .stat-icon.orange { background: #fff3e0; color: #ef6c00; }
    .stat-icon.purple { background: #f3e5f5; color: #7b1fa2; }
    .stat-value { font-size: 1.5rem; font-weight: 800; color: #1a1a2e; line-height: 1.2; }
    .stat-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; }

    /* Section headers */
    .section-header { margin-bottom: 2rem; }
    .section-header h2 {
      font-weight: 800;
      color: var(--primary);
      font-size: 1.75rem;
      margin-bottom: 0.25rem;
    }
    .section-header p { color: #6c757d; margin: 0; }

    /* Category chips */
    .category-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 2rem;
    }
    .category-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.5rem 1rem;
      border-radius: 50px;
      background: #fff;
      border: 2px solid #e3f2fd;
      color: var(--primary);
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 600;
      transition: all 0.2s;
    }
    .category-chip:hover, .category-chip.active {
      background: var(--primary);
      border-color: var(--primary);
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(13,71,161,0.25);
    }
    .category-chip .count {
      background: rgba(13,71,161,0.1);
      border-radius: 50px;
      padding: 0.1rem 0.5rem;
      font-size: 0.75rem;
    }
    .category-chip:hover .count, .category-chip.active .count {
      background: rgba(255,255,255,0.25);
    }

    /* Book cards */
    .book-card {
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--card-shadow);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      display: flex;
      flex-direction: column;
    }
    .book-card:hover {
      transform: translateY(-6px);
      box-shadow: var(--card-hover);
    }
    .book-cover-wrap {
      position: relative;
      height: 220px;
      background: linear-gradient(145deg, #e3f2fd, #bbdefb);
      overflow: hidden;
    }
    .book-cover {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }
    .book-cover.is-placeholder {
      object-fit: contain;
      padding: 1.5rem;
      background: linear-gradient(145deg, #e3f2fd, #bbdefb);
    }
    .book-card:hover .book-cover { transform: scale(1.05); }
    .book-cover-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, transparent 50%);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 0.75rem;
      opacity: 0;
      transition: opacity 0.25s;
    }
    .book-card:hover .book-cover-overlay { opacity: 1; }
    .overlay-stat {
      align-self: flex-end;
      background: rgba(255,255,255,0.95);
      color: var(--primary);
      padding: 0.25rem 0.6rem;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 700;
    }
    .overlay-year {
      align-self: flex-start;
      background: var(--primary);
      color: #fff;
      padding: 0.2rem 0.6rem;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .book-card-body {
      padding: 1.25rem;
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    .book-category {
      display: inline-block;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--accent);
      margin-bottom: 0.4rem;
    }
    .book-title {
      font-size: 1rem;
      font-weight: 700;
      color: #1a1a2e;
      line-height: 1.35;
      margin-bottom: 0.35rem;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .book-author {
      font-size: 0.85rem;
      color: #6c757d;
      margin-bottom: 0.5rem;
    }
    .book-desc {
      font-size: 0.8rem;
      color: #868e96;
      line-height: 1.5;
      margin-bottom: 0.75rem;
      flex: 1;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .book-meta {
      display: flex;
      gap: 1rem;
      font-size: 0.78rem;
      color: #adb5bd;
      margin-bottom: 0.75rem;
    }
    .book-meta span { display: flex; align-items: center; gap: 0.25rem; }
    .book-actions {
      display: flex;
      gap: 0.5rem;
      padding-top: 0.75rem;
      border-top: 1px solid #f0f0f0;
      margin-top: auto;
    }

    /* Tabs */
    .nav-pills .nav-link {
      border-radius: 50px;
      padding: 0.5rem 1.25rem;
      font-weight: 600;
      color: var(--primary);
      border: 2px solid #e3f2fd;
      margin: 0 0.25rem;
    }
    .nav-pills .nav-link.active {
      background: var(--primary);
      border-color: var(--primary);
    }

    /* Filter card */
    .filter-card {
      border-radius: 16px;
      border: none;
      box-shadow: var(--card-shadow);
      margin-bottom: 2rem;
    }
    .filter-card .form-label {
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      color: #6c757d;
    }
    .results-info {
      background: #e3f2fd;
      border-radius: 12px;
      padding: 0.75rem 1.25rem;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
      color: var(--primary);
    }

    /* Footer */
    .site-footer {
      background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);
      color: rgba(255,255,255,0.9);
      padding: 3rem 0 1.5rem;
      margin-top: 4rem;
    }
    .site-footer a { color: rgba(255,255,255,0.85); text-decoration: none; }
    .site-footer a:hover { color: #fff; }
    .footer-links { list-style: none; padding: 0; margin: 0; }
    .footer-links li { margin-bottom: 0.5rem; }

    .modal-backdrop.fade { opacity: 0; }
    .modal-backdrop.show { opacity: var(--bs-backdrop-opacity, 0.5) !important; }

    @media (max-width: 991px) {
      .hero { min-height: 520px; padding: 2rem 0; }
      .stats-strip { margin-top: -32px; }
      .stat-card { margin-bottom: 0.75rem; }
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top" style="background: linear-gradient(135deg, #0d47a1 0%, #1976d2 100%);">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center text-white" href="index.php">
        <img src="assets/images/logo.png?v=<?php echo file_exists('assets/images/logo.png') ? filemtime('assets/images/logo.png') : time(); ?>" alt="Logo" height="40" class="me-2 bg-white rounded p-1">
        <span class="fw-bold ms-2 d-none d-md-block">PERPUSTAKAAN DIGITAL | <?php echo $schoolName; ?></span>
        <span class="fw-bold ms-2 d-block d-md-none" style="font-size: 1.2rem;">PUSDIGI</span>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navContent">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item me-3 text-white fw-bold d-none d-lg-block" id="live-clock" style="font-size: 0.85rem;"></li>
          <li class="nav-item"><a class="nav-link text-white fw-bold" href="#katalog"><i class="bi bi-grid"></i> Katalog</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="#populer"><i class="bi bi-fire"></i> Populer</a></li>
          <li class="nav-item">
            <a class="nav-link text-white" href="<?php echo isset($_SESSION['user']) ? 'dashboard.php' : 'auth/login.php'; ?>" title="<?php echo isset($_SESSION['user']) ? 'Dashboard Admin' : 'Login Admin'; ?>">
              <i class="bi bi-gear" style="font-size: 1.2rem;"></i>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <section class="hero">
    <div class="container hero-content text-center py-5">
      <div class="hero-badge">
        <i class="bi bi-book"></i>
        <?php echo number_format($publicStats['total_books']); ?> buku tersedia
      </div>
      <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars(get_setting('hero_title', 'Temukan Buku Favoritmu')); ?></h1>
      <p class="lead mb-4 mx-auto opacity-90" style="max-width: 600px;">
        <?php echo get_setting('hero_description', 'Akses ribuan koleksi buku digital dan fisik perpustakaan kami dengan mudah. Mulai petualangan literasimu hari ini.'); ?>
      </p>
      <form method="GET" action="index.php#katalog" class="hero-search">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Cari judul buku, penulis, atau topik..." value="<?php echo htmlspecialchars($q); ?>" autocomplete="off">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
        </div>
      </form>
    </div>
  </section>

  <div class="container stats-strip">
    <div class="row g-3">
      <div class="col-6 col-lg-3">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="bi bi-journal-bookmark"></i></div>
          <div>
            <div class="stat-value"><?php echo number_format($publicStats['total_books']); ?></div>
            <div class="stat-label">Total Buku</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-tags"></i></div>
          <div>
            <div class="stat-value"><?php echo number_format($publicStats['total_categories']); ?></div>
            <div class="stat-label">Kategori</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card">
          <div class="stat-icon orange"><i class="bi bi-eye"></i></div>
          <div>
            <div class="stat-value"><?php echo number_format($publicStats['total_views']); ?></div>
            <div class="stat-label">Total Dilihat</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-card">
          <div class="stat-icon purple"><i class="bi bi-download"></i></div>
          <div>
            <div class="stat-value"><?php echo number_format($publicStats['total_downloads']); ?></div>
            <div class="stat-label">Total Diunduh</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($categoryStats)): ?>
  <section class="container mb-5">
    <div class="section-header text-center">
      <h2><i class="bi bi-collection"></i> Jelajahi Kategori</h2>
      <p>Pilih kategori untuk menemukan buku sesuai minatmu</p>
    </div>
    <div class="category-chips justify-content-center">
      <a href="index.php#katalog" class="category-chip <?php echo !$cat ? 'active' : ''; ?>">
        <i class="bi bi-grid"></i> Semua
        <span class="count"><?php echo $publicStats['total_books']; ?></span>
      </a>
      <?php foreach ($categoryStats as $cs): ?>
      <a href="?cat=<?php echo urlencode($cs['category']); ?>#katalog" class="category-chip <?php echo $cat === $cs['category'] ? 'active' : ''; ?>">
        <?php echo htmlspecialchars($cs['category']); ?>
        <span class="count"><?php echo (int)$cs['cnt']; ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($newBooks)): ?>
  <section class="container mb-5">
    <div class="section-header d-flex justify-content-between align-items-end flex-wrap gap-2">
      <div>
        <h2><i class="bi bi-stars"></i> Buku Terbaru</h2>
        <p>Koleksi buku yang baru ditambahkan ke perpustakaan</p>
      </div>
      <a href="index.php#katalog" class="btn btn-outline-primary btn-sm rounded-pill">Lihat Semua <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row">
      <?php foreach ($newBooks as $book): render_book_card($book); endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="container mb-5" id="populer">
    <div class="section-header text-center">
      <h2><i class="bi bi-fire"></i> Buku Terpopuler</h2>
      <p>Koleksi buku yang paling sering diakses oleh pengunjung</p>
    </div>

    <ul class="nav nav-pills justify-content-center mb-4" id="pills-tab" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="pills-view-tab" data-bs-toggle="pill" href="#pills-view" role="tab">Paling Banyak Dilihat</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="pills-download-tab" data-bs-toggle="pill" href="#pills-download" role="tab">Paling Banyak Diunduh</a>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="pills-view" role="tabpanel">
        <div class="row">
          <?php foreach ($popViews as $book): render_book_card($book, ['show_views' => true]); endforeach; ?>
        </div>
      </div>
      <div class="tab-pane fade" id="pills-download" role="tabpanel">
        <div class="row">
          <?php foreach ($popDownloads as $book): render_book_card($book, ['show_downloads' => true]); endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <main class="container mb-5" id="katalog">
    <div class="section-header">
      <h2><i class="bi bi-search"></i> Katalog Lengkap</h2>
      <p>Telusuri seluruh koleksi buku perpustakaan digital kami</p>
    </div>

    <div class="card filter-card">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">KATEGORI</label>
            <select name="cat" class="form-select" onchange="this.form.submit()">
              <option value="">Semua Kategori</option>
              <?php foreach ($cats as $c): ?>
              <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $cat === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">TAHUN</label>
            <select name="year" class="form-select" onchange="this.form.submit()">
              <option value="">Semua Tahun</option>
              <?php foreach ($years as $y): ?>
              <option value="<?php echo htmlspecialchars($y); ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo htmlspecialchars($y); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">PENCARIAN</label>
            <div class="input-group">
              <input type="text" name="q" class="form-control" placeholder="Cari judul atau penulis..." value="<?php echo htmlspecialchars($q); ?>">
              <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
            </div>
          </div>
          <div class="col-md-2">
            <a href="index.php#katalog" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Reset</a>
          </div>
        </form>
      </div>
    </div>

    <?php if ($hasActiveFilter): ?>
    <div class="results-info">
      <i class="bi bi-funnel"></i>
      Menampilkan <strong><?php echo number_format($filteredTotal); ?></strong> buku
      <?php if ($cat): ?> dalam kategori <strong><?php echo htmlspecialchars($cat); ?></strong><?php endif; ?>
      <?php if ($year): ?> tahun <strong><?php echo htmlspecialchars($year); ?></strong><?php endif; ?>
      <?php if ($q): ?> untuk pencarian "<strong><?php echo htmlspecialchars($q); ?></strong>"<?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="row">
      <?php if (empty($books)): ?>
      <div class="col-12 text-center py-5">
        <i class="bi bi-book text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
        <h5 class="mt-3 text-muted">Tidak ada buku yang ditemukan</h5>
        <p class="text-muted small">Coba ubah filter atau kata kunci pencarian.</p>
        <a href="index.php#katalog" class="btn btn-primary btn-sm mt-2">Reset Filter</a>
      </div>
      <?php else: ?>
        <?php foreach ($books as $book): render_book_card($book); endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav aria-label="Navigasi halaman" class="mt-4">
      <ul class="pagination justify-content-center">
        <?php
        $queryParams = $_GET;
        unset($queryParams['page']);
        $queryString = http_build_query($queryParams);
        $querySuffix = $queryString ? '&' . $queryString : '';
        ?>
        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $querySuffix; ?>#katalog">&laquo;</a>
        </li>
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?><?php echo $querySuffix; ?>#katalog"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
          <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $querySuffix; ?>#katalog">&raquo;</a>
        </li>
      </ul>
    </nav>
    <?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <h5 class="fw-bold mb-3"><i class="bi bi-book"></i> Perpustakaan Digital</h5>
          <p class="small opacity-75"><?php echo $schoolName; ?> — Platform literasi digital untuk mengakses koleksi buku kapan saja, di mana saja.</p>
        </div>
        <div class="col-md-4">
          <h6 class="fw-bold mb-3">Navigasi Cepat</h6>
          <ul class="footer-links small">
            <li><a href="#katalog"><i class="bi bi-chevron-right"></i> Katalog Buku</a></li>
            <li><a href="#populer"><i class="bi bi-chevron-right"></i> Buku Populer</a></li>
            <li><a href="auth/login.php"><i class="bi bi-chevron-right"></i> Login Admin</a></li>
          </ul>
        </div>
        <div class="col-md-4">
          <h6 class="fw-bold mb-3">Statistik Koleksi</h6>
          <ul class="footer-links small">
            <li><i class="bi bi-journal-bookmark"></i> <?php echo number_format($publicStats['total_books']); ?> buku</li>
            <li><i class="bi bi-tags"></i> <?php echo number_format($publicStats['total_categories']); ?> kategori</li>
            <li><i class="bi bi-eye"></i> <?php echo number_format($publicStats['total_views']); ?> kali dibaca</li>
          </ul>
        </div>
      </div>
      <hr class="border-light opacity-25 my-4">
      <div class="text-center small opacity-75">
        &copy; <?php echo date('Y'); ?> Perpustakaan Digital — <?php echo $schoolName; ?>
      </div>
    </div>
  </footer>

  <div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width:95vw;width:95vw;">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-book-half"></i> Pratinjau Buku</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body p-0" style="height:90vh;overflow:auto;">
          <iframe id="previewFrame" src="" width="100%" height="100%" style="border:0" title="Pratinjau buku"></iframe>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function updateClock() {
      var el = document.getElementById('live-clock');
      if (el) {
        el.textContent = new Date().toLocaleDateString('id-ID', {
          weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
          hour: '2-digit', minute: '2-digit'
        });
      }
    }
    setInterval(updateClock, 1000);
    updateClock();

    function previewBook(bookId, path) {
      var frame = document.getElementById('previewFrame');
      if (frame) {
        frame.src = 'preview_book_viewer.php?path=' + encodeURIComponent(path) + '&id=' + bookId;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('previewModal')).show();
      }
    }

    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function() {
      document.getElementById('previewFrame').src = '';
    });
  </script>
</body>
</html>
