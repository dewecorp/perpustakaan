        </div><!-- /.container-fluid -->
    </div><!-- /.app-content -->
</main><!-- /.app-main -->
<?php if (!empty($page_modals)) echo $page_modals; ?>
<footer class="app-footer">
    <strong>&copy; <?php echo date('Y'); ?> Perpustakaan Digital - <?php echo htmlspecialchars((string)get_setting('school_name', 'Nama Sekolah')); ?></strong>
    <div class="float-end d-none d-sm-inline">PUSDIGI Admin</div>
</footer>
</div><!-- /.app-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="<?php echo BASE_URL; ?>assets/vendor/admin-lte/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.modal').forEach(function (modal) {
        if (modal.closest('.app-main, .app-content, .container-fluid')) {
            document.body.appendChild(modal);
        }
    });

    const sidebarWrapper = document.querySelector('.sidebar-wrapper');
    const isMobile = window.innerWidth <= 992;
    if (sidebarWrapper && typeof OverlayScrollbarsGlobal !== 'undefined' && OverlayScrollbarsGlobal?.OverlayScrollbars && !isMobile) {
        OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: { theme: 'os-theme-light', autoHide: 'leave', clickScroll: true }
        });
    }
    if ($.fn.DataTable) {
        $('.table:not(.custom-table):not(.no-datatable)').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                var isCompact = $(this).hasClass('table-compact');
                var options = {
                    language: { url: '<?php echo BASE_URL; ?>assets/lang/datatables-id.json' },
                    autoWidth: false,
                    columnDefs: [
                        { targets: -1, orderable: false, className: 'text-nowrap' }
                    ]
                };
                if (isCompact) {
                    options.columnDefs.unshift({ targets: 0, className: 'text-center text-nowrap', width: '1%' });
                    options.columnDefs.push({ targets: -1, width: '1%' });
                }
                $(this).DataTable(options);
            }
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).on('click', '#btnUpdateSystem, #btnUpdateFromNotif', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Update Sistem?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, update',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (!result.isConfirmed) return;

        var stepTimer;
        var startTime = Date.now();
        var steps = [
            { text: 'Mengunduh paket dari GitHub...', pct: 20 },
            { text: 'Mengekstrak file update...', pct: 45 },
            { text: 'Menerapkan pembaruan sistem...', pct: 70 },
            { text: 'Menyelesaikan proses update...', pct: 90 }
        ];
        var stepIdx = 0;

        Swal.fire({
            title: 'Memproses Update',
            html: '<p class="mb-3" id="updateStepText">' + steps[0].text + '</p>' +
                  '<div class="progress" role="progressbar" aria-label="Progress update">' +
                  '<div class="progress-bar progress-bar-striped progress-bar-animated" id="updateProgressBar" style="width:20%"></div></div>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function() {
                stepTimer = setInterval(function() {
                    stepIdx++;
                    if (stepIdx < steps.length) {
                        document.getElementById('updateStepText').textContent = steps[stepIdx].text;
                        document.getElementById('updateProgressBar').style.width = steps[stepIdx].pct + '%';
                    }
                }, 1200);
            }
        });

        fetch('<?php echo BASE_URL; ?>update_system.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var elapsed = Date.now() - startTime;
            var wait = Math.max(0, 4500 - elapsed);
            setTimeout(function() {
                clearInterval(stepTimer);
                Swal.close();
                Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Update Berhasil' : 'Update Gagal',
                    text: data.message || (data.success ? 'Sistem berhasil diperbarui.' : 'Terjadi kesalahan saat update.'),
                    confirmButtonText: 'OK'
                }).then(function() {
                    if (data.success) window.location.reload();
                });
            }, wait);
        })
        .catch(function() {
            clearInterval(stepTimer);
            Swal.fire({
                icon: 'error',
                title: 'Update Gagal',
                text: 'Tidak dapat menghubungi server update.'
            });
        });
    });
});
$(document).on('click', '.logout-link', function(e) {
    e.preventDefault();
    var href = $(this).attr('href');
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Anda yakin ingin keluar dari akun?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, logout',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) window.location.href = href;
    });
});
$(document).on('submit', '.delete-book-form', function(e) {
    e.preventDefault();
    var form = this;
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: 'Hapus buku ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then(function(result) {
        if (result.isConfirmed) form.submit();
    });
});
<?php if (isset($_SESSION['success'])): ?>
Swal.fire({ icon: 'success', title: 'Sukses!', text: <?php echo json_encode($_SESSION['success']); ?>, timer: 2000, showConfirmButton: false });
<?php unset($_SESSION['success']); endif; ?>
<?php if (isset($_SESSION['error'])): ?>
Swal.fire({ icon: 'error', title: 'Gagal!', text: <?php echo json_encode($_SESSION['error']); ?> });
<?php unset($_SESSION['error']); endif; ?>
<?php if (
    !empty($githubUpdate['has_update'])
    && current_user_role() === 'admin'
    && github_should_show_update_popup($githubUpdate ?? [])
): ?>
<?php $latestUpdateSha = (string)($githubUpdate['latest']['sha_full'] ?? ''); ?>
Swal.fire({
    title: 'Pembaruan Sistem Tersedia!',
    html: 'Versi terbaru <strong><?php echo htmlspecialchars($githubUpdate['latest']['sha'] ?? ''); ?></strong> tersedia di GitHub.<br>Perbarui sekarang untuk mendapatkan fitur dan perbaikan terbaru.',
    icon: 'info',
    showCancelButton: true,
    confirmButtonText: 'Update Sekarang',
    cancelButtonText: 'Nanti',
    confirmButtonColor: '#0d6efd'
}).then(function(result) {
    if (result.isConfirmed) {
        var btn = document.getElementById('btnUpdateSystem');
        if (btn) btn.click();
    } else if (result.dismiss === Swal.DismissReason.cancel) {
        fetch('<?php echo BASE_URL; ?>check_github_update.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'dismiss=1&sha=' + encodeURIComponent(<?php echo json_encode($latestUpdateSha); ?>)
        });
    }
});
<?php endif; ?>
<?php if (current_user_role() === 'admin'): ?>
(function pollGithubUpdate() {
    var hasUpdateBadge = <?php echo !empty($githubUpdate['has_update']) ? 'true' : 'false'; ?>;
    var pollMs = <?php echo GITHUB_CHECK_CACHE_SECONDS * 1000; ?>;
    setInterval(function() {
        fetch('<?php echo BASE_URL; ?>check_github_update.php?force=1', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.has_update && !hasUpdateBadge) {
                window.location.reload();
            }
        })
        .catch(function() {});
    }, pollMs);
})();
<?php endif; ?>
<?php include __DIR__ . '/idle_lock_script.php'; ?>
function updateClock() {
    const now = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
    var el = document.getElementById('live-clock');
    if (el) el.innerText = now.toLocaleDateString('id-ID', options).replace('pukul', '');
}
setInterval(updateClock, 1000);
updateClock();
</script>
<?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
