<?php
require_once 'config/config.php';
require_login();
$pdo = db();
$pageTitle = "Kategori Buku";
$pageSubtitle = "Kelola data kategori buku perpustakaan";
$activePage = 'categories';
$pageActions = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKategori" onclick="resetForm()"><i class="bi bi-plus-lg me-1"></i> Tambah Kategori</button>';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll();

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Kategori</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-compact">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th class="text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $index => $category): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($category['nama_kategori']); ?></td>
                        <td class="text-nowrap">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Aksi kategori">
                                <button type="button" class="btn btn-warning" title="Edit" onclick="editKategori(<?php echo $category['id']; ?>, '<?php echo addslashes($category['nama_kategori']); ?>')"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="btn btn-danger" title="Hapus" onclick="confirmDelete(<?php echo $category['id']; ?>)"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<div class="modal fade" id="modalKategori" tabindex="-1" aria-labelledby="modalKategoriLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalKategoriLabel">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="categories_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="categoryId">
                    <div class="mb-3">
                        <label for="nama_kategori" class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required placeholder="Masukkan nama kategori">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $page_modals = ob_get_clean(); ?>

<form id="deleteForm" action="categories_process.php" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<?php
$extra_js = '
<script>
function resetForm() {
    document.getElementById("formAction").value = "add";
    document.getElementById("categoryId").value = "";
    document.getElementById("nama_kategori").value = "";
    document.getElementById("modalKategoriLabel").innerText = "Tambah Kategori";
}
function editKategori(id, nama) {
    document.getElementById("formAction").value = "edit";
    document.getElementById("categoryId").value = id;
    document.getElementById("nama_kategori").value = nama;
    document.getElementById("modalKategoriLabel").innerText = "Edit Kategori";
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalKategori")).show();
}
function confirmDelete(id) {
    Swal.fire({
        title: "Apakah Anda yakin?",
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById("deleteId").value = id;
            document.getElementById("deleteForm").submit();
        }
    });
}
</script>
';
include 'template/footer.php';
