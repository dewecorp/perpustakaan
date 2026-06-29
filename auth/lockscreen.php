<?php
require_once __DIR__ . '/../config/config.php';

if (empty($_SESSION['user']) || !is_staff_user()) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$error = '';
$userId = (int)$_SESSION['user']['id'];
$pdo = db();
$stmt = $pdo->prepare('SELECT id, username, name, avatar, password FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    if ($password === '') {
        $error = 'Masukkan password Anda.';
    } elseif (!password_verify($password, $user['password'])) {
        $error = 'Password salah.';
    } else {
        unlock_session();
        touch_session_activity();
        $redirect = $_SESSION['redirect_after_unlock'] ?? (BASE_URL . 'dashboard.php');
        unset($_SESSION['redirect_after_unlock']);
        header('Location: ' . $redirect);
        exit;
    }
}

if (isset($_GET['lock'])) {
    lock_session();
}

$lastActivity = (int)($_SESSION['last_activity'] ?? 0);
if (isset($_GET['idle']) || ($lastActivity > 0 && (time() - $lastActivity) > SESSION_IDLE_SECONDS)) {
    lock_session();
}

if (!is_session_locked()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$displayName = trim((string)($user['name'] ?? ''));
if ($displayName === '') {
    $displayName = (string)$user['username'];
}

$roleLabel = current_user_role() === 'pustakawan' ? 'Pustakawan' : 'Admin';

$displayAvatar = $user['avatar'] ?? '';
if ($displayAvatar === '' || !file_exists(__DIR__ . '/../' . ltrim($displayAvatar, '/'))) {
    $displayAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=0d47a1&color=fff&size=128';
} else {
    $displayAvatar = BASE_URL . ltrim($displayAvatar, '/');
}

$schoolName = htmlspecialchars(mb_strtoupper((string)get_setting('school_name', 'Perpustakaan'), 'UTF-8'));
$lteBase = BASE_URL . 'assets/vendor/admin-lte/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Layar Terkunci - PUSDIGI</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon_library.svg?v=<?php echo time(); ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $lteBase; ?>css/adminlte.min.css">
</head>
<body class="lockscreen bg-body-secondary">
    <div class="lockscreen-wrapper">
        <div class="lockscreen-logo">
            <a href="<?php echo BASE_URL; ?>dashboard.php"><b>PERPUSTAKAAN</b> DIGITAL</a>
        </div>

        <div class="lockscreen-name"><?php echo htmlspecialchars($displayName); ?></div>
        <div class="text-center text-muted small mb-2"><?php echo htmlspecialchars($roleLabel); ?></div>

        <?php if ($error): ?>
        <div class="alert alert-danger text-center py-2 px-3 mx-auto" style="max-width:320px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="lockscreen-item">
            <div class="lockscreen-image">
                <img src="<?php echo htmlspecialchars($displayAvatar); ?>" alt="<?php echo htmlspecialchars($displayName); ?>">
            </div>

            <form class="lockscreen-credentials" method="POST" action="">
                <div class="input-group">
                    <input type="password" name="password" class="form-control shadow-none" placeholder="Password" required autofocus autocomplete="current-password">
                    <div class="input-group-text border-0 bg-transparent px-1">
                        <button type="submit" class="btn shadow-none" title="Buka kunci">
                            <i class="bi bi-box-arrow-right text-body-secondary"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="help-block text-center">Masukkan password untuk melanjutkan sesi Anda</div>
        <div class="text-center">
            <a href="<?php echo BASE_URL; ?>auth/logout.php" class="text-decoration-none">Atau masuk sebagai pengguna lain</a>
        </div>
        <div class="lockscreen-footer text-center">
            <?php echo $schoolName; ?> &copy; <?php echo date('Y'); ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?php echo $lteBase; ?>js/adminlte.min.js"></script>
</body>
</html>
