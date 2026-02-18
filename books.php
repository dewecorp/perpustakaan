<?php
require_once 'config/config.php';
require_login();

$pdo = db();
$books = $pdo->query("SELECT * FROM books ORDER BY created_at DESC")->fetchAll();
// Ambil daftar kategori
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
$activePage = 'books';

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
                                <i class="icofont icofont-book-alt bg-c-blue"></i>
                                <div class="d-inline">
                                    <h4>Kelola Buku</h4>
                                    <span>Tambah, ubah, dan hapus data buku</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="page-header-breadcrumb">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#bookModal" onclick="resetForm()">
                                    <i class="ti-plus me-1"></i> Tambah Buku
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="page-body">

                    <div class="card">
                        <div class="card-header">
                            <h5>Daftar Buku</h5>
                        </div>
                        <div class="card-block">
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
                                            <th style="width:160px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach($books as $b): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($b['code']); ?></td>
                                            <td>
                                                <?php $coverSrc = !empty($b['cover_path']) ? $b['cover_path'] : $b['cover_url']; ?>
                                                <?php if(!empty($coverSrc)): ?>
                                                    <img src="<?php echo htmlspecialchars($coverSrc); ?>" alt="Cover" style="height:48px; width:36px; object-fit:cover; border-radius:4px">
                                                <?php else: ?>
                                                    <span class="text-muted">Tidak ada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($b['title']); ?></td>
                                            <td><?php echo htmlspecialchars($b['author']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($b['category']); ?></span></td>
                                            <td><span class="badge bg-primary"><?php echo htmlspecialchars($b['year']); ?></span></td>
                                            <td><?php echo htmlspecialchars($b['isbn'] ?? ''); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                    data-toggle="modal" data-target="#bookModal"
                                                    onclick='editBook(<?php echo json_encode($b); ?>)'>
                                                    <i class="ti-pencil"></i>
                                                </button>
                                                <form action="books_process.php" method="POST" style="display:inline" class="delete-book-form">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti-trash"></i></button>
                                                </form>
                                                <?php
                                                    $hasLocalBook  = !empty($b['book_path']);
                                                    $hasRemoteBook = !empty($b['book_url']);
                                                ?>
                                                <?php if($hasLocalBook || $hasRemoteBook): ?>
                                                    <?php if($hasLocalBook): ?>
                                                        <a href="track_download.php?path=<?php echo urlencode($b['book_path']); ?>" class="btn btn-sm btn-outline-success" target="_blank">Unduh</a>
                                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="previewBook('<?php echo htmlspecialchars($b['book_path']); ?>')">Lihat</button>
                                                    <?php else: ?>
                                                        <a href="<?php echo htmlspecialchars($b['book_url']); ?>" class="btn btn-sm btn-outline-success" target="_blank">Lihat</a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
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

<!-- Modal -->
<div class="modal fade" id="bookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="books_process.php" method="POST" id="bookForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Buku</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="bookId">
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Kode Buku</label>
                            <input name="code" id="code" class="form-control" required placeholder="ex: BK-001">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>ISBN</label>
                            <input name="isbn" id="isbn" class="form-control" placeholder="ex: 978-602-XXXX-XX">
                        </div>
                        <div class="col-md-5 form-group">
                            <label>Judul</label>
                            <input name="title" id="title" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Penulis</label>
                            <input name="author" id="author" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Kategori</label>
                            <select name="category" id="category" class="form-control">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach($categoriesList as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['nama_kategori']); ?>">
                                        <?php echo htmlspecialchars($c['nama_kategori']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Tahun</label>
                            <input name="year" id="year" type="number" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>URL Sampul (opsional)</label>
                            <input name="cover_url" id="cover_url" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Upload Sampul</label>
                            <input type="file" name="cover_file" id="cover_file" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Upload File Buku (PDF)</label>
                            <input type="file" name="book_file" id="book_file" class="form-control" accept="application/pdf">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>URL File Buku (opsional)</label>
                            <input type="url" name="book_url" id="book_url" class="form-control" placeholder="https://drive.google.com/...">
                        </div>
                        <div class="col-12 form-group">
                            <label class="mb-1">
                                <input type="checkbox" name="use_book_url_only" id="use_book_url_only" value="1" style="margin-right:6px;">
                                Gunakan hanya URL File Buku dan abaikan file PDF yang diupload
                            </label>
                            <small class="text-muted d-block" id="bookSourceHint"></small>
                        </div>
                        <div class="col-12 form-group">
                            <label>Deskripsi</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
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

<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width:95vw;width:95vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pratinjau Buku</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding:0;height:92vh;display:flex;">
                <iframe id="previewFrame" src="" style="border:0;flex:1 1 auto;height:100%;width:100%;display:block;"></iframe>
            </div>
        </div>
    </div>
    </div>

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
    document.getElementById('formAction').value = 'update';
    document.getElementById('bookId').value = data.id;
    document.getElementById('code').value = data.code;
    document.getElementById('isbn').value = data.isbn || '';
    document.getElementById('title').value = data.title;
    document.getElementById('author').value = data.author;
    document.getElementById('category').value = data.category;
    document.getElementById('year').value = data.year;
    document.getElementById('cover_url').value = data.cover_url;
    document.getElementById('book_url').value = data.book_url || '';
    document.getElementById('description').value = data.description;
    var hasLocal = !!data.book_path;
    var hasUrl = !!data.book_url;
    var useUrlOnly = document.getElementById('use_book_url_only');
    if (useUrlOnly) {
        useUrlOnly.checked = !hasLocal && hasUrl;
    }
    var hint = document.getElementById('bookSourceHint');
    if (hint) {
        var msg = '';
        if (hasLocal && hasUrl) {
            msg = 'Saat ini buku memiliki file upload dan URL. Sistem memakai file upload. Centang untuk beralih ke URL saja.';
        } else if (hasLocal) {
            msg = 'Saat ini sumber file buku dari upload server.';
        } else if (hasUrl) {
            msg = 'Saat ini sumber file buku dari URL.';
        }
        hint.textContent = msg;
    }
    document.getElementById('modalTitle').textContent = 'Ubah Buku';
}
function previewBook(path) {
    var f = document.getElementById('previewFrame');
    var url = 'preview_book_viewer.php?path=' + encodeURIComponent(path);
    f.src = url;
    $('#previewModal').modal('show');
}
</script>

<?php
$extra_js = "
<script>
$(document).ready(function() {
    var table = $('#booksTable').DataTable({
        language: {
            url: 'assets/lang/datatables-id.json'
        },
        columnDefs: [
            { targets: 0, orderable: false, searchable: false }
        ],
        order: [[1, 'asc']]
    });

    table.on('order.dt search.dt draw.dt', function() {
        table.column(0, { search: 'applied', order: 'applied' }).nodes().each(function(cell, i) {
            cell.innerHTML = (i + 1);
        });
    }).draw();
});
</script>
";
include 'template/footer.php';
?>
