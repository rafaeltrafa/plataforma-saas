document.addEventListener('DOMContentLoaded', function () {
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

  document.querySelectorAll('.js-sub-action').forEach(function (link) {
    if (link.dataset.bound === '1') return;
    link.dataset.bound = '1';
    link.addEventListener('click', function (e) {
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
        }).then(function (result) {
          if (result.isConfirmed) proceed();
        });
      } else {
        if (confirm(message)) proceed();
      }
    });
  });
});