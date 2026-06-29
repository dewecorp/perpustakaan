<?php
require_once '../config/config.php';

if (isset($_SESSION['user'])) {
    $uid = (int)$_SESSION['user']['id'];
    log_activity('logout', activity_user_label($uid, $_SESSION['user']['username']) . ' keluar dari sistem', $uid);
}

session_destroy();
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
