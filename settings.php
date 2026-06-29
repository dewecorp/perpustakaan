<?php
require_once 'config/config.php';
require_login();
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetDir = "assets/images/";
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'ico'];
    $successMsg = '';
    $errorMsg = '';

    if (isset($_POST['school_name'])) {
        $schoolName = trim($_POST['school_name']);
        if (save_setting('school_name', $schoolName)) {
             $successMsg .= "Nama sekolah berhasil diperbarui. ";
        } else {
             $errorMsg .= "Gagal menyimpan nama sekolah. ";
        }
    }

    if (!empty($_FILES["logo"]["name"])) {
        $fileName = basename($_FILES["logo"]["name"]);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($fileType, $allowedTypes)) {
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

    if (isset($_POST['hero_title']) && isset($_POST['hero_description'])) {
        $heroTitle = trim($_POST['hero_title']);
        $heroDesc = $_POST['hero_description'];
        $savedTitle = save_setting('hero_title', $heroTitle);
        $savedDesc = save_setting('hero_description', $heroDesc);
        if ($savedTitle && $savedDesc) {
             $successMsg .= "Teks Hero berhasil diperbarui. ";
        } else {
             $errorMsg .= "Gagal menyimpan teks hero. ";
        }
    }

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
$pageSubtitle = "Atur tampilan dan konfigurasi aplikasi";
$activePage = 'settings';
include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Informasi Sekolah</h3>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Nama Sekolah</label>
                <div class="col-sm-9">
                    <input type="text" name="school_name" class="form-control" value="<?php echo htmlspecialchars((string)get_setting('school_name', 'Perpustakaan')); ?>" placeholder="Masukkan Nama Sekolah">
                </div>
            </div>
            <hr>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Logo Aplikasi</label>
                <div class="col-sm-9">
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <small class="form-text text-muted">Akan menggantikan <code>assets/images/logo.png</code>.</small>
                    <div class="mt-2">
                        <img src="assets/images/logo.png?t=<?php echo time(); ?>" alt="Current Logo" style="max-height: 50px; background: #eee; padding: 5px;">
                    </div>
                </div>
            </div>
            <hr>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Gambar Hero (Depan)</label>
                <div class="col-sm-9">
                    <input type="file" name="hero" class="form-control" accept="image/*">
                    <small class="form-text text-muted">Akan menggantikan <code>assets/images/book-hero.png</code>.</small>
                    <div class="mt-2">
                        <img src="assets/images/book-hero.png?t=<?php echo time(); ?>" alt="Current Hero" style="max-height: 100px; background: #eee; padding: 5px;" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
            <hr>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Judul Hero</label>
                <div class="col-sm-9">
                    <input type="text" name="hero_title" class="form-control" value="<?php echo htmlspecialchars((string)get_setting('hero_title', 'Temukan Buku Favoritmu')); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Deskripsi Hero</label>
                <div class="col-sm-9">
                    <textarea name="hero_description" id="hero_description" class="form-control"><?php echo htmlspecialchars((string)get_setting('hero_description', 'Akses ribuan koleksi buku digital dan fisik perpustakaan kami dengan mudah. Mulai petualangan literasimu hari ini.')); ?></textarea>
                </div>
            </div>
            <hr>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Background Login</label>
                <div class="col-sm-9">
                    <input type="file" name="login_bg" class="form-control" accept="image/*">
                    <small class="form-text text-muted">Akan menggantikan <code>assets/images/login-bg.png</code>.</small>
                    <div class="mt-2">
                        <img src="assets/images/login-bg.png?t=<?php echo time(); ?>" alt="Current Login BG" style="max-height: 100px; background: #eee; padding: 5px;" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php
$extra_js = '
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
ClassicEditor.create(document.querySelector("#hero_description"), {
    toolbar: ["undo", "redo", "|", "heading", "|", "bold", "italic", "|", "link", "blockQuote", "insertTable", "|", "bulletedList", "numberedList", "|", "outdent", "indent"]
}).catch(console.error);
</script>
<style>.ck-editor__editable_inline { min-height: 150px; }</style>
';
include 'template/footer.php';
