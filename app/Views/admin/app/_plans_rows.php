<?php

/** @var array $plans */ ?>
<?php if (!empty($plans)) : ?>
    <?php foreach ($plans as $plan) : ?>
        <tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="ms-0">
                        <h6 class="fs-4 fw-semibold mb-0"> <span class="badge bg-light-subtle me-2">Cod. <?php echo (int)($plan['id'] ?? 0); ?></span><i class="ti ti-package me-2 text-primary"></i><?php echo esc($plan['name'] ?? '—'); ?></h6>
                    </div>
                </div>
            </td>
            <td>
                <?php
                $intervalKey = $plan['billing_interval'] ?? null;
                $intervalMap = [
                    'monthly'   => 'Mensal',
                    'month' => 'Mensal',
                    'quarterly' => 'Trimestral',
                    'yearly'    => 'Anual',
                    'one_time'  => 'Pagamento Único',
                ];
                $intervalLabel = $intervalKey ? ($intervalMap[$intervalKey] ?? $intervalKey) : '—';
                ?>
                <span class="badge bg-secondary-subtle text-secondary"><?php echo esc($intervalLabel); ?></span>
            </td>
            <td>
                <?php
                $amountVal = $plan['price_amount'] ?? null;
                $currency  = strtoupper($plan['currency'] ?? 'BRL');
                $symbolMap = [
                    'BRL' => 'R$',
                    'USD' => 'U$',
                    'EUR' => '€',
                ];
                $symbol    = $symbolMap[$currency] ?? $currency;
                $formatted = '—';
                if ($amountVal !== null && $amountVal !== '') {
                    $formatted = number_format((float) $amountVal, 2, ',', '.');
                }
                ?>
                <span class="fw-normal"><?php echo esc($symbol) . ' ' . esc($formatted); ?></span>
            </td>
            <td><span class="fw-normal"><?php echo esc($plan['stripe_price_id'] ?? '—'); ?></span></td>
            <td>
                <?php
                $badgeText = '—';
                $badgeClasses = 'bg-secondary-subtle text-secondary';
                if (isset($plan['is_active'])) {
                    if (!empty($plan['is_active'])) {
                        $badgeText = 'Ativo';
                        $badgeClasses = 'bg-success-subtle text-success';
                    } else {
                        $badgeText = 'Inativo';
                        $badgeClasses = 'bg-danger-subtle text-danger';
                    }
                } elseif (!empty($plan['status'])) {
                    $status = strtolower((string) $plan['status']);
                    if (in_array($status, ['ativo', 'active'])) {
                        $badgeText = 'Ativo';
                        $badgeClasses = 'bg-success-subtle text-success';
                    } elseif (in_array($status, ['inativo', 'inactive'])) {
                        $badgeText = 'Inativo';
                        $badgeClasses = 'bg-danger-subtle text-danger';
                    } elseif (in_array($status, ['em_revisao', 'review'])) {
                        $badgeText = 'Em revisão';
                        $badgeClasses = 'bg-warning-subtle text-warning';
                    }
                }
                ?>
                <span class="badge <?php echo $badgeClasses; ?>"><?php echo esc($badgeText); ?></span>
            </td>
            <td>
                <div class="action-btn d-flex gap-2">
                    <i class="ti ti-pencil fs-5 text-primary cursor-pointer edit-plan-action" data-bs-toggle="tooltip" title="Alterar plano" data-plan-id="<?php echo (int)($plan['id'] ?? 0); ?>"></i>
                    <?php $isActive = !empty($plan['is_active']); ?>
                    <?php if ($isActive): ?>
                        <i class="ti ti-trash fs-5 text-danger cursor-pointer delete-plan-action" data-bs-toggle="tooltip" title="Desativar plano" data-plan-id="<?php echo (int)($plan['id'] ?? 0); ?>"></i>
                    <?php else: ?>
                        <i class="ti ti-check fs-5 text-success cursor-pointer activate-plan-action" data-bs-toggle="tooltip" title="Ativar plano" data-plan-id="<?php echo (int)($plan['id'] ?? 0); ?>"></i>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else : ?>
    <tr>
        <td colspan="6" class="text-center text-muted">Nenhum plano encontrado para este app.</td>
    </tr>
<?php endif; ?>