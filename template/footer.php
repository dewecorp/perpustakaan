                </div> <!-- pcoded-wrapper -->
                <footer class="py-4 border-top mt-auto" style="background: linear-gradient(135deg, #e3f2fd 0%, #90caf9 100%); margin-top: 20px;">
                    <div class="container-fluid text-center">
                        <span class="text-dark font-weight-bold">© <?php echo date('Y'); ?> Perpustakaan Digital - <?php echo htmlspecialchars((string)get_setting('school_name', 'Nama Sekolah')); ?></span>
                    </div>
                </footer>
            </div> <!-- pcoded-main-container -->
        </div> <!-- pcoded-container -->
    </div> <!-- pcoded -->

    <script src="assets/js/jquery/jquery.min.js"></script>
<script src="assets/js/jquery-ui/jquery-ui.min.js"></script>
<script>
  $.widget.bridge('uibutton', $.ui.button);
</script>
<script src="assets/js/popper.js/popper.min.js"></script>
<script src="assets/js/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/js/jquery-slimscroll/jquery.slimscroll.js"></script>
<script src="assets/js/modernizr/modernizr.js"></script>
<script src="assets/js/script.js"></script>
<script src="assets/js/SmoothScroll.js"></script>
<script src="assets/js/pcoded.min.js"></script>
<script src="assets/js/demo-12.js"></script>
<script src="assets/js/jquery.mCustomScrollbar.concat.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
 <script>
     $(document).ready(function() {
         $('.table:not(.custom-table)').DataTable({
             language: {
                 url: 'assets/lang/datatables-id.json'
             }
         });
     });
 </script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
        if (result.isConfirmed) {
            window.location.href = href;
        }
    });
});
<?php if(isset($_SESSION['success'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Sukses!',
    text: '<?php echo $_SESSION['success']; ?>',
    timer: 2000,
    showConfirmButton: false
});
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
Swal.fire({
    icon: 'error',
    title: 'Gagal!',
    text: '<?php echo $_SESSION['error']; ?>'
});
<?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>
<?php if (isset($extra_js)) echo $extra_js; ?>
<script>
    function updateClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
        var clockElement = document.getElementById('live-clock');
        if (clockElement) {
            clockElement.innerText = now.toLocaleDateString('id-ID', options).replace('pukul', '');
        }
    }
    setInterval(updateClock, 1000);
    updateClock(); // initial call
</script>
</body>
</html>
