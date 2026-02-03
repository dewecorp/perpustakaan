(() => {
  const API = 'api/books.php';
  const tbody = document.getElementById('booksTbody');
  const btnAdd = document.getElementById('btnAdd');
  const modalEl = document.getElementById('bookModal');
  const saveBtn = document.getElementById('saveBtn');
  const form = document.getElementById('bookForm');

  let currentAction = 'create';
  let currentId = '';

  function fetchBooks() {
    return fetch(API).then(r => r.json()).then(j => j.data || []);
  }
  function postForm(action, data) {
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    return fetch(API, { method: 'POST', body: fd }).then(r => r.json());
  }

  function renderRows(books) {
    tbody.innerHTML = books.map(b => `
      <tr>
        <td class="text-muted">${b.id}</td>
        <td><img src="${b.cover_url}" alt="${b.title}" style="height:48px; width:36px; object-fit:cover; border-radius:4px"></td>
        <td>${b.title}</td>
        <td>${b.author}</td>
        <td><span class="badge bg-secondary">${b.category}</span></td>
        <td><span class="badge bg-primary">${b.year}</span></td>
        <td>
          <button class="btn btn-sm btn-outline-primary me-1" data-id="${b.id}" data-act="edit"><i class="ti-pencil"></i></button>
          <button class="btn btn-sm btn-outline-danger" data-id="${b.id}" data-act="delete"><i class="ti-trash"></i></button>
        </td>
      </tr>
    `).join('');
  }

  async function reload() {
    const books = await fetchBooks();
    renderRows(books);
  }

  btnAdd.addEventListener('click', () => {
    currentAction = 'create';
    currentId = '';
    form.reset();
    form.querySelector('[name=id]').value = '';
    modalEl.querySelector('.modal-title').textContent = 'Tambah Buku';
    saveBtn.textContent = 'Simpan';
    $('#bookModal').modal('show');
  });

  tbody.addEventListener('click', (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    const id = btn.getAttribute('data-id');
    const act = btn.getAttribute('data-act');
    if (act === 'edit') {
      editBook(id);
    } else if (act === 'delete') {
      deleteBook(id);
    }
  });

  async function editBook(id) {
    const books = await fetchBooks();
    const b = books.find(x => x.id === id);
    if (!b) return;
    currentAction = 'update';
    currentId = id;
    form.querySelector('[name=id]').value = b.id;
    form.querySelector('[name=title]').value = b.title;
    form.querySelector('[name=author]').value = b.author;
    form.querySelector('[name=category]').value = b.category;
    form.querySelector('[name=year]').value = b.year;
    form.querySelector('[name=cover_url]').value = b.cover_url;
    form.querySelector('[name=description]').value = b.description;
    modalEl.querySelector('.modal-title').textContent = 'Ubah Buku';
    saveBtn.textContent = 'Perbarui';
    $('#bookModal').modal('show');
  }

  async function deleteBook(id) {
    if (!confirm('Hapus buku ini?')) return;
    const res = await postForm('delete', { id });
    await reload();
  }

  saveBtn.addEventListener('click', async () => {
    const data = Object.fromEntries(new FormData(form).entries());
    if (currentAction === 'update') {
      data.id = currentId || data.id;
    }
    await postForm(currentAction, data);
    $('#bookModal').modal('hide');
    await reload();
  });

  document.addEventListener('DOMContentLoaded', reload);
})(); 
