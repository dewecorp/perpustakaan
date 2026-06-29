<?php
require_once 'config/config.php';
require_login();

$pdo = db();
$books = $pdo->query("SELECT * FROM books ORDER BY created_at DESC")->fetchAll();
$categoriesList = [];
try {
    $stmtCats = $pdo->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori ASC");
    $categoriesList = $stmtCats->fetchAll();
} catch (PDOException $e) {
    try {
        $stmtCats = $pdo->query("SELECT id, name AS nama_kategori FROM categories ORDER BY name ASC");
        $categoriesList = $stmtCats->fetchAll();
    } catch (PDOException $e2) {
        $categoriesList = [];
    }
}

$pageTitle = "Kelola Buku";
$pageSubtitle = "Tambah, ubah, dan hapus data buku";
$activePage = 'books';
$pageActions = '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookModal" onclick="resetForm()"><i class="bi bi-plus-lg me-1"></i> Tambah Buku</button>';

include 'template/header.php';
include 'template/sidebar.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Buku</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="booksTable" class="table table-hover custom-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Sampul</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Tahun</th>
                        <th>ISBN</th>
                        <th class="text-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($books as $b): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars((string)($b['code'] ?? '')); ?></td>
                        <td>
                            <?php $coverSrc = book_cover_url($b); ?>
                            <img src="<?php echo htmlspecialchars($coverSrc); ?>" alt="Cover" style="height:48px; width:36px; object-fit:<?php echo $coverSrc === book_cover_placeholder() ? 'contain' : 'cover'; ?>; border-radius:4px; background:#e3f2fd; padding:<?php echo $coverSrc === book_cover_placeholder() ? '4px' : '0'; ?>;" onerror="this.onerror=null;this.src='<?php echo book_cover_placeholder(); ?>';">
                        </td>
                        <td><?php echo htmlspecialchars($b['title']); ?></td>
                        <td><?php echo htmlspecialchars($b['author']); ?></td>
                        <td><span class="badge text-bg-secondary"><?php echo htmlspecialchars($b['category']); ?></span></td>
                        <td><span class="badge text-bg-primary"><?php echo htmlspecialchars($b['year']); ?></span></td>
                        <td><?php echo htmlspecialchars($b['isbn'] ?? ''); ?></td>
                        <td class="text-nowrap">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Aksi buku">
                                <button type="button" class="btn btn-warning btn-edit-book" title="Edit"
                                    data-bs-toggle="modal" data-bs-target="#bookModal"
                                    data-book="<?php echo htmlspecialchars(json_encode($b, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="books_process.php" method="POST" class="delete-book-form" style="display: contents">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                    <button type="submit" class="btn btn-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php
                                    $hasLocalBook = !empty($b['book_path']);
                                    $hasRemoteBook = !empty($b['book_url']);
                                ?>
                                <?php if ($hasLocalBook || $hasRemoteBook): ?>
                                    <?php if ($hasLocalBook): ?>
                                        <button type="button" class="btn btn-info btn-preview-book" title="Lihat" data-id="<?php echo (int)$b['id']; ?>" data-path="<?php echo htmlspecialchars($b['book_path'], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-eye"></i></button>
                                        <a href="track_book.php?id=<?php echo (int)$b['id']; ?>&action=download" class="btn btn-success" title="Unduh" target="_blank" rel="noopener"><i class="bi bi-download"></i></a>
                                    <?php else: ?>
                                        <a href="track_book.php?id=<?php echo (int)$b['id']; ?>&action=view" class="btn btn-info" title="Lihat" target="_blank" rel="noopener"><i class="bi bi-eye"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="bookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="books_process.php" method="POST" id="bookForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Buku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="bookId">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Kode Buku</label>
                            <input name="code" id="code" class="form-control" required placeholder="ex: BK-001">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">ISBN</label>
                            <input name="isbn" id="isbn" class="form-control" placeholder="ex: 978-602-XXXX-XX">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Judul</label>
                            <input name="title" id="title" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Penulis</label>
                            <input name="author" id="author" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="category" id="category" class="form-select">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categoriesList as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['nama_kategori']); ?>"><?php echo htmlspecialchars($c['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Tahun</label>
                            <input name="year" id="year" type="number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">URL Sampul (opsional)</label>
                            <input name="cover_url" id="cover_url" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload Sampul</label>
                            <input type="file" name="cover_file" id="cover_file" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Upload File Buku (PDF)</label>
                            <input type="file" name="book_file" id="book_file" class="form-control" accept="application/pdf">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">URL File Buku (opsional)</label>
                            <input type="url" name="book_url" id="book_url" class="form-control" placeholder="https://drive.google.com/...">
                        </div>
                        <div class="col-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="use_book_url_only" id="use_book_url_only" value="1" class="form-check-input">
                                <label class="form-check-label" for="use_book_url_only">Gunakan hanya URL File Buku dan abaikan file PDF yang diupload</label>
                            </div>
                            <small class="text-muted d-block" id="bookSourceHint"></small>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
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

