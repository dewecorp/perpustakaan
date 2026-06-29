<?php
if (!is_staff_user() || !empty($_SESSION['locked'])) {
    return;
}
?>
(function() {
    var idleLimit = <?php echo SESSION_IDLE_SECONDS * 1000; ?>;
    var idleTimer;
    var lockUrl = <?php echo json_encode(BASE_URL . 'auth/lockscreen.php?idle=1'); ?>;

    function resetIdleTimer() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(function() {
            window.location.href = lockUrl;
        }, idleLimit);
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function(evt) {
        document.addEventListener(evt, resetIdleTimer, { passive: true });
    });

    resetIdleTimer();
})();
