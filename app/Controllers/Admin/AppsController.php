<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AppModel;
use App\Models\AppPlanModel;
use Stripe\Stripe;
use Stripe\Product as StripeProduct;
use Stripe\Price as StripePrice;

class AppsController extends BaseController
{
    public function index(): string
    {
        // Cabeçalho da página (Apps)
        $pageHeaderData = $this->buildPageHeader('Apps', [
            ['title' => 'Dashboard', 'url' => base_url('admin/dashboard')],
            ['title' => 'Apps', 'url' => base_url('admin/apps')],
        ]);

        // Lista de apps
        $appsModel = new AppModel();
        $apps = $appsModel->orderBy('created_at', 'DESC')->findAll();

        $data = [
            'titulo' => 'Apps',
            'sidebarActive' => 'apps',
            'pageHeader'  => $pageHeaderData,
            'apps' => $apps,
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

        return view('admin/app/indexView', $data);
    }

    public function deactivate(int $id)
    {
        $model = new AppModel();
        $app = $model->find($id);
        if (!$app) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'App não encontrado']);
        }

        $updated = $model->update($id, ['is_active' => 0]);
        if ($updated === false) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Falha ao desativar app']);
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function activate(int $id)
    {
        $model = new AppModel();
        $app = $model->find($id);
        if (!$app) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'App não encontrado']);
        }

        $updated = $model->update($id, ['is_active' => 1]);
        if ($updated === false) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Falha ao ativar app']);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Retorna as linhas HTML dos planos (app_plans) do app informado.
     * Usado para preencher o modal de Assinaturas via AJAX.
     */
    public function plans(int $appId)
    {
        $apps = new AppModel();
        $app = $apps->find($appId);
        if (!$app) {
            return $this->response->setStatusCode(404)->setBody('App não encontrado');
        }

        $plansModel = new AppPlanModel();
        $plans = $plansModel->where('app_id', $appId)->orderBy('created_at', 'DESC')->findAll();

        // Retorna somente as linhas da tabela para inserção no <tbody>
        return view('admin/app/_plans_rows', [
            'plans' => $plans,
        ]);
    }

    /**
     * Retorna o formulário de criação de plano para o app informado.
     */
    public function planForm(int $appId)
    {
        $apps = new AppModel();
        $app = $apps->find($appId);
        if (!$app) {
            return $this->response->setStatusCode(404)->setBody('App não encontrado');
        }

        return view('admin/app/_plan_form', [
            'appId' => $appId,
        ]);
    }

    /**
     * Recebe os dados do formulário e cria um novo plano (app_plans).
     */
    public function storePlan(int $appId)
    {
        $apps = new AppModel();
        $app = $apps->find($appId);
        if (!$app) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'App não encontrado']);
        }

        $name = trim((string) $this->request->getPost('name'));
        $billing = (string) $this->request->getPost('billing_interval');
        $currency = (string) $this->request->getPost('currency');
        $stripePriceId = trim((string) $this->request->getPost('stripe_price_id'));
        $priceRaw = (string) $this->request->getPost('price_amount');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Nome é obrigatório';
        }
        if (!in_array($billing, ['monthly', 'quarterly', 'yearly', 'one_time'], true)) {
            $errors['billing_interval'] = 'Cobrança inválida';
        }
        if (!in_array($currency, ['BRL', 'USD'], true)) {
            $errors['currency'] = 'Moeda inválida';
        }
        // Stripe Price ID pode ser gerado automaticamente se não for fornecido
        // Normalizar preço (aceitar vírgula como separador decimal)
        $priceNorm = str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', $priceRaw));
        if ($priceNorm === '' || !is_numeric($priceNorm)) {
            $errors['price_amount'] = 'Preço inválido';
        } else {
            $priceAmount = number_format((float) $priceNorm, 2, '.', '');
            if ((float) $priceAmount < 0) {
                $errors['price_amount'] = 'Preço deve ser maior ou igual a zero';
            }
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $errors]);
        }

        // Se não foi informado Stripe Price ID, tenta criar Product & Price na Stripe
        if ($stripePriceId === '') {
            $secret = getenv('STRIPE_SECRET_KEY') ?: getenv('STRIPE_SECRET') ?: '';
            if (!$secret) {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Chave da Stripe não configurada']);
            }
            try {
                Stripe::setApiKey($secret);
                // Cria o Product na Stripe
                $product = StripeProduct::create([
                    'name' => '#' . (string) $appId . ' | ' . (string) ($app['name'] ?? 'App') . ' | ' . $name,
                    'type' => 'service',
                    'metadata' => [
                        'app_id' => (string) $appId,
                        'billing_interval' => $billing,
                    ],
                ]);
                // Prepara criação do Price (recorrente para mensal/trimestral/anual; avulso para one_time)
                $currencyCode = strtolower($currency);
                $unitAmount = (int) round(((float) $priceAmount) * 100);
                $priceParams = [
                    'currency' => $currencyCode,
                    'product' => $product->id,
                    'unit_amount' => $unitAmount,
                    'metadata' => [
                        'app_id' => (string) $appId,
                        'billing_interval' => $billing,
                    ],
                ];
                if ($billing === 'one_time') {
                    // não adicionar recurring
                } elseif ($billing === 'monthly') {
                    $priceParams['recurring'] = ['interval' => 'month', 'interval_count' => 1];
                } elseif ($billing === 'quarterly') {
                    $priceParams['recurring'] = ['interval' => 'month', 'interval_count' => 3];
                } elseif ($billing === 'yearly') {
                    $priceParams['recurring'] = ['interval' => 'year', 'interval_count' => 1];
                }
                $price = StripePrice::create($priceParams);
                $stripePriceId = $price->id;
            } catch (\Throwable $ex) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Erro ao criar preço na Stripe: ' . $ex->getMessage(),
                ]);
            }
        }

        $planModel = new AppPlanModel();

        // Gerar slug único por app
        helper('text');
        $baseSlug = url_title($name, '-', true);
        if ($baseSlug === '') {
            $baseSlug = 'plano';
        }
        $slug = $baseSlug;
        $suffix = 2;
        while ($planModel->where('app_id', $appId)->where('slug', $slug)->first()) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
            if ($suffix > 100) {
                $slug = $baseSlug . '-' . uniqid();
                break;
            }
        }

        $data = [
            'app_id' => $appId,
            'name' => $name,
            'slug' => $slug,
            'billing_interval' => $billing,
            'currency' => $currency,
            'price_amount' => $priceAmount ?? null,
            'stripe_price_id' => $stripePriceId,
            'is_active' => 1,
        ];

        $newId = $planModel->insert($data);
        if ($newId === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Falha ao criar plano'
            ]);
        }

        // Atualizar plan_key = slug + id (ex: meu-plano-123)
        $planKey = $slug . '-' . $newId;
        $planModel->update($newId, ['plan_key' => $planKey]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Plano criado com sucesso',
            'id' => $newId,
            'plan_key' => $planKey,
            'stripe_price_id' => $stripePriceId,
        ]);
    }

    /**
     * Cria Product & Price na Stripe e retorna o Price ID (para preenchimento automático no formulário via AJAX).
     */
    public function createStripePrice(int $appId)
    {
        $apps = new AppModel();
        $app = $apps->find($appId);
        if (!$app) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'App não encontrado']);
        }

        $name = trim((string) ($this->request->getPost('name') ?? $this->request->getGet('name')));
        $billing = (string) ($this->request->getPost('billing_interval') ?? $this->request->getGet('billing_interval'));
        $currency = (string) ($this->request->getPost('currency') ?? $this->request->getGet('currency'));
        $priceRaw = (string) ($this->request->getPost('price_amount') ?? $this->request->getGet('price_amount'));

        $errors = [];
        if ($name === '') $errors['name'] = 'Nome é obrigatório';
        if (!in_array($billing, ['monthly', 'quarterly', 'yearly', 'one_time'], true)) $errors['billing_interval'] = 'Cobrança inválida';
        if (!in_array($currency, ['BRL', 'USD'], true)) $errors['currency'] = 'Moeda inválida';
        $priceNorm = str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', $priceRaw));
        if ($priceNorm === '' || !is_numeric($priceNorm)) {
            $errors['price_amount'] = 'Preço inválido';
        }
        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $errors]);
        }

        $priceAmount = number_format((float) $priceNorm, 2, '.', '');
        $secret = getenv('STRIPE_SECRET_KEY') ?: getenv('STRIPE_SECRET') ?: '';
        if (!$secret) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Chave da Stripe não configurada']);
        }

        try {
            Stripe::setApiKey($secret);
            // Cria o Product
            $product = StripeProduct::create([
                'name' => (string) $appId . ' | ' . (string) ($app['name'] ?? 'App') . ' | ' . $name,
                'type' => 'service',
                'metadata' => [
                    'app_id' => (string) $appId,
                    'billing_interval' => $billing,
                ],
            ]);
            // Cria o Price
            $currencyCode = strtolower($currency);
            $unitAmount = (int) round(((float) $priceAmount) * 100);
            $priceParams = [
                'currency' => $currencyCode,
                'product' => $product->id,
                'unit_amount' => $unitAmount,
                'metadata' => [
                    'app_id' => (string) $appId,
                    'billing_interval' => $billing,
                ],
            ];
            if ($billing === 'one_time') {
                // não adicionar recurring
            } elseif ($billing === 'monthly') {
                $priceParams['recurring'] = ['interval' => 'month', 'interval_count' => 1];
            } elseif ($billing === 'quarterly') {
                $priceParams['recurring'] = ['interval' => 'month', 'interval_count' => 3];
            } elseif ($billing === 'yearly') {
                $priceParams['recurring'] = ['interval' => 'year', 'interval_count' => 1];
            }
            $price = StripePrice::create($priceParams);

            return $this->response->setJSON([
                'success' => true,
                'product_id' => $product->id,
                'price_id' => $price->id,
            ]);
        } catch (\Throwable $ex) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Erro ao criar preço na Stripe: ' . $ex->getMessage(),
            ]);
        }
    }

    /**
     * Carrega formulário de edição de plano.
     */
    public function editPlanForm(int $appId, int $planId)
    {
        $apps = new AppModel();
        $app = $apps->find($appId);
        if (!$app) {
            return $this->response->setStatusCode(404)->setBody('App não encontrado');
        }

        $planModel = new AppPlanModel();
        $plan = $planModel->find($planId);
        if (!$plan || (int)($plan['app_id'] ?? 0) !== $appId) {
            return $this->response->setStatusCode(404)->setBody('Plano não encontrado');
        }

        return view('admin/app/_plan_edit_form', [
            'appId' => $appId,
            'plan' => $plan,
        ]);
    }

    /**
     * Atualiza um plano existente.
     */
    public function updatePlan(int $appId, int $planId)
    {
        $apps = new AppModel();
        $app = $apps->find($appId);
        if (!$app) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'App não encontrado']);
        }

        $planModel = new AppPlanModel();
        $existing = $planModel->find($planId);
        if (!$existing || (int)($existing['app_id'] ?? 0) !== $appId) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Plano não encontrado']);
        }

        $name = trim((string) $this->request->getPost('name'));
        $billing = (string) $this->request->getPost('billing_interval');
        $currency = (string) $this->request->getPost('currency');
        $stripePriceId = trim((string) $this->request->getPost('stripe_price_id'));
        $priceRaw = (string) $this->request->getPost('price_amount');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Nome é obrigatório';
        }
        if (!in_array($billing, ['monthly', 'quarterly', 'yearly', 'one_time'], true)) {
            $errors['billing_interval'] = 'Cobrança inválida';
        }
        if (!in_array($currency, ['BRL', 'USD'], true)) {
            $errors['currency'] = 'Moeda inválida';
        }
        if ($stripePriceId === '') {
            $errors['stripe_price_id'] = 'Stripe Price ID é obrigatório';
        }
        // Normalizar preço (aceitar vírgula como separador decimal)
        $priceNorm = str_replace(',', '.', preg_replace('/[^0-9,\.]/', '', $priceRaw));
        if ($priceNorm === '' || !is_numeric($priceNorm)) {
            $errors['price_amount'] = 'Preço inválido';
        } else {
            $priceAmount = number_format((float) $priceNorm, 2, '.', '');
            if ((float) $priceAmount < 0) {
                $errors['price_amount'] = 'Preço deve ser maior ou igual a zero';
            }
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $errors]);
        }

        // Atualizar campos e slug/plan_key se necessário
        helper('text');
        $baseSlug = url_title($name, '-', true);
        if ($baseSlug === '') {
            $baseSlug = 'plano';
        }
        $slug = $baseSlug;
        $suffix = 2;
        while ($planModel->where('app_id', $appId)->where('slug', $slug)->where('id !=', $planId)->first()) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
            if ($suffix > 100) {
                $slug = $baseSlug . '-' . uniqid();
                break;
            }
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'billing_interval' => $billing,
            'currency' => $currency,
            'price_amount' => $priceAmount ?? null,
            'stripe_price_id' => $stripePriceId,
        ];

        $updated = $planModel->update($planId, $data);
        if ($updated === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Falha ao atualizar plano'
            ]);
        }

        // Atualizar plan_key = slug + id (ex: meu-plano-123)
        $planKey = $slug . '-' . $planId;
        $planModel->update($planId, ['plan_key' => $planKey]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Plano atualizado com sucesso',
            'id' => $planId,
            'plan_key' => $planKey,
        ]);
    }

    /**
     * Desativa um plano (is_active = 0)
     */
    public function deactivatePlan(int $appId, int $planId)
    {
        $apps = new AppModel();
        $app = $apps->find($appId);
        if (!$app) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'App não encontrado']);
        }

        $planModel = new AppPlanModel();
        $plan = $planModel->find($planId);
        if (!$plan || (int)($plan['app_id'] ?? 0) !== $appId) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Plano não encontrado']);
        }

        $updated = $planModel->update($planId, ['is_active' => 0]);
        if ($updated === false) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Falha ao desativar plano']);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Ativa um plano (is_active = 1)
     */
    public function activatePlan(int $appId, int $planId)
    {
        $apps = new AppModel();
        $app = $apps->find($appId);
        if (!$app) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'App não encontrado']);
        }

        $planModel = new AppPlanModel();
        $plan = $planModel->find($planId);
        if (!$plan || (int)($plan['app_id'] ?? 0) !== $appId) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Plano não encontrado']);
        }

        $updated = $planModel->update($planId, ['is_active' => 1]);
        if ($updated === false) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Falha ao ativar plano']);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Retorna o formulário de criação de App (HTML parcial para modal).
     */
    public function newAppForm()
    {
        return view('admin/app/_app_form');
    }

    /**
     * Cria um novo App com slug e app_key automáticos; is_active = 1.
     */
    public function storeApp()
    {
        $name = trim((string) $this->request->getPost('name'));
        $description = trim((string) $this->request->getPost('description'));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Nome do App é obrigatório';
        }
        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $errors]);
        }

        // Gerar slug único
        helper('text');
        $appsModel = new AppModel();
        $baseSlug = url_title($name, '-', true);
        if ($baseSlug === '') {
            $baseSlug = 'app';
        }
        $slug = $baseSlug;
        $suffix = 2;
        while ($appsModel->where('slug', $slug)->first()) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
            if ($suffix > 100) {
                $slug = $baseSlug . '-' . uniqid();
                break;
            }
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_active' => 1,
        ];

        $newId = $appsModel->insert($data);
        if ($newId === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Falha ao criar App'
            ]);
        }

        // Atualiza app_key = slug + id (ex: meu-app-123)
        $appKey = $slug . '-' . $newId;
        $appsModel->update($newId, ['app_key' => $appKey]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'App criado com sucesso',
            'id' => $newId,
            'slug' => $slug,
            'app_key' => $appKey,
        ]);
    }

    /**
     * Retorna formulário de edição de App (HTML parcial para modal).
     */
    public function editAppForm(int $id)
    {
        $appsModel = new AppModel();
        $app = $appsModel->find($id);
        if (!$app) {
            return $this->response->setStatusCode(404)->setBody('App não encontrado');
        }
        return view('admin/app/_app_edit_form', [
            'app' => $app,
        ]);
    }

    /**
     * Atualiza Nome e Descrição do App.
     */
    public function updateApp(int $id)
    {
        $appsModel = new AppModel();
        $app = $appsModel->find($id);
        if (!$app) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'App não encontrado']);
        }

        $name = trim((string) $this->request->getPost('name'));
        $description = trim((string) $this->request->getPost('description'));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Nome do App é obrigatório';
        }
        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON(['success' => false, 'errors' => $errors]);
        }

        $data = [
            'name' => $name,
            'description' => $description,
        ];
        $updated = $appsModel->update($id, $data);
        if ($updated === false) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Falha ao atualizar App']);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'App atualizado com sucesso',
            'id' => $id,
        ]);
    }

    public function delete(int $id)
    {
        $appsModel = new AppModel();
        $app = $appsModel->find($id);
        if (!$app) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'App não encontrado'
            ]);
        }

        try {
            $db = \Config\Database::connect();
            $tenantAppsCount = $db->table('tenant_apps')->where('app_id', $id)->countAllResults();
            $paymentsCount   = $db->table('payments')->where('app_id', $id)->countAllResults();
            $subsCount       = $db->table('subscriptions')->where('app_id', $id)->countAllResults();

            if ($tenantAppsCount > 0 || $paymentsCount > 0 || $subsCount > 0) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'App possui vínculos e não pode ser excluído'
                ]);
            }
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Erro ao verificar vínculos'
            ]);
        }

        $deleted = $appsModel->delete($id);
        if ($deleted === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Falha ao excluir app'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'App excluído com sucesso',
            'id' => $id,
        ]);
    }
}
