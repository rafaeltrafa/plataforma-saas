<?php echo $this->extend('admin/masterView') ?>

<?php echo $this->section('content') ?>

<div class="body-wrapper">
    <div class="container-fluid">

        <?php echo $this->include('Admin/components/pageHeader', $pageHeader); ?>

        <!-- FORMULÁRIO DE FILTROS (estático) -->
        <div class="card card-body py-3">
            <form>
                <div class="row align-items-center">
                    <!-- Nome -->
                    <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                            <label for="nome" class="form-label">Nome</label>
                            <div class="input-group">
                                <span class="input-group-text" id="addon-name"><i class="ti ti-user text-primary"></i></span>
                                <input type="text" class="form-control" id="nome" name="nome" placeholder="Digite o nome..." aria-describedby="addon-name">
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text" id="addon-email"><i class="ti ti-mail text-primary"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Digite o email..." aria-describedby="addon-email">
                            </div>
                        </div>
                    </div>

                    <!-- Aplicativo -->
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

                    <!-- Locale -->
                    <div class="col-md-6 col-lg-2">
                        <div class="form-group">
                            <label for="locale" class="form-label">Locale</label>
                            <div class="input-group">
                                <span class="input-group-text" id="addon-locale"><i class="ti ti-language text-primary"></i></span>
                                <input type="text" class="form-control" id="locale" name="locale" placeholder="Ex: pt_BR" aria-describedby="addon-locale">
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
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Botão Limpar -->
                    <div class="col-md-6 col-lg-2 d-flex align-items-end">
                        <button type="button" id="filters-reset" class="btn btn-outline-primary w-100">
                            <i class="ti ti-eraser fs-4 me-2"></i> Limpar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabela Tenants -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table text-nowrap mb-0 align-middle">
                        <thead>
                            <tr>
                                <th><h6 class="fs-4 fw-semibold mb-0">Nome</h6></th>
                                <th><h6 class="fs-4 fw-semibold mb-0">Email</h6></th>
                                <th><h6 class="fs-4 fw-semibold mb-0">Locale</h6></th>
                                <th><h6 class="fs-4 fw-semibold mb-0">Status</h6></th>
                                <th><h6 class="fs-4 fw-semibold mb-0">Aplicativo</h6></th>
                            </tr>
                        </thead>
                        <tbody id="tenants-rows">
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php echo $this->endSection() ?>