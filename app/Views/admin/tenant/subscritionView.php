<?php echo $this->extend('admin/masterView') ?>

<?php echo $this->section('content') ?>

<div class="body-wrapper">
    <div class="container-fluid">

        <!-- Renderiza o componente pageHeader.php com título "Gerenciar Notícias" -->
        <!-- Os dados são enviados via variável $pageHeader definida no controller -->
        <?php echo $this->include('Admin/components/pageHeader', $pageHeader); ?>

        <!-- FORMULÁRIO DE FILTROS (estático) -->
        <div class="card card-body py-3">
            <form>
                <div class="row align-items-center">
                    <!-- Busca por Email -->
                    <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text" id="addon-email"><i class="ti ti-mail text-primary"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Digite o email do tenant..." aria-describedby="addon-email">
                            </div>
                        </div>
                    </div>

                    <!-- Busca por Aplicativo -->
                    <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                            <label for="aplicativo" class="form-label">Aplicativo</label>
                            <div class="input-group">
                                <span class="input-group-text" id="addon-app"><i class="ti ti-apps text-primary"></i></span>
                                <select class="form-select" id="aplicativo" name="aplicativo" aria-describedby="addon-app">
                                    <option value="">Selecione...</option>
                                    <optgroup label="Aplicativos Disponíveis">
                                        <?php if (!empty($apps)): ?>
                                            <?php foreach ($apps as $app): ?>
                                                <option value="<?= esc($app['slug']) ?>"><?= esc($app['name']) ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="" disabled>Nenhum aplicativo encontrado</option>
                                        <?php endif; ?>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Busca por Plano (dependente do aplicativo) -->
                    <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                            <label for="plano" class="form-label">Plano</label>
                            <div class="input-group">
                                <span class="input-group-text" id="addon-plan"><i class="ti ti-receipt text-primary"></i></span>
                                <select class="form-select" id="plano" name="plano" disabled aria-describedby="addon-plan">
                                    <option value="">Selecione o aplicativo primeiro...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6 col-lg-2">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <div class="input-group">
                                <span class="input-group-text" id="addon-status"><i class="ti ti-list-check text-primary"></i></span>
                                <select class="form-select" id="status" name="status" aria-describedby="addon-status">
                                    <option value="">Todos...</option>
                                    <?php if (!empty($statusOptions)): ?>
                                        <?php foreach ($statusOptions as $opt): ?>
                                            <option value="<?= esc($opt['value']) ?>"><?= esc($opt['label']) ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>Nenhum status disponível</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Botão Limpar -->
                    <div class="col-md-6 col-lg-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" id="filters-reset" class="btn bg-primary-subtle text-primary d-flex align-items-center justify-content-center w-100">
                            <i class="ti ti-eraser fs-4 me-2"></i>
                            Limpar
                        </button>
                    </div>


                </div>
            </form>
        </div>

        <!-- TABELA DE NOTÍCIAS (estático) -->
        <div class="card card-body py-3">
            <div class="table-responsive">
                <table id="noticias-table" class="table text-nowrap align-middle table-hover">
                    <thead class="text-dark fs-4">
                        <tr>
                            <th>
                                <h6 class="fs-4 fw-semibold mb-0">Nome</h6>
                            </th>
                            <th>
                                <h6 class="fs-4 fw-semibold mb-0">Stripe ID</h6>
                            </th>
                            <th>
                                <h6 class="fs-4 fw-semibold mb-0">App</h6>
                            </th>
                            <th>
                                <h6 class="fs-4 fw-semibold mb-0">Plano</h6>
                            </th>
                            <th>
                                <h6 class="fs-4 fw-semibold mb-0">Valor</h6>
                            </th>
                            <th>
                                <h6 class="fs-4 fw-semibold mb-0">Status</h6>
                            </th>
                            <th>
                                <h6 class="fs-4 fw-semibold mb-0">Ação</h6>
                            </th>
                        </tr>

                    </thead>
                    <tbody id="subscriptions-rows">
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
                    </tbody>
                </table>
            </div>

            <!-- ÁREA DE AÇÕES E PAGINAÇÃO (estático) -->
            <div class="d-flex justify-content-end align-items-center mt-3">


                <!-- Paginação estática -->
                <nav id="subscriptions-pager" aria-label="Paginação">
                    <?= $pager->links('default', 'default_full') ?>
                </nav>
            </div>
        </div>
    </div>
</div>



<?php echo $this->endSection() ?>