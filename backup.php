<?php
require_once 'config/config.php';
require_login();
$pageTitle = "Backup & Restore";
$activePage = 'backup';

// Helper to format size
function formatSize($bytes) {
    if ($bytes >= 1073741824) $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    elseif ($bytes >= 1048576) $bytes = number_format($bytes / 1048576, 2) . ' MB';
    elseif ($bytes >= 1024) $bytes = number_format($bytes / 1024, 2) . ' KB';
    elseif ($bytes > 1) $bytes = $bytes . ' bytes';
    elseif ($bytes == 1) $bytes = $bytes . ' byte';
    else $bytes = '0 bytes';
    return $bytes;
}

// Get backup files
$backupDir = __DIR__ . '/assets/backups/';
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'time' => filemtime($backupDir . $file)
            ];
        }
    }
}
// Sort by time desc
usort($backups, function($a, $b) {
    return $b['time'] - $a['time'];
});

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="pcoded-content">
    <div class="pcoded-inner-content">
        <div class="main-body">
            <div class="page-wrapper">
                <div class="page-header card">
                    <div class="card-block">
                        <h5 class="m-b-10">Backup & Restore Database</h5>
                        <p class="text-muted m-b-10">Kelola cadangan data perpustakaan anda</p>
                    </div>
                </div>
                <div class="page-body">
                    
                    <div class="row">
                        <!-- Column 1: Backup -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Buat Backup Baru</h5>
                                </div>
                                <div class="card-block">
                                    <p class="text-muted">Klik tombol di bawah untuk membuat cadangan database terbaru. File akan tersimpan di server dan dapat diunduh.</p>
                                    <button id="btnBackup" class="btn btn-success btn-block"><i class="ti-download"></i> Proses Backup Database</button>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Restore -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Restore Database</h5>
                                </div>
                                <div class="card-block">
                                    <p class="text-muted">Upload file .sql untuk mengembalikan database. <b>Peringatan: Data saat ini akan ditimpa!</b></p>
                                    <form action="backup_process.php?action=restore" method="post" enctype="multipart/form-data" id="formRestore">
                                        <div class="form-group">
                                            <input type="file" name="backup_file" class="form-control" required accept=".sql">
                                        </div>
                                        <button type="submit" class="btn btn-warning btn-block"><i class="ti-upload"></i> Restore Database</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup History Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Riwayat Backup</h5>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table class="table table-striped table-bordered nowrap">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Backup</th>
                                            <th>Ukuran</th>
                                            <th>Waktu</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($backups)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Belum ada file backup</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach($backups as $index => $backup): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($backup['name']); ?></td>
                                                <td><?php echo formatSize($backup['size']); ?></td>
                                                <td><?php echo date('d M Y H:i:s', $backup['time']); ?></td>
                                                <td>
                                                    <a href="backup_process.php?action=download&file=<?php echo urlencode($backup['name']); ?>" class="btn btn-primary btn-sm" title="Unduh"><i class="ti-download"></i></a>
                                                    <a href="backup_process.php?action=delete&file=<?php echo urlencode($backup['name']); ?>" class="btn btn-danger btn-sm btn-delete" title="Hapus"><i class="ti-trash"></i></a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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
    // Backup Process
    $('#btnBackup').click(function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Memproses Backup...',
            text: 'Mohon tunggu sebentar, jangan tutup halaman ini.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: 'backup_process.php',
            type: 'POST',
            data: { action: 'backup' },
            dataType: 'json',
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Backup berhasil dibuat.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Gagal membuat backup: ' + error, 'error');
            }
        });
    });

    // Delete Confirmation
    $('.btn-delete').click(function(e) {
        e.preventDefault();
        var link = $(this).attr('href');
        Swal.fire({
            title: 'Apakah anda yakin?',
            text: 'File backup akan dihapus secara permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = link;
            }
        })
    });

    // Restore Confirmation
    $('#formRestore').submit(function(e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: 'Peringatan Restore',
            text: 'Apakah anda yakin ingin merestore database? Data saat ini akan ditimpa dengan data dari backup!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f0ad4e',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Restore!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        })
    });
});
</script>
";
include 'template/footer.php'; 
?>
