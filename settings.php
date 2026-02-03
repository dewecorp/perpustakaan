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

    // Handle Hero Text
    if (isset($_POST['hero_title']) && isset($_POST['hero_description'])) {
        $heroTitle = trim($_POST['hero_title']);
        $heroDesc = $_POST['hero_description']; // Don't trim or sanitize yet, let CKEditor handle HTML
        
        $savedTitle = save_setting('hero_title', $heroTitle);
        $savedDesc = save_setting('hero_description', $heroDesc);
        
        if ($savedTitle && $savedDesc) {
             $successMsg .= "Teks Hero berhasil diperbarui. ";
        } else {
             $errorMsg .= "Gagal menyimpan teks hero. ";
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
                    <div class="row align-items-end">
                        <div class="col-lg-8">
                            <div class="page-header-title">
                                <i class="icofont icofont-settings bg-c-yellow"></i>
                                <div class="d-inline">
                                    <h4>Pengaturan Sistem</h4>
                                    <span>Atur tampilan dan konfigurasi aplikasi</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="page-body">
                    <div class="row">
                        <div class="col-md-12">
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
                                            <label class="col-sm-3 col-form-label">Judul Hero</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars((string)get_setting('hero_title', 'Temukan Buku Favoritmu')); ?>" placeholder="Masukkan Judul Hero">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Deskripsi Hero</label>
                                            <div class="col-sm-9">
                                                <textarea name="hero_description" id="hero_description" class="form-control"><?php echo htmlspecialchars((string)get_setting('hero_description', 'Akses ribuan koleksi buku digital dan fisik perpustakaan kami dengan mudah. Mulai petualangan literasimu hari ini.')); ?></textarea>
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
                        

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
<script>
    CKEDITOR.replace('hero_description');
</script>
<?php include 'template/footer.php'; ?>