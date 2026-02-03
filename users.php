<?php
require_once 'config/config.php';
require_login();
$pdo = db();

// Fetch users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

$pageTitle = "Manajemen Pengguna";
$activePage = 'users';
include 'template/header.php';
include 'template/sidebar.php';
?>
<!-- Content -->
<div class="pcoded-content">
    <div class="pcoded-inner-content">
        <div class="main-body">
            <div class="page-wrapper">
                <div class="page-header card">
                    <div class="row align-items-end">
                        <div class="col-lg-8">
                            <div class="page-header-title">
                                <i class="icofont icofont-users-alt-5 bg-c-pink"></i>
                                <div class="d-inline">
                                    <h4>Manajemen Pengguna</h4>
                                    <span>Kelola akun pengguna sistem</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="page-body">
                    <div class="card">
                        <div class="card-header">
                            <h5>Daftar Pengguna</h5>
                            <button class="btn btn-primary float-right" data-toggle="modal" data-target="#addUserModal"><i class="ti-plus"></i> Tambah Pengguna</button>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table class="table table-striped table-bordered nowrap">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Foto</th>
                                            <th>Nama</th>
                                            <th>Username</th>
                                            <th>Password</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($users as $i => $u): ?>
                                        <tr>
                                            <td><?= $i+1 ?></td>
                                            <td>
                                                <?php 
                                                if (!empty($u['avatar']) && file_exists($u['avatar'])) {
                                                    $avatarUrl = $u['avatar'];
                                                } else {
                                                    $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($u['name']).'&background=random&color=fff&size=50';
                                                }
                                                ?>
                                                <img src="<?= $avatarUrl ?>" alt="Avatar" class="img-radius" width="40" height="40" style="object-fit:cover;">
                                            </td>
                                            <td><?= htmlspecialchars($u['name']) ?></td>
                                            <td><?= htmlspecialchars($u['username']) ?></td>
                                            <td>********</td>
                                            <td>
                                                <button class="btn btn-warning btn-sm btn-edit" 
                                                    data-id="<?= $u['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($u['name']) ?>"
                                                    data-username="<?= htmlspecialchars($u['username']) ?>"
                                                    data-toggle="modal" data-target="#editUserModal">
                                                    <i class="ti-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $u['id'] ?>">
                                                    <i class="ti-trash"></i> Hapus
                                                </button>
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

<!-- Add Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="users_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengguna</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Foto Avatar (Opsional)</label>
                        <input type="file" name="avatar" class="form-control">
                        <small class="text-muted">Jika kosong, akan menggunakan inisial nama.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default waves-effect " data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary waves-effect waves-light ">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="users_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pengguna</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Ganti Foto Avatar</label>
                        <input type="file" name="avatar" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default waves-effect " data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary waves-effect waves-light ">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extra_js = '
<script>
$(document).ready(function() {
    // Edit Button
    $(".btn-edit").on("click", function() {
        var id = $(this).data("id");
        var name = $(this).data("name");
        var username = $(this).data("username");
        
        $("#edit_id").val(id);
        $("#edit_name").val(name);
        $("#edit_username").val(username);
    });

    // Delete Button
    $(".btn-delete").on("click", function() {
        var id = $(this).data("id");
        Swal.fire({
            title: "Apakah Anda yakin?",
            text: "Data pengguna akan dihapus permanen!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "users_process.php?action=delete&id=" + id;
            }
        });
    });
});
</script>
';
include 'template/footer.php'; 
?>
