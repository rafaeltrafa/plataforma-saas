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
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Digite o email do tenant...">
                        </div>
                    </div>

                    <!-- Busca por Aplicativo -->
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label for="aplicativo" class="form-label">Aplicativo</label>
                            <select class="form-select" id="aplicativo" name="aplicativo">
                                <option value="">Selecione...</option>
                                <optgroup label="Aplicativos Disponíveis">
                                    <option value="kids-stories">Kids Stories</option>
                                    <option value="crm">CRM</option>
                                    <option value="erp">ERP</option>
                                    <option value="analytics">Analytics</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6 col-lg-3">
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos...</option>
                                <option value="ativo">Ativo</option>
                                <option value="suspenso">Suspenso</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Botão Buscar -->
                    <div class="col-md-6 col-lg-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center w-100">
                            <i class="ti ti-search fs-4 me-2"></i>
                            Buscar
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
                                <h6 class="fs-4 fw-semibold mb-0">APP</h6>
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
                    <tbody>
                        <!-- Entrada estática de exemplo -->
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">

                                    <div class="">
                                        <h6 class="fs-4 fw-semibold mb-0">Rafael Dias Trafaniuc</h6>
                                        <div class="mt-2">
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="ti ti-mail me-1"></i>rafael@studioalef.com.br
                                            </span>


                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                cus_TFog5lMXKSu9i5
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-apps me-2 text-secondary"></i> Kids Stories</span>

                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">MENSAL BASIC <br> </span>
                            </td>
                            <td>
                                <strong>R$ 35,00</strong>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success"><i class="ti ti-circle-check me-1"></i> Ativo</span>
                            </td>
                            <td>
                                <!-- Example single danger button -->
                                <div class="btn-group">
                                    <button class="btn bg-primary-subtle text-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        Ações
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">

                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)">Ativar Plano</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)">Cancelar Plano</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">

                                    <div class="">
                                        <h6 class="fs-4 fw-semibold mb-0">Rafael Dias Trafaniuc</h6>
                                        <div class="mt-2">
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="ti ti-mail me-1"></i>rafael@studioalef.com.br
                                            </span>


                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                cus_TFog5lMXKSu9i5
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-apps me-2 text-secondary"></i> Kids Stories</span>

                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">MENSAL BASIC <br> </span>
                            </td>
                            <td>
                                <strong>R$ 35,00</strong>
                            </td>
                            <td>
                                <span class="badge bg-danger-subtle text-danger"><i class="ti ti-circle-x me-1"></i> Cancelado</span>
                            </td>
                            <td>
                                <!-- Example single danger button -->
                                <div class="btn-group">
                                    <button class="btn bg-primary-subtle text-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        Ações
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">

                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)">Ativar Plano</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)">Cancelar Plano</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">

                                    <div class="">
                                        <h6 class="fs-4 fw-semibold mb-0">Rafael Dias Trafaniuc</h6>
                                        <div class="mt-2">
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="ti ti-mail me-1"></i>rafael@studioalef.com.br
                                            </span>


                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                cus_TFog5lMXKSu9i5
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-apps me-2 text-secondary"></i> Kids Stories</span>

                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">MENSAL BASIC <br> </span>
                            </td>
                            <td>
                                <strong>R$ 35,00</strong>
                            </td>
                            <td>
                                <span class="badge bg-danger-subtle text-danger"><i class="ti ti-hourglass me-1"></i> Vencido</span>
                            </td>
                            <td>
                                <!-- Example single danger button -->
                                <div class="btn-group">
                                    <button class="btn bg-primary-subtle text-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        Ações
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">

                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)">Ativar Plano</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)">Cancelar Plano</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <!-- Fim da linha estática de exemplo -->
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">

                                    <div class="">
                                        <h6 class="fs-4 fw-semibold mb-0">Rafael Dias Trafaniuc</h6>
                                        <div class="mt-2">
                                            <span class="badge bg-primary-subtle text-primary">
                                                <i class="ti ti-mail me-1"></i>rafael@studioalef.com.br
                                            </span>


                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                cus_TFog5lMXKSu9i5
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-apps me-2 text-secondary"></i> Kids Stories</span>

                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">MENSAL BASIC <br> </span>
                            </td>
                            <td>
                                <strong>R$ 35,00</strong>
                            </td>
                            <td>
                                <span class="badge bg-warning-subtle text-warning"><i class="ti ti-alert-circle me-1"></i> Incompleto</span>
                            </td>
                            <td>
                                <!-- Example single danger button -->
                                <div class="btn-group">
                                    <button class="btn bg-primary-subtle text-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        Ações
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">

                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)">Ativar Plano</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)">Cancelar Plano</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ÁREA DE AÇÕES E PAGINAÇÃO (estático) -->
            <div class="d-flex justify-content-end align-items-center mt-3">


                <!-- Paginação estática -->
                <nav aria-label="Paginação">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled"><span class="page-link">Anterior</span></li>
                        <li class="page-item active"><span class="page-link">1</span></li>
                        <li class="page-item"><a class="page-link" href="javascript:void(0)">2</a></li>
                        <li class="page-item"><a class="page-link" href="javascript:void(0)">Próxima</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php echo $this->endSection() ?>