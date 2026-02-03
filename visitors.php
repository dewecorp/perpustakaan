<?php
require_once 'config/config.php';
require_login();
$pdo = db();
$pageTitle = "Data Pengunjung";
$activePage = 'visitors';
include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="pcoded-content">
    <div class="pcoded-inner-content">
        <div class="main-body">
            <div class="page-wrapper">
                <div class="page-header card">
                    <div class="card-block">
                        <h5 class="m-b-10">Data Pengunjung</h5>
                        <p class="text-muted m-b-10">Rekap data pengunjung perpustakaan</p>
                    </div>
                </div>
                <div class="page-body">
                    <div class="card">
                        <div class="card-header">
                            <h5>Daftar Pengunjung</h5>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="visitorsTable" class="table table-striped table-bordered nowrap">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama</th>
                                            <th>Keperluan</th>
                                            <th>Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM visitors ORDER BY visit_date DESC");
                                        $no = 1;
                                        while($row = $stmt->fetch()):
                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($row['visit_date'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'template/footer.php'; ?>
