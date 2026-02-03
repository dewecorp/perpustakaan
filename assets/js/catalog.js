(() => {
  const API = 'api/books.php';
  const elGrid = document.getElementById('booksGrid');
  const elCategory = document.getElementById('categorySelect');
  const elYear = document.getElementById('yearSelect');
  const elSearch = document.getElementById('searchInput');
  const elEmpty = document.getElementById('emptyState');
  const elReset = document.getElementById('resetBtn');

  const state = {
    books: [],
    filters: { category: '', year: '', q: '' }
  };

  function fetchBooks(params = {}) {
    const query = new URLSearchParams(params).toString();
    return fetch(`${API}${query ? '?' + query : ''}`)
      .then(r => r.json())
      .then(json => json.data || []);
  }

  function renderFilters() {
    const categories = Array.from(new Set(state.books.map(b => b.category).filter(Boolean))).sort();
    const years = Array.from(new Set(state.books.map(b => b.year).filter(Boolean))).sort((a,b)=>b-a);

    elCategory.innerHTML = '<option value="">Semua Kategori</option>' +
      categories.map(c => `<option value="${c}">${c}</option>`).join('');
    elYear.innerHTML = '<option value="">Semua Tahun</option>' +
      years.map(y => `<option value="${y}">${y}</option>`).join('');
  }

  function renderBooks(books) {
    if (!books.length) {
      elGrid.innerHTML = '';
      elEmpty.classList.remove('d-none');
      return;
    }
    elEmpty.classList.add('d-none');
    elGrid.innerHTML = books.map(b => `
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card book-card h-100">
          <img src="${b.cover_url}" alt="${b.title}" class="cover">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <h6 class="card-title mb-1">${b.title}</h6>
              <span class="badge bg-primary">${b.year}</span>
            </div>
            <p class="text-muted mb-2">by ${b.author}</p>
            <span class="badge bg-secondary mb-2">${b.category}</span>
            <p class="card-text small">${b.description}</p>
          </div>
        </div>
      </div>`).join('');
  }

  async function load() {
    state.books = await fetchBooks();
    renderFilters();
    renderBooks(state.books);
  }

  async function applyFilters() {
    const params = {};
    if (state.filters.category) params.category = state.filters.category;
    if (state.filters.year) params.year = state.filters.year;
    if (state.filters.q) params.q = state.filters.q;
    const data = await fetchBooks(params);
    renderBooks(data);
  }

  elCategory.addEventListener('change', () => {
    state.filters.category = elCategory.value;
    applyFilters();
  });
  elYear.addEventListener('change', () => {
    state.filters.year = elYear.value;
    applyFilters();
  });
  elSearch.addEventListener('input', () => {
    state.filters.q = elSearch.value.trim();
    applyFilters();
  });
  elReset.addEventListener('click', () => {
    state.filters = { category: '', year: '', q: '' };
    elCategory.value = '';
    elYear.value = '';
    elSearch.value = '';
    applyFilters();
  });

  document.addEventListener('DOMContentLoaded', load);
})(); 