<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width:95vw;width:95vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pratinjau Buku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="height:92vh;display:flex;">
                <iframe id="previewFrame" src="" style="border:0;flex:1 1 auto;height:100%;width:100%;display:block;"></iframe>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = "
<script>
function resetForm() {
    document.getElementById('bookForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('bookId').value = '';
    document.getElementById('modalTitle').textContent = 'Tambah Buku';
    var useUrlOnly = document.getElementById('use_book_url_only');
    if (useUrlOnly) useUrlOnly.checked = false;
    var hint = document.getElementById('bookSourceHint');
    if (hint) hint.textContent = '';
}
function editBook(data) {
    if (!data) return;
    document.getElementById('formAction').value = 'update';
    document.getElementById('bookId').value = data.id;
    document.getElementById('code').value = data.code || '';
    document.getElementById('isbn').value = data.isbn || '';
    document.getElementById('title').value = data.title || '';
    document.getElementById('author').value = data.author || '';
    document.getElementById('category').value = data.category || '';
    document.getElementById('year').value = data.year || '';
    document.getElementById('cover_url').value = data.cover_url || '';
    document.getElementById('book_url').value = data.book_url || '';
    document.getElementById('description').value = data.description || '';
    var hasLocal = !!data.book_path;
    var hasUrl = !!data.book_url;
    var useUrlOnly = document.getElementById('use_book_url_only');
    if (useUrlOnly) useUrlOnly.checked = !hasLocal && hasUrl;
    var hint = document.getElementById('bookSourceHint');
    if (hint) {
        if (hasLocal && hasUrl) hint.textContent = 'Saat ini buku memiliki file upload dan URL. Sistem memakai file upload.';
        else if (hasLocal) hint.textContent = 'Saat ini sumber file buku dari upload server.';
        else if (hasUrl) hint.textContent = 'Saat ini sumber file buku dari URL.';
        else hint.textContent = '';
    }
    document.getElementById('modalTitle').textContent = 'Ubah Buku';
}
function previewBook(bookId, path) {
    if (!path) return;
    var url = 'preview_book_viewer.php?path=' + encodeURIComponent(path);
    if (bookId) url += '&id=' + bookId;
    document.getElementById('previewFrame').src = url;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('previewModal')).show();
}
$(document).ready(function() {
    $(document).on('click', '.btn-edit-book', function() {
        try {
            editBook(JSON.parse(this.getAttribute('data-book') || '{}'));
        } catch (e) {
            console.error('Gagal memuat data buku:', e);
        }
    });
    $(document).on('click', '.btn-preview-book', function() {
        previewBook(this.getAttribute('data-id'), this.getAttribute('data-path'));
    });

    var table = $('#booksTable').DataTable({
        language: { url: '" . BASE_URL . "assets/lang/datatables-id.json' },
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, className: 'text-center' },
            { targets: -1, orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        drawCallback: function() {
            var api = this.api();
            var start = api.page.info().start;
            api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                cell.innerHTML = start + i + 1;
            });
        }
    });
});
</script>
";
include 'template/footer.php';
