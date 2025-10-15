<!-- Sidebar Start -->
<aside class="side-mini-panel with-vertical">
    <!-- ---------------------------------- -->
    <!-- Start Vertical Layout Sidebar -->
    <!-- ---------------------------------- -->
    <div class="iconbar">
        <div>
            <div class="mini-nav">
                <div class="brand-logo d-flex align-items-center justify-content-center">
                    <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                        <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-7"></iconify-icon>
                    </a>
                </div>
                <ul class="mini-nav-ul" data-simplebar>

                    <!-- ---------------------------------- -->
                    <!-- Grupo: Dashboard (mini-1) -->
                    <!-- ---------------------------------- -->
                    <li class="mini-nav-item" id="mini-1">
                        <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip" data-bs-placement="right" data-bs-title="Dashboard">
                            <iconify-icon icon="mdi:monitor-dashboard" class="fs-7"></iconify-icon>
                        </a>
                    </li>

                    <li>
                        <span class="sidebar-divider lg"></span>
                    </li>

                    <!-- ---------------------------------- -->
                    <!-- Grupo: Aplicativos (mini-3) -->
                    <!-- ---------------------------------- -->
                    <li class="mini-nav-item" id="mini-3">
                        <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip" data-bs-placement="right" data-bs-title="Aplicativos">
                            <iconify-icon icon="mdi:view-grid-outline" class="fs-7"></iconify-icon>
                        </a>
                    </li>
                    <!-- ---------------------------------- -->
                    <!-- Grupo: Clientes (mini-4) -->
                    <!-- ---------------------------------- -->
                    <li class="mini-nav-item" id="mini-4">
                        <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip" data-bs-placement="right" data-bs-title="Clientes">
                            <iconify-icon icon="mdi:account-multiple-outline" class="fs-7"></iconify-icon>
                        </a>
                    </li>

                    <li>
                        <span class="sidebar-divider lg"></span>
                    </li>
                    <!-- ---------------------------------- -->
                    <!-- Grupo: Financeiro (mini-5) -->
                    <!-- ---------------------------------- -->
                    <li class="mini-nav-item" id="mini-5">
                        <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip" data-bs-placement="right" data-bs-title="Financeiro">
                            <iconify-icon icon="mdi:cash-multiple" class="fs-7"></iconify-icon>
                        </a>
                    </li>


                    <li>
                        <span class="sidebar-divider lg"></span>
                    </li>
                    <!-- ---------------------------------- -->
                    <!-- Grupo: Usuários (mini-11) -->
                    <!-- ---------------------------------- -->
                    <li class="mini-nav-item" id="mini-11">
                        <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip" data-bs-placement="right" data-bs-title="Usuários">
                            <iconify-icon icon="solar:users-group-rounded-line-duotone" class="fs-7"></iconify-icon>
                        </a>
                    </li>

                    <li>
                        <span class="sidebar-divider lg"></span>
                    </li>
                    <!-- ---------------------------------- -->
                    <!-- Grupo: Sessão/Auth (mini-9) -->
                    <!-- ---------------------------------- -->
                    <li class="mini-nav-item" id="mini-9">
                        <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip" data-bs-placement="right" data-bs-title="Sair">
                            <iconify-icon icon="solar:exit-line-duotone" class="fs-7"></iconify-icon>
                        </a>
                    </li>

                    <!-- --------------------------------------------------------------------------------------------------------- -->
                    <!-- Multi level -->
                    <!-- --------------------------------------------------------------------------------------------------------- -->

                </ul>

            </div>
            <div class="sidebarmenu">
                <div class="brand-logo d-flex align-items-center nav-logo">
                    <a href="../main/index.html" class="text-nowrap logo-img">
                        <img src="<?php echo base_url() ?>assets/images/logos/logo.png" alt="Logo" />
                    </a>

                </div>
                <!-- ---------------------------------- -->
                <!-- Dashboard -->
                <!-- ---------------------------------- -->
                <nav class="sidebar-nav" id="menu-right-mini-1" data-simplebar>
                    <ul class="sidebar-menu" id="sidebarnav">
                        <li class="nav-small-cap">
                            <span class="hide-menu">Dashboard</span>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link <?php echo ($sidebarActive == 'dashboard') ? 'sidebar-link active' : ''; ?>" href="<?= site_url('admin/dashboard') ?>" id="get-url" aria-expanded="false">
                                <iconify-icon icon="mdi:monitor-dashboard"></iconify-icon>
                                <span class="hide-menu">Visão Geral</span>
                            </a>
                        </li>

                    </ul>
                </nav>

                <!-- ---------------------------------- -->
                <!-- Aplicativos -->
                <!-- ---------------------------------- -->
                <nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-3" data-simplebar>
                    <ul class="sidebar-menu" id="sidebarnav">
                        <li class="nav-small-cap">
                            <span class="hide-menu">Aplicativos</span>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link <?php echo ($sidebarActive == 'apps') ? 'sidebar-link active' : ''; ?>" href="<?= site_url('admin/apps') ?>" id="get-url" aria-expanded="false">
                                <iconify-icon icon="mdi:view-grid-outline"></iconify-icon>
                                <span class="hide-menu">Gerenciar</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- ---------------------------------- -->
                <!-- Clientes -->
                <!-- ---------------------------------- -->
                <nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-4" data-simplebar>
                    <ul class="sidebar-menu" id="sidebarnav">
                        <li class="nav-small-cap">
                            <span class="hide-menu">Clientes</span>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link <?php echo ($sidebarActive == 'clientes') ? 'sidebar-link active' : ''; ?>" href="<?= site_url('admin/clientes') ?>" id="get-url" aria-expanded="false">
                                <iconify-icon icon="mdi:account-multiple-outline"></iconify-icon>
                                <span class="hide-menu">Gerenciar</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- ---------------------------------- -->

                <!-- ---------------------------------- -->
                <!-- Financeiro -->
                <!-- ---------------------------------- -->
                <nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-5" data-simplebar>
                    <ul class="sidebar-menu" id="sidebarnav">
                        <li class="nav-small-cap">
                            <span class="hide-menu">Financeiro</span>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link <?php echo ($sidebarActive == 'financeiro') ? 'sidebar-link active' : ''; ?>" href="<?= site_url('admin/financeiro') ?>" id="get-url" aria-expanded="false">
                                <iconify-icon icon="mdi:cash-multiple"></iconify-icon>
                                <span class="hide-menu">Transações</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- ---------------------------------- -->
                <!-- Usuários -->
                <!-- ---------------------------------- -->
                <nav class="sidebar-nav scroll-sidebar" id="menu-right-mini-11" data-simplebar>
                    <ul class="sidebar-menu" id="sidebarnav">
                        <li class="nav-small-cap">
                            <span class="hide-menu">Usuários</span>
                        </li>
                        <!-- ---------------------------------- -->
                        <!-- Dashboard -->
                        <!-- ---------------------------------- -->
                        <li class="sidebar-item">
                            <a href="<?= site_url('admin/user/cadastro') ?>" class="sidebar-link">
                                <iconify-icon icon="solar:users-group-rounded-line-duotone"></iconify-icon>
                                <span class="hide-menu">Gerenciar Usuários</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</aside>
<!--  Sidebar End -->