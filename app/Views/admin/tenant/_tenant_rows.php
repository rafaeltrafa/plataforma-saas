<?php if (isset($tenants) && is_array($tenants) && count($tenants)): ?>
    <?php foreach ($tenants as $t): ?>
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="fs-4 fw-semibold mb-0"><?= esc($t['name'] ?? '—') ?></h6>
                    </div>
                </div>
            </td>
            <td><?= esc($t['contact_email'] ?? '—') ?></td>
            <td><?= esc($t['locale'] ?? '—') ?></td>
            <td>
                <?php $isActive = (int)($t['is_active'] ?? 0) === 1; ?>
                <span class="badge <?= $isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                    <i class="ti <?= $isActive ? 'ti-circle-check' : 'ti-circle-x' ?> me-1"></i>
                    <?= $isActive ? 'Ativo' : 'Inativo' ?>
                </span>
            </td>
            <td><?= esc($t['app_name'] ?? '—') ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="5" class="text-center text-muted">
            <div class="py-3">Nenhum registro encontrado</div>
        </td>
    </tr>
<?php endif; ?>