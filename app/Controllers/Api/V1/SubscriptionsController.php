<?php

namespace App\Controllers\Api\V1;

class SubscriptionsController extends BaseApiController
{
    public function index()
    {
        // Requer autenticação via JWT
        $payload = $this->getAuthPayload();
        if (! $payload || ! isset($payload['sub'])) {
            return $this->respondError('Unauthorized', 401);
        }

        $tenantId = (int) $payload['sub'];

        // app_id pode vir por query ?app_id= ou header X-App-ID
        $appId = (int) ($this->request->getGet('app_id') ?? $this->request->getHeaderLine('X-App-ID') ?? 0);

        $builder = $this->db->table('subscriptions')->where('tenant_id', $tenantId);
        if ($appId > 0) {
            $builder->where('app_id', $appId);
        }

        $rows = $builder->get()->getResultArray();

        return $this->respondOk([
            'tenant_id' => $tenantId,
            'app_id' => $appId ?: null,
            'subscriptions' => $rows,
        ]);
    }

    public function create()
    {
        // Requer autenticação via JWT
        $payload = $this->getAuthPayload();
        if (! $payload || ! isset($payload['sub'])) {
            return $this->respondError('Unauthorized', 401);
        }
        $tenantId = (int) $payload['sub'];

        $input = $this->getInputPayload();

        $validation = \Config\Services::validation();
        $validation->setRules([
            'app_id' => 'required|is_natural_no_zero',
            'app_plan_id' => 'required|is_natural_no_zero',
            'quantity' => 'permit_empty|is_natural_no_zero',
        ]);
        if (! $validation->run($input)) {
            return $this->respondError('Dados inválidos', 422, $validation->getErrors());
        }

        $appId = (int) $input['app_id'];
        $appPlanId = (int) $input['app_plan_id'];
        $quantity = (int) ($input['quantity'] ?? 1);

        // Verificar vínculo tenant -> app
        $tenantApp = $this->db->table('tenant_apps')
            ->where(['tenant_id' => $tenantId, 'app_id' => $appId])
            ->get()
            ->getRow();
        if (! $tenantApp) {
            return $this->respondError('Tenant não possui este app instalado', 403);
        }

        // Validar plano pertence ao app e está ativo
        $plan = $this->db->table('app_plans')
            ->where(['id' => $appPlanId, 'app_id' => $appId, 'is_active' => 1])
            ->get()
            ->getRowArray();
        if (! $plan) {
            return $this->respondError('Plano inválido para este app', 404);
        }

        // Evitar duplicidade: assinatura ativa ou pendente para o mesmo (tenant_id, app_id)
        $existing = $this->db->table('subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('app_id', $appId)
            ->where('is_active', 1)
            ->whereIn('status', ['active', 'trialing', 'past_due', 'incomplete'])
            ->get()
            ->getRow();
        if ($existing) {
            return $this->respondError('Já existe assinatura ativa ou pendente para este app', 409);
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'tenant_id' => $tenantId,
            'app_id' => $appId,
            'app_plan_id' => $appPlanId,
            'status' => 'incomplete',
            'quantity' => $quantity > 0 ? $quantity : 1,
            'unit_price' => $plan['price_amount'] ?? null,
            'currency' => $plan['currency'] ?? null,
            'current_period_start' => $now,
            'current_period_end' => date('Y-m-d H:i:s', time() + 30 * 86400),
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        try {
            $this->db->table('subscriptions')->insert($data);
            $id = (int) $this->db->insertID();
            $sub = $this->db->table('subscriptions')->where('id', $id)->get()->getRowArray();
            return $this->respondOk(['subscription' => $sub], 201);
        } catch (\Throwable $e) {
            return $this->respondError('Falha ao criar assinatura', 500, ['exception' => $e->getMessage()]);
        }
    }
}