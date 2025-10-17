<?php echo $this->extend('admin/masterView') ?>

<?php echo $this->section('content') ?>


<div class="body-wrapper">
    <div class="container-fluid">

        <!-- Renderiza o componente pageHeader.php com título "Gerenciar Notícias" -->
        <!-- Os dados são enviados via variável $pageHeader definida no controller -->
        <?php echo $this->include('Admin/components/pageHeader', $pageHeader); ?>

        <!-- Ações da página -->
        <div class="d-flex justify-content-center mb-3">
            <a href="javascript:void(0)" class="btn bg-primary-subtle text-primary align-items-center gap-2 add-app-action" data-bs-toggle="modal" data-bs-target="#new-app-modal">
                <i class="ti ti-plus"></i> Novo App
            </a>
        </div>

        <!-- FORMULÁRIO DE FILTROS -->
        <table class="table text-nowrap mb-0 align-middle table-hover">
            <thead class="text-dark fs-4">
                <tr>

                    <th>
                        <h6 class="fs-4 fw-semibold mb-0">App</h6>
                    </th>


                    <th>
                        <h6 class="fs-4 fw-semibold mb-0">Status</h6>
                    </th>
                    <th>
                        <h6 class="fs-4 fw-semibold mb-0">Acoes</h6>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($apps)) : ?>
                    <?php foreach ($apps as $app) : ?>
                        <tr>

                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <h6 class="fs-4 fw-semibold mb-0">
                                            <span class="badge bg-light-subtle me-2">Cod. <?php echo (int)($app['id'] ?? 0); ?></span>
                                            <i class="ti ti-apps me-2 text-primary"></i>
                                            <?php echo esc($app['name']); ?>

                                        </h6>


                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($app['is_active'])) : ?>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                <?php else : ?>
                                    <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-btn d-flex gap-2">
                                    <a href="javascript:void(0)" class="text-primary edit-app-action" data-bs-toggle="modal" data-bs-target="#edit-app-modal" data-bs-toggle="tooltip" title="Editar" data-app-id="<?php echo (int)($app['id'] ?? 0); ?>" data-app-name="<?php echo esc($app['name'] ?? ''); ?>">
                                        <i class="ti ti-pencil fs-5"></i>
                                    </a>
                                    <i class="ti ti-receipt fs-5 text-warning cursor-pointer subscriptions-action" title="Assinaturas" data-app-id="<?php echo (int)($app['id'] ?? 0); ?>" data-app-name="<?php echo esc($app['name'] ?? ''); ?>" data-bs-toggle="modal" data-bs-target="#bs-example-modal-xlg"></i>
                                    <?php if (!empty($app['is_active'])) : ?>
                                        <i class="ti ti-power fs-5 text-danger cursor-pointer deactivate-action" data-app-id="<?php echo (int)($app['id'] ?? 0); ?>" data-bs-toggle="tooltip" title="Desativar"></i>
                                    <?php else : ?>
                                        <i class="ti ti-check fs-5 text-success cursor-pointer activate-action" data-app-id="<?php echo (int)($app['id'] ?? 0); ?>" data-bs-toggle="tooltip" title="Ativar"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted">Nenhum app cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- sample modal content -->
    <div class="modal fade" id="bs-example-modal-xlg" tabindex="-1" aria-labelledby="bs-example-modal-lg" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header modal-colored-header bg-primary text-white">
                    <h4 class="modal-title text-white" id="myLargeModalLabel">
                        Kids Stories <small>(Assinaturas)</small>
                    </h4>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="plans-table-container">
                        <!-- Tabela de Planos (conteúdo carregado via AJAX) -->
                        <table class="table text-nowrap mb-0 align-middle">
                            <thead class="text-dark fs-4">
                                <tr>
                                    <th>
                                        <h6 class="fs-4 fw-semibold mb-0">Nome do Plano</h6>
                                    </th>
                                    <th>
                                        <h6 class="fs-4 fw-semibold mb-0">Tipo de Cobrança</h6>
                                    </th>
                                    <th>
                                        <h6 class="fs-4 fw-semibold mb-0">Preço</h6>
                                    </th>
                                    <th>
                                        <h6 class="fs-4 fw-semibold mb-0">stripe_price_id</h6>
                                    </th>
                                    <th>
                                        <h6 class="fs-4 fw-semibold mb-0">Status</h6>
                                    </th>
                                    <th>
                                        <h6 class="fs-4 fw-semibold mb-0">Ações</h6>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="plans-tbody">
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
                            </tbody>
                        </table>

                        <!-- Botão de ação abaixo da tabela -->
                        <div class="mt-3 d-flex justify-content-center">
                            <a href="javascript:void(0)" class="btn bg-primary-subtle text-primary align-items-center gap-2 add-plan-action">
                                <i class="ti ti-plus"></i>
                                <span>Novo Plano</span>
                            </a>
                        </div>
                    </div>

                    <!-- Contêiner do formulário de novo plano (carregado via AJAX) -->
                    <div id="plan-form-container" class="d-none">
                        <div class="py-4 d-flex align-items-center justify-content-center text-muted">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Carregando formulário...</span>
                            </div>
                            Preparando formulário...
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-danger-subtle text-danger  waves-effect text-start" data-bs-dismiss="modal">
                        Fechar
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.modal -->



    <!-- Interações de Apps movidas para assets/js/custom/admin/apps.js -->

    <!-- Modal de Novo App -->
    <div class="modal fade" id="new-app-modal" tabindex="-1" aria-labelledby="new-app-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-colored-header bg-primary text-white">
                    <h4 class="modal-title text-white" id="new-app-modal-label">Novo App</h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="app-create-form-container">
                        <div class="py-4 d-flex align-items-center justify-content-center text-muted">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Carregando formulário...</span>
                            </div>
                            Preparando formulário...
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de Editar App -->
    <div class="modal fade" id="edit-app-modal" tabindex="-1" aria-labelledby="edit-app-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-colored-header bg-primary text-white">
                    <h4 class="modal-title text-white" id="edit-app-modal-label">Editar App</h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="app-edit-form-container">
                        <div class="py-4 d-flex align-items-center justify-content-center text-muted">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Carregando formulário...</span>
                            </div>
                            Preparando formulário...
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn bg-danger-subtle text-danger" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <?php echo $this->endSection() ?>