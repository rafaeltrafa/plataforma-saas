<?php

namespace App\Controllers\Api\V1;

use Stripe\StripeClient;

class CheckoutController extends BaseApiController
{
    public function createSession()
    {
        // Requer autenticação via JWT
        $payload = $this->getAuthPayload();
        if (! $payload || ! isset($payload['sub'])) {
            return $this->respondError('Unauthorized', 401);
        }
        $tenantId = (int) $payload['sub'];

        $input = $this->getInputPayload();

        $priceId = (string) ($input['price_id'] ?? '');
        $appId = (int) ($input['app_id'] ?? 0);
        $appPlanId = (int) ($input['app_plan_id'] ?? 0);
        $quantity = (int) ($input['quantity'] ?? 1);
        $successUrl = (string) ($input['success_url'] ?? '');
        $cancelUrl = (string) ($input['cancel_url'] ?? '');

        // Regras de validação mínimas
        if ($appId <= 0 || $appPlanId <= 0) {
            return $this->respondError('Parâmetros obrigatórios: app_id, app_plan_id', 422);
        }

        // Validar que o plano pertence ao app e está ativo
        $planRow = $this->db->table('app_plans')
            ->select('id, app_id, is_active, stripe_price_id')
            ->where(['id' => $appPlanId, 'app_id' => $appId, 'is_active' => 1])
            ->get()
            ->getRowArray();
        if (! $planRow) {
            return $this->respondError('Plano inválido para este app', 404);
        }

        // Se price_id não vier, tenta obter do plano
        if ($priceId === '') {
            $priceId = (string) ($planRow['stripe_price_id'] ?? '');
        }
        if ($priceId === '') {
            return $this->respondError('stripe_price_id ausente para o plano informado. Cadastre-o no app_plans ou envie price_id explicitamente.', 422);
        }

        // BaseURL do ambiente (não hardcoded)
        $base = rtrim((string) (env('APP_BASE_URL') ?? (config('App')->baseURL ?? '')), '/');
        if ($base === '') {
            return $this->respondError('Configuração de baseURL ausente. Defina app.baseURL no .env ou APP_BASE_URL.', 500);
        }
        // URL padrão se não informadas
        if ($successUrl === '') {
            $successUrl = $base . '/stripe/success?session_id={CHECKOUT_SESSION_ID}';
        }
        if ($cancelUrl === '') {
            $cancelUrl = $base . '/stripe/cancel';
        }

        // Verificar vínculo tenant -> app
        $tenantApp = $this->db->table('tenant_apps')
            ->where(['tenant_id' => $tenantId, 'app_id' => $appId])
            ->get()
            ->getRow();
        if (! $tenantApp) {
            return $this->respondError('Tenant não possui este app instalado', 403);
        }

        // Obter stripe_customer_id se existir
        $tenant = $this->db->table('tenants')->where('id', $tenantId)->get()->getRowArray();
        $stripeCustomerId = $tenant['stripe_customer_id'] ?? null;

        $secret = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
        if ($secret === '') {
            return $this->respondError('Configuração STRIPE_SECRET_KEY ausente', 500);
        }

        $stripe = new StripeClient($secret);

        try {
            // Monta payload evitando enviar 'customer' vazio (Stripe rejeita string vazia/nulo)
            $params = [
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => max(1, $quantity),
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                // Amarração determinística no webhook
                'client_reference_id' => (string) $tenantId,
                'metadata' => [
                    'tenant_id' => (string) $tenantId,
                    'app_id' => (string) $appId,
                    'app_plan_id' => (string) $appPlanId,
                    'price_id' => $priceId,
                ],
                // As metadata abaixo propagam para a assinatura criada
                'subscription_data' => [
                    'metadata' => [
                        'tenant_id' => (string) $tenantId,
                        'app_id' => (string) $appId,
                        'app_plan_id' => (string) $appPlanId,
                        'price_id' => $priceId,
                    ],
                ],
                // Qualidade de vida
                'allow_promotion_codes' => true,
            ];

            if (is_string($stripeCustomerId) && trim($stripeCustomerId) !== '') {
                // Reaproveitar o cliente se já existir
                $params['customer'] = trim($stripeCustomerId);
            }

            $session = $stripe->checkout->sessions->create($params);

            return $this->respondOk([
                'id' => $session->id,
                'url' => $session->url,
            ], 201);
        } catch (\Throwable $e) {
            log_message('error', 'Stripe Checkout createSession error: ' . $e->getMessage());
            return $this->respondError('Falha ao criar sessão de checkout', 500, ['exception' => $e->getMessage()]);
        }
    }
}