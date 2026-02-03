<?php
require_once 'config/config.php';
require_login();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetDir = "assets/images/";
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'ico'];
    $successMsg = '';
    $errorMsg = '';

    // Handle School Name
    if (isset($_POST['school_name'])) {
        $schoolName = trim($_POST['school_name']);
        if (save_setting('school_name', $schoolName)) {
             $successMsg .= "Nama sekolah berhasil diperbarui. ";
        } else {
             $errorMsg .= "Gagal menyimpan nama sekolah. ";
        }
    }

    // Handle Logo Upload
    if (!empty($_FILES["logo"]["name"])) {
        $fileName = basename($_FILES["logo"]["name"]);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (in_array($fileType, $allowedTypes)) {
            // Overwrite existing logo.png if possible, or save as new
            $targetFile = $targetDir . "logo.png"; 
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFile)) {
                $successMsg .= "Logo berhasil diperbarui. ";
            } else {
                $errorMsg .= "Gagal mengupload logo. ";
            }
        } else {
            $errorMsg .= "Format file logo tidak didukung. ";
        }
    }

    // Handle Hero Image Upload
    if (!empty($_FILES["hero"]["name"])) {
        $fileName = basename($_FILES["hero"]["name"]);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (in_array($fileType, $allowedTypes)) {
             $targetFile = $targetDir . "book-hero.png";
             if (move_uploaded_file($_FILES["hero"]["tmp_name"], $targetFile)) {
                $successMsg .= "Gambar Hero berhasil diperbarui. ";
             } else {
                $errorMsg .= "Gagal mengupload gambar hero. ";
             }
        } else {
            $errorMsg .= "Format file hero tidak didukung. ";
        }
    }

    // Handle Login Background Upload
    if (!empty($_FILES["login_bg"]["name"])) {
        $fileName = basename($_FILES["login_bg"]["name"]);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (in_array($fileType, $allowedTypes)) {
             $targetFile = $targetDir . "login-bg.png";
             if (move_uploaded_file($_FILES["login_bg"]["tmp_name"], $targetFile)) {
                $successMsg .= "Background Login berhasil diperbarui. ";
             } else {
                $errorMsg .= "Gagal mengupload background login. ";
             }
        } else {
            $errorMsg .= "Format file background tidak didukung. ";
        }
    }
    
    if (!empty($successMsg)) $_SESSION['success'] = $successMsg;
    if (!empty($errorMsg)) $_SESSION['error'] = $errorMsg;
}

$pageTitle = "Pengaturan";
$activePage = 'settings';
include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="pcoded-content">
    <div class="pcoded-inner-content">
        <div class="main-body">
            <div class="page-wrapper">
                <div class="page-header card">
                    <div class="card-block">
                        <h5 class="m-b-10">Pengaturan Sistem</h5>
                        <p class="text-muted m-b-10">Atur tampilan dan konfigurasi aplikasi</p>
                    </div>
                </div>
                
                <div class="page-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Informasi Sekolah</h5>
                                </div>
                                <div class="card-block">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Nama Sekolah</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="school_name" class="form-control" value="<?php echo htmlspecialchars((string)get_setting('school_name', 'Perpustakaan')); ?>" placeholder="Masukkan Nama Sekolah">
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Logo Aplikasi</label>
                                            <div class="col-sm-9">
                                                <input type="file" name="logo" class="form-control" accept="image/*">
                                                <small class="form-text text-muted">Akan menggantikan <code>assets/images/logo.png</code>. Disarankan format PNG transparan.</small>
                                                <div class="mt-2">
                                                    <img src="assets/images/logo.png?t=<?php echo time(); ?>" alt="Current Logo" style="max-height: 50px; background: #eee; padding: 5px;">
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Gambar Hero (Depan)</label>
                                            <div class="col-sm-9">
                                                <input type="file" name="hero" class="form-control" accept="image/*">
                                                <small class="form-text text-muted">Akan menggantikan <code>assets/images/book-hero.png</code>. Tampil di halaman depan.</small>
                                                <div class="mt-2">
                                                    <img src="assets/images/book-hero.png?t=<?php echo time(); ?>" alt="Current Hero" style="max-height: 100px; background: #eee; padding: 5px;" onerror="this.style.display='none'">
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Background Login</label>
                                            <div class="col-sm-9">
                                                <input type="file" name="login_bg" class="form-control" accept="image/*">
                                                <small class="form-text text-muted">Akan menggantikan <code>assets/images/login-bg.png</code>. Tampil di halaman login.</small>
                                                <div class="mt-2">
                                                    <img src="assets/images/login-bg.png?t=<?php echo time(); ?>" alt="Current Login BG" style="max-height: 100px; background: #eee; padding: 5px;" onerror="this.style.display='none'">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-sm-12 text-right">
                                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Informasi Sistem</h5>
                                </div>
                                <div class="card-block">
                                    <dl class="row">
                                        <dt class="col-sm-4">Versi PHP</dt>
                                        <dd class="col-sm-8"><?php echo phpversion(); ?></dd>
                                        
                                        <dt class="col-sm-4">Server</dt>
                                        <dd class="col-sm-8"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></dd>
                                        
                                        <dt class="col-sm-4">Database</dt>
                                        <dd class="col-sm-8">MySQL</dd>
                                        
                                        <dt class="col-sm-4">Direktori Upload</dt>
                                        <dd class="col-sm-8"><code>assets/uploads/</code></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'template/footer.php'; ?>