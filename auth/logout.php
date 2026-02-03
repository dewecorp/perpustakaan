<?php
require_once '../config/config.php';

if (isset($_SESSION['user'])) {
    log_activity('logout', 'User logged out');
}

session_destroy();
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
