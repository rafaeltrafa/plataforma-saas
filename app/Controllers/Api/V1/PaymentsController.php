<?php

namespace App\Controllers\Api\V1;

use App\Models\PaymentModel;

class PaymentsController extends BaseApiController
{
    public function index()
    {
        return $this->respondOk([
            'message' => 'Lista de pagamentos (placeholder)',
        ]);
    }

    public function create()
    {
        // Requer autenticação via JWT
        $payload = $this->getAuthPayload();
        if (! $payload || ! isset($payload['sub'])) {
            return $this->respondError('Unauthorized', 401);
        }
        $tenantIdFromToken = (int) $payload['sub'];
        $tokenAppId = (int) ($payload['app'] ?? 0);

        $input = $this->getInputPayload();

        $rules = [
            'tenant_id' => 'required|is_natural_no_zero',
            'app_id' => 'required|is_natural_no_zero',
            'subscription_id' => 'required|is_natural_no_zero',
            'amount' => 'permit_empty|numeric',
            'currency' => 'permit_empty|max_length[10]',
            'status' => 'permit_empty|in_list[pending,succeeded,failed,refunded,partial_refund]',
            'payment_method' => 'permit_empty|max_length[50]',
            'provider' => 'permit_empty|max_length[20]',
            'stripe_payment_intent_id' => 'permit_empty|max_length[64]',
            'stripe_charge_id' => 'permit_empty|max_length[64]',
            'stripe_invoice_id' => 'permit_empty|max_length[64]',
            'receipt_url' => 'permit_empty|max_length[255]',
            'error_code' => 'permit_empty|max_length[64]',
            'error_message' => 'permit_empty|max_length[255]',
            'paid_at' => 'permit_empty|valid_date',
            'due_at' => 'permit_empty|valid_date',
        ];

        if (! $this->validate($rules)) {
            return $this->respondError('Dados inválidos', 422, $this->validator->getErrors());
        }

        // Validar identidade do tenant
        $tenantId = (int) $input['tenant_id'];
        if ($tenantId !== $tenantIdFromToken) {
            return $this->respondError('Tenant do token não corresponde ao tenant_id enviado', 403);
        }

        $appId = (int) $input['app_id'];
        if ($tokenAppId > 0 && $tokenAppId !== $appId) {
            return $this->respondError('Token não pertence ao app informado', 403);
        }

        $subscriptionId = (int) $input['subscription_id'];

        // Validar assinatura vinculada ao tenant/app
        $subscription = $this->db->table('subscriptions')
            ->where('id', $subscriptionId)
            ->get()
            ->getRowArray();
        if (! $subscription) {
            return $this->respondError('Assinatura não encontrada', 404);
        }
        if ((int) $subscription['tenant_id'] !== $tenantId || (int) $subscription['app_id'] !== $appId) {
            return $this->respondError('Assinatura não pertence ao tenant/app informados', 403);
        }

        // Bloquear pagamento para assinaturas canceladas
        if (($subscription['status'] ?? null) === 'canceled') {
            return $this->respondError('Assinatura cancelada. Crie uma nova para pagar.', 409);
        }

        // Bloquear pagamento para assinaturas incompletas expiradas
        $expiresAt = $subscription['incomplete_expires_at'] ?? null;
        if (($subscription['status'] ?? null) === 'incomplete' && ! empty($expiresAt)) {
            $expiresTs = strtotime($expiresAt);
            if ($expiresTs !== false && $expiresTs <= time()) {
                return $this->respondError('Assinatura expirada. Crie uma nova.', 422);
            }
        }

        $status = $input['status'] ?? 'succeeded';

        $data = [
            'tenant_id' => $tenantId,
            'app_id' => $appId,
            'subscription_id' => $subscriptionId,
            'amount' => isset($input['amount']) ? (float) $input['amount'] : (float) ($subscription['unit_price'] ?? 0.0),
            'currency' => $input['currency'] ?? ($subscription['currency'] ?? 'BRL'),
            'status' => $status,
            'payment_method' => $input['payment_method'] ?? null,
            'provider' => $input['provider'] ?? 'stripe',
            'stripe_payment_intent_id' => $input['stripe_payment_intent_id'] ?? null,
            'stripe_charge_id' => $input['stripe_charge_id'] ?? null,
            'stripe_invoice_id' => $input['stripe_invoice_id'] ?? null,
            'receipt_url' => $input['receipt_url'] ?? null,
            'error_code' => $input['error_code'] ?? null,
            'error_message' => $input['error_message'] ?? null,
            'paid_at' => $input['paid_at'] ?? null,
            'due_at' => $input['due_at'] ?? null,
        ];

        // Ajustes automáticos de datas conforme status
        if (in_array($status, ['succeeded', 'refunded'], true) && empty($data['paid_at'])) {
            $data['paid_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'pending' && empty($data['due_at'])) {
            $data['due_at'] = date('Y-m-d H:i:s', time() + 3 * 86400);
        }

        $model = new PaymentModel();

        // Idempotência simples via stripe_payment_intent_id
        if (! empty($data['stripe_payment_intent_id'])) {
            $existing = $model->where('stripe_payment_intent_id', $data['stripe_payment_intent_id'])->first();
            if ($existing) {
                return $this->respondOk([
                    'payment' => $existing,
                    'idempotent_replay' => true,
                ], 200);
            }
        }

        try {
            $id = $model->insert($data, true);
            $payment = $model->find($id);
            // Ativar assinatura APENAS quando o pagamento for succeeded
            if ($status === 'succeeded') {
                // Calcular período com base no billing_interval do plano
                $periodStart = date('Y-m-d H:i:s');
                $periodEnd = null;
                $plan = null;
                try {
                    $plan = $this->db->table('app_plans')
                        ->select('billing_interval')
                        ->where('id', (int) ($subscription['app_plan_id'] ?? 0))
                        ->get()
                        ->getRowArray();
                } catch (\Throwable $e) {
                    // mantém fallback abaixo
                }
                $interval = (string) ($plan['billing_interval'] ?? '');
                switch ($interval) {
                    case 'monthly':
                        $periodEnd = date('Y-m-d H:i:s', strtotime('+1 month'));
                        break;
                    case 'quarterly':
                        $periodEnd = date('Y-m-d H:i:s', strtotime('+3 months'));
                        break;
                    case 'yearly':
                        $periodEnd = date('Y-m-d H:i:s', strtotime('+1 year'));
                        break;
                    default:
                        // Fallback seguro para casos não mapeados
                        $periodEnd = date('Y-m-d H:i:s', time() + 30 * 86400);
                        break;
                }

                $this->db->table('subscriptions')
                    ->where('id', $subscriptionId)
                    ->update([
                        'status' => 'active',
                        'is_active' => 1,
                        'current_period_start' => $periodStart,
                        'current_period_end' => $periodEnd,
                        'incomplete_expires_at' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }
            return $this->respondOk(['payment' => $payment], 201);
        } catch (\Throwable $e) {
            // Violação de UNIQUE ou outros erros
            return $this->respondError('Falha ao criar pagamento', 500, ['exception' => $e->getMessage()]);
        }
    }
}
