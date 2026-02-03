<?php
require_once 'config/config.php';
require_login();
$pdo = db();
$pageTitle = "Kategori Buku";
$activePage = 'categories';

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll();

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="pcoded-content">
    <div class="pcoded-inner-content">
        <div class="main-body">
            <div class="page-wrapper">
                <div class="page-header card">
                    <div class="card-block">
                        <h5 class="m-b-10">Kategori Buku</h5>
                        <p class="text-muted m-b-10">Kelola data kategori buku perpustakaan</p>
                    </div>
                </div>
                <div class="page-body">
                    <div class="card">
                        <div class="card-header">
                            <h5>Daftar Kategori</h5>
                            <button class="btn btn-primary float-right" data-toggle="modal" data-target="#modalKategori" onclick="resetForm()"><i class="ti-plus"></i> Tambah Kategori</button>
                        </div>
                        <div class="card-block">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Kategori</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $index => $category): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($category['nama_kategori']); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm" onclick="editKategori(<?php echo $category['id']; ?>, '<?php echo addslashes($category['nama_kategori']); ?>')"><i class="ti-pencil"></i> Edit</button>
                                                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $category['id']; ?>)"><i class="ti-trash"></i> Hapus</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Kategori -->
<div class="modal fade" id="modalKategori" tabindex="-1" role="dialog" aria-labelledby="modalKategoriLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalKategoriLabel">Tambah Kategori</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="categories_process.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="categoryId">
                    <div class="form-group">
                        <label for="nama_kategori">Nama Kategori</label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required placeholder="Masukkan nama kategori">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form delete (hidden) -->
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
        $("#modalKategori").modal("show");
    }

    function confirmDelete(id) {
        Swal.fire({
            title: "Apakah Anda yakin?",
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
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
?>