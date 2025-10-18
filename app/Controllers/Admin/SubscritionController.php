<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SubscriptionModel;
use App\Models\AppModel;
use App\Models\AppPlanModel;
use CodeIgniter\HTTP\ResponseInterface;

class SubscritionController extends BaseController
{
    public function index(): string
    {
        // Model e paginação

        $subsModel = new SubscriptionModel();
        $perPage  = 20;
        $subscriptions = $subsModel
            ->selectForList()
            ->orderBy('subscriptions.id', 'DESC')
            ->paginate($perPage);
        $pager = $subsModel->pager;

        // Buscar aplicativos para o filtro
        $appModel = new AppModel();
        $apps = $appModel->select(['name', 'slug'])->orderBy('name', 'ASC')->findAll();

        // Opções de status (com valores internos e rótulos em PT-BR)
        $statusOptions = [
            ['value' => 'active', 'label' => 'Ativo'],
            ['value' => 'paused', 'label' => 'Pausado'],
            ['value' => 'canceled', 'label' => 'Cancelado'],
            ['value' => 'incomplete', 'label' => 'Incompleto'],
            ['value' => 'unpaid', 'label' => 'Não pago'],
            ['value' => 'trialing', 'label' => 'Em teste'],
            ['value' => 'incomplete_expired', 'label' => 'Expirado'],
            ['value' => 'past_due', 'label' => 'Vencido'],
        ];

        // Cabeçalho da página (Assinaturas)
        $pageHeaderData = $this->buildPageHeader('Assinaturas', [
            ['title' => 'Dashboard', 'url' => base_url('admin/dashboard')],
            ['title' => 'Clientes', 'url' => '#'],
            ['title' => 'Assinaturas', 'url' => base_url('admin/subscription')]
        ]);

        $data = [
            'titulo' => 'Assinaturas',
            'sidebarActive' => 'subscriptions',
            'pageHeader'  => $pageHeaderData,
            'subscriptions' => $subscriptions,
            'pager' => $pager,
            'apps' => $apps,
            'statusOptions' => $statusOptions,

            'css' => [
                'assets/libs/sweetalert2/dist/sweetalert2.min.css',
            ],
            'js' => [
                // Demais libs e scripts já utilizados
                'assets/libs/select2/dist/js/select2.min.js',
                'assets/libs/sweetalert2/dist/sweetalert2.min.js',
                'assets/js/custom/admin/batch-actions.js',
                'assets/js/custom/admin/apps.js',
                'assets/js/custom/admin/subscription-actions.js',
                'assets/js/custom/admin/subscription-search.js'
            ],
        ];

        return view('admin/tenant/subscritionView', $data);
    }

    // Novo: busca em tempo real (AJAX) de assinaturas por email/aplicativo/status
    public function search(): ResponseInterface|string
    {
        $email = trim((string) $this->request->getGet('email'));
        $aplicativo = trim((string) $this->request->getGet('aplicativo'));
        $uiStatus = strtolower((string) $this->request->getGet('status'));
        $planId = trim((string) $this->request->getGet('plano'));

        $subsModel = new SubscriptionModel();
        $subsModel
            ->selectForList()
            ->orderBy('subscriptions.id', 'DESC');

        if ($email !== '') {
            $subsModel->like('tenants.contact_email', $email);
        }
        if ($aplicativo !== '') {
            $subsModel->where('apps.slug', $aplicativo);
        }
        if ($uiStatus !== '') {
            $allowed = ['active', 'paused', 'canceled', 'incomplete', 'unpaid', 'trialing', 'incomplete_expired', 'past_due'];
            if (in_array($uiStatus, $allowed, true)) {
                $subsModel->where('subscriptions.status', $uiStatus);
            }
        }
        if ($planId !== '' && ctype_digit($planId)) {
            $subsModel->where('app_plans.id', (int) $planId);
        }

        // Paginar respeitando filtros
        $perPage = 20; // manter o mesmo perPage do index()
        $subscriptions = $subsModel->paginate($perPage);
        $pager = $subsModel->pager;

        // Quando chamado via AJAX, retornamos JSON com rows e pager
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'rows' => view('admin/tenant/_subscription_rows', [
                    'subscriptions' => $subscriptions,
                ]),
                'pager' => view('admin/tenant/_subscription_pager', [
                    'pager' => $pager,
                ]),
                'csrf' => csrf_hash(),
            ]);
        }

        // Fallback: retorno somente das linhas (compatibilidade)
        return view('admin/tenant/_subscription_rows', [
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Retorna os planos de um aplicativo por slug (JSON), para preencher o select de planos.
     */
    public function plans(?string $slug = null)
    {
        $slug = $slug ?? trim((string) $this->request->getGet('app'));
        if ($slug === '') {
            return $this->response->setJSON([
                'success' => false,
                'plans' => [],
                'message' => 'Slug do aplicativo não informado.',
                'csrf' => csrf_hash(),
            ]);
        }

        $appModel = new AppModel();
        $app = $appModel->select('id')->where('slug', $slug)->first();
        if (! $app) {
            return $this->response->setJSON([
                'success' => false,
                'plans' => [],
                'message' => 'Aplicativo não encontrado.',
                'csrf' => csrf_hash(),
            ]);
        }

        $planModel = new AppPlanModel();
        $plans = $planModel
            ->select(['id', 'name', 'price_amount', 'currency'])
            ->where('app_id', (int) $app['id'])
            ->orderBy('name', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'success' => true,
            'plans' => $plans,
            'csrf' => csrf_hash(),
        ]);
    }

    // Métodos legados removidos: use updateStatus(int $id) + performStatusChange()

    /**
     * Atualiza o status da assinatura de forma genérica.
     * Recebe via POST o parâmetro 'status' e aplica side-effects necessários.
     */
    public function updateStatus(int $id)
    {
        $status = strtolower((string) $this->request->getPost('status'));
        $allowed = ['active', 'paused', 'canceled', 'incomplete', 'unpaid', 'trialing', 'incomplete_expired', 'past_due'];
        if (! in_array($status, $allowed, true)) {
            if ($this->request->isAJAX() || $this->request->getMethod() === 'post') {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Status inválido.',
                    'csrf' => csrf_hash(),
                ]);
            }
            return redirect()->back();
        }
        return $this->performStatusChange($id, $status, 'Status atualizado.');
    }

    private function performStatusChange(int $id, string $status, ?string $messageOverride = null)
    {
        $update = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        switch ($status) {
            case 'paused':
            case 'unpaid':
            case 'past_due':
            case 'incomplete_expired':
            case 'incomplete':
                $update['is_active'] = 0;
                break;
            case 'canceled':
                $update['is_active'] = 0;
                $update['canceled_at'] = date('Y-m-d H:i:s');
                break;
            case 'trialing':
                $update['is_active'] = 1;
                $update['trial_end_at'] = date('Y-m-d H:i:s', strtotime('+5 days'));
                break;
            case 'active':
                $update['is_active'] = 1;
                break;
        }

        $subs = new SubscriptionModel();
        $subs->update($id, $update);

        $response = [
            'success' => true,
            'subscription_id' => $id,
            'status' => $status,
            'is_active' => $update['is_active'] ?? null,
            'csrf' => csrf_hash(),
            'message' => $messageOverride ?? 'Status atualizado.',
        ];
        if (isset($update['trial_end_at'])) {
            $response['trial_end_at'] = $update['trial_end_at'];
        }

        if ($this->request->isAJAX() || $this->request->getMethod() === 'post') {
            return $this->response->setJSON($response);
        }
        return redirect()->back();
    }
}
