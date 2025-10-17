<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;


class TenantController extends BaseController
{
    public function index(): string
    {
        // Cabeçalho da página (Apps)
        $pageHeaderData = $this->buildPageHeader('Assinaturas', [
            ['title' => 'Dashboard', 'url' => base_url('admin/dashboard')],
            ['title' => 'Clientes', 'url' => "#"],
            ['title' => 'Assinaturas', 'url' => base_url('admin/apps')]
        ]);

        $data = [
            'titulo' => 'Apps',
            'sidebarActive' => 'apps',
            'pageHeader'  => $pageHeaderData,

            'css' => [
                'assets/libs/sweetalert2/dist/sweetalert2.min.css',
            ],
            'js' => [
                // Demais libs e scripts já utilizados
                'assets/libs/select2/dist/js/select2.min.js',
                'assets/libs/sweetalert2/dist/sweetalert2.min.js',
                'assets/js/custom/admin/batch-actions.js',
                'assets/js/custom/admin/apps.js'
            ],
        ];

        return view('admin/tenant/indexView', $data);
    }
}
