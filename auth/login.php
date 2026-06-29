<?php
require_once '../config/config.php';

$error = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo = db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'] ?? $user['username'],
                'role' => $user['role'] ?? 'admin'
            ];
            log_activity('login', activity_user_label((int)$user['id'], $user['username']) . ' berhasil masuk ke sistem', (int)$user['id']);
            $displayName = $user['name'] ?? $user['username'];
            $successMessage = 'Selamat datang, ' . $displayName . '!';
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi semua bidang.';
    }
}

$lteBase = BASE_URL . 'assets/vendor/admin-lte/';
$loginBg = BASE_URL . 'assets/images/login-bg.png?v=' . time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Login - PUSDIGI</title>
    <link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon_library.svg?v=<?php echo time(); ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $lteBase; ?>css/adminlte.min.css">
    <style>
        body.login-page {
            background: linear-gradient(45deg, rgba(13,71,161,0.85), rgba(25,118,210,0.85)), url('<?php echo $loginBg; ?>') center/cover no-repeat fixed;
        }
        .login-logo img { max-height: 70px; margin-bottom: 10px; }
    </style>
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
    <div class="login-logo">
        <a href="<?php echo BASE_URL; ?>index.php">
            <img src="<?php echo BASE_URL; ?>assets/images/logo.png?v=<?php echo time(); ?>" alt="Logo"><br>
            <b>PERPUSTAKAAN</b> DIGITAL
        </a>
    </div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg"><?php echo htmlspecialchars((string)get_setting('school_name', 'Perpustakaan')); ?></p>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                    <div class="input-group-text"><span class="bi bi-person"></span></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                    <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Sign in</button>
                        </div>
                    </div>
                </div>
            </form>
            <p class="mb-0 mt-3 text-center">
                <a href="<?php echo BASE_URL; ?>index.php">Kembali ke Katalog</a>
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="<?php echo $lteBase; ?>js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if ($successMessage): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Sukses!',
    text: <?php echo json_encode($successMessage); ?>,
    timer: 2000,
    showConfirmButton: false
}).then(function() {
    window.location.href = <?php echo json_encode(BASE_URL . 'dashboard.php'); ?>;
});
</script>
<?php endif; ?>
</body>
</html>
