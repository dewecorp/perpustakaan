<?php
require_once 'config/config.php';
require_login();
require_admin();
$pdo = db();

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

$pageTitle = "Manajemen Pengguna";
$pageSubtitle = "Kelola akun pengguna sistem";
$activePage = 'users';
$pageActions = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-lg me-1"></i> Tambah Pengguna</button>';

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Pengguna</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered nowrap">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Level</th>
                        <th>Password</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td>
                            <?php
                            if (!empty($u['avatar']) && file_exists($u['avatar'])) {
                                $avatarUrl = $u['avatar'];
                            } else {
                                $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($u['name']) . '&background=random&color=fff&size=50';
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="rounded-circle" width="40" height="40" style="object-fit:cover;">
                        </td>
                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars(($u['role'] ?? 'admin') === 'pustakawan' ? 'Pustakawan' : 'Admin'); ?></td>
                        <td>********</td>
                        <td>
                            <button class="btn btn-warning btn-sm btn-edit"
                                data-id="<?php echo $u['id']; ?>"
                                data-name="<?php echo htmlspecialchars($u['name']); ?>"
                                data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                data-role="<?php echo htmlspecialchars($u['role'] ?? 'admin'); ?>"
                                data-bs-toggle="modal" data-bs-target="#editUserModal">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <?php if ($u['id'] != 1): ?>
                            <button class="btn btn-danger btn-sm btn-delete" data-id="<?php echo $u['id']; ?>">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="users_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <select name="role" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="pustakawan">Pustakawan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto Avatar (Opsional)</label>
                        <input type="file" name="avatar" class="form-control">
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

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="users_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="edit_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <select name="role" id="edit_role" class="form-select">
                            <option value="admin">Admin</option>
                            <option value="pustakawan">Pustakawan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ganti Foto Avatar</label>
                        <input type="file" name="avatar" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
$(document).ready(function() {
    $(".btn-edit").on("click", function() {
        $("#edit_id").val($(this).data("id"));
        $("#edit_name").val($(this).data("name"));
        $("#edit_username").val($(this).data("username"));
        $("#edit_role").val($(this).data("role") || "admin");
    });
    $(".btn-delete").on("click", function() {
        var id = $(this).data("id");
        Swal.fire({
            title: "Apakah Anda yakin?",
            text: "Data pengguna akan dihapus permanen!",
            icon: "warning",
            showCancelButton: true,
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
