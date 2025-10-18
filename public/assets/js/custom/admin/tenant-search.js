document.addEventListener('DOMContentLoaded', function () {
  const nameInput = document.getElementById('nome');
  const emailInput = document.getElementById('email');
  const appSelect = document.getElementById('aplicativo');
  const localeInput = document.getElementById('locale');
  const statusSelect = document.getElementById('status');
  const rowsContainer = document.getElementById('tenants-rows');
  const clearBtn = document.getElementById('filters-reset');
  const SEARCH_URL = '/admin/tenant/search';

  if (!rowsContainer) return;

  let debounceTimer = null;

  function showLoadingRow() {
    rowsContainer.innerHTML = '<tr><td colspan="5" class="text-center text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Carregando...</td></tr>';
  }

  function buildQuery() {
    const params = new URLSearchParams();
    const nome = nameInput ? nameInput.value.trim() : '';
    const email = emailInput ? emailInput.value.trim() : '';
    const app = appSelect ? appSelect.value.trim() : '';
    const locale = localeInput ? localeInput.value.trim() : '';
    const st = statusSelect ? statusSelect.value.trim() : '';
    if (nome) params.append('nome', nome);
    if (email) params.append('email', email);
    if (app) params.append('aplicativo', app);
    if (locale) params.append('locale', locale);
    if (st) params.append('status', st);
    return params.toString();
  }

  function fetchRows() {
    const url = SEARCH_URL + (buildQuery() ? ('?' + buildQuery()) : '');
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (res) { return res.text(); })
      .then(function (html) {
        rowsContainer.innerHTML = html;
      })
      .catch(function (err) {
        console.error('Erro ao buscar tenants:', err);
        rowsContainer.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erro ao carregar resultados.</td></tr>';
      });
  }

  function triggerSearchDebounced() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      showLoadingRow();
      fetchRows();
    }, 300);
  }

  if (nameInput) nameInput.addEventListener('input', triggerSearchDebounced);
  if (emailInput) emailInput.addEventListener('input', triggerSearchDebounced);
  if (localeInput) localeInput.addEventListener('input', triggerSearchDebounced);
  if (appSelect) appSelect.addEventListener('change', triggerSearchDebounced);
  if (statusSelect) statusSelect.addEventListener('change', triggerSearchDebounced);

  // Botão de limpar filtros
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      clearBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Limpando...';

      if (nameInput) nameInput.value = '';
      if (emailInput) emailInput.value = '';
      if (appSelect) appSelect.value = '';
      if (localeInput) localeInput.value = '';
      if (statusSelect) statusSelect.value = '';

      triggerSearchDebounced();

      setTimeout(function () {
        clearBtn.innerHTML = '<i class="ti ti-eraser fs-4 me-2"></i> Limpar';
      }, 600);
    });
  }

  // Impedir o submit padrão do formulário de filtros
  const filterForm = (nameInput || emailInput)?.closest('form');
  if (filterForm) {
    filterForm.addEventListener('submit', function (e) {
      e.preventDefault();
      triggerSearchDebounced();
    });
  }
});