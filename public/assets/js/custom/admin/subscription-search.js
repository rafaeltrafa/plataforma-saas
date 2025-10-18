document.addEventListener('DOMContentLoaded', function () {
  const emailInput = document.getElementById('email');
  const appSelect = document.getElementById('aplicativo');
  const statusSelect = document.getElementById('status');
  const planSelect = document.getElementById('plano');
  const rowsContainer = document.getElementById('subscriptions-rows');
  const clearBtn = document.getElementById('filters-reset');
  const pagerContainer = document.getElementById('subscriptions-pager');
  const SEARCH_URL = '/admin/subscription/search';
  const PLANS_URL = '/admin/subscription/plans';

  if (!emailInput || !rowsContainer) return;

  let debounceTimer = null;

  function showLoadingRow() {
    rowsContainer.innerHTML = '<tr><td colspan="7" class="text-center text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Carregando...</td></tr>';
  }
  function showLoadingPager() {
    if (pagerContainer) {
      pagerContainer.innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Carregando...</div>';
    }
  }

  function buildQuery() {
    const params = new URLSearchParams();
    const q = emailInput.value.trim();
    const app = appSelect ? appSelect.value.trim() : '';
    const st = statusSelect ? statusSelect.value.trim() : '';
    const pl = planSelect ? planSelect.value.trim() : '';
    if (q) params.append('email', q);
    if (app) params.append('aplicativo', app);
    if (st) params.append('status', st);
    if (pl) params.append('plano', pl);
    return params.toString();
  }

  function fetchRows(page) {
    const qs = buildQuery();
    let url = SEARCH_URL + (qs ? ('?' + qs) : '');
    if (page) {
      url += (qs ? '&' : '?') + 'page=' + encodeURIComponent(page);
    }
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (json && typeof json === 'object') {
          rowsContainer.innerHTML = json.rows || '<tr><td colspan="7" class="text-center text-muted">Nenhum registro encontrado.</td></tr>';
          if (pagerContainer) pagerContainer.innerHTML = json.pager || '';
        } else {
          rowsContainer.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Resposta inválida.</td></tr>';
        }
      })
      .catch(function (err) {
        console.error('Erro ao buscar assinaturas:', err);
        rowsContainer.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Erro ao carregar resultados.</td></tr>';
      });
  }

  function triggerSearchDebounced() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      showLoadingRow();
      showLoadingPager();
      fetchRows();
    }, 300);
  }

  emailInput.addEventListener('input', triggerSearchDebounced);
  if (appSelect) appSelect.addEventListener('change', function () {
    // sempre que alterar app, limpar e carregar planos
    if (planSelect) {
      planSelect.innerHTML = '<option value="">Carregando planos...</option>';
      planSelect.disabled = true;
    }

    const app = appSelect.value.trim();
    if (!app) {
      if (planSelect) {
        planSelect.innerHTML = '<option value="">Selecione o aplicativo primeiro...</option>';
        planSelect.disabled = true;
      }
      triggerSearchDebounced();
      return;
    }

    fetch(PLANS_URL + '/' + encodeURIComponent(app), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (res) { return res.json(); })
      .then(function (json) {
        if (!planSelect) return;
        if (json.success && Array.isArray(json.plans) && json.plans.length) {
          const opts = ['<option value="">Todos os planos...</option>'];
          json.plans.forEach(function (p) {
            const label = p.name + (p.price_amount ? (' - ' + p.price_amount + (p.currency ? (' ' + p.currency.toUpperCase()) : '')) : '');
            opts.push('<option value="' + p.id + '">' + label + '</option>');
          });
          planSelect.innerHTML = opts.join('');
          planSelect.disabled = false;
        } else {
          planSelect.innerHTML = '<option value="">Nenhum plano encontrado</option>';
          planSelect.disabled = true;
        }
        // dispara busca após atualizar lista
        triggerSearchDebounced();
      })
      .catch(function (err) {
        console.error('Erro ao carregar planos:', err);
        if (planSelect) {
          planSelect.innerHTML = '<option value="">Erro ao carregar planos</option>';
          planSelect.disabled = true;
        }
        triggerSearchDebounced();
      });
  });
  if (statusSelect) statusSelect.addEventListener('change', triggerSearchDebounced);
  if (planSelect) planSelect.addEventListener('change', triggerSearchDebounced);

  // Botão de limpar filtros
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      const originalHTML = clearBtn.innerHTML;
      clearBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Limpando...';

      if (emailInput) emailInput.value = '';
      if (appSelect) {
        appSelect.value = '';
        // disparar change para garantir reset de planos
        const evt = new Event('change', { bubbles: true });
        appSelect.dispatchEvent(evt);
      }
      if (statusSelect) statusSelect.value = '';
      if (planSelect) {
        planSelect.value = '';
        planSelect.innerHTML = '<option value="">Selecione o aplicativo primeiro...</option>';
        planSelect.disabled = true;
      }

      // disparar busca com filtros limpos
      triggerSearchDebounced();

      setTimeout(function () {
        clearBtn.innerHTML = '<i class="ti ti-eraser fs-4 me-2"></i> Limpar';
      }, 600);
    });
  }

  // Impedir o submit padrão do formulário de filtros
  const filterForm = emailInput.closest('form');
  if (filterForm) {
    filterForm.addEventListener('submit', function (e) {
      e.preventDefault();
      triggerSearchDebounced();
    });
  }

  // Interceptar cliques na paginação para manter filtros
  if (pagerContainer) {
    pagerContainer.addEventListener('click', function (e) {
      const link = e.target.closest('a');
      if (!link) return;
      const href = link.getAttribute('href');
      if (!href) return;
      const url = new URL(href, window.location.origin);
      const page = url.searchParams.get('page') || url.searchParams.get('page_default') || url.searchParams.get('page_subscriptions');
      if (!page) return;
      e.preventDefault();
      showLoadingRow();
      showLoadingPager();
      fetchRows(page);
    });
  }

  // Delegação para ações de status funcionar em linhas recém-carregadas
  const STATUS_MAP = {
    active: { class: 'bg-success-subtle text-success', icon: 'ti ti-circle-check', text: 'Ativo' },
    past_due: { class: 'bg-danger-subtle text-danger', icon: 'ti ti-hourglass', text: 'Vencido' },
    canceled: { class: 'bg-danger-subtle text-danger', icon: 'ti ti-circle-x', text: 'Cancelado' },
    incomplete: { class: 'bg-warning-subtle text-warning', icon: 'ti ti-alert-circle', text: 'Incompleto' },
    unpaid: { class: 'bg-danger-subtle text-danger', icon: 'ti ti-alert-circle', text: 'Não pago' },
    trialing: { class: 'bg-info-subtle text-info', icon: 'ti ti-clock', text: 'Em teste' },
    incomplete_expired: { class: 'bg-danger-subtle text-danger', icon: 'ti ti-hourglass', text: 'Expirado' },
    paused: { class: 'bg-secondary-subtle text-secondary', icon: 'ti ti-player-pause', text: 'Pausado' },
  };

  function updateStatusBadge(cell, statusKey) {
    const info = STATUS_MAP[statusKey] || { class: 'bg-secondary-subtle text-secondary', icon: 'ti ti-dots', text: '—' };
    cell.innerHTML = '<span class="badge ' + info.class + '"><i class="' + info.icon + ' me-1"></i> ' + info.text + '</span>';
  }

  document.addEventListener('click', function (e) {
    const link = e.target.closest('.js-sub-action');
    if (!link || link.dataset.bound === '1') return; // evitar duplicação em links já inicializados
    e.preventDefault();

    const url = link.getAttribute('href');
    const message = link.dataset.message || 'Confirmar ação?';
    const row = link.closest('tr');
    const statusCell = row ? row.querySelector('.js-status-cell') : null;

    const csrfName = link.getAttribute('data-csrf-name') || (document.querySelector('meta[name="csrf-token-name"]')?.content || '');
    let csrfValue = link.getAttribute('data-csrf-value') || (document.querySelector('meta[name="csrf-token-value"]')?.content || '');

    const proceed = function () {
      const body = new URLSearchParams();
      const status = link.getAttribute('data-status') || '';
      if (status) body.append('status', status);
      if (csrfName && csrfValue) {
        body.append(csrfName, csrfValue);
      }

      fetch(url, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: body.toString()
      }).then(function (res) {
        if (!res.ok) throw new Error('Falha na requisição');
        return res.json();
      }).then(function (json) {
        if (json.csrf) { csrfValue = json.csrf; link.setAttribute('data-csrf-value', csrfValue); }
        if (json.success) {
          if (statusCell) updateStatusBadge(statusCell, (json.status || '').toLowerCase());
          if (window.Swal) {
            Swal.fire({ icon: 'success', title: 'Atualizado', text: json.message || 'Status atualizado.' });
          }
        } else {
          if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Erro', text: json.message || 'Não foi possível atualizar.' });
          } else {
            alert(json.message || 'Não foi possível atualizar.');
          }
        }
      }).catch(function (err) {
        if (window.Swal) {
          Swal.fire({ icon: 'error', title: 'Erro', text: err.message || 'Falha na comunicação.' });
        } else {
          alert(err.message || 'Falha na comunicação.');
        }
      });
    };

    if (window.Swal) {
      Swal.fire({
        title: 'Confirmar ação',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
      }).then(function (result) { if (result.isConfirmed) proceed(); });
    } else {
      if (confirm(message)) proceed();
    }
  });
});