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
        $tokenAppId = (int) ($payload['app'] ?? 0);

        // app_id pode vir por query ?app_id= ou header X-App-ID
        $appId = (int) ($this->request->getGet('app_id') ?? $this->request->getHeaderLine('X-App-ID') ?? 0);
        if ($tokenAppId > 0 && $appId > 0 && $tokenAppId !== $appId) {
            return $this->respondError('Token não pertence ao app informado', 403);
        }

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
        $tokenAppId = (int) ($payload['app'] ?? 0);

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

        if ($tokenAppId > 0 && $tokenAppId !== $appId) {
            return $this->respondError('Token não pertence ao app informado', 403);
        }

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

        // Bloquear criação para planos one_time/lifetime
        $billingInterval = (string) ($plan['billing_interval'] ?? '');
        if (in_array($billingInterval, ['one_time', 'lifetime'], true)) {
            return $this->respondError('Plano de cobrança única não permite assinatura', 422, [
                'billing_interval' => $billingInterval,
            ]);
        }

        // Expirar automaticamente assinaturas 'incomplete' vencidas (sem job), para permitir novo cadastro
        $now = date('Y-m-d H:i:s');
        $this->db->table('subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('app_id', $appId)
            ->where('status', 'incomplete')
            ->where('incomplete_expires_at <=', $now)
            ->update([
                'status' => 'canceled',
                'is_active' => 0,
                'canceled_at' => $now,
                'updated_at' => $now,
            ]);

        // Evitar duplicidade: assinatura ativa ou pendente (inclui incomplete) para o mesmo (tenant_id, app_id)
        $existing = $this->db->table('subscriptions')
            ->where('tenant_id', $tenantId)
            ->where('app_id', $appId)
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
            'current_period_start' => null,
            'current_period_end' => null,
            'incomplete_expires_at' => date('Y-m-d H:i:s', time() + 48 * 3600),
            'is_active' => 0,
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

    public function cancel(int $id = 0)
    {
        // Requer autenticação via JWT
        $payload = $this->getAuthPayload();
        if (! $payload || ! isset($payload['sub'])) {
            return $this->respondError('Unauthorized', 401);
        }
        $tenantId = (int) $payload['sub'];

        if ($id <= 0) {
            return $this->respondError('ID inválido', 422);
        }

        // Buscar a assinatura
        $sub = $this->db->table('subscriptions')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (! $sub) {
            return $this->respondError('Assinatura não encontrada', 404);
        }

        // Garantir que pertence ao tenant autenticado
        if ((int) ($sub['tenant_id'] ?? 0) !== $tenantId) {
            return $this->respondError('Assinatura não pertence ao tenant autenticado', 403);
        }

        // Se já cancelada ou inativa, evitar operação redundante
        if ((string) ($sub['status'] ?? '') === 'canceled' || (int) ($sub['is_active'] ?? 1) === 0) {
            return $this->respondError('Assinatura já cancelada ou inativa', 409);
        }

        $now = date('Y-m-d H:i:s');
        try {
            $this->db->table('subscriptions')
                ->where('id', $id)
                ->update([
                    'status' => 'canceled',
                    'is_active' => 0,
                    'cancel_at' => $now,
                    'canceled_at' => $now,
                    'current_period_end' => $now,
                    'updated_at' => $now,
                ]);

            $updated = $this->db->table('subscriptions')->where('id', $id)->get()->getRowArray();
            return $this->respondOk(['subscription' => $updated], 200);
        } catch (\Throwable $e) {
            return $this->respondError('Falha ao cancelar assinatura', 500, ['exception' => $e->getMessage()]);
        }
    }

    public function cancelAtPeriodEnd(int $id = 0)
    {
        // Requer autenticação via JWT
        $payload = $this->getAuthPayload();
        if (! $payload || ! isset($payload['sub'])) {
            return $this->respondError('Unauthorized', 401);
        }
        $tenantId = (int) $payload['sub'];

        if ($id <= 0) {
            return $this->respondError('ID inválido', 422);
        }

        // Buscar a assinatura
        $sub = $this->db->table('subscriptions')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (! $sub) {
            return $this->respondError('Assinatura não encontrada', 404);
        }

        // Garantir que pertence ao tenant autenticado
        if ((int) ($sub['tenant_id'] ?? 0) !== $tenantId) {
            return $this->respondError('Assinatura não pertence ao tenant autenticado', 403);
        }

        // Se já cancelada, evitar operação redundante
        if ((string) ($sub['status'] ?? '') === 'canceled') {
            return $this->respondError('Assinatura já cancelada', 409);
        }

        // Se já possui cancel_at futuro, tornar idempotente
        $now = date('Y-m-d H:i:s');
        $existingCancelAt = $sub['cancel_at'] ?? null;
        if (! empty($existingCancelAt) && strtotime($existingCancelAt) >= time()) {
            // Não altera novamente; retorna estado atual
            $updated = $this->db->table('subscriptions')->where('id', $id)->get()->getRowArray();
            return $this->respondOk(['subscription' => $updated], 200);
        }

        // Define cancelamento ao final do período atual, mantendo ativa até lá
        $periodEnd = $sub['current_period_end'] ?? null;
        $cancelAt = $periodEnd && strtotime($periodEnd) > 0 ? $periodEnd : $now;

        try {
            $this->db->table('subscriptions')
                ->where('id', $id)
                ->update([
                    'cancel_at' => $cancelAt,
                    'is_active' => 1,
                    'updated_at' => $now,
                ]);

            $updated = $this->db->table('subscriptions')->where('id', $id)->get()->getRowArray();
            return $this->respondOk(['subscription' => $updated], 200);
        } catch (\Throwable $e) {
            return $this->respondError('Falha ao agendar cancelamento', 500, ['exception' => $e->getMessage()]);
        }
    }
}