<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - PUSDIGI' : 'PUSDIGI'; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="assets/images/favicon_library.svg?v=<?php echo time(); ?>" type="image/svg+xml">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/icon/themify-icons/themify-icons.css">
    <link rel="stylesheet" type="text/css" href="assets/icon/icofont/css/icofont.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="assets/css/jquery.mCustomScrollbar.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
    <style>
        /* Admin Navbar Customization - Dark Blue Gradient */
        .pcoded .pcoded-header {
            background: linear-gradient(to right, #0d47a1, #1976d2) !important;
        }
        .pcoded .pcoded-header .navbar-logo {
            background: transparent !important;
        }
        .pcoded .pcoded-header .navbar-logo a {
            color: #fff !important;
        }
        /* Icons and Text in Navbar */
        .pcoded .pcoded-header .nav-left > li > a,
        .pcoded .pcoded-header .nav-left > li > a > i,
        .pcoded .pcoded-header .nav-right > li > a,
        .pcoded .pcoded-header .nav-right > li > a > i,
        .pcoded .pcoded-header .nav-right > li > a > span,
        .pcoded .pcoded-header .mobile-menu i {
            color: #fff !important;
        }
        
        /* Mobile Navbar Fix */
        @media only screen and (max-width: 992px) {
            .header-navbar .navbar-wrapper .navbar-logo a:not(.mobile-menu) img {
                display: inline-block !important;
            }
            .header-navbar .navbar-wrapper .navbar-logo a:not(.mobile-menu) {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                width: 100%; /* Ensure it takes full width for centering */
            }
            /* Adjust span inside for mobile if needed */
            .header-navbar .navbar-wrapper .navbar-logo a:not(.mobile-menu) span {
                display: inline-block !important;
            }
            
            /* Ensure mobile menu button is positioned correctly */
            .header-navbar .navbar-wrapper .navbar-logo .mobile-menu {
                z-index: 1001; /* Ensure it's on top */
            }
        }
    </style>
</head>
<body>
    <div id="pcoded" class="pcoded">
        <div class="pcoded-overlay-box"></div>
        <div class="pcoded-container navbar-wrapper">
            <nav class="navbar header-navbar pcoded-header">
                <div class="navbar-wrapper">
                    <div class="navbar-logo">
                        <a class="mobile-menu" id="mobile-collapse" href="#!"><i class="ti-menu"></i></a>
                        <a href="index.php">
                            <img class="img-fluid" src="assets/images/logo.png?v=<?php echo file_exists('assets/images/logo.png') ? filemtime('assets/images/logo.png') : time(); ?>" alt="Theme-Logo" style="max-height: 50px;" />
                            <span style="font-weight: bold; font-size: 20px; margin-left: 10px; vertical-align: middle; display: inline-block;">PUSDIGI</span>
                        </a>
                    </div>
                    <div class="navbar-container container-fluid">
                        <ul class="nav-left">
                            <li><div class="sidebar_toggle"><a href="javascript:void(0)"><i class="ti-menu"></i></a></div></li>
                            <li><a href="#!" onclick="javascript:toggleFullScreen()"><i class="ti-fullscreen"></i></a></li>
                        </ul>
                        <ul class="nav-right">
                            <li class="header-notification" id="live-clock" style="color: #fff; padding: 0 15px; line-height: 80px; font-size: 14px;"></li>
                            <?php if(isset($_SESSION['user'])): 
                                // Fetch fresh user data
                                if (!isset($pdo)) $pdo = db();
                                $stmtHeader = $pdo->prepare("SELECT name, avatar, username FROM users WHERE id = ?");
                                $stmtHeader->execute([$_SESSION['user']['id']]);
                                $currentUser = $stmtHeader->fetch();
                                
                                $displayName = $currentUser['name'] ?? $currentUser['username'] ?? 'Admin';
                                $displayAvatar = $currentUser['avatar'] ?? '';
                                
                                if (empty($displayAvatar) || !file_exists($displayAvatar)) {
                                    $displayAvatar = 'https://ui-avatars.com/api/?name='.urlencode($displayName).'&background=random&color=fff&size=50';
                                }
                            ?>
                            <li class="user-profile header-notification">
                                <a href="#!">
                                    <img src="<?php echo $displayAvatar; ?>" class="img-radius" alt="User-Profile-Image" style="width:40px; height:40px; object-fit:cover;">
                                    <span><?php echo htmlspecialchars($displayName); ?></span>
                                    <i class="ti-angle-down"></i>
                                </a>
                                <ul class="show-notification profile-notification">
                                    <li><a href="auth/logout.php"><i class="ti-layout-sidebar-left"></i> Logout</a></li>
                                </ul>
                            </li>
                            <?php else: ?>
                            <li><a href="auth/login.php" target="_blank" class="btn btn-sm btn-primary text-white" style="margin-top: 10px;">Login Admin</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="pcoded-main-container" style="min-height: 100vh; display: flex; flex-direction: column;">
                <div class="pcoded-wrapper" style="flex: 1;">
