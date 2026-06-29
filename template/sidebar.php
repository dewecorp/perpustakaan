<?php
$currentRole = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'admin';
$logoUrl = BASE_URL . 'assets/images/logo.png?v=' . (file_exists('assets/images/logo.png') ? filemtime('assets/images/logo.png') : time());
$masterOpen = in_array($activePage ?? '', ['categories', 'books']);
?>
<aside class="app-sidebar shadow">
    <div class="sidebar-brand">
        <a href="<?php echo BASE_URL; ?>dashboard.php" class="brand-link">
            <img src="<?php echo $logoUrl; ?>" alt="Logo" class="brand-image opacity-75 shadow" style="max-height: 33px; width: auto;">
            <span class="brand-text fw-light">PUSDIGI</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" data-accordion="false">
                <li class="nav-header">Navigasi</li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>dashboard.php" class="nav-link <?php echo ($activePage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item <?php echo $masterOpen ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link <?php echo $masterOpen ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-layers"></i>
                        <p>
                            Master Data
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>categories.php" class="nav-link <?php echo ($activePage ?? '') === 'categories' ? 'active' : ''; ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Kategori Buku</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>books.php" class="nav-link <?php echo ($activePage ?? '') === 'books' ? 'active' : ''; ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Data Buku</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>visitors.php" class="nav-link <?php echo ($activePage ?? '') === 'visitors' ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-person-badge"></i>
                        <p>Data Pengunjung</p>
                    </a>
                </li>
                <?php if ($currentRole === 'admin'): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>users.php" class="nav-link <?php echo ($activePage ?? '') === 'users' ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-people"></i>
                        <p>Pengguna</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>settings.php" class="nav-link <?php echo ($activePage ?? '') === 'settings' ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-gear"></i>
                        <p>Pengaturan</p>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>backup.php" class="nav-link <?php echo ($activePage ?? '') === 'backup' ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-cloud-download"></i>
                        <p>Backup Restore</p>
                    </a>
                </li>
                <?php if ($currentRole === 'admin'): ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>sibi_import.php" class="nav-link <?php echo ($activePage ?? '') === 'sibi_import' ? 'active' : ''; ?>">
                        <i class="nav-icon bi bi-box-arrow-in-down"></i>
                        <p>Impor Buku</p>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>auth/logout.php" class="nav-link logout-link">
                        <i class="nav-icon bi bi-box-arrow-left"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></h3>
                    <?php if (!empty($pageSubtitle)): ?>
                    <small class="text-muted"><?php echo htmlspecialchars($pageSubtitle); ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?></li>
                    </ol>
                </div>
            </div>
            <?php if (!empty($pageActions)): ?>
            <div class="row mt-2">
                <div class="col-12 text-end"><?php echo $pageActions; ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="app-content">
        <div class="container-fluid">
