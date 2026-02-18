<nav class="pcoded-navbar">
    <div class="sidebar_toggle"><a href="#"><i class="icon-close icons"></i></a></div>
    <div class="pcoded-inner-navbar main-menu">


        <div class="pcoded-navigatio-lavel" data-i18n="nav.category.navigation">Navigasi</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="<?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <span class="pcoded-micon"><i class="ti-home"></i></span>
                    <span class="pcoded-mtext">Dashboard</span>
                    <span class="pcoded-mcaret"></span>
                </a>
            </li>

            <li class="pcoded-hasmenu <?php echo in_array($activePage, ['categories', 'books']) ? 'active pcoded-trigger' : ''; ?>">
                <a href="javascript:void(0)">
                    <span class="pcoded-micon"><i class="ti-layers"></i></span>
                    <span class="pcoded-mtext">Master Data</span>
                    <span class="pcoded-mcaret"></span>
                </a>
                <ul class="pcoded-submenu">
                    <li class="<?php echo ($activePage == 'categories') ? 'active' : ''; ?>">
                        <a href="categories.php">
                            <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                            <span class="pcoded-mtext">Kategori Buku</span>
                            <span class="pcoded-mcaret"></span>
                        </a>
                    </li>
                    <li class="<?php echo ($activePage == 'books') ? 'active' : ''; ?>">
                        <a href="books.php">
                            <span class="pcoded-micon"><i class="ti-angle-right"></i></span>
                            <span class="pcoded-mtext">Data Buku</span>
                            <span class="pcoded-mcaret"></span>
                        </a>
                    </li>
                </ul>
            </li>

            <li class="<?php echo ($activePage == 'visitors') ? 'active' : ''; ?>">
                <a href="visitors.php">
                    <span class="pcoded-micon"><i class="ti-id-badge"></i></span>
                    <span class="pcoded-mtext">Data Pengunjung</span>
                    <span class="pcoded-mcaret"></span>
                </a>
            </li>

            <li class="<?php echo ($activePage == 'users') ? 'active' : ''; ?>">
                <a href="users.php">
                    <span class="pcoded-micon"><i class="ti-user"></i></span>
                    <span class="pcoded-mtext">Pengguna</span>
                    <span class="pcoded-mcaret"></span>
                </a>
            </li>

            <li class="<?php echo ($activePage == 'settings') ? 'active' : ''; ?>">
                <a href="settings.php">
                    <span class="pcoded-micon"><i class="ti-settings"></i></span>
                    <span class="pcoded-mtext">Pengaturan</span>
                    <span class="pcoded-mcaret"></span>
                </a>
            </li>

            <li class="<?php echo ($activePage == 'backup') ? 'active' : ''; ?>">
                <a href="backup.php">
                    <span class="pcoded-micon"><i class="ti-cloud-down"></i></span>
                    <span class="pcoded-mtext">Backup Restore</span>
                    <span class="pcoded-mcaret"></span>
                </a>
            </li>
            
            <li class="<?php echo ($activePage == 'sibi_import') ? 'active' : ''; ?>">
                <a href="sibi_import.php">
                    <span class="pcoded-micon"><i class="ti-import"></i></span>
                    <span class="pcoded-mtext">Impor Buku</span>
                    <span class="pcoded-mcaret"></span>
                </a>
            </li>

            <li>
                <a href="auth/logout.php" class="logout-link">
                    <span class="pcoded-micon"><i class="ti-layout-sidebar-left"></i></span>
                    <span class="pcoded-mtext">Logout</span>
                    <span class="pcoded-mcaret"></span>
                </a>
            </li>
        </ul>
    </div>
</nav>
