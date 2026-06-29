<?php
$lteBase = BASE_URL . 'assets/vendor/admin-lte/';
$pageSubtitle = $pageSubtitle ?? '';
$githubUpdate = null;
if (isset($_SESSION['user']) && current_user_role() === 'admin') {
    $githubUpdate = check_github_update(true);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta name="color-scheme" content="light dark">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - PUSDIGI' : 'PUSDIGI'; ?></title>
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon_library.svg?v=<?php echo time(); ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $lteBase; ?>css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    <?php if (!empty($extra_css)) echo $extra_css; ?>
    <style>
        .app-header.navbar {
            background: linear-gradient(to right, #0d47a1, #1976d2) !important;
        }
        .app-header .nav-link,
        .app-header .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        .app-header .navbar-nav .nav-link:hover {
            color: #fff !important;
        }
        #live-clock {
            color: #fff;
            font-size: 0.875rem;
            padding: 0 0.75rem;
            display: flex;
            align-items: center;
        }
        .user-menu .user-image {
            width: 2rem;
            height: 2rem;
            object-fit: cover;
        }
        /* Fix: AdminLTE reduced-motion rule sets .fade { opacity: 1 !important } which makes backdrop solid black */
        .modal-backdrop.fade {
            opacity: 0;
        }
        .modal-backdrop.show {
            opacity: var(--bs-backdrop-opacity, 0.5) !important;
        }
        body.modal-open .app-wrapper {
            z-index: auto;
        }
        .app-sidebar {
            background: linear-gradient(180deg, #0d47a1 0%, #1976d2 100%) !important;
        }
        .app-sidebar .brand-link,
        .app-sidebar .brand-text {
            color: #fff !important;
        }
        .app-sidebar .sidebar-brand {
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        .app-sidebar .nav-header {
            color: rgba(255, 255, 255, 0.55) !important;
        }
        .app-sidebar .sidebar-menu .nav-link {
            color: rgba(255, 255, 255, 0.88) !important;
        }
        .app-sidebar .sidebar-menu .nav-link .nav-icon,
        .app-sidebar .sidebar-menu .nav-link p,
        .app-sidebar .sidebar-menu .nav-link .nav-arrow {
            color: inherit !important;
        }
        .app-sidebar .sidebar-menu .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.12) !important;
            color: #fff !important;
        }
        .app-sidebar .sidebar-menu .nav-link.active {
            background-color: rgba(255, 255, 255, 0.22) !important;
            color: #fff !important;
            box-shadow: inset 3px 0 0 #fff;
        }
        .app-sidebar .nav-treeview {
            background-color: rgba(0, 0, 0, 0.12) !important;
        }
        .app-sidebar .nav-treeview > .nav-item > .nav-link {
            color: rgba(255, 255, 255, 0.82) !important;
        }
        .app-sidebar .nav-treeview > .nav-item > .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15) !important;
            color: #fff !important;
        }
        /* Tabel compact: lebar kolom mengikuti isi */
        table.table-compact.dataTable {
            width: auto !important;
            max-width: 100%;
            table-layout: auto !important;
        }
        table.table-compact.dataTable thead th:first-child,
        table.table-compact.dataTable tbody td:first-child {
            width: 1%;
            white-space: nowrap;
            text-align: center;
        }
        table.table-compact.dataTable thead th:last-child,
        table.table-compact.dataTable tbody td:last-child {
            width: 1%;
            white-space: nowrap;
        }
        table.table-compact.dataTable thead th:not(:first-child):not(:last-child),
        table.table-compact.dataTable tbody td:not(:first-child):not(:last-child) {
            white-space: nowrap;
        }
        .user-footer .btn-update-system,
        .user-footer .logout-link {
            width: 100%;
        }
        .user-footer-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0.75rem;
            width: 100%;
        }
        .navbar-badge {
            position: absolute;
            top: 4px;
            right: 0;
            font-size: 0.6rem;
            padding: 0.2rem 0.35rem;
        }
        .nav-item.position-relative > .nav-link {
            position: relative;
        }
        .btn-update-system.has-update-pulse {
            animation: updatePulse 2s infinite;
        }
        @keyframes updatePulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.35); }
            50% { box-shadow: 0 0 0 6px rgba(13, 110, 253, 0); }
        }
    </style>
