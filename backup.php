<?php
require_once 'config/config.php';
require_login();
$pageTitle = "Backup & Restore";
$pageSubtitle = "Kelola cadangan data perpustakaan anda";
$activePage = 'backup';

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    if ($bytes > 1) return $bytes . ' bytes';
    if ($bytes == 1) return '1 byte';
    return '0 bytes';
}

$backupDir = __DIR__ . '/assets/backups/';
$backups = [];
if (is_dir($backupDir)) {
    foreach (scandir($backupDir) as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'time' => filemtime($backupDir . $file)
            ];
        }
    }
}
usort($backups, fn($a, $b) => $b['time'] - $a['time']);

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="row mb-4">
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Buat Backup Baru</h3></div>
            <div class="card-body d-flex flex-column justify-content-between">
                <p class="text-muted">Klik tombol di bawah untuk membuat cadangan database terbaru.</p>
                <button id="btnBackup" class="btn btn-success w-100"><i class="bi bi-download me-1"></i> Proses Backup Database</button>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Restore Database</h3></div>
            <div class="card-body d-flex flex-column justify-content-between">
                <p class="text-muted">Upload file .sql untuk mengembalikan database. <strong>Peringatan: Data saat ini akan ditimpa!</strong></p>
                <form action="backup_process.php?action=restore" method="post" enctype="multipart/form-data" id="formRestore">
                    <div class="mb-3">
                        <input type="file" name="backup_file" class="form-control" required accept=".sql">
                    </div>
                    <button type="submit" class="btn btn-warning w-100"><i class="bi bi-upload me-1"></i> Restore Database</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Riwayat Backup</h3></div>
    <div class="card-body">
        <div class="table-responsive">
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
                    <?php foreach ($backups as $index => $backup): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($backup['name']); ?></td>
                        <td><?php echo formatSize($backup['size']); ?></td>
                        <td><?php echo format_date_id($backup['time']) . ' ' . date('H:i:s', $backup['time']); ?></td>
                        <td>
                            <a href="backup_process.php?action=download&file=<?php echo urlencode($backup['name']); ?>" class="btn btn-primary btn-sm" title="Unduh"><i class="bi bi-download"></i></a>
                            <a href="backup_process.php?action=delete&file=<?php echo urlencode($backup['file'] ?? $backup['name']); ?>" class="btn btn-danger btn-sm btn-delete" title="Hapus"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
$(document).ready(function() {
    $("#btnBackup").click(function(e) {
        e.preventDefault();
        var startTime = Date.now();
        var progressTimer;
        var progressPct = 20;

        Swal.fire({
            title: "Memproses Backup Database...",
            html: \'<p class="text-muted mb-3">Membuat file cadangan database perpustakaan...</p>\' +
                  \'<div class="progress" style="height: 18px;">\' +
                  \'<div id="backupProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 20%;">20%</div>\' +
                  \'</div>\',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function() {
                progressTimer = setInterval(function() {
                    if (progressPct < 90) {
                        progressPct += Math.floor(Math.random() * 15) + 10;
                        if (progressPct > 90) progressPct = 90;
                        var pBar = document.getElementById("backupProgressBar");
                        if (pBar) {
                            pBar.style.width = progressPct + "%";
                            pBar.textContent = progressPct + "%";
                        }
                    }
                }, 250);
            }
        });

        $.ajax({
            url: "backup_process.php",
            type: "POST",
            data: { action: "backup" },
            dataType: "json",
            success: function(response) {
                var elapsed = Date.now() - startTime;
                var delay = Math.max(0, 1800 - elapsed);
                setTimeout(function() {
                    clearInterval(progressTimer);
                    var pBar = document.getElementById("backupProgressBar");
                    if (pBar) {
                        pBar.style.width = "100%";
                        pBar.textContent = "100%";
                    }
                    setTimeout(function() {
                        location.reload();
                    }, 400);
                }, delay);
            },
            error: function(xhr, status, error) {
                var elapsed = Date.now() - startTime;
                var delay = Math.max(0, 1500 - elapsed);
                setTimeout(function() {
                    clearInterval(progressTimer);
                    var msg = "Gagal membuat backup.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire("Error", msg, "error");
                }, delay);
            }
        });
    });
    $(".btn-delete").click(function(e) {
        e.preventDefault();
        var link = $(this).attr("href");
        Swal.fire({
            title: "Apakah anda yakin?",
            text: "File backup akan dihapus secara permanen!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) window.location.href = link;
        });
    });
    $("#formRestore").submit(function(e) {
        e.preventDefault();
        var form = this;
        Swal.fire({
            title: "Peringatan Restore",
            text: "Apakah anda yakin ingin merestore database? Data saat ini akan ditimpa!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, Restore!",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: "Memproses Restore Database...",
                    html: \'<p class="text-muted mb-3">Mengembalikan database dari file backup. Mohon tunggu...</p>\' +
                          \'<div class="progress" style="height: 18px;">\' +
                          \'<div class="progress-bar progress-bar-striped progress-bar-animated bg-warning w-100" role="progressbar">100%</div>\' +
                          \'</div>\',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false
                });
                form.submit();
            }
        });
    });
});
</script>
';
include 'template/footer.php';
