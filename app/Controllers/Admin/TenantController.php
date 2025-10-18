<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TenantModel;
use App\Models\AppModel;

class TenantController extends BaseController
{
    public function index(): string
    {
        // Cabeçalho da página (Tenants)
        $pageHeaderData = $this->buildPageHeader('Tenants', [
            ['title' => 'Dashboard', 'url' => base_url('admin/dashboard')],
            ['title' => 'Clientes', 'url' => '#'],
            ['title' => 'Tenants', 'url' => base_url('admin/tenant')],
        ]);

        // Lista de tenants com app vinculado (primeiro habilitado)
        $tenantModel = new TenantModel();
        $perPage = 20;
        $tenants = $tenantModel
            ->select([
                'tenants.id',
                'tenants.name',
                'tenants.contact_email',
                'tenants.locale',
                'tenants.is_active',
                "(SELECT apps.name FROM tenant_apps ta JOIN apps ON apps.id = ta.app_id WHERE ta.tenant_id = tenants.id AND ta.is_enabled = 1 ORDER BY ta.installed_at DESC LIMIT 1) AS app_name",
            ])
            ->orderBy('tenants.id', 'DESC')
            ->paginate($perPage);
        $pager = $tenantModel->pager;

        // Buscar aplicativos para o filtro
        $appModel = new AppModel();
        $apps = $appModel->select(['name', 'slug'])->where('is_active', 1)->orderBy('name', 'ASC')->findAll();

        // Opções de status (is_active)
        $statusOptions = [
            ['value' => '1', 'label' => 'Ativo'],
            ['value' => '0', 'label' => 'Inativo'],
        ];

        $data = [
            'titulo' => 'Tenants',
            'sidebarActive' => 'tenants',
            'pageHeader' => $pageHeaderData,
            'tenants' => $tenants,
            'pager' => $pager,
            'apps' => $apps,
            'statusOptions' => $statusOptions,
            'css' => [
                'assets/libs/sweetalert2/dist/sweetalert2.min.css',
            ],
            'js' => [
                'assets/libs/select2/dist/js/select2.min.js',
                'assets/libs/sweetalert2/dist/sweetalert2.min.js',
                'assets/js/custom/admin/batch-actions.js',
                'assets/js/custom/admin/tenant-search.js',
            ],
        ];

        return view('admin/tenant/tenantView', $data);
    }

    // Busca em tempo real (AJAX) de tenants por nome/email/locale/status/aplicativo
    public function search(): string
    {
        $name = trim((string) $this->request->getGet('nome'));
        $email = trim((string) $this->request->getGet('email'));
        $locale = trim((string) $this->request->getGet('locale'));
        $status = trim((string) $this->request->getGet('status')); // '1' ou '0'
        $aplicativo = trim((string) $this->request->getGet('aplicativo'));

        $tenantModel = new TenantModel();
        $builder = $tenantModel
            ->select([
                'tenants.id',
                'tenants.name',
                'tenants.contact_email',
                'tenants.locale',
                'tenants.is_active',
                "(SELECT apps.name FROM tenant_apps ta JOIN apps ON apps.id = ta.app_id WHERE ta.tenant_id = tenants.id AND ta.is_enabled = 1 ORDER BY ta.installed_at DESC LIMIT 1) AS app_name",
            ])
            ->orderBy('tenants.id', 'DESC');

        if ($name !== '') {
            $builder->like('tenants.name', $name);
        }
        if ($email !== '') {
            $builder->like('tenants.contact_email', $email);
        }
        if ($locale !== '') {
            $builder->where('tenants.locale', $locale);
        }
        if ($status !== '') {
            if (in_array($status, ['0', '1'], true)) {
                $builder->where('tenants.is_active', (int) $status);
            }
        }
        if ($aplicativo !== '') {
            // Filtrar por app vinculado
            $builder->join('tenant_apps', 'tenant_apps.tenant_id = tenants.id', 'left')
                ->join('apps', 'apps.id = tenant_apps.app_id', 'left')
                ->where('tenant_apps.is_enabled', 1)
                ->where('apps.slug', $aplicativo)
                ->groupBy('tenants.id');
        }

        // Limite razoável para respostas rápidas em tempo real
        $tenants = $builder->findAll(50);

        return view('admin/tenant/_tenant_rows', [
            'tenants' => $tenants,
        ]);
    }
}