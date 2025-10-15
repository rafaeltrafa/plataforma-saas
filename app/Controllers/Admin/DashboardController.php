<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index(): string
    {
        // Constrói dados do cabeçalho da página com título e estrutura de breadcrumb para navegação


        $data = [
            'titulo' => 'Dashboard',
            'sidebarActive' => 'dashboard',
            'css' => [
                'assets/libs/sweetalert2/dist/sweetalert2.min.css',
            ],
            'js' => [
                // Libs necessárias para os gráficos do dashboard
                'assets/libs/apexcharts/dist/apexcharts.min.js',
                // Script do dashboard correspondente aos IDs da view
                'assets/js/dashboards/dashboard1.js',
                // Demais libs e scripts já utilizados
                'assets/libs/select2/dist/js/select2.min.js',
                'assets/libs/sweetalert2/dist/sweetalert2.min.js',
                'assets/js/custom/admin/user-filter.js',
                'assets/js/custom/admin/batch-actions.js'
            ],
        ];

        return view('admin/dashboardView', $data);
    }
}
