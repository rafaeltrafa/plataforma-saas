<?php if (isset($subscriptions) && is_array($subscriptions) && count($subscriptions)): ?>
    <?php foreach ($subscriptions as $sub): ?>
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="">
                        <h6 class="fs-4 fw-semibold mb-0"><?= esc($sub['tenant_name'] ?? '—') ?></h6>
                        <div class="mt-2">
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="ti ti-mail me-1"></i><?= esc($sub['tenant_email'] ?? '') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <?= esc($sub['stripe_customer_id'] ?? '—') ?>
            </td>
            <td>
                <div class="d-flex flex-wrap gap-1">
                    <?php $appName = $sub['app_name'] ?? null; ?>
                    <?php if ($appName): ?>
                        <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-apps me-2 text-secondary"></i> <?= esc($appName) ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-apps me-2 text-secondary"></i> —</span>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <span class="badge bg-primary-subtle text-primary"><?= esc($sub['plan_name'] ?? '—') ?></span>
            </td>
            <td>
                <?php
                $amount = $sub['unit_price'] ?? $sub['plan_price_amount'] ?? null;
                $currency = $sub['currency'] ?? $sub['plan_currency'] ?? null;
                $symbol = $currency === 'brl' ? 'R$' : ($currency === 'usd' ? 'US$' : ($currency === 'eur' ? '€' : ($currency ?? '')));
                $formatted =  number_format((float)$amount, 2, ',', '.');
                ?>
                <strong><?= $formatted ? $symbol . ' ' . $formatted : '—' ?></strong>
            </td>
            <td class="js-status-cell">
                <?php
                $status = $sub['status'] ?? null;
                $statusKey = $status ? strtolower($status) : '';
                $statusMap = [
                    'active' => ['class' => 'bg-success-subtle text-success', 'icon' => 'ti ti-circle-check', 'text' => 'Ativo'],
                    'past_due' => ['class' => 'bg-danger-subtle text-danger', 'icon' => 'ti ti-hourglass', 'text' => 'Vencido'],
                    'canceled' => ['class' => 'bg-danger-subtle text-danger', 'icon' => 'ti ti-circle-x', 'text' => 'Cancelado'],
                    'incomplete' => ['class' => 'bg-warning-subtle text-warning', 'icon' => 'ti ti-alert-circle', 'text' => 'Incompleto'],
                    'unpaid' => ['class' => 'bg-danger-subtle text-danger', 'icon' => 'ti ti-alert-circle', 'text' => 'Não pago'],
                    'trialing' => ['class' => 'bg-info-subtle text-info', 'icon' => 'ti ti-clock', 'text' => 'Em teste'],
                    'incomplete_expired' => ['class' => 'bg-danger-subtle text-danger', 'icon' => 'ti ti-hourglass', 'text' => 'Expirado'],
                    'paused' => ['class' => 'bg-secondary-subtle text-secondary', 'icon' => 'ti ti-player-pause', 'text' => 'Pausado'],
                ];
                $info = $statusMap[$statusKey] ?? ['class' => 'bg-secondary-subtle text-secondary', 'icon' => 'ti ti-dots', 'text' => '—'];
                ?>
                <span class="badge <?= esc($info['class']) ?>">
                    <i class="<?= esc($info['icon']) ?> me-1"></i> <?= esc($info['text']) ?>
                </span>
            </td>
            <td>
                <div class="btn-group">
                    <button class="btn bg-primary-subtle text-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        Status
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item js-sub-action" href="<?= base_url('admin/subscription/status/' . ($sub['subscription_id'] ?? 0)) ?>" data-status="paused" data-message="Pausar assinatura?" data-csrf-name="<?= csrf_token() ?>" data-csrf-value="<?= csrf_hash() ?>">Pausar</a></li>
                        <li><a class="dropdown-item js-sub-action" href="<?= base_url('admin/subscription/status/' . ($sub['subscription_id'] ?? 0)) ?>" data-status="canceled" data-message="Cancelar assinatura?" data-csrf-name="<?= csrf_token() ?>" data-csrf-value="<?= csrf_hash() ?>">Cancelar</a></li>
                        <li><a class="dropdown-item js-sub-action" href="<?= base_url('admin/subscription/status/' . ($sub['subscription_id'] ?? 0)) ?>" data-status="incomplete_expired" data-message="Marcar como expirada?" data-csrf-name="<?= csrf_token() ?>" data-csrf-value="<?= csrf_hash() ?>">Expirar</a></li>
                        <li><a class="dropdown-item js-sub-action" href="<?= base_url('admin/subscription/status/' . ($sub['subscription_id'] ?? 0)) ?>" data-status="unpaid" data-message="Marcar como não pago?" data-csrf-name="<?= csrf_token() ?>" data-csrf-value="<?= csrf_hash() ?>">Não Pago</a></li>
                        <li><a class="dropdown-item js-sub-action" href="<?= base_url('admin/subscription/status/' . ($sub['subscription_id'] ?? 0)) ?>" data-status="trialing" data-message="Iniciar período de teste por 5 dias?" data-csrf-name="<?= csrf_token() ?>" data-csrf-value="<?= csrf_hash() ?>">Em Teste</a></li>
                        <li><a class="dropdown-item js-sub-action" href="<?= base_url('admin/subscription/status/' . ($sub['subscription_id'] ?? 0)) ?>" data-status="past_due" data-message="Marcar como vencido (past due)?" data-csrf-name="<?= csrf_token() ?>" data-csrf-value="<?= csrf_hash() ?>">Vencido</a></li>
                    </ul>
                </div>
            </td>

        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="7" class="text-center text-muted">Nenhuma assinatura encontrada.</td>
    </tr>
<?php endif; ?>