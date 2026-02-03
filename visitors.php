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
                    <div class="row align-items-end">
                        <div class="col-lg-8">
                            <div class="page-header-title">
                                <i class="icofont icofont-users-social bg-c-green"></i>
                                <div class="d-inline">
                                    <h4>Data Pengunjung</h4>
                                    <span>Rekap data pengunjung perpustakaan</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="page-body">
                    <div class="card">
                        <div class="card-header">
                            <h5>Daftar Pengunjung</h5>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="visitorsTable" class="table custom-table table-striped table-bordered nowrap">
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
<?php 
$extra_js = "
<script>
$(document).ready(function() {
    $('#visitorsTable').DataTable({
        language: {
            url: 'assets/lang/datatables-id.json'
        },
        order: [] // Disable initial sorting to respect PHP order
    });
});
</script>
";
include 'template/footer.php'; 
?>
