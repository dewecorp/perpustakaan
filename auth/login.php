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
                'username' => $user['username']
            ];
            
            log_activity('login', 'User logged in');
            
            $displayName = $user['name'] ?? $user['username'];
            $successMessage = 'Selamat datang, ' . $displayName . '!';
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Harap isi semua bidang.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - PUSDIGI</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <link rel="icon" href="../assets/images/favicon_library.svg?v=<?php echo time(); ?>" type="image/svg+xml">
    <link rel="stylesheet" type="text/css" href="../assets/css/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../assets/icon/themify-icons/themify-icons.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <style>
        .common-img-bg { 
            background: linear-gradient(45deg, #4099ff, #73b4ff); 
            background-image: url('<?php echo BASE_URL; ?>assets/images/login-bg.png?v=<?php echo time(); ?>'), linear-gradient(45deg, #4099ff, #73b4ff);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .login-card { background: rgba(255, 255, 255, 0.95); padding: 30px; border-radius: 5px; box-shadow: 0 0 15px rgba(0,0,0,0.2); max-width: 450px; margin: 50px auto; }
    </style>
</head>
<body>
    <section class="login p-fixed d-flex text-center common-img-bg" style="height:100vh; width:100%; align-items:center; justify-content:center;">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="login-card card-block auth-body">
                        <form class="md-float-material" method="POST">
                            <div class="text-center">
                                <img src="<?php echo BASE_URL; ?>assets/images/logo.png?v=<?php echo time(); ?>" alt="logo.png" style="max-height: 70px; margin-bottom: 10px;">
                                <h4 class="text-uppercase font-weight-bold mt-2" style="color: #4099ff; letter-spacing: 1px;">PERPUSTAKAAN DIGITAL</h4>
                                <h5 class="font-weight-normal mb-4 text-muted"><?php echo htmlspecialchars((string)get_setting('school_name', 'Perpustakaan')); ?></h5>
                            </div>
                            <div class="auth-box">
                                <hr/>
                                <?php if($error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <div class="input-group mb-3">
                                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                                </div>
                                <div class="input-group mb-3">
                                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                                </div>
                                <div class="row m-t-30">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary btn-md btn-block waves-effect text-center m-b-20 w-100">Sign in</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="../assets/js/jquery/jquery.min.js"></script>
    <script src="../assets/js/bootstrap/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($successMessage): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Sukses!',
        text: '<?php echo $successMessage; ?>',
        timer: 2000,
        showConfirmButton: false
    }).then(function() {
        window.location.href = '<?php echo BASE_URL; ?>dashboard.php';
    });
    </script>
    <?php endif; ?>
</body>
</html>