</head>
<body class="layout-fixed fixed-header sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <span class="nav-link fw-semibold text-uppercase mb-0">
                        <?php echo htmlspecialchars(mb_strtoupper(get_setting('school_name', 'Perpustakaan'), 'UTF-8')); ?>
                    </span>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item d-none d-md-block">
                    <span class="nav-link" id="live-clock"></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                        <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                        <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
                    </a>
                </li>
                <?php if (isset($_SESSION['user']) && current_user_role() === 'admin' && !empty($githubUpdate['has_update'])): ?>
                <li class="nav-item dropdown position-relative">
                    <a class="nav-link" href="#" data-bs-toggle="dropdown" aria-label="Notifikasi update">
                        <i class="bi bi-bell"></i>
                        <span class="badge rounded-pill text-bg-warning navbar-badge">1</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 shadow">
                        <div class="dropdown-header bg-warning text-dark fw-bold">
                            <i class="bi bi-cloud-download me-1"></i> Pembaruan Tersedia
                        </div>
                        <div class="p-3">
                            <p class="small text-muted mb-2">
                                Commit <strong><?php echo htmlspecialchars($githubUpdate['latest']['sha'] ?? ''); ?></strong>
                                <?php if (!empty($githubUpdate['latest']['message'])): ?>
                                — <?php echo htmlspecialchars($githubUpdate['latest']['message']); ?>
                                <?php endif; ?>
                            </p>
                            <button type="button" class="btn btn-primary btn-sm w-100" id="btnUpdateFromNotif">
                                <i class="bi bi-cloud-download me-1"></i> Update Sekarang
                            </button>
                        </div>
                    </div>
                </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user'])):
                    if (!isset($pdo)) $pdo = db();
                    $stmtHeader = $pdo->prepare("SELECT name, avatar, username FROM users WHERE id = ?");
                    $stmtHeader->execute([$_SESSION['user']['id']]);
                    $currentUser = $stmtHeader->fetch();
                    $displayName = $currentUser['name'] ?? $currentUser['username'] ?? 'Admin';
                    $displayAvatar = $currentUser['avatar'] ?? '';
                    if (empty($displayAvatar) || !file_exists($displayAvatar)) {
                        $displayAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=random&color=fff&size=50';
                    }
                ?>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?php echo htmlspecialchars($displayAvatar); ?>" class="user-image rounded-circle shadow" alt="User">
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($displayName); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <img src="<?php echo htmlspecialchars($displayAvatar); ?>" class="rounded-circle shadow" alt="User">
                            <p><?php echo htmlspecialchars($displayName); ?></p>
                        </li>
                        <li class="user-footer">
                            <div class="user-footer-actions">
                                <a href="<?php echo BASE_URL; ?>auth/lockscreen.php?lock=1" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-lock me-1"></i> Kunci Layar
                                </a>
                                <?php if (current_user_role() === 'admin'): ?>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-update-system<?php echo !empty($githubUpdate['has_update']) ? ' has-update-pulse' : ''; ?>" id="btnUpdateSystem">
                                    <i class="bi bi-cloud-download me-1"></i> Update Sistem
                                    <?php if (!empty($githubUpdate['has_update'])): ?>
                                    <span class="badge text-bg-warning text-dark ms-1">Baru</span>
                                    <?php endif; ?>
                                </button>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-outline-danger btn-sm logout-link">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>auth/login.php" class="nav-link btn btn-sm btn-light text-primary ms-2">Login Admin</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
