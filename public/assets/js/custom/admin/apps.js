/* Interações da listagem de Apps: alternar ativação/desativação e ícone */
(function () {
  function ensureTrailingSlash(path) {
    return path.endsWith('/') ? path : path + '/';
  }

  function buildBase() {
    const basePath = ensureTrailingSlash(window.location.pathname);
    return `${window.location.origin}${basePath}`; // e.g., http://localhost:8080/admin/apps/
  }

  async function requestToggle(id, action) {
    const fullBase = buildBase();
    try {
      let res = await fetch(`${fullBase}${id}/${action}`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
      });
      if (!res.ok) {
        res = await fetch(`${fullBase}${id}/${action}`, {
          method: 'GET',
          headers: { 'Accept': 'application/json' },
        });
      }
      const data = await res.json().catch(() => ({ success: false }));
      return { ok: res.ok && data.success, data };
    } catch (e) {
      return { ok: false, data: { success: false, message: 'Erro de rede' } };
    }
  }

  function updateBadge(row, isActive) {
    const badge = row.querySelector('td:nth-child(2) .badge');
    if (!badge) return;
    if (isActive) {
      badge.textContent = 'Active';
      badge.classList.remove('bg-danger-subtle', 'text-danger');
      badge.classList.add('bg-success-subtle', 'text-success');
    } else {
      badge.textContent = 'Inactive';
      badge.classList.remove('bg-success-subtle', 'text-success');
      badge.classList.add('bg-danger-subtle', 'text-danger');
    }
  }

  function updateIcon(el, isActive) {
    function setTooltip(element, text) {
      element.setAttribute('title', text);
      element.setAttribute('data-bs-original-title', text);
      if (window.bootstrap && bootstrap.Tooltip) {
        const instance = bootstrap.Tooltip.getInstance(element);
        if (instance) {
          try {
            instance.setContent({ '.tooltip-inner': text });
          } catch (_) {
            instance.dispose();
            new bootstrap.Tooltip(element);
          }
        }
      }
    }

    if (isActive) {
      // Ícone para desativar
      el.classList.remove('activate-action', 'text-success', 'ti-check');
      el.classList.add('deactivate-action', 'text-danger', 'ti-power');
      setTooltip(el, 'Desativar');
    } else {
      // Ícone para ativar
      el.classList.remove('deactivate-action', 'text-danger', 'ti-power');
      el.classList.add('activate-action', 'text-success', 'ti-check');
      setTooltip(el, 'Ativar');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', async function (e) {
      const el = e.target.closest('.deactivate-action, .activate-action');
      if (!el) return;
      e.preventDefault();

      const id = el.dataset.appId;
      if (!id) return;
      const row = el.closest('tr');

      const isActivating = el.classList.contains('activate-action');
      const action = isActivating ? 'activate' : 'deactivate';

      const { ok, data } = await requestToggle(id, action);
      if (!ok) {
        alert(data.message || 'Falha ao processar ação');
        return;
      }

      const nowActive = action === 'activate'; // estado após ação bem-sucedida
      updateBadge(row, nowActive);
      updateIcon(el, nowActive);
    });

    // Abrir modal de Novo App e carregar formulário via AJAX
    document.addEventListener('click', async function (e) {
      const addAppBtn = e.target.closest('.add-app-action');
      if (!addAppBtn) return;
      // Modal abre via data-bs-target automaticamente; apenas carregamos o formulário
      const container = document.getElementById('app-create-form-container');
      if (container) {
        container.innerHTML = `
          <div class="py-4 d-flex align-items-center justify-content-center text-muted">
            <div class="spinner-border spinner-border-sm me-2" role="status">
              <span class="visually-hidden">Carregando formulário...</span>
            </div>
            Carregando formulário...
          </div>
        `;
      }

      const fullBase = buildBase(); // e.g., http://localhost:8080/admin/apps/
      const url = `${fullBase}new`;
      try {
        const res = await fetch(url, {
          method: 'GET',
          headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await res.text();
        if (container) container.innerHTML = html;
      } catch (err) {
        if (window.Swal) Swal.fire('Erro', 'Não foi possível carregar o formulário.', 'error');
        else alert('Não foi possível carregar o formulário.');
        console.error('Erro ao carregar formulário de novo app:', err);
      }
    });

    // Cancelar formulário de Novo App
    document.addEventListener('click', function (e) {
      const cancelBtn = e.target.closest('#cancel-app-form');
      if (!cancelBtn) return;
      e.preventDefault();
      const modalEl = document.getElementById('new-app-modal');
      if (modalEl) {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.hide();
      }
    });

    // Submeter criação de App via AJAX
    document.addEventListener('submit', async function (e) {
      const form = e.target.closest('#app-create-form');
      if (!form) return;
      e.preventDefault();

      const action = form.getAttribute('action');
      const formData = new FormData(form);
      try {
        const res = await fetch(action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (res.ok && data && data.success) {
          if (window.Swal) Swal.fire('Sucesso', data.message || 'App criado com sucesso', 'success');
          const modalEl = document.getElementById('new-app-modal');
          if (modalEl) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.hide();
          }
          // Recarregar a página para refletir o novo app na tabela
          window.location.reload();
        } else {
          const msg = (data && data.message) || 'Falha ao criar App';
          const errs = (data && data.errors) ? Object.values(data.errors).join('\n') : '';
          if (window.Swal) Swal.fire('Erro', `${msg}${errs ? '\n' + errs : ''}`, 'error');
          else alert(`${msg}${errs ? '\n' + errs : ''}`);
        }
      } catch (err) {
        if (window.Swal) Swal.fire('Erro', 'Erro de rede ao criar App', 'error');
        else alert('Erro de rede ao criar App');
        console.error('Erro ao enviar formulário de novo app:', err);
      }
    });

    // Abrir modal de Editar App e carregar formulário via AJAX
    document.addEventListener('click', async function (e) {
      const editAppBtn = e.target.closest('.edit-app-action');
      if (!editAppBtn) return;
      const appId = editAppBtn.dataset.appId;
      const container = document.getElementById('app-edit-form-container');
      if (container) {
        container.innerHTML = `
          <div class="py-4 d-flex align-items-center justify-content-center text-muted">
            <div class="spinner-border spinner-border-sm me-2" role="status">
              <span class="visually-hidden">Carregando formulário...</span>
            </div>
            Carregando formulário...
          </div>
        `;
      }
      const fullBase = buildBase(); // e.g., http://localhost:8080/admin/apps/
      const url = `${fullBase}${appId}/edit`;
      try {
        const res = await fetch(url, {
          method: 'GET',
          headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await res.text();
        if (container) container.innerHTML = html;
      } catch (err) {
        if (window.Swal) Swal.fire('Erro', 'Não foi possível carregar o formulário de edição.', 'error');
        else alert('Não foi possível carregar o formulário de edição.');
        console.error('Erro ao carregar formulário de edição:', err);
      }
    });

    // Cancelar formulário de Editar App
    document.addEventListener('click', function (e) {
      const cancelBtn = e.target.closest('#cancel-app-edit-form');
      if (!cancelBtn) return;
      e.preventDefault();
      const modalEl = document.getElementById('edit-app-modal');
      if (modalEl) {
        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.hide();
      }
    });

    // Submeter edição de App via AJAX
    document.addEventListener('submit', async function (e) {
      const form = e.target.closest('#app-edit-form');
      if (!form) return;
      e.preventDefault();
      const action = form.getAttribute('action');
      const formData = new FormData(form);
      try {
        const res = await fetch(action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (res.ok && data && data.success) {
          if (window.Swal) Swal.fire('Sucesso', data.message || 'App atualizado com sucesso', 'success');
          const modalEl = document.getElementById('edit-app-modal');
          if (modalEl) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.hide();
          }
          // Atualizar a linha da tabela ou recarregar
          window.location.reload();
        } else {
          const msg = (data && data.message) || 'Falha ao atualizar App';
          const errs = (data && data.errors) ? Object.values(data.errors).join('\n') : '';
          if (window.Swal) Swal.fire('Erro', `${msg}${errs ? '\n' + errs : ''}`, 'error');
          else alert(`${msg}${errs ? '\n' + errs : ''}`);
        }
      } catch (err) {
        if (window.Swal) Swal.fire('Erro', 'Erro de rede ao atualizar App', 'error');
        else alert('Erro de rede ao atualizar App');
        console.error('Erro ao enviar formulário de edição de app:', err);
      }
    });

    // Abrir modal de assinaturas e carregar planos via AJAX
    document.addEventListener('click', async function (e) {
      const subIcon = e.target.closest('.subscriptions-action');
      if (!subIcon) return;
      e.preventDefault();

      const appId = subIcon.dataset.appId;
      const appName = subIcon.dataset.appName || '';
      if (!appId) return;

      // Atualiza o título do modal com o nome do app
      const modalTitle = document.getElementById('myLargeModalLabel');
      if (modalTitle) {
        modalTitle.innerHTML = `${appName} <small>(Assinaturas)</small>`;
      }

      // Armazena appId no modal para uso subsequente
      const modalEl = document.getElementById('bs-example-modal-xlg');
      if (modalEl) {
        modalEl.dataset.appId = appId;
      }

      // Garantir que a área de tabela esteja visível e o formulário oculto
      const tableContainer = document.getElementById('plans-table-container');
      const formContainer = document.getElementById('plan-form-container');
      if (tableContainer) tableContainer.classList.remove('d-none');
      if (formContainer) formContainer.classList.add('d-none');

      // Mostra loading no tbody
      const tbody = document.getElementById('plans-tbody');
      if (tbody) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6">
              <div class="py-4 d-flex align-items-center justify-content-center text-muted">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                  <span class="visually-hidden">Carregando...</span>
                </div>
                Carregando planos...
              </div>
            </td>
          </tr>
        `;
      }

      // Busca os planos via AJAX (HTML parcial)
      const fullBase = buildBase();
      const url = `${fullBase}${appId}/plans`;
      try {
        const res = await fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const html = await res.text();
        if (tbody) {
          tbody.innerHTML = html;
        }
      } catch (err) {
        if (tbody) {
          tbody.innerHTML = `
            <tr>
              <td colspan="6" class="text-center text-danger">Erro ao carregar planos. Tente novamente.</td>
            </tr>
          `;
        }
        console.error('Erro ao buscar planos:', err);
      }
    });

    // Carregar formulário de novo plano ao clicar no botão do modal
    document.addEventListener('click', async function (e) {
      const addBtn = e.target.closest('.add-plan-action');
      if (!addBtn) return;
      e.preventDefault();

      const modalEl = document.getElementById('bs-example-modal-xlg');
      const appId = modalEl ? modalEl.dataset.appId : null;
      if (!appId) return;

      const tableContainer = document.getElementById('plans-table-container');
      const formContainer = document.getElementById('plan-form-container');
      if (formContainer) {
        formContainer.innerHTML = `
          <div class="py-4 d-flex align-items-center justify-content-center text-muted">
            <div class="spinner-border spinner-border-sm me-2" role="status">
              <span class="visually-hidden">Carregando formulário...</span>
            </div>
            Carregando formulário...
          </div>
        `;
      }

      const fullBase = buildBase();
      const url = `${fullBase}${appId}/plans/new`;
      try {
        const res = await fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const html = await res.text();
        if (formContainer) {
          formContainer.innerHTML = html;
          formContainer.classList.remove('d-none');
          // Inicializar máscara de preço e integração com moeda
          try {
            initPlanForm(formContainer);
          } catch (_) {}
        }
        if (tableContainer) tableContainer.classList.add('d-none');
      } catch (err) {
        if (window.Swal) {
          Swal.fire('Erro', 'Não foi possível carregar o formulário.', 'error');
        } else {
          alert('Erro ao carregar formulário');
        }
        console.error('Erro ao carregar formulário de plano:', err);
      }
    });

    // Abrir formulário de edição ao clicar no ícone de lápis na lista
    document.addEventListener('click', async function (e) {
      const editIcon = e.target.closest('.edit-plan-action');
      if (!editIcon) return;
      e.preventDefault();

      const planId = editIcon.dataset.planId;
      const modalEl = document.getElementById('bs-example-modal-xlg');
      const appId = modalEl ? modalEl.dataset.appId : null;
      if (!appId || !planId) return;

      const tableContainer = document.getElementById('plans-table-container');
      const formContainer = document.getElementById('plan-form-container');
      if (formContainer) {
        formContainer.innerHTML = `
          <div class="py-4 d-flex align-items-center justify-content-center text-muted">
            <div class="spinner-border spinner-border-sm me-2" role="status">
              <span class="visualmente-hidden">Carregando formulário...</span>
            </div>
            Carregando formulário...
          </div>
        `;
        formContainer.classList.remove('d-none');
      }
      if (tableContainer) tableContainer.classList.add('d-none');

      const fullBase = buildBase();
      const url = `${fullBase}${appId}/plans/${planId}/edit`;
      try {
        const res = await fetch(url, {
          method: 'GET',
          headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await res.text();
        if (formContainer) {
          formContainer.innerHTML = html;
          try { initPlanForm(formContainer); } catch (_) {}
        }
      } catch (err) {
        if (window.Swal) {
          Swal.fire('Erro', 'Não foi possível carregar o formulário de edição.', 'error');
        } else {
          alert('Erro ao carregar formulário de edição');
        }
        console.error('Erro ao carregar formulário de edição:', err);
        // Voltar para a tabela em caso de erro
        if (formContainer) formContainer.classList.add('d-none');
        if (tableContainer) tableContainer.classList.remove('d-none');
      }
    });

    // Alternar status do plano (desativar/ativar) ao clicar no ícone
    function setTooltip(element, text) {
      element.setAttribute('title', text);
      element.setAttribute('data-bs-original-title', text);
      if (window.bootstrap && bootstrap.Tooltip) {
        const instance = bootstrap.Tooltip.getInstance(element);
        if (instance) {
          try { instance.setContent({ '.tooltip-inner': text }); }
          catch (_) { instance.dispose(); new bootstrap.Tooltip(element); }
        }
      }
    }

    function updatePlanBadge(row, isActive) {
      const td = row && row.querySelector('td:nth-child(5) .badge');
      if (!td) return;
      if (isActive) {
        td.textContent = 'Ativo';
        td.classList.remove('bg-danger-subtle', 'text-danger');
        td.classList.add('bg-success-subtle', 'text-success');
      } else {
        td.textContent = 'Inativo';
        td.classList.remove('bg-success-subtle', 'text-success');
        td.classList.add('bg-danger-subtle', 'text-danger');
      }
    }

    function updatePlanIcon(el, isActive) {
      if (isActive) {
        el.classList.remove('activate-plan-action', 'text-success', 'ti-check');
        el.classList.add('delete-plan-action', 'text-danger', 'ti-trash');
        setTooltip(el, 'Desativar plano');
      } else {
        el.classList.remove('delete-plan-action', 'text-danger', 'ti-trash');
        el.classList.add('activate-plan-action', 'text-success', 'ti-check');
        setTooltip(el, 'Ativar plano');
      }
    }

    async function requestPlanToggle(appId, planId, action) {
      const fullBase = buildBase();
      try {
        let res = await fetch(`${fullBase}${appId}/plans/${planId}/${action}`, {
          method: 'POST',
          headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) {
          res = await fetch(`${fullBase}${appId}/plans/${planId}/${action}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
          });
        }
        const data = await res.json().catch(() => ({ success: false }));
        return { ok: res.ok && data.success, data };
      } catch (e) {
        return { ok: false, data: { success: false, message: 'Erro de rede' } };
      }
    }

    document.addEventListener('click', async function (e) {
      const el = e.target.closest('.delete-plan-action, .activate-plan-action');
      if (!el) return;
      e.preventDefault();

      const planId = el.dataset.planId;
      const modalEl = document.getElementById('bs-example-modal-xlg');
      const appId = modalEl ? modalEl.dataset.appId : null;
      if (!appId || !planId) return;

      const action = el.classList.contains('delete-plan-action') ? 'deactivate' : 'activate';
      const { ok, data } = await requestPlanToggle(appId, planId, action);
      if (!ok) {
        if (window.Swal) {
          Swal.fire('Erro', data.message || 'Falha ao alternar status do plano', 'error');
        } else {
          alert(data.message || 'Falha ao alternar status do plano');
        }
        return;
      }

      const row = el.closest('tr');
      const nowActive = action === 'activate';
      updatePlanBadge(row, nowActive);
      updatePlanIcon(el, nowActive);
    });

    // Cancelar o formulário e voltar para a lista de planos
    document.addEventListener('click', function (e) {
      const cancelBtn = e.target.closest('#cancel-plan-form');
      if (!cancelBtn) return;
      e.preventDefault();

      const tableContainer = document.getElementById('plans-table-container');
      const formContainer = document.getElementById('plan-form-container');
      if (formContainer) formContainer.classList.add('d-none');
      if (tableContainer) tableContainer.classList.remove('d-none');
    });

    // Submeter o formulário de criação de plano via AJAX
    document.addEventListener('submit', async function (e) {
      const form = e.target.closest('#plan-create-form');
      if (!form) return;
      e.preventDefault();

      const action = form.getAttribute('action');
      const formData = new FormData(form);

      try {
        const res = await fetch(action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const data = await res.json();
        if (res.ok && data && data.success) {
          if (window.Swal) {
            Swal.fire('Sucesso', data.message || 'Plano criado com sucesso', 'success');
          }
          // Voltar para a lista e recarregar planos
          const tableContainer = document.getElementById('plans-table-container');
          const formContainer = document.getElementById('plan-form-container');
          if (formContainer) formContainer.classList.add('d-none');
          if (tableContainer) tableContainer.classList.remove('d-none');

          // Recarregar lista de planos
          const modalEl = document.getElementById('bs-example-modal-xlg');
          const appId = modalEl ? modalEl.dataset.appId : null;
          const tbody = document.getElementById('plans-tbody');
          if (appId && tbody) {
            tbody.innerHTML = `
              <tr>
                <td colspan="6">
                  <div class="py-4 d-flex align-items-center justify-content-center text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                      <span class="visually-hidden">Carregando...</span>
                    </div>
                    Atualizando planos...
                  </div>
                </td>
              </tr>
            `;
            const fullBase = buildBase();
            const url = `${fullBase}${appId}/plans`;
            const res2 = await fetch(url, {
              method: 'GET',
              headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await res2.text();
            tbody.innerHTML = html;
          }
        } else {
          const msg = (data && data.message) || 'Falha ao criar plano';
          if (window.Swal) {
            const errors = (data && data.errors) ? Object.values(data.errors).join('\n') : '';
            Swal.fire('Erro', `${msg}${errors ? '\n' + errors : ''}`, 'error');
          } else {
            alert(msg);
          }
        }
      } catch (err) {
        if (window.Swal) {
          Swal.fire('Erro', 'Erro de rede ao criar plano', 'error');
        } else {
          alert('Erro de rede ao criar plano');
        }
        console.error('Erro ao enviar formulário de plano:', err);
      }
    });

    // Submeter o formulário de edição de plano via AJAX
    document.addEventListener('submit', async function (e) {
      const form = e.target.closest('#plan-edit-form');
      if (!form) return;
      e.preventDefault();

      const action = form.getAttribute('action');
      const formData = new FormData(form);

      try {
        const res = await fetch(action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const data = await res.json();
        if (res.ok && data && data.success) {
          if (window.Swal) {
            Swal.fire('Sucesso', data.message || 'Plano atualizado com sucesso', 'success');
          }
          // Voltar para a lista e recarregar planos
          const tableContainer = document.getElementById('plans-table-container');
          const formContainer = document.getElementById('plan-form-container');
          if (formContainer) formContainer.classList.add('d-none');
          if (tableContainer) tableContainer.classList.remove('d-none');

          const modalEl = document.getElementById('bs-example-modal-xlg');
          const appId = modalEl ? modalEl.dataset.appId : null;
          const tbody = document.getElementById('plans-tbody');
          if (appId && tbody) {
            tbody.innerHTML = `
              <tr>
                <td colspan="6">
                  <div class="py-4 d-flex align-items-center justify-content-center text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                      <span class="visually-hidden">Carregando...</span>
                    </div>
                    Atualizando planos...
                  </div>
                </td>
              </tr>
            `;
            const fullBase = buildBase();
            const url = `${fullBase}${appId}/plans`;
            const res2 = await fetch(url, {
              method: 'GET',
              headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await res2.text();
            tbody.innerHTML = html;
          }
        } else {
          const msg = (data && data.message) || 'Falha ao atualizar plano';
          if (window.Swal) {
            const errors = (data && data.errors) ? Object.values(data.errors).join('\n') : '';
            Swal.fire('Erro', `${msg}${errors ? '\n' + errors : ''}`, 'error');
          } else {
            alert(msg);
          }
        }
      } catch (err) {
        if (window.Swal) {
          Swal.fire('Erro', 'Erro de rede ao atualizar plano', 'error');
        } else {
          alert('Erro de rede ao atualizar plano');
        }
        console.error('Erro ao enviar formulário de edição:', err);
      }
    });
  });

  // Inicializa comportamentos do formulário de plano (máscara de preço)
  function initPlanForm(container) {
    const priceInput = container.querySelector('#plan-price');
    const currencySelect = container.querySelector('#plan-currency');
    const nameInput = container.querySelector('#plan-name');
    const billingSelect = container.querySelector('#plan-billing');
    const stripeBtn = container.querySelector('.generate-stripe-price-action');
    const stripeInput = container.querySelector('#stripe-price-id');
    if (!priceInput) return;

    function getDecimalSeparator() {
      const curr = (currencySelect && currencySelect.value) || 'BRL';
      return curr === 'USD' ? '.' : ',';
    }
    // Sanitiza conforme digitação, sem forçar decimais durante input
    function sanitizeOnInput() {
      const sep = getDecimalSeparator();
      let v = String(priceInput.value || '');
      // unificar separador para o atual
      if (sep === ',') v = v.replace(/\./g, ','); else v = v.replace(/,/g, '.');
      // remover caracteres inválidos
      v = v.replace(new RegExp(`[^0-9\\${sep}]`, 'g'), '');
      // manter apenas um separador
      const firstSepIdx = v.indexOf(sep);
      if (firstSepIdx !== -1) {
        const whole = v.slice(0, firstSepIdx).replace(/\D/g, '');
        let dec = v.slice(firstSepIdx + 1).replace(/\D/g, '');
        // remover separadores adicionais na parte decimal e limitar a 2
        dec = dec.replace(new RegExp(`\\${sep}`, 'g'), '').slice(0, 2);
        v = dec.length ? `${whole}${sep}${dec}` : `${whole}${sep}`;
      } else {
        v = v.replace(/\D/g, '');
      }
      priceInput.value = v;
    }

    // No blur, padronizar para 2 casas decimais
    function formatOnBlur() {
      const sep = getDecimalSeparator();
      let v = String(priceInput.value || '').trim();
      if (!v) return;
      if (sep === ',') v = v.replace(/\./g, ','); else v = v.replace(/,/g, '.');
      const idx = v.indexOf(sep);
      if (idx === -1) {
        priceInput.value = `${v}${sep}00`;
        return;
      }
      const whole = v.slice(0, idx).replace(/\D/g, '') || '0';
      let dec = v.slice(idx + 1).replace(/\D/g, '');
      if (dec.length === 0) dec = '00';
      else if (dec.length === 1) dec = dec + '0';
      else if (dec.length > 2) dec = dec.slice(0, 2);
      priceInput.value = `${whole}${sep}${dec}`;
    }

    function updatePlaceholderAndSeparator() {
      const sep = getDecimalSeparator();
      priceInput.placeholder = sep === ',' ? 'Ex: 49,90' : 'Ex: 49.90';
      // converter separador do valor atual sem alterar dígitos
      let v = String(priceInput.value || '');
      if (sep === ',') v = v.replace(/\./g, ','); else v = v.replace(/,/g, '.');
      priceInput.value = v;
    }

    priceInput.addEventListener('input', sanitizeOnInput);
    priceInput.addEventListener('blur', formatOnBlur);
    if (currencySelect) {
      currencySelect.addEventListener('change', updatePlaceholderAndSeparator);
    }
    updatePlaceholderAndSeparator();

    // Geração de Product & Price na Stripe e preenchimento automático do Price ID
    async function generateStripePrice() {
      const modalEl = document.getElementById('bs-example-modal-xlg');
      const appId = modalEl ? modalEl.dataset.appId : null;
      if (!appId) return;
      const name = nameInput ? nameInput.value.trim() : '';
      const billing = billingSelect ? billingSelect.value : '';
      const currency = currencySelect ? currencySelect.value : 'BRL';
      let priceVal = priceInput.value || '';
      // Converter separador para ponto e garantir 2 casas
      priceVal = priceVal.replace(/,/g, '.');
      if (/^\d+(\.\d{1,2})?$/.test(priceVal) === false) {
        // tentar forçar duas casas se só inteiro
        if (/^\d+$/.test(priceVal)) priceVal = `${priceVal}.00`;
      }

      const errors = [];
      if (!name) errors.push('Nome');
      if (!['monthly', 'quarterly', 'yearly', 'one_time'].includes(billing)) errors.push('Cobrança');
      if (!['BRL', 'USD'].includes(currency)) errors.push('Moeda');
      if (!priceVal || isNaN(Number(priceVal))) errors.push('Preço');
      if (errors.length) {
        if (window.Swal) {
          Swal.fire('Campos obrigatórios', `Preencha: ${errors.join(', ')}`, 'warning');
        } else {
          alert(`Preencha: ${errors.join(', ')}`);
        }
        return;
      }

      const fullBase = buildBase();
      const url = `${fullBase}${appId}/stripe/price`;
      try {
        const formData = new FormData();
        formData.append('name', name);
        formData.append('billing_interval', billing);
        formData.append('currency', currency);
        formData.append('price_amount', priceVal);
        const res = await fetch(url, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (res.ok && data && data.success) {
          if (stripeInput) stripeInput.value = data.price_id || '';
          if (window.Swal) {
            Swal.fire('Gerado', 'Stripe Price criado e preenchido.', 'success');
          }
        } else {
          const msg = (data && data.message) || 'Falha ao criar preço na Stripe';
          if (window.Swal) Swal.fire('Erro', msg, 'error'); else alert(msg);
        }
      } catch (err) {
        if (window.Swal) Swal.fire('Erro', 'Erro de rede ao criar preço na Stripe', 'error'); else alert('Erro de rede ao criar preço na Stripe');
        console.error('Erro ao criar preço na Stripe:', err);
      }
    }

    if (stripeBtn) {
      stripeBtn.addEventListener('click', generateStripePrice);
    }
  }
})();