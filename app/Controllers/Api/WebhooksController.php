<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;

class WebhooksController extends BaseController
{
    public function ping()
    {
        log_message('info', 'Stripe webhook ping');
        return $this->response->setStatusCode(200)->setJSON(['ok' => true]);
    }

    public function stripe()
    {
        // 1) Log de entrada do webhook
        log_message('info', 'Stripe webhook called: ' . $this->request->getMethod() . ' ' . (string) $this->request->getUri());

        // 2) Recuperar payload e validar assinatura
        $payload = $this->request->getBody() ?? '';
        $sigHeader = $this->request->getHeaderLine('Stripe-Signature');
        $secret = (string) (env('STRIPE_WEBHOOK_SECRET') ?? getenv('STRIPE_WEBHOOK_SECRET') ?? '');

        if ($secret === '') {
            log_message('error', 'Stripe webhook secret missing in env.');
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Webhook secret not configured']);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            log_message('error', 'Stripe webhook invalid payload: ' . $e->getMessage());
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid payload']);
        } catch (SignatureVerificationException $e) {
            log_message('error', 'Stripe webhook invalid signature: ' . $e->getMessage());
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid signature']);
        }

        // 3) Tratar tipos de eventos
        $eventId = $event->id ?? null;
        $type = $event->type ?? 'unknown';

        switch ($type) {
            case 'customer.subscription.deleted':
                $sub = $event->data->object;
                $stripeSubId = isset($sub->id) ? (string) $sub->id : '';
                $status = 'canceled';
                $tenantId = 0;
                $appId = 0;
                $appPlanId = 0;
                $customerId = isset($sub->customer) ? (string) $sub->customer : '';
                $cancelAtDt = null;
                $canceledAtDt = null;

                // Datas de cancelamento
                if (! empty($sub->cancel_at) && is_int($sub->cancel_at)) {
                    $cancelAtDt = date('Y-m-d H:i:s', (int) $sub->cancel_at);
                }
                if (! empty($sub->canceled_at) && is_int($sub->canceled_at)) {
                    $canceledAtDt = date('Y-m-d H:i:s', (int) $sub->canceled_at);
                }
                if ($canceledAtDt === null && ! empty($event->created) && is_int($event->created)) {
                    $canceledAtDt = date('Y-m-d H:i:s', (int) $event->created);
                }

                // Metadados
                $md = (array) ($sub->metadata ?? []);
                $tenantId = (int) ($md['tenant_id'] ?? 0);
                $appId = (int) ($md['app_id'] ?? 0);
                $appPlanId = (int) ($md['app_plan_id'] ?? 0);

                // Fallback: resolver tenant via customer
                if ($tenantId <= 0 && $customerId !== '') {
                    try {
                        $tenantRow = $this->db->table('tenants')
                            ->select('id')
                            ->where('stripe_customer_id', $customerId)
                            ->get()
                            ->getRowArray();
                        if ($tenantRow) {
                            $tenantId = (int) $tenantRow['id'];
                        }
                    } catch (\Throwable $e) {
                        log_message('warning', 'customer.subscription.deleted: falha ao resolver tenant por customer: ' . $e->getMessage());
                    }
                }

                // Upsert por stripe_subscription_id (preferido) ou tenant/app
                try {
                    $now = date('Y-m-d H:i:s');
                    $subsTable = $this->db->table('subscriptions');
                    $existing = null;
                    if ($stripeSubId !== '') {
                        $existing = $subsTable->where('stripe_subscription_id', $stripeSubId)->orderBy('id', 'DESC')->get()->getRowArray();
                    }
                    if (! $existing && $tenantId > 0 && $appId > 0) {
                        $existing = $subsTable->where('tenant_id', $tenantId)->where('app_id', $appId)->orderBy('id', 'DESC')->get()->getRowArray();
                    }

                    $data = [
                        'tenant_id' => $tenantId ?: ($existing['tenant_id'] ?? null),
                        'app_id' => $appId ?: ($existing['app_id'] ?? null),
                        'app_plan_id' => $appPlanId ?: ($existing['app_plan_id'] ?? null),
                        'status' => $status,
                        'is_active' => 0,
                        'cancel_at' => $cancelAtDt ?: ($existing['cancel_at'] ?? null),
                        'canceled_at' => $canceledAtDt ?: ($existing['canceled_at'] ?? $now),
                        'stripe_subscription_id' => $stripeSubId ?: ($existing['stripe_subscription_id'] ?? null),
                        'updated_at' => $now,
                    ];

                    if ($existing) {
                        $subsTable->where('id', (int) $existing['id'])->update($data);
                        log_message('info', 'Assinatura atualizada via customer.subscription.deleted. tenant_id=' . ($tenantId ?: ($existing['tenant_id'] ?? 0)) . ' app_id=' . ($appId ?: ($existing['app_id'] ?? 0)) . ' stripe_sub=' . $stripeSubId);
                    } else {
                        $data['created_at'] = $now;
                        $subsTable->insert($data);
                        $newId = (int) $this->db->insertID();
                        log_message('info', 'Assinatura criada via customer.subscription.deleted (fallback). tenant_id=' . ($tenantId ?: 0) . ' app_id=' . ($appId ?: 0) . ' sub_id_local=' . $newId . ' stripe_sub=' . $stripeSubId);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao processar customer.subscription.deleted: ' . $e->getMessage());
                }

                break;
            case 'customer.subscription.updated':
                $sub = $event->data->object;
                $stripeSubId = isset($sub->id) ? (string) $sub->id : '';
                $status = (string) ($sub->status ?? 'incomplete');
                $periodStartDt = null;
                $periodEndDt = null;
                $trialEndDt = null;
                $cancelAtDt = null;
                $canceledAtDt = null;
                $quantity = 1;
                $priceId = null;
                $currency = null;
                $unitPrice = null;
                $tenantId = 0;
                $appId = 0;
                $appPlanId = 0;
                $customerId = isset($sub->customer) ? (string) $sub->customer : '';

                // Extrair períodos
                if (! empty($sub->current_period_start)) {
                    $periodStartDt = date('Y-m-d H:i:s', (int) $sub->current_period_start);
                }
                if (! empty($sub->current_period_end)) {
                    $periodEndDt = date('Y-m-d H:i:s', (int) $sub->current_period_end);
                }
                // Itens/preço
                if (isset($sub->items) && isset($sub->items->data[0])) {
                    $item = $sub->items->data[0];
                    $quantity = (int) ($item->quantity ?? $quantity);
                    if (isset($item->price)) {
                        $priceId = (string) ($item->price->id ?? $priceId);
                        $currency = (string) ($item->price->currency ?? $currency);
                        $unit = $item->price->unit_amount ?? null;
                        if (is_int($unit)) {
                            $unitPrice = number_format($unit / 100, 2, '.', '');
                        }
                    }
                }
                // Datas de trial/cancelamento
                if (! empty($sub->trial_end) && is_int($sub->trial_end)) {
                    $trialEndDt = date('Y-m-d H:i:s', (int) $sub->trial_end);
                }
                if (! empty($sub->cancel_at) && is_int($sub->cancel_at)) {
                    $cancelAtDt = date('Y-m-d H:i:s', (int) $sub->cancel_at);
                }
                if (! empty($sub->canceled_at) && is_int($sub->canceled_at)) {
                    $canceledAtDt = date('Y-m-d H:i:s', (int) $sub->canceled_at);
                }
                // Metadados
                $md = (array) ($sub->metadata ?? []);
                $tenantId = (int) ($md['tenant_id'] ?? 0);
                $appId = (int) ($md['app_id'] ?? 0);
                $appPlanId = (int) ($md['app_plan_id'] ?? 0);
                $priceIdMeta = (string) ($md['price_id'] ?? '');
                if ($priceId === null && $priceIdMeta !== '') {
                    $priceId = $priceIdMeta;
                }

                // Fallback: resolver tenant via customer
                if ($tenantId <= 0 && $customerId !== '') {
                    try {
                        $tenantRow = $this->db->table('tenants')
                            ->select('id')
                            ->where('stripe_customer_id', $customerId)
                            ->get()
                            ->getRowArray();
                        if ($tenantRow) {
                            $tenantId = (int) $tenantRow['id'];
                        }
                    } catch (\Throwable $e) {
                        log_message('warning', 'customer.subscription.updated: falha ao resolver tenant por customer: ' . $e->getMessage());
                    }
                }
                // Fallback: app via app_plans por price
                if (($appId <= 0 || $appPlanId <= 0) && is_string($priceId) && $priceId !== '') {
                    try {
                        $planRow = $this->db->table('app_plans')
                            ->select('id, app_id, price_amount, currency, is_active')
                            ->where(['stripe_price_id' => $priceId, 'is_active' => 1])
                            ->get()
                            ->getRowArray();
                        if ($planRow) {
                            $appId = (int) ($planRow['app_id'] ?? $appId);
                            $appPlanId = (int) ($planRow['id'] ?? $appPlanId);
                            if ($unitPrice === null && isset($planRow['price_amount'])) {
                                $unitPrice = (string) $planRow['price_amount'];
                            }
                            if ($currency === null && isset($planRow['currency'])) {
                                $currency = (string) $planRow['currency'];
                            }
                        }
                    } catch (\Throwable $e) {
                        log_message('warning', 'customer.subscription.updated: falha ao consultar app_plans por price: ' . $e->getMessage());
                    }
                }

                // Fallback opcional: buscar assinatura na Stripe para preencher campos ausentes
                if (($periodStartDt === null || $periodEndDt === null || $priceId === null || $currency === null) && $stripeSubId !== '') {
                    $secretKey = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
                    if ($secretKey !== '') {
                        try {
                            $stripe = new StripeClient($secretKey);
                            $subFull = $stripe->subscriptions->retrieve($stripeSubId, ['expand' => ['items.data.price']]);
                            if ($periodStartDt === null && ! empty($subFull->current_period_start)) {
                                $periodStartDt = date('Y-m-d H:i:s', (int) $subFull->current_period_start);
                            }
                            if ($periodEndDt === null && ! empty($subFull->current_period_end)) {
                                $periodEndDt = date('Y-m-d H:i:s', (int) $subFull->current_period_end);
                            }
                            if ($trialEndDt === null && ! empty($subFull->trial_end)) {
                                $trialEndDt = date('Y-m-d H:i:s', (int) $subFull->trial_end);
                            }
                            if ($cancelAtDt === null && ! empty($subFull->cancel_at)) {
                                $cancelAtDt = date('Y-m-d H:i:s', (int) $subFull->cancel_at);
                            }
                            if ($canceledAtDt === null && ! empty($subFull->canceled_at)) {
                                $canceledAtDt = date('Y-m-d H:i:s', (int) $subFull->canceled_at);
                            }
                            if (isset($subFull->items) && isset($subFull->items->data[0]) && isset($subFull->items->data[0]->price)) {
                                $item = $subFull->items->data[0];
                                $priceId = (string) ($item->price->id ?? $priceId);
                                $currency = (string) ($item->price->currency ?? $currency);
                                $unit = $item->price->unit_amount ?? null;
                                if ($unitPrice === null && is_int($unit)) {
                                    $unitPrice = number_format($unit / 100, 2, '.', '');
                                }
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'customer.subscription.updated: falha ao complementar dados via Stripe: ' . $e->getMessage());
                        }
                    } else {
                        log_message('warning', 'customer.subscription.updated: STRIPE_SECRET_KEY ausente; não foi possível complementar dados da assinatura.');
                    }
                }

                // Upsert por stripe_subscription_id (preferido) ou tenant/app
                try {
                    $now = date('Y-m-d H:i:s');
                    $subsTable = $this->db->table('subscriptions');
                    $existing = null;
                    if ($stripeSubId !== '') {
                        $existing = $subsTable->where('stripe_subscription_id', $stripeSubId)->orderBy('id', 'DESC')->get()->getRowArray();
                    }
                    if (! $existing && $tenantId > 0 && $appId > 0) {
                        $existing = $subsTable->where('tenant_id', $tenantId)->where('app_id', $appId)->orderBy('id', 'DESC')->get()->getRowArray();
                    }

                    $data = [
                        'tenant_id' => $tenantId ?: ($existing['tenant_id'] ?? null),
                        'app_id' => $appId ?: ($existing['app_id'] ?? null),
                        'app_plan_id' => $appPlanId ?: ($existing['app_plan_id'] ?? null),
                        'status' => $status,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice ?: ($existing['unit_price'] ?? null),
                        'currency' => $currency ?: ($existing['currency'] ?? null),
                        'current_period_start' => $periodStartDt,
                        'current_period_end' => $periodEndDt,
                        'trial_end_at' => $trialEndDt ?: ($existing['trial_end_at'] ?? null),
                        'cancel_at' => $cancelAtDt ?: ($existing['cancel_at'] ?? null),
                        'canceled_at' => $canceledAtDt ?: ($existing['canceled_at'] ?? null),
                        'stripe_subscription_id' => $stripeSubId ?: ($existing['stripe_subscription_id'] ?? null),
                        'stripe_price_id' => $priceId ?: ($existing['stripe_price_id'] ?? null),
                        'is_active' => in_array($status, ['active', 'trialing', 'past_due', 'incomplete'], true) ? 1 : 0,
                        'incomplete_expires_at' => ($status === 'incomplete' ? ($existing['incomplete_expires_at'] ?? null) : null),
                        'updated_at' => $now,
                    ];

                    if ($existing) {
                        $existingCanceled = (
                            (isset($existing['status']) && $existing['status'] === 'canceled')
                            && (isset($existing['is_active']) && (int) $existing['is_active'] === 0)
                        );
                        if ($existingCanceled) {
                            $data['created_at'] = $now;
                            $subsTable->insert($data);
                            $newId = (int) $this->db->insertID();
                            log_message('info', 'Assinatura criada (nova) pois anterior estava cancelada via customer.subscription.updated. tenant_id=' . ($tenantId ?: ($existing['tenant_id'] ?? 0)) . ' app_id=' . ($appId ?: ($existing['app_id'] ?? 0)) . ' sub_id_local=' . $newId . ' stripe_sub=' . $stripeSubId);
                        } else {
                            $subsTable->where('id', (int) $existing['id'])->update($data);
                            log_message('info', 'Assinatura atualizada via customer.subscription.updated. tenant_id=' . ($tenantId ?: ($existing['tenant_id'] ?? 0)) . ' app_id=' . ($appId ?: ($existing['app_id'] ?? 0)) . ' stripe_sub=' . $stripeSubId);
                        }
                    } else {
                        $data['created_at'] = $now;
                        $subsTable->insert($data);
                        $newId = (int) $this->db->insertID();
                        log_message('info', 'Assinatura criada via customer.subscription.updated. tenant_id=' . ($tenantId ?: 0) . ' app_id=' . ($appId ?: 0) . ' sub_id_local=' . $newId . ' stripe_sub=' . $stripeSubId);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao processar customer.subscription.updated: ' . $e->getMessage());
                }

                break;
            case 'invoice.payment_succeeded':
                $inv = $event->data->object;
                // Log geral do invoice
                log_message(
                    'info',
                    'Stripe invoice.payment_succeeded event=' . ($eventId ?? '')
                        . ' invoice_id=' . ($inv->id ?? '')
                        . ' customer=' . ($inv->customer ?? '')
                        . ' subscription=' . ($inv->subscription ?? '')
                        . ' payment_intent=' . ($inv->payment_intent ?? '')
                        . ' status=' . ($inv->status ?? '')
                        . ' hosted_invoice_url=' . ($inv->hosted_invoice_url ?? '')
                        . ' amount_paid=' . (isset($inv->amount_paid) ? (string) $inv->amount_paid : '')
                );

                // Log dos detalhes da primeira linha, se existir
                try {
                    if (isset($inv->lines) && isset($inv->lines->data[0])) {
                        $line = $inv->lines->data[0];
                        $periodStart = isset($line->period->start) ? (string) $line->period->start : '';
                        $periodEnd = isset($line->period->end) ? (string) $line->period->end : '';
                        $priceIdLine = isset($line->price->id) ? (string) $line->price->id : '';
                        $unitAmount = isset($line->price->unit_amount) ? (string) $line->price->unit_amount : '';
                        $currencyLine = isset($line->price->currency) ? (string) $line->price->currency : '';
                        log_message(
                            'info',
                            'Invoice line: price=' . $priceIdLine
                                . ' unit_amount=' . $unitAmount
                                . ' currency=' . $currencyLine
                                . ' period_start=' . $periodStart
                                . ' period_end=' . $periodEnd
                        );
                    }
                } catch (\Throwable $e) {
                    log_message('warning', 'Falha ao inspecionar linhas da fatura: ' . $e->getMessage());
                }

                // Upsert baseado na assinatura da Stripe (se disponível) ou via fallback customer/price
                try {
                    $stripeSubId = isset($inv->subscription) ? (string) $inv->subscription : '';
                    $tenantId = 0;
                    $appId = 0;
                    $appPlanId = 0;
                    $priceIdMeta = '';
                    $status = 'incomplete';
                    $quantity = 1;
                    $unitPrice = null;
                    $currency = null;
                    $periodStartDt = null;
                    $periodEndDt = null;
                    $priceId = null;
                    $customerId = isset($inv->customer) ? (string) $inv->customer : '';

                    $secretKey = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
                    if ($secretKey === '') {
                        log_message('warning', 'invoice.payment_succeeded: STRIPE_SECRET_KEY ausente; não é possível prosseguir.');
                        break;
                    }

                    $stripe = new StripeClient($secretKey);

                    if ($stripeSubId === '') {
                        // Fallback sem subscription: usar customer -> tenant e price_id -> app_plans
                        try {
                            $invFull = $stripe->invoices->retrieve((string) $inv->id, [
                                'expand' => ['lines.data.price', 'subscription'],
                            ]);
                        } catch (\Throwable $e) {
                            $invFull = null;
                            log_message('warning', 'Falha ao recuperar invoice expandida no fallback: ' . $e->getMessage());
                        }
                        // Price da linha
                        if ($invFull && isset($invFull->lines) && isset($invFull->lines->data[0])) {
                            $line = $invFull->lines->data[0];
                            if (isset($line->price)) {
                                $priceId = (string) ($line->price->id ?? '');
                                $currency = (string) ($line->price->currency ?? $currency);
                                $unit = $line->price->unit_amount ?? null;
                                if (is_int($unit)) {
                                    $unitPrice = number_format($unit / 100, 2, '.', '');
                                }
                            }
                            // Período da linha
                            $ps = $line->period->start ?? null;
                            $pe = $line->period->end ?? null;
                            if (is_int($ps)) {
                                $periodStartDt = date('Y-m-d H:i:s', $ps);
                            }
                            if (is_int($pe)) {
                                $periodEndDt = date('Y-m-d H:i:s', $pe);
                            }
                        }
                        // Resolver tenant via customer
                        if ($tenantId <= 0 && $customerId !== '') {
                            try {
                                $tenantRow = $this->db->table('tenants')
                                    ->select('id')
                                    ->where('stripe_customer_id', $customerId)
                                    ->get()
                                    ->getRowArray();
                                if ($tenantRow) {
                                    $tenantId = (int) $tenantRow['id'];
                                    log_message('info', 'invoice.payment_succeeded fallback: tenant resolvido via stripe_customer_id=' . $customerId . ' tenant_id=' . $tenantId);
                                } else {
                                    log_message('warning', 'invoice.payment_succeeded fallback: nenhum tenant encontrado para stripe_customer_id=' . $customerId);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao consultar tenant por stripe_customer_id: ' . $e->getMessage());
                            }
                        }
                        // Resolver app via app_plans usando price_id
                        if (($appId <= 0 || $appPlanId <= 0) && is_string($priceId) && $priceId !== '') {
                            try {
                                $planRow = $this->db->table('app_plans')
                                    ->select('id, app_id, price_amount, currency, is_active')
                                    ->where(['stripe_price_id' => $priceId, 'is_active' => 1])
                                    ->get()
                                    ->getRowArray();
                                if ($planRow) {
                                    $appId = (int) ($planRow['app_id'] ?? $appId);
                                    $appPlanId = (int) ($planRow['id'] ?? $appPlanId);
                                    if ($unitPrice === null && isset($planRow['price_amount'])) {
                                        $unitPrice = (string) $planRow['price_amount'];
                                    }
                                    if ($currency === null && isset($planRow['currency'])) {
                                        $currency = (string) $planRow['currency'];
                                    }
                                    log_message('info', 'invoice.payment_succeeded fallback: app resolvido via app_plans. app_id=' . $appId . ' app_plan_id=' . $appPlanId . ' price_id=' . $priceId);
                                } else {
                                    log_message('warning', 'invoice.payment_succeeded fallback: nenhum app_plans ativo encontrado para price_id=' . $priceId);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao consultar app_plans por price_id: ' . $e->getMessage());
                            }
                        }
                        // Fallback adicional: obter assinatura mais recente do customer para preencher períodos/metadados
                        if ($stripeSubId === '' && $customerId !== '') {
                            try {
                                $subsList = $stripe->subscriptions->all([
                                    'customer' => $customerId,
                                    'limit' => 1,
                                ]);
                                if (isset($subsList->data) && isset($subsList->data[0])) {
                                    $sub = $subsList->data[0];
                                    $stripeSubId = (string) ($sub->id ?? '');
                                    $status = (string) ($sub->status ?? $status);
                                    if (! empty($sub->current_period_start)) {
                                        $periodStartDt = date('Y-m-d H:i:s', (int) $sub->current_period_start);
                                    }
                                    if (! empty($sub->current_period_end)) {
                                        $periodEndDt = date('Y-m-d H:i:s', (int) $sub->current_period_end);
                                    }
                                    if (isset($sub->items) && isset($sub->items->data[0]) && isset($sub->items->data[0]->price)) {
                                        $item = $sub->items->data[0];
                                        $quantity = (int) ($item->quantity ?? $quantity);
                                        $priceId = (string) ($item->price->id ?? $priceId);
                                        $currency = (string) ($item->price->currency ?? $currency);
                                        $unit = $item->price->unit_amount ?? null;
                                        if (is_int($unit)) {
                                            $unitPrice = number_format($unit / 100, 2, '.', '');
                                        }
                                    }
                                    // Metadados
                                    $md = (array) ($sub->metadata ?? []);
                                    if ($tenantId <= 0 && isset($md['tenant_id'])) {
                                        $tenantId = (int) $md['tenant_id'];
                                    }
                                    if ($appId <= 0 && isset($md['app_id'])) {
                                        $appId = (int) $md['app_id'];
                                    }
                                    if ($appPlanId <= 0 && isset($md['app_plan_id'])) {
                                        $appPlanId = (int) $md['app_plan_id'];
                                    }
                                    if ($priceId === null && isset($md['price_id'])) {
                                        $priceId = (string) $md['price_id'];
                                    }
                                    log_message('info', 'invoice.payment_succeeded fallback: assinatura do customer usada. stripe_sub=' . $stripeSubId . ' status=' . $status . ' tenant_id=' . $tenantId . ' app_id=' . $appId . ' app_plan_id=' . $appPlanId);
                                } else {
                                    log_message('warning', 'invoice.payment_succeeded fallback: nenhuma assinatura encontrada para customer=' . $customerId);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao listar assinaturas por customer: ' . $e->getMessage());
                            }
                        }
                        // Status ativo após pagamento bem-sucedido
                        $status = 'active';
                    } else {
                        // Caminho padrão com subscription disponível
                        $sub = $stripe->subscriptions->retrieve($stripeSubId, [
                            'expand' => ['items.data.price'],
                        ]);
                        $status = (string) ($sub->status ?? $status);
                        if (! empty($sub->current_period_start)) {
                            $periodStartDt = date('Y-m-d H:i:s', (int) $sub->current_period_start);
                        }
                        if (! empty($sub->current_period_end)) {
                            $periodEndDt = date('Y-m-d H:i:s', (int) $sub->current_period_end);
                        }
                        if (isset($sub->items) && isset($sub->items->data[0])) {
                            $item = $sub->items->data[0];
                            $quantity = (int) ($item->quantity ?? $quantity);
                            if (isset($item->price)) {
                                $priceId = (string) ($item->price->id ?? $priceId);
                                $currency = (string) ($item->price->currency ?? $currency);
                                $unit = $item->price->unit_amount ?? null;
                                if (is_int($unit)) {
                                    $unitPrice = number_format($unit / 100, 2, '.', '');
                                }
                            }
                        }
                        $md = (array) ($sub->metadata ?? []);
                        $tenantId = (int) ($md['tenant_id'] ?? 0);
                        $appId = (int) ($md['app_id'] ?? 0);
                        $appPlanId = (int) ($md['app_plan_id'] ?? 0);
                        $priceIdMeta = (string) ($md['price_id'] ?? '');

                        log_message('info', 'Subscription retrieved for invoice: stripe_sub=' . $stripeSubId . ' status=' . $status . ' tenant_id=' . $tenantId . ' app_id=' . $appId . ' app_plan_id=' . $appPlanId . ' price_id_meta=' . $priceIdMeta);
                        // Fallback períodos: se período vier nulo, tentar reconsultar a Subscription e depois invoice.lines
                        if (($periodStartDt === null || $periodEndDt === null) && $stripeSubId !== '') {
                            try {
                                $subCheck = $stripe->subscriptions->retrieve($stripeSubId);
                                $ps = isset($subCheck->current_period_start) && is_int($subCheck->current_period_start) ? (int) $subCheck->current_period_start : null;
                                $pe = isset($subCheck->current_period_end) && is_int($subCheck->current_period_end) ? (int) $subCheck->current_period_end : null;
                                if ($ps !== null) {
                                    $periodStartDt = date('Y-m-d H:i:s', $ps);
                                }
                                if ($pe !== null) {
                                    $periodEndDt = date('Y-m-d H:i:s', $pe);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'invoice.payment_succeeded: reconsulta de Subscription para períodos falhou: ' . $e->getMessage());
                            }
                        }
                        if (($periodStartDt === null || $periodEndDt === null) && isset($inv->id)) {
                            try {
                                $invLines = $stripe->invoices->retrieve((string) $inv->id, ['expand' => ['lines.data']]);
                                if (isset($invLines->lines) && isset($invLines->lines->data[0])) {
                                    $linePeriod = $invLines->lines->data[0]->period ?? null;
                                    $ps = is_object($linePeriod) && isset($linePeriod->start) ? $linePeriod->start : null;
                                    $pe = is_object($linePeriod) && isset($linePeriod->end) ? $linePeriod->end : null;
                                    if (is_int($ps)) {
                                        $periodStartDt = date('Y-m-d H:i:s', $ps);
                                    }
                                    if (is_int($pe)) {
                                        $periodEndDt = date('Y-m-d H:i:s', $pe);
                                    }
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'invoice.payment_succeeded: fallback períodos via invoice.lines falhou: ' . $e->getMessage());
                            }
                        }
                    }

                    if ($tenantId <= 0 || $appId <= 0) {
                        // Fallback: atualizar assinatura existente via stripe_subscription_id para preencher períodos/status
                        if ($stripeSubId !== '') {
                            try {
                                $subsTable = $this->db->table('subscriptions');
                                $existingByStripe = $subsTable
                                    ->where('stripe_subscription_id', $stripeSubId)
                                    ->orderBy('id', 'DESC')
                                    ->get()
                                    ->getRowArray();
                                if ($existingByStripe) {
                                    $now = date('Y-m-d H:i:s');
                                    $dataPartial = [
                                        'status' => $status,
                                        'quantity' => $quantity,
                                        'unit_price' => $unitPrice ?: ($existingByStripe['unit_price'] ?? null),
                                        'currency' => $currency ?: ($existingByStripe['currency'] ?? null),
                                        'current_period_start' => $periodStartDt,
                                        'current_period_end' => $periodEndDt,
                                        'incomplete_expires_at' => null,
                                        'updated_at' => $now,
                                    ];
                                    $subsTable->where('id', (int) $existingByStripe['id'])->update($dataPartial);
                                    // Normalizar tenant/app e sub local a partir do registro encontrado
                                    $localSubId = (int) $existingByStripe['id'];
                                    $tenantId = (int) ($existingByStripe['tenant_id'] ?? $tenantId);
                                    $appId = (int) ($existingByStripe['app_id'] ?? $appId);
                                    log_message('info', 'Assinatura atualizada via invoice.payment_succeeded (fallback por stripe_subscription_id). sub_id_local=' . (int) $existingByStripe['id'] . ' stripe_sub=' . $stripeSubId);
                                } else {
                                    log_message('warning', 'invoice.payment_succeeded fallback: nenhuma assinatura local encontrada por stripe_subscription_id=' . $stripeSubId);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao atualizar assinatura por stripe_subscription_id no fallback: ' . $e->getMessage());
                            }
                        }
                        // Registrar payment mesmo no fallback se conseguimos resolver assinatura/tenant/app
                        try {
                            if (! empty($localSubId) && $tenantId > 0 && $appId > 0) {
                                $amountPaid = isset($inv->amount_paid) && is_int($inv->amount_paid)
                                    ? number_format($inv->amount_paid / 100, 2, '.', '')
                                    : ($unitPrice ?? null);

                                // Backfill via API: obter dados do PaymentIntent (charge/receipt/amount/currency)
                                $piId = isset($inv->payment_intent) ? (string) $inv->payment_intent : '';
                                $receiptUrlCh = null;
                                $chargeIdCh = null;
                                $paidAtCh = null;
                                $amountFromPi = null;
                                $currencyFromPi = null;
                                if ($piId !== '') {
                                    try {
                                        $piFresh = $stripe->paymentIntents->retrieve($piId, ['expand' => ['charges.data']]);
                                        if (isset($piFresh->charges) && isset($piFresh->charges->data[0])) {
                                            $ch = $piFresh->charges->data[0];
                                            $receiptUrlCh = isset($ch->receipt_url) ? (string) $ch->receipt_url : null;
                                            $chargeIdCh = isset($ch->id) ? (string) $ch->id : null;
                                            if (isset($ch->created)) {
                                                $paidAtCh = date('Y-m-d H:i:s', (int) $ch->created);
                                            }
                                        }
                                        if (isset($piFresh->amount) && is_int($piFresh->amount)) {
                                            $amountFromPi = number_format($piFresh->amount / 100, 2, '.', '');
                                        }
                                        if (isset($piFresh->currency) && is_string($piFresh->currency)) {
                                            $currencyFromPi = strtoupper((string) $piFresh->currency);
                                        }
                                    } catch (\Throwable $e) {
                                        log_message('warning', 'invoice.payment_succeeded: falha ao recuperar PaymentIntent para backfill: ' . $e->getMessage());
                                    }
                                }
                                $receiptUrlFinal = $receiptUrlCh ?: (isset($inv->hosted_invoice_url) ? (string) $inv->hosted_invoice_url : null);
                                $chargeIdFinal = $chargeIdCh ?: (isset($inv->charge) ? (string) $inv->charge : null);
                                $amountFinal = $amountPaid !== null ? $amountPaid : $amountFromPi;
                                $currencyFinal = (string) ($inv->currency ?? $currency ?? ($currencyFromPi ?? ''));
                                $paidAtFinal = $paidAtCh ?: date('Y-m-d H:i:s');

                                $payData = [
                                    'tenant_id' => $tenantId,
                                    'app_id' => $appId,
                                    'subscription_id' => (int) $localSubId,
                                    'amount' => $amountFinal !== null ? (float) $amountFinal : null,
                                    'currency' => $currencyFinal,
                                    'status' => 'succeeded',
                                    'payment_method' => null,
                                    'provider' => 'stripe',
                                    'stripe_payment_intent_id' => $piId ?: null,
                                    'stripe_charge_id' => $chargeIdFinal,
                                    'stripe_invoice_id' => isset($inv->id) ? (string) $inv->id : null,
                                    'receipt_url' => $receiptUrlFinal,
                                    'error_code' => null,
                                    'error_message' => null,
                                    'paid_at' => $paidAtFinal,
                                    'due_at' => null,
                                ];

                                // Idempotência: checar por payment_intent, senão por invoice
                                $existsByPi = null;
                                if (! empty($payData['stripe_payment_intent_id'])) {
                                    $existsByPi = $this->db->table('payments')
                                        ->where('stripe_payment_intent_id', $payData['stripe_payment_intent_id'])
                                        ->get()->getRowArray();
                                }
                                if (! $existsByPi) {
                                    $existsByInv = null;
                                    if (! empty($payData['stripe_invoice_id'])) {
                                        $existsByInv = $this->db->table('payments')
                                            ->where('stripe_invoice_id', $payData['stripe_invoice_id'])
                                            ->where('tenant_id', $tenantId)
                                            ->where('app_id', $appId)
                                            ->get()->getRowArray();
                                    }
                                    if (! $existsByInv) {
                                        $this->db->table('payments')->insert($payData);
                                        $paymentId = (int) $this->db->insertID();
                                        log_message('info', 'Payment registrado via invoice.payment_succeeded (fallback). payment_id=' . $paymentId . ' tenant_id=' . $tenantId . ' app_id=' . $appId . ' subscription_id=' . (int) $localSubId . ' invoice=' . ($payData['stripe_invoice_id'] ?? ''));
                                    } else {
                                log_message('info', 'Payment idempotente (invoice já registrada - fallback) invoice=' . ($payData['stripe_invoice_id'] ?? '') . ' tenant_id=' . $tenantId . ' app_id=' . $appId);
                                // Atualizar campos faltantes no registro existente (backfill)
                                try {
                                    $exists = $existsByInv;
                                    $update = [];
                                    if (empty($exists['stripe_charge_id']) && ! empty($chargeIdFinal)) {
                                        $update['stripe_charge_id'] = $chargeIdFinal;
                                    }
                                    if (empty($exists['receipt_url']) && ! empty($receiptUrlFinal)) {
                                        $update['receipt_url'] = $receiptUrlFinal;
                                    }
                                    if ((empty($exists['amount']) || (float) $exists['amount'] === 0.0) && $amountFinal !== null) {
                                        $update['amount'] = (float) $amountFinal;
                                    }
                                    if (empty($exists['currency']) && ! empty($currencyFinal)) {
                                        $update['currency'] = $currencyFinal;
                                    }
                                    if (! empty($update)) {
                                        $update['updated_at'] = date('Y-m-d H:i:s');
                                        $this->db->table('payments')->where('id', (int) $exists['id'])->update($update);
                                        log_message('info', 'Payment idempotente atualizado (fallback) payment_id=' . (int) $exists['id']);
                                    }
                                } catch (\Throwable $e) {
                                    log_message('warning', 'invoice.payment_succeeded: falha ao atualizar payment idempotente (fallback): ' . $e->getMessage());
                                }
                            }
                                } else {
                            log_message('info', 'Payment idempotente (payment_intent já registrado - fallback) pi=' . ($payData['stripe_payment_intent_id'] ?? ''));
                            // Atualizar campos faltantes no registro existente (backfill)
                            try {
                                $exists = $existsByPi;
                                $update = [];
                                if (empty($exists['stripe_charge_id']) && ! empty($chargeIdFinal)) {
                                    $update['stripe_charge_id'] = $chargeIdFinal;
                                }
                                if (empty($exists['receipt_url']) && ! empty($receiptUrlFinal)) {
                                    $update['receipt_url'] = $receiptUrlFinal;
                                }
                                if ((empty($exists['amount']) || (float) $exists['amount'] === 0.0) && $amountFinal !== null) {
                                    $update['amount'] = (float) $amountFinal;
                                }
                                if (empty($exists['currency']) && ! empty($currencyFinal)) {
                                    $update['currency'] = $currencyFinal;
                                }
                                if (! empty($update)) {
                                    $update['updated_at'] = date('Y-m-d H:i:s');
                                    $this->db->table('payments')->where('id', (int) $exists['id'])->update($update);
                                    log_message('info', 'Payment idempotente atualizado (fallback) payment_id=' . (int) $exists['id']);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'invoice.payment_succeeded: falha ao atualizar payment idempotente (fallback): ' . $e->getMessage());
                            }
                        }
                            } else {
                                log_message('info', 'Payment não registrado: assinatura/local tenant/app não resolvidos no fallback.');
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'Falha ao registrar payment no fallback de invoice.payment_succeeded: ' . $e->getMessage());
                        }

                        log_message('warning', 'Metadata de assinatura ausente para tenant/app; não é possível upsert.');
                        break;
                    }

                    $now = date('Y-m-d H:i:s');
                    $subsTable = $this->db->table('subscriptions');
                    $existing = $subsTable
                        ->where('tenant_id', $tenantId)
                        ->where('app_id', $appId)
                        ->orderBy('id', 'DESC')
                        ->get()
                        ->getRowArray();

                    $data = [
                        'tenant_id' => $tenantId,
                        'app_id' => $appId,
                        'app_plan_id' => $appPlanId ?: ($existing['app_plan_id'] ?? null),
                        'status' => $status,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'currency' => $currency,
                        'current_period_start' => $periodStartDt,
                        'current_period_end' => $periodEndDt,
                        'stripe_subscription_id' => $stripeSubId,
                        'stripe_price_id' => ($priceId ?: ($priceIdMeta ?: ($existing['stripe_price_id'] ?? null))),
                        'is_active' => in_array($status, ['active', 'trialing', 'past_due', 'incomplete'], true) ? 1 : 0,
                        'incomplete_expires_at' => null,
                        'updated_at' => $now,
                    ];

                    $localSubId = null;
                    if ($existing) {
                        $existingCanceled = (
                            (isset($existing['status']) && $existing['status'] === 'canceled')
                            && (isset($existing['is_active']) && (int) $existing['is_active'] === 0)
                        );
                        if ($existingCanceled) {
                            $data['created_at'] = $now;
                            $subsTable->insert($data);
                            $newId = (int) $this->db->insertID();
                            $localSubId = $newId;
                            log_message('info', 'Assinatura criada (nova) pois anterior estava cancelada via invoice.payment_succeeded. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub_id_local=' . $newId . ' stripe_sub=' . $stripeSubId);
                        } else {
                            $subsTable->where('id', (int) $existing['id'])->update($data);
                            $localSubId = (int) $existing['id'];
                            log_message('info', 'Assinatura atualizada via invoice.payment_succeeded. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub_id_local=' . (int) $existing['id'] . ' stripe_sub=' . $stripeSubId);
                        }
                    } else {
                        $data['created_at'] = $now;
                        $subsTable->insert($data);
                        $newId = (int) $this->db->insertID();
                        $localSubId = $newId;
                        log_message('info', 'Assinatura criada via invoice.payment_succeeded. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub_id_local=' . $newId . ' stripe_sub=' . $stripeSubId);
                    }

                    // Registrar histórico de pagamento na tabela payments (idempotente via payment_intent/charge)
                    try {
                        if ($localSubId !== null) {
                            $amountPaid = isset($inv->amount_paid) && is_int($inv->amount_paid)
                        ? number_format($inv->amount_paid / 100, 2, '.', '')
                        : ($unitPrice ?? null);

                    // Backfill via API: obter dados do PaymentIntent (charge/receipt/amount/currency)
                    $piId = isset($inv->payment_intent) ? (string) $inv->payment_intent : '';
                    $receiptUrlCh = null;
                    $chargeIdCh = null;
                    $paidAtCh = null;
                    $amountFromPi = null;
                    $currencyFromPi = null;
                    if ($piId !== '') {
                        try {
                            $piFresh = $stripe->paymentIntents->retrieve($piId, ['expand' => ['charges.data']]);
                            if (isset($piFresh->charges) && isset($piFresh->charges->data[0])) {
                                $ch = $piFresh->charges->data[0];
                                $receiptUrlCh = isset($ch->receipt_url) ? (string) $ch->receipt_url : null;
                                $chargeIdCh = isset($ch->id) ? (string) $ch->id : null;
                                if (isset($ch->created)) {
                                    $paidAtCh = date('Y-m-d H:i:s', (int) $ch->created);
                                }
                            }
                            if (isset($piFresh->amount) && is_int($piFresh->amount)) {
                                $amountFromPi = number_format($piFresh->amount / 100, 2, '.', '');
                            }
                            if (isset($piFresh->currency) && is_string($piFresh->currency)) {
                                $currencyFromPi = strtoupper((string) $piFresh->currency);
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'invoice.payment_succeeded: falha ao recuperar PaymentIntent para backfill: ' . $e->getMessage());
                        }
                    }
                    $receiptUrlFinal = $receiptUrlCh ?: (isset($inv->hosted_invoice_url) ? (string) $inv->hosted_invoice_url : null);
                    $chargeIdFinal = $chargeIdCh ?: (isset($inv->charge) ? (string) $inv->charge : null);
                    $amountFinal = $amountPaid !== null ? $amountPaid : $amountFromPi;
                    $currencyFinal = (string) ($inv->currency ?? $currency ?? ($currencyFromPi ?? ''));
                    $paidAtFinal = $paidAtCh ?: date('Y-m-d H:i:s');

                    $payData = [
                        'tenant_id' => $tenantId,
                        'app_id' => $appId,
                        'subscription_id' => (int) $localSubId,
                        'amount' => $amountFinal !== null ? (float) $amountFinal : null,
                        'currency' => $currencyFinal,
                        'status' => 'succeeded',
                        'payment_method' => null,
                        'provider' => 'stripe',
                        'stripe_payment_intent_id' => $piId ?: null,
                        'stripe_charge_id' => $chargeIdFinal,
                        'stripe_invoice_id' => isset($inv->id) ? (string) $inv->id : null,
                        'receipt_url' => $receiptUrlFinal,
                        'error_code' => null,
                        'error_message' => null,
                        'paid_at' => $paidAtFinal,
                        'due_at' => null,
                    ];

                            $existsByPi = null;
                            if (! empty($payData['stripe_payment_intent_id'])) {
                                $existsByPi = $this->db->table('payments')
                                    ->where('stripe_payment_intent_id', $payData['stripe_payment_intent_id'])
                                    ->get()->getRowArray();
                            }
                            if (! $existsByPi) {
                                // Checagem secundária por invoice para evitar duplicidade em replays
                                $existsByInv = null;
                                if (! empty($payData['stripe_invoice_id'])) {
                                    $existsByInv = $this->db->table('payments')
                                        ->where('stripe_invoice_id', $payData['stripe_invoice_id'])
                                        ->where('tenant_id', $tenantId)
                                        ->where('app_id', $appId)
                                        ->get()->getRowArray();
                                }
                                if (! $existsByInv) {
                                    $this->db->table('payments')->insert($payData);
                                    $paymentId = (int) $this->db->insertID();
                                    log_message('info', 'Payment registrado via invoice.payment_succeeded. payment_id=' . $paymentId . ' tenant_id=' . $tenantId . ' app_id=' . $appId . ' subscription_id=' . (int) $localSubId . ' invoice=' . ($payData['stripe_invoice_id'] ?? ''));
                                } else {
                            log_message('info', 'Payment idempotente (invoice já registrada) invoice=' . ($payData['stripe_invoice_id'] ?? '') . ' tenant_id=' . $tenantId . ' app_id=' . $appId);
                            // Atualizar campos faltantes no registro existente (backfill)
                            try {
                                $exists = $existsByInv;
                                $update = [];
                                if (empty($exists['stripe_charge_id']) && ! empty($chargeIdFinal)) {
                                    $update['stripe_charge_id'] = $chargeIdFinal;
                                }
                                if (empty($exists['receipt_url']) && ! empty($receiptUrlFinal)) {
                                    $update['receipt_url'] = $receiptUrlFinal;
                                }
                                if ((empty($exists['amount']) || (float) $exists['amount'] === 0.0) && $amountFinal !== null) {
                                    $update['amount'] = (float) $amountFinal;
                                }
                                if (empty($exists['currency']) && ! empty($currencyFinal)) {
                                    $update['currency'] = $currencyFinal;
                                }
                                if (! empty($update)) {
                                    $update['updated_at'] = date('Y-m-d H:i:s');
                                    $this->db->table('payments')->where('id', (int) $exists['id'])->update($update);
                                    log_message('info', 'Payment idempotente atualizado payment_id=' . (int) $exists['id']);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'invoice.payment_succeeded: falha ao atualizar payment idempotente: ' . $e->getMessage());
                            }
                        }
                            } else {
                        log_message('info', 'Payment idempotente (payment_intent já registrado) pi=' . ($payData['stripe_payment_intent_id'] ?? ''));
                        // Atualizar campos faltantes no registro existente (backfill)
                        try {
                            $exists = $existsByPi;
                            $update = [];
                            if (empty($exists['stripe_charge_id']) && ! empty($chargeIdFinal)) {
                                $update['stripe_charge_id'] = $chargeIdFinal;
                            }
                            if (empty($exists['receipt_url']) && ! empty($receiptUrlFinal)) {
                                $update['receipt_url'] = $receiptUrlFinal;
                            }
                            if ((empty($exists['amount']) || (float) $exists['amount'] === 0.0) && $amountFinal !== null) {
                                $update['amount'] = (float) $amountFinal;
                            }
                            if (empty($exists['currency']) && ! empty($currencyFinal)) {
                                $update['currency'] = $currencyFinal;
                            }
                            if (! empty($update)) {
                                $update['updated_at'] = date('Y-m-d H:i:s');
                                $this->db->table('payments')->where('id', (int) $exists['id'])->update($update);
                                log_message('info', 'Payment idempotente atualizado payment_id=' . (int) $exists['id']);
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'invoice.payment_succeeded: falha ao atualizar payment idempotente: ' . $e->getMessage());
                        }
                    }
                        }
                    } catch (\Throwable $e) {
                        log_message('warning', 'Falha ao registrar payment no invoice.payment_succeeded: ' . $e->getMessage());
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao processar invoice.payment_succeeded: ' . $e->getMessage());
                }

                break;
            case 'invoice.payment_failed':
                $inv = $event->data->object;
                log_message(
                    'info',
                    'Stripe invoice.payment_failed event=' . ($eventId ?? '')
                        . ' invoice_id=' . ($inv->id ?? '')
                        . ' customer=' . ($inv->customer ?? '')
                        . ' subscription=' . ($inv->subscription ?? '')
                        . ' payment_intent=' . ($inv->payment_intent ?? '')
                        . ' status=' . ($inv->status ?? '')
                        . ' hosted_invoice_url=' . ($inv->hosted_invoice_url ?? '')
                );

                try {
                    $collectionMethod = isset($inv->collection_method) ? (string) $inv->collection_method : '';
                    $nextAttempt = isset($inv->next_payment_attempt) && is_int($inv->next_payment_attempt) ? (int) $inv->next_payment_attempt : null;
                    $statusTarget = (
                        $collectionMethod === 'charge_automatically' && ($nextAttempt === null || $nextAttempt === 0)
                    ) ? 'unpaid' : 'past_due';
                    $stripeSubId = isset($inv->subscription) ? (string) $inv->subscription : '';
                    $tenantId = 0;
                    $appId = 0;
                    $appPlanId = 0;
                    $priceIdMeta = '';
                    $status = 'past_due';
                    $quantity = 1;
                    $unitPrice = null;
                    $currency = null;
                    $periodStartDt = null;
                    $periodEndDt = null;
                    $priceId = null;
                    $customerId = isset($inv->customer) ? (string) $inv->customer : '';

                    $secretKey = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
                    if ($secretKey === '') {
                        log_message('warning', 'invoice.payment_failed: STRIPE_SECRET_KEY ausente; não é possível prosseguir.');
                        break;
                    }

                    $stripe = new \Stripe\StripeClient($secretKey);

                    if ($stripeSubId === '') {
                        // Fallback: recuperar invoice expandida para price/period
                        try {
                            $invFull = $stripe->invoices->retrieve((string) $inv->id, [
                                'expand' => ['lines.data.price', 'subscription'],
                            ]);
                        } catch (\Throwable $e) {
                            $invFull = null;
                            log_message('warning', 'Falha ao recuperar invoice expandida (failed): ' . $e->getMessage());
                        }
                        if ($invFull && isset($invFull->lines) && isset($invFull->lines->data[0])) {
                            $line = $invFull->lines->data[0];
                            if (isset($line->price)) {
                                $priceId = (string) ($line->price->id ?? '');
                                $currency = (string) ($line->price->currency ?? $currency);
                                $unit = $line->price->unit_amount ?? null;
                                if (is_int($unit)) {
                                    $unitPrice = number_format($unit / 100, 2, '.', '');
                                }
                            }
                            $ps = $line->period->start ?? null;
                            $pe = $line->period->end ?? null;
                            if (is_int($ps)) {
                                $periodStartDt = date('Y-m-d H:i:s', $ps);
                            }
                            if (is_int($pe)) {
                                $periodEndDt = date('Y-m-d H:i:s', $pe);
                            }
                        }
                        // Resolver tenant via customer
                        if ($tenantId <= 0 && $customerId !== '') {
                            try {
                                $tenantRow = $this->db->table('tenants')
                                    ->select('id')
                                    ->where('stripe_customer_id', $customerId)
                                    ->get()->getRowArray();
                                if ($tenantRow) {
                                    $tenantId = (int) $tenantRow['id'];
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'invoice.payment_failed: falha ao resolver tenant por customer: ' . $e->getMessage());
                            }
                        }
                        // Resolver app via app_plans usando price_id
                        if (($appId <= 0 || $appPlanId <= 0) && is_string($priceId) && $priceId !== '') {
                            try {
                                $planRow = $this->db->table('app_plans')
                                    ->select('id, app_id, price_amount, currency, is_active')
                                    ->where(['stripe_price_id' => $priceId, 'is_active' => 1])
                                    ->get()->getRowArray();
                                if ($planRow) {
                                    $appId = (int) ($planRow['app_id'] ?? $appId);
                                    $appPlanId = (int) ($planRow['id'] ?? $appPlanId);
                                    if ($unitPrice === null && isset($planRow['price_amount'])) {
                                        $unitPrice = (string) $planRow['price_amount'];
                                    }
                                    if ($currency === null && isset($planRow['currency'])) {
                                        $currency = (string) $planRow['currency'];
                                    }
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao consultar app_plans por price_id (failed): ' . $e->getMessage());
                            }
                        }
                    } else {
                        // Caminho padrão com subscription disponível
                        try {
                            $sub = $stripe->subscriptions->retrieve($stripeSubId, ['expand' => ['items.data.price']]);
                            if (! empty($sub->current_period_start)) {
                                $periodStartDt = date('Y-m-d H:i:s', (int) $sub->current_period_start);
                            }
                            if (! empty($sub->current_period_end)) {
                                $periodEndDt = date('Y-m-d H:i:s', (int) $sub->current_period_end);
                            }
                            if (isset($sub->items) && isset($sub->items->data[0])) {
                                $item = $sub->items->data[0];
                                $quantity = (int) ($item->quantity ?? $quantity);
                                if (isset($item->price)) {
                                    $priceId = (string) ($item->price->id ?? $priceId);
                                    $currency = (string) ($item->price->currency ?? $currency);
                                    $unit = $item->price->unit_amount ?? null;
                                    if (is_int($unit)) {
                                        $unitPrice = number_format($unit / 100, 2, '.', '');
                                    }
                                }
                            }
                            $md = (array) ($sub->metadata ?? []);
                            $tenantId = (int) ($md['tenant_id'] ?? 0);
                            $appId = (int) ($md['app_id'] ?? 0);
                            $appPlanId = (int) ($md['app_plan_id'] ?? 0);
                            $priceIdMeta = (string) ($md['price_id'] ?? '');
                            if ($priceId === null && $priceIdMeta !== '') {
                                $priceId = $priceIdMeta;
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'Falha ao recuperar assinatura para invoice.payment_failed: ' . $e->getMessage());
                        }
                    }

                    // Se não conseguimos tenant/app, tentar atualizar por stripe_subscription_id e registrar pagamento
                    $now = date('Y-m-d H:i:s');
                    $localSubId = null;

                    if ($tenantId <= 0 || $appId <= 0) {
                        if ($stripeSubId !== '') {
                            try {
                                $subsTable = $this->db->table('subscriptions');
                                $existingByStripe = $subsTable
                                    ->where('stripe_subscription_id', $stripeSubId)
                                    ->orderBy('id', 'DESC')
                                    ->get()->getRowArray();
                                if ($existingByStripe) {
                                    $subsTable->where('id', (int) $existingByStripe['id'])->update([
                                        'status' => $statusTarget,
                                        'current_period_start' => $periodStartDt ?: ($existingByStripe['current_period_start'] ?? null),
                                        'current_period_end' => $periodEndDt ?: ($existingByStripe['current_period_end'] ?? null),
                                        'is_active' => in_array($statusTarget, ['active', 'trialing', 'past_due', 'incomplete'], true) ? 1 : 0,
                                        'updated_at' => $now,
                                    ]);
                                    $localSubId = (int) $existingByStripe['id'];
                                    $tenantId = (int) ($existingByStripe['tenant_id'] ?? $tenantId);
                                    $appId = (int) ($existingByStripe['app_id'] ?? $appId);
                                    log_message('info', 'Assinatura marcada ' . $statusTarget . ' via invoice.payment_failed (fallback). sub_id_local=' . $localSubId . ' stripe_sub=' . $stripeSubId);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao atualizar assinatura past_due (fallback): ' . $e->getMessage());
                            }
                        }

                        // Registrar pagamento failed se conseguimos resolver assinatura/tenant/app
                        try {
                            if (! empty($localSubId) && $tenantId > 0 && $appId > 0) {
                                $errorCode = null;
                                $errorMessage = null;
                                try {
                                    $piId = isset($inv->payment_intent) ? (string) $inv->payment_intent : '';
                                    if ($piId !== '') {
                                        $pi = $stripe->paymentIntents->retrieve($piId);
                                        if (isset($pi->last_payment_error)) {
                                            $errorCode = (string) ($pi->last_payment_error->code ?? '');
                                            $errorMessage = (string) ($pi->last_payment_error->message ?? '');
                                        }
                                    }
                                } catch (\Throwable $e) {
                                    log_message('warning', 'Falha ao obter last_payment_error (fallback): ' . $e->getMessage());
                                }

                                $payData = [
                                    'tenant_id' => $tenantId,
                                    'app_id' => $appId,
                                    'subscription_id' => (int) $localSubId,
                                    'amount' => isset($inv->amount_due) && is_int($inv->amount_due) ? (float) number_format($inv->amount_due / 100, 2, '.', '') : ($unitPrice !== null ? (float) $unitPrice : null),
                                    'currency' => (string) ($inv->currency ?? $currency ?? ''),
                                    'status' => 'failed',
                                    'payment_method' => null,
                                    'provider' => 'stripe',
                                    'stripe_payment_intent_id' => isset($inv->payment_intent) ? (string) $inv->payment_intent : null,
                                    'stripe_charge_id' => isset($inv->charge) ? (string) $inv->charge : null,
                                    'stripe_invoice_id' => isset($inv->id) ? (string) $inv->id : null,
                                    'receipt_url' => isset($inv->hosted_invoice_url) ? (string) $inv->hosted_invoice_url : null,
                                    'error_code' => $errorCode,
                                    'error_message' => $errorMessage,
                                    'paid_at' => null,
                                    'due_at' => null,
                                ];

                                $existsByPi = null;
                                if (! empty($payData['stripe_payment_intent_id'])) {
                                    $existsByPi = $this->db->table('payments')
                                        ->where('stripe_payment_intent_id', $payData['stripe_payment_intent_id'])
                                        ->get()->getRowArray();
                                }
                                if (! $existsByPi) {
                                    $existsByInv = null;
                                    if (! empty($payData['stripe_invoice_id'])) {
                                        $existsByInv = $this->db->table('payments')
                                            ->where('stripe_invoice_id', $payData['stripe_invoice_id'])
                                            ->where('tenant_id', $tenantId)
                                            ->where('app_id', $appId)
                                            ->get()->getRowArray();
                                    }
                                    if (! $existsByInv) {
                                        $this->db->table('payments')->insert($payData);
                                        $paymentId = (int) $this->db->insertID();
                                        log_message('info', 'Payment failed registrado (fallback). payment_id=' . $paymentId . ' tenant_id=' . $tenantId . ' app_id=' . $appId . ' subscription_id=' . (int) $localSubId);
                                    } else {
                                        log_message('info', 'Payment failed idempotente (invoice já registrada - fallback) invoice=' . ($payData['stripe_invoice_id'] ?? ''));
                                    }
                                } else {
                                    log_message('info', 'Payment failed idempotente (payment_intent já registrado - fallback) pi=' . ($payData['stripe_payment_intent_id'] ?? ''));
                                }
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'Falha ao registrar payment failed (fallback): ' . $e->getMessage());
                        }

                        break;
                    }

                    // Caminho normal: upsert da assinatura e registro de pagamento failed
                    $subsTable = $this->db->table('subscriptions');
                    $existing = $subsTable
                        ->where('tenant_id', $tenantId)
                        ->where('app_id', $appId)
                        ->orderBy('id', 'DESC')
                        ->get()->getRowArray();

                    $data = [
                        'tenant_id' => $tenantId,
                        'app_id' => $appId,
                        'app_plan_id' => $appPlanId ?: ($existing['app_plan_id'] ?? null),
                        'status' => $statusTarget,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice ?: ($existing['unit_price'] ?? null),
                        'currency' => $currency ?: ($existing['currency'] ?? null),
                        'current_period_start' => $periodStartDt ?: ($existing['current_period_start'] ?? null),
                        'current_period_end' => $periodEndDt ?: ($existing['current_period_end'] ?? null),
                        'stripe_subscription_id' => $stripeSubId ?: ($existing['stripe_subscription_id'] ?? null),
                        'stripe_price_id' => ($priceId ?: ($priceIdMeta ?: ($existing['stripe_price_id'] ?? null))),
                        'is_active' => in_array($statusTarget, ['active', 'trialing', 'past_due', 'incomplete'], true) ? 1 : 0,
                        'updated_at' => $now,
                    ];

                    if ($existing) {
                        $existingCanceled = (
                            (isset($existing['status']) && $existing['status'] === 'canceled')
                            && (isset($existing['is_active']) && (int) $existing['is_active'] === 0)
                        );
                        if ($existingCanceled) {
                            $data['created_at'] = $now;
                            $subsTable->insert($data);
                            $localSubId = (int) $this->db->insertID();
                            log_message('info', 'Assinatura criada (nova) pois anterior estava cancelada via invoice.payment_failed. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub_id_local=' . $localSubId);
                        } else {
                            $subsTable->where('id', (int) $existing['id'])->update($data);
                            $localSubId = (int) $existing['id'];
                            log_message('info', 'Assinatura atualizada para past_due via invoice.payment_failed. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub_id_local=' . (int) $existing['id']);
                        }
                    } else {
                        $data['created_at'] = $now;
                        $subsTable->insert($data);
                        $localSubId = (int) $this->db->insertID();
                        log_message('info', 'Assinatura criada via invoice.payment_failed. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub_id_local=' . $localSubId);
                    }

                    // Registrar payment failed (idempotente)
                    try {
                        if ($localSubId !== null) {
                            $errorCode = null;
                            $errorMessage = null;
                            try {
                                $piId = isset($inv->payment_intent) ? (string) $inv->payment_intent : '';
                                if ($piId !== '') {
                                    $pi = $stripe->paymentIntents->retrieve($piId);
                                    if (isset($pi->last_payment_error)) {
                                        $errorCode = (string) ($pi->last_payment_error->code ?? '');
                                        $errorMessage = (string) ($pi->last_payment_error->message ?? '');
                                    }
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao obter last_payment_error: ' . $e->getMessage());
                            }

                            $payData = [
                                'tenant_id' => $tenantId,
                                'app_id' => $appId,
                                'subscription_id' => (int) $localSubId,
                                'amount' => isset($inv->amount_due) && is_int($inv->amount_due) ? (float) number_format($inv->amount_due / 100, 2, '.', '') : ($unitPrice !== null ? (float) $unitPrice : null),
                                'currency' => (string) ($inv->currency ?? $currency ?? ''),
                                'status' => 'failed',
                                'payment_method' => null,
                                'provider' => 'stripe',
                                'stripe_payment_intent_id' => isset($inv->payment_intent) ? (string) $inv->payment_intent : null,
                                'stripe_charge_id' => isset($inv->charge) ? (string) $inv->charge : null,
                                'stripe_invoice_id' => isset($inv->id) ? (string) $inv->id : null,
                                'receipt_url' => isset($inv->hosted_invoice_url) ? (string) $inv->hosted_invoice_url : null,
                                'error_code' => $errorCode,
                                'error_message' => $errorMessage,
                                'paid_at' => null,
                                'due_at' => null,
                            ];

                            $existsByPi = null;
                            if (! empty($payData['stripe_payment_intent_id'])) {
                                $existsByPi = $this->db->table('payments')
                                    ->where('stripe_payment_intent_id', $payData['stripe_payment_intent_id'])
                                    ->get()->getRowArray();
                            }
                            if (! $existsByPi) {
                                $existsByInv = null;
                                if (! empty($payData['stripe_invoice_id'])) {
                                    $existsByInv = $this->db->table('payments')
                                        ->where('stripe_invoice_id', $payData['stripe_invoice_id'])
                                        ->where('tenant_id', $tenantId)
                                        ->where('app_id', $appId)
                                        ->get()->getRowArray();
                                }
                                if (! $existsByInv) {
                                    $this->db->table('payments')->insert($payData);
                                    $paymentId = (int) $this->db->insertID();
                                    log_message('info', 'Payment failed registrado. payment_id=' . $paymentId . ' tenant_id=' . $tenantId . ' app_id=' . $appId . ' subscription_id=' . (int) $localSubId . ' invoice=' . ($payData['stripe_invoice_id'] ?? ''));
                                } else {
                                    log_message('info', 'Payment failed idempotente (invoice já registrada) invoice=' . ($payData['stripe_invoice_id'] ?? '') . ' tenant_id=' . $tenantId . ' app_id=' . $appId);
                                }
                            } else {
                                log_message('info', 'Payment failed idempotente (payment_intent já registrado) pi=' . ($payData['stripe_payment_intent_id'] ?? ''));
                            }
                        }
                    } catch (\Throwable $e) {
                        log_message('warning', 'Falha ao registrar payment failed: ' . $e->getMessage());
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao processar invoice.payment_failed: ' . $e->getMessage());
                }

                break;
            case 'payment_intent.succeeded':
                $pi = $event->data->object;
                log_message(
                    'info',
                    'Stripe payment_intent.succeeded event=' . ($eventId ?? '')
                        . ' pi=' . ($pi->id ?? '')
                        . ' customer=' . ($pi->customer ?? '')
                );
                try {
                    $piId = isset($pi->id) ? (string) $pi->id : '';
                    $customerId = isset($pi->customer) ? (string) $pi->customer : '';
                    $metaRaw = $pi->metadata ?? null;
                    $meta = [];
                    try {
                        if (is_array($metaRaw)) {
                            $meta = $metaRaw;
                        } elseif (is_object($metaRaw) && method_exists($metaRaw, 'toArray')) {
                            $meta = $metaRaw->toArray();
                        } elseif (is_object($metaRaw) && $metaRaw instanceof \ArrayAccess) {
                            $meta = [
                                'tenant_id' => isset($metaRaw['tenant_id']) ? (string) $metaRaw['tenant_id'] : null,
                                'app_id' => isset($metaRaw['app_id']) ? (string) $metaRaw['app_id'] : null,
                                'app_plan_id' => isset($metaRaw['app_plan_id']) ? (string) $metaRaw['app_plan_id'] : null,
                                'price_id' => isset($metaRaw['price_id']) ? (string) $metaRaw['price_id'] : null,
                            ];
                        }
                    } catch (\Throwable $e) {
                        log_message('warning', 'payment_intent.succeeded: falha ao converter metadata: ' . $e->getMessage());
                    }
                    $tenantId = (int) ($meta['tenant_id'] ?? 0);
                    $appId = (int) ($meta['app_id'] ?? 0);
                    $appPlanId = (int) ($meta['app_plan_id'] ?? 0);
                    $priceId = (string) ($meta['price_id'] ?? '');
                    $amount = null;
                    $currency = null;

                    if (isset($pi->amount) && is_int($pi->amount)) {
                        $amount = number_format($pi->amount / 100, 2, '.', '');
                    }
                    if (isset($pi->currency) && is_string($pi->currency)) {
                        $currency = strtoupper((string) $pi->currency);
                    }

                    // Fallback tenant via customer id
                    if ($tenantId <= 0 && $customerId !== '') {
                        try {
                            $tenantRow = $this->db->table('tenants')
                                ->select('id')
                                ->where('stripe_customer_id', $customerId)
                                ->get()
                                ->getRowArray();
                            if ($tenantRow) {
                                $tenantId = (int) $tenantRow['id'];
                                log_message('info', 'payment_intent.succeeded fallback: tenant via stripe_customer_id=' . $customerId . ' tenant_id=' . $tenantId);
                            } else {
                                log_message('warning', 'payment_intent.succeeded fallback: nenhum tenant para stripe_customer_id=' . $customerId);
                            }
                        } catch (
                            \Throwable $e
                        ) {
                            log_message('warning', 'payment_intent.succeeded fallback: falha ao consultar tenant por customer: ' . $e->getMessage());
                        }
                    }

                    // Fallback app via app_plans usando price_id
                    if (($appId <= 0 || $appPlanId <= 0) && $priceId !== '') {
                        try {
                            $planRow = $this->db->table('app_plans')
                                ->select('id, app_id, price_amount, currency, is_active')
                                ->where(['stripe_price_id' => $priceId, 'is_active' => 1])
                                ->get()
                                ->getRowArray();
                            if ($planRow) {
                                $appId = (int) ($planRow['app_id'] ?? $appId);
                                $appPlanId = (int) ($planRow['id'] ?? $appPlanId);
                                if ($amount === null && isset($planRow['price_amount'])) {
                                    $amount = (string) $planRow['price_amount'];
                                }
                                if ($currency === null && isset($planRow['currency'])) {
                                    $currency = (string) $planRow['currency'];
                                }
                                log_message('info', 'payment_intent.succeeded fallback: app via app_plans. app_id=' . $appId . ' app_plan_id=' . $appPlanId . ' price_id=' . $priceId);
                            } else {
                                log_message('warning', 'payment_intent.succeeded fallback: nenhum app_plans ativo para price_id=' . $priceId);
                            }
                        } catch (
                            \Throwable $e
                        ) {
                            log_message('warning', 'payment_intent.succeeded: falha ao consultar app_plans por price: ' . $e->getMessage());
                        }
                    }

                    if ($tenantId <= 0 || $appId <= 0) {
                        $mdDebugStr = null;
                        try {
                            if (is_array($meta)) {
                                $mdDebugStr = json_encode($meta);
                            } elseif (is_object($metaRaw) && method_exists($metaRaw, 'toArray')) {
                                $mdDebugStr = json_encode($metaRaw->toArray());
                            } else {
                                $mdDebugStr = is_object($metaRaw) ? ('object:' . get_class($metaRaw)) : 'null';
                            }
                        } catch (\Throwable $e) {
                            $mdDebugStr = 'unavailable: ' . $e->getMessage();
                        }
                        log_message('warning', 'payment_intent.succeeded metadados insuficientes. tenant_id=' . $tenantId . ' app_id=' . $appId . ' customer=' . $customerId . ' metadata=' . $mdDebugStr);
                        break;
                    }

                    // Charge/receipt
                    $receiptUrl = null;
                    $chargeId = null;
                    $paidAt = null;
                    if (isset($pi->charges) && isset($pi->charges->data[0])) {
                        try {
                            $ch = $pi->charges->data[0];
                            if (isset($ch->receipt_url)) {
                                $receiptUrl = (string) $ch->receipt_url;
                            }
                            if (isset($ch->id)) {
                                $chargeId = (string) $ch->id;
                            }
                            if (isset($ch->created)) {
                                $paidAt = date('Y-m-d H:i:s', (int) $ch->created);
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'payment_intent.succeeded: falha ao extrair charge: ' . $e->getMessage());
                        }
                    }

                    // Se faltarem detalhes, recuperar PI com expand para obter charge/receipt e valor/moeda
                    if (($receiptUrl === null || $chargeId === null || $amount === null || $currency === null) && $piId !== '') {
                        $secretKey = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
                        if ($secretKey !== '') {
                            try {
                                $stripe = new \Stripe\StripeClient($secretKey);
                                $piFresh = $stripe->paymentIntents->retrieve($piId, ['expand' => ['charges.data']]);
                                if (isset($piFresh->charges) && isset($piFresh->charges->data[0])) {
                                    $ch = $piFresh->charges->data[0];
                                    if ($receiptUrl === null && isset($ch->receipt_url)) {
                                        $receiptUrl = (string) $ch->receipt_url;
                                    }
                                    if ($chargeId === null && isset($ch->id)) {
                                        $chargeId = (string) $ch->id;
                                    }
                                    if ($paidAt === null && isset($ch->created)) {
                                        $paidAt = date('Y-m-d H:i:s', (int) $ch->created);
                                    }
                                }
                                if ($amount === null && isset($piFresh->amount) && is_int($piFresh->amount)) {
                                    $amount = number_format($piFresh->amount / 100, 2, '.', '');
                                }
                                if ($currency === null && isset($piFresh->currency) && is_string($piFresh->currency)) {
                                    $currency = strtoupper((string) $piFresh->currency);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'payment_intent.succeeded: falha ao recuperar PI para charge/receipt: ' . $e->getMessage());
                            }
                        }
                    }

                    $now = date('Y-m-d H:i:s');
                    $payData = [
                        'tenant_id' => $tenantId,
                        'app_id' => $appId,
                        'subscription_id' => null,
                        'amount' => $amount !== null ? (float) $amount : null,
                        'currency' => (string) ($currency ?? ''),
                        'status' => 'succeeded',
                        'payment_method' => null,
                        'provider' => 'stripe',
                        'stripe_payment_intent_id' => $piId ?: null,
                        'stripe_charge_id' => $chargeId ?: null,
                        'stripe_invoice_id' => null,
                        'receipt_url' => $receiptUrl ?: null,
                        'error_code' => null,
                        'error_message' => null,
                        'paid_at' => $paidAt ?? $now,
                        'due_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Idempotência
                    $exists = null;
                    if (! empty($payData['stripe_payment_intent_id'])) {
                        $exists = $this->db->table('payments')
                            ->where('stripe_payment_intent_id', $payData['stripe_payment_intent_id'])
                            ->get()->getRowArray();
                    }
                    if (! $exists && ! empty($payData['stripe_charge_id'])) {
                        $exists = $this->db->table('payments')
                            ->where('stripe_charge_id', $payData['stripe_charge_id'])
                            ->get()->getRowArray();
                    }
                    if (! $exists) {
                        $this->db->table('payments')->insert($payData);
                        $paymentId = (int) $this->db->insertID();
                        log_message('info', 'Payment registrado via payment_intent.succeeded (one_time). payment_id=' . $paymentId . ' tenant_id=' . $tenantId . ' app_id=' . $appId . ' pi=' . ($payData['stripe_payment_intent_id'] ?? '') . ' charge=' . ($payData['stripe_charge_id'] ?? ''));
                    } else {
                            // Atualizar registro idempotente com campos ausentes, se disponíveis agora
                            try {
                                $update = [];
                                if (empty($exists['stripe_charge_id']) && ! empty($chargeId)) { $update['stripe_charge_id'] = $chargeId; }
                                if (empty($exists['receipt_url']) && ! empty($receiptUrl)) { $update['receipt_url'] = $receiptUrl; }
                                if ((empty($exists['amount']) || (float)$exists['amount'] === 0.0) && $amount !== null) { $update['amount'] = (float) $amount; }
                                if (empty($exists['currency']) && ! empty($currency)) { $update['currency'] = $currency; }
                                if (! empty($update)) {
                                    $update['updated_at'] = date('Y-m-d H:i:s');
                                    $this->db->table('payments')->where('id', (int) $exists['id'])->update($update);
                                    log_message('info', 'Payment idempotente atualizado via payment_intent.succeeded payment_id=' . (int) $exists['id']);
                                } else {
                                    log_message('info', 'Payment idempotente via payment_intent.succeeded pi=' . ($payData['stripe_payment_intent_id'] ?? '') . ' charge=' . ($payData['stripe_charge_id'] ?? ''));
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'payment_intent.succeeded: falha ao atualizar payment idempotente: ' . $e->getMessage());
                            }
                        }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao processar payment_intent.succeeded: ' . $e->getMessage());
                }

                break;
            case 'checkout.session.completed':
                $cs = $event->data->object;
                log_message(
                    'info',
                    'Stripe checkout.session.completed event=' . ($eventId ?? '')
                        . ' session=' . ($cs->id ?? '')
                        . ' mode=' . ($cs->mode ?? '')
                        . ' customer=' . ($cs->customer ?? '')
                        . ' subscription=' . ($cs->subscription ?? '')
                        . ' client_reference_id=' . ($cs->client_reference_id ?? '')
                );

                try {
                    // 3.1) Extrair tenant_id do client_reference_id (preferido) ou dos metadados
                    $clientRef = isset($cs->client_reference_id) ? (string) $cs->client_reference_id : '';
                    $meta = (array) ($cs->metadata ?? []);
                    $tenantId = is_numeric($clientRef) ? (int) $clientRef : (int) ($meta['tenant_id'] ?? 0);
                    $customerId = isset($cs->customer) ? (string) $cs->customer : '';

                    if ($tenantId > 0 && $customerId !== '') {
                        // 3.2) Mapear stripe_customer_id no tenant
                        $tenant = $this->db->table('tenants')->where('id', $tenantId)->get()->getRowArray();
                        if (! $tenant) {
                            log_message('warning', 'Tenant não encontrado para mapear stripe_customer_id. tenant_id=' . $tenantId);
                        } else {
                            $now = date('Y-m-d H:i:s');
                            $already = !empty($tenant['stripe_customer_id']);
                            $this->db->table('tenants')->where('id', $tenantId)->update([
                                'stripe_customer_id' => $customerId,
                                'updated_at' => $now,
                            ]);
                            log_message('info', ($already ? 'Atualizado' : 'Criado') . ' vínculo stripe_customer_id -> tenant. tenant_id=' . $tenantId . ' customer=' . $customerId);
                        }
                    } else {
                        $metaDebug = [];
                        foreach ($meta as $k => $v) {
                            $metaDebug[] = $k . '=' . (is_scalar($v) ? (string) $v : json_encode($v));
                        }
                        log_message('warning', 'checkout.session.completed sem dados suficientes para mapear tenant <-> stripe_customer. client_reference_id=' . ($cs->client_reference_id ?? '') . ' metadata_tenant_id=' . ($meta['tenant_id'] ?? '') . ' (metadata session: ' . implode(', ', $metaDebug) . '). Irei tentar metadados da assinatura na etapa de upsert.');
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao processar checkout.session.completed (mapa tenant/customer): ' . $e->getMessage());
                }

                // 3.3) Upsert de assinatura para o tenant/app
                // 3.3.0) Se for pagamento único (mode=payment), registrar payment e não criar assinatura
                $mode = isset($cs->mode) ? (string) $cs->mode : '';
                if ($mode === 'payment') {
                    try {
                        $metaRaw = $cs->metadata ?? null;
                        $meta = [];
                        try {
                            if (is_array($metaRaw)) {
                                $meta = $metaRaw;
                            } elseif (is_object($metaRaw) && method_exists($metaRaw, 'toArray')) {
                                $meta = $metaRaw->toArray();
                            } elseif (is_object($metaRaw) && $metaRaw instanceof \ArrayAccess) {
                                $meta = [
                                    'tenant_id' => isset($metaRaw['tenant_id']) ? (string) $metaRaw['tenant_id'] : null,
                                    'app_id' => isset($metaRaw['app_id']) ? (string) $metaRaw['app_id'] : null,
                                    'app_plan_id' => isset($metaRaw['app_plan_id']) ? (string) $metaRaw['app_plan_id'] : null,
                                    'price_id' => isset($metaRaw['price_id']) ? (string) $metaRaw['price_id'] : null,
                                ];
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'checkout.session.completed payment: falha ao converter metadata: ' . $e->getMessage());
                        }
                        $clientRef = (string) ($cs->client_reference_id ?? '');
                        $customerId = (string) ($cs->customer ?? '');
                        $tenantId = is_numeric($clientRef) ? (int) $clientRef : (int) ($meta['tenant_id'] ?? 0);
                        $appId = (int) ($meta['app_id'] ?? 0);
                        $appPlanId = (int) ($meta['app_plan_id'] ?? 0);
                        $priceId = (string) ($meta['price_id'] ?? '');
                        $amount = null;
                        $currency = null;

                        if (isset($cs->amount_total) && is_int($cs->amount_total)) {
                            $amount = number_format($cs->amount_total / 100, 2, '.', '');
                        }
                        if (isset($cs->currency) && is_string($cs->currency)) {
                            $currency = strtoupper((string) $cs->currency);
                        }

                        // Fallback: resolver tenant via customer -> tenants.stripe_customer_id
                        if ($tenantId <= 0 && $customerId !== '') {
                            try {
                                $tenantRow = $this->db->table('tenants')
                                    ->select('id')
                                    ->where('stripe_customer_id', $customerId)
                                    ->get()
                                    ->getRowArray();
                                if ($tenantRow) {
                                    $tenantId = (int) $tenantRow['id'];
                                    log_message('info', 'checkout.session.completed payment fallback: tenant resolvido via stripe_customer_id=' . $customerId . ' tenant_id=' . $tenantId);
                                } else {
                                    log_message('warning', 'checkout.session.completed payment fallback: nenhum tenant encontrado para stripe_customer_id=' . $customerId);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'checkout.session.completed payment fallback: falha ao consultar tenant por customer: ' . $e->getMessage());
                            }
                        }

                        // Fallback: resolver app via app_plans usando price_id
                        if (($appId <= 0 || $appPlanId <= 0) && $priceId !== '') {
                            try {
                                $planRow = $this->db->table('app_plans')
                                    ->select('id, app_id, price_amount, currency, is_active')
                                    ->where(['stripe_price_id' => $priceId, 'is_active' => 1])
                                    ->get()
                                    ->getRowArray();
                                if ($planRow) {
                                    $appId = (int) ($planRow['app_id'] ?? $appId);
                                    $appPlanId = (int) ($planRow['id'] ?? $appPlanId);
                                    if ($amount === null && isset($planRow['price_amount'])) {
                                        $amount = (string) $planRow['price_amount'];
                                    }
                                    if ($currency === null && isset($planRow['currency'])) {
                                        $currency = (string) $planRow['currency'];
                                    }
                                    log_message('info', 'checkout.session.completed payment fallback: app resolvido via app_plans. app_id=' . $appId . ' app_plan_id=' . $appPlanId . ' price_id=' . $priceId);
                                } else {
                                    log_message('warning', 'checkout.session.completed payment fallback: nenhum app_plans ativo encontrado para price_id=' . $priceId);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'checkout.session.completed payment: falha ao consultar app_plans por price: ' . $e->getMessage());
                            }
                        }

                        if ($tenantId <= 0 || $appId <= 0) {
                            $mdDebugStr = null;
                            try {
                                if (is_array($meta)) {
                                    $mdDebugStr = json_encode($meta);
                                } elseif (is_object($metaRaw) && method_exists($metaRaw, 'toArray')) {
                                    $mdDebugStr = json_encode($metaRaw->toArray());
                                } else {
                                    $mdDebugStr = is_object($metaRaw) ? ('object:' . get_class($metaRaw)) : 'null';
                                }
                            } catch (\Throwable $e) {
                                $mdDebugStr = 'unavailable: ' . $e->getMessage();
                            }
                            log_message('warning', 'checkout.session.completed payment metadados insuficientes. tenant_id=' . $tenantId . ' app_id=' . $appId . ' client_reference_id=' . $clientRef . ' customer=' . $customerId . ' metadata=' . $mdDebugStr);
                            break;
                        }

                        // Obter dados do PaymentIntent/Charge para receipt_url
                        $piId = isset($cs->payment_intent) ? (string) $cs->payment_intent : '';
                        $receiptUrl = null;
                        $chargeId = null;
                        $paidAt = null;

                        $secretKey = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
                        if ($secretKey !== '' && $piId !== '') {
                            try {
                                $stripe = new StripeClient($secretKey);
                                $pi = $stripe->paymentIntents->retrieve($piId, ['expand' => ['charges.data']]);
                                if (isset($pi->charges) && isset($pi->charges->data[0])) {
                                    $ch = $pi->charges->data[0];
                                    $chargeId = (string) ($ch->id ?? '');
                                    $receiptUrl = (string) ($ch->receipt_url ?? '');
                                    if (isset($ch->created) && is_int($ch->created)) {
                                        $paidAt = date('Y-m-d H:i:s', (int) $ch->created);
                                    }
                                }
                                if ($amount === null && isset($pi->amount) && is_int($pi->amount)) {
                                    $amount = number_format($pi->amount / 100, 2, '.', '');
                                }
                                if ($currency === null && isset($pi->currency) && is_string($pi->currency)) {
                                    $currency = strtoupper((string) $pi->currency);
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao recuperar PaymentIntent para pagamento único: ' . $e->getMessage());
                            }
                        }

                        $status = ((string) ($cs->payment_status ?? '')) === 'paid' ? 'succeeded' : 'pending';

                        $now = date('Y-m-d H:i:s');
                        $payData = [
                            'tenant_id' => $tenantId,
                            'app_id' => $appId,
                            'subscription_id' => null,
                            'amount' => $amount !== null ? (float) $amount : 0.0,
                            'currency' => $currency ?? 'BRL',
                            'status' => $status,
                            'payment_method' => null,
                            'provider' => 'stripe',
                            'stripe_payment_intent_id' => $piId ?: null,
                            'stripe_charge_id' => $chargeId ?: null,
                            'stripe_invoice_id' => null,
                            'receipt_url' => $receiptUrl ?: null,
                            'error_code' => null,
                            'error_message' => null,
                            'paid_at' => $status === 'succeeded' ? ($paidAt ?? $now) : null,
                            'due_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        // Idempotência: checar por payment_intent OU charge_id
                        $exists = null;
                        if (! empty($payData['stripe_payment_intent_id'])) {
                            $exists = $this->db->table('payments')
                                ->where('stripe_payment_intent_id', $payData['stripe_payment_intent_id'])
                                ->get()->getRowArray();
                        }
                        if (! $exists && ! empty($payData['stripe_charge_id'])) {
                            $exists = $this->db->table('payments')
                                ->where('stripe_charge_id', $payData['stripe_charge_id'])
                                ->get()->getRowArray();
                        }
                        if (! $exists) {
                            $this->db->table('payments')->insert($payData);
                            $paymentId = (int) $this->db->insertID();
                            log_message('info', 'Payment registrado via checkout.session.completed (one_time). payment_id=' . $paymentId . ' tenant_id=' . $tenantId . ' app_id=' . $appId . ' pi=' . ($payData['stripe_payment_intent_id'] ?? '') . ' charge=' . ($payData['stripe_charge_id'] ?? ''));
                        } else {
                            log_message('info', 'Payment idempotente (one_time) pi=' . ($payData['stripe_payment_intent_id'] ?? '') . ' charge=' . ($payData['stripe_charge_id'] ?? ''));
                        }
                    } catch (\Throwable $e) {
                        log_message('error', 'Erro ao processar checkout.session.completed (mode=payment): ' . $e->getMessage());
                    }
                    break;
                }

                try {
                    $meta = (array) ($cs->metadata ?? []);
                    $tenantId = is_numeric((string) ($cs->client_reference_id ?? '')) ? (int) $cs->client_reference_id : (int) ($meta['tenant_id'] ?? 0);
                    $appId = (int) ($meta['app_id'] ?? 0);
                    $appPlanId = (int) ($meta['app_plan_id'] ?? 0);
                    $priceIdMeta = (string) ($meta['price_id'] ?? '');
                    $stripeSubId = isset($cs->subscription) ? (string) $cs->subscription : '';

                    // Opcional: buscar detalhes da assinatura na Stripe
                    $status = 'incomplete';
                    $quantity = 1;
                    $unitPrice = null;
                    $currency = null;
                    $priceId = $priceIdMeta ?: null;
                    $periodStart = null;
                    $periodEnd = null;

                    $secretKey = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
                    if ($secretKey !== '' && $stripeSubId !== '') {
                        try {
                            $stripe = new StripeClient($secretKey);
                            $sub = $stripe->subscriptions->retrieve($stripeSubId, [
                                'expand' => ['items.data.price'],
                            ]);
                            $status = (string) ($sub->status ?? $status);
                            if (! empty($sub->current_period_start)) {
                                $periodStart = date('Y-m-d H:i:s', (int) $sub->current_period_start);
                            }
                            if (! empty($sub->current_period_end)) {
                                $periodEnd = date('Y-m-d H:i:s', (int) $sub->current_period_end);
                            }
                            // Fallback: se app_id/tenant_id não vierem na sessão, ler dos metadados da assinatura
                            $mdSub = (array) ($sub->metadata ?? []);
                            $mdDebug = [];
                            foreach ($mdSub as $k => $v) {
                                $mdDebug[] = $k . '=' . (is_scalar($v) ? (string) $v : json_encode($v));
                            }
                            log_message('info', 'checkout.session.completed assinatura Stripe metadata: ' . implode(', ', $mdDebug));
                            if ($appId <= 0 && isset($mdSub['app_id'])) {
                                $appId = (int) $mdSub['app_id'];
                            }
                            if ($tenantId <= 0 && isset($mdSub['tenant_id'])) {
                                $tenantId = (int) $mdSub['tenant_id'];
                            }
                            if ($appPlanId <= 0 && isset($mdSub['app_plan_id'])) {
                                $appPlanId = (int) $mdSub['app_plan_id'];
                            }
                            if ($priceIdMeta === '' && isset($mdSub['price_id'])) {
                                $priceIdMeta = (string) $mdSub['price_id'];
                            }
                            if (isset($sub->items) && isset($sub->items->data[0])) {
                                $item = $sub->items->data[0];
                                $quantity = (int) ($item->quantity ?? $quantity);
                                if (isset($item->price)) {
                                    $priceId = (string) ($item->price->id ?? $priceId);
                                    $currency = (string) ($item->price->currency ?? $currency);
                                    $unit = $item->price->unit_amount ?? null;
                                    if (is_int($unit)) {
                                        $unitPrice = number_format($unit / 100, 2, '.', '');
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'Falha ao buscar assinatura no Stripe para upsert: ' . $e->getMessage());
                        }
                    }

                    // Fallback: resolver app_id/app_plan_id a partir do price_id em app_plans
                    if (($appId <= 0 || $appPlanId <= 0) && is_string($priceId) && $priceId !== '') {
                        try {
                            $planRow = $this->db->table('app_plans')
                                ->select('id, app_id, price_amount, currency, is_active')
                                ->where(['stripe_price_id' => $priceId, 'is_active' => 1])
                                ->get()
                                ->getRowArray();
                            if ($planRow) {
                                $appId = (int) ($planRow['app_id'] ?? $appId);
                                $appPlanId = (int) ($planRow['id'] ?? $appPlanId);
                                if ($unitPrice === null && isset($planRow['price_amount'])) {
                                    $unitPrice = (string) $planRow['price_amount'];
                                }
                                if ($currency === null && isset($planRow['currency'])) {
                                    $currency = (string) $planRow['currency'];
                                }
                                log_message('info', 'checkout.session.completed fallback: app resolvido via app_plans. app_id=' . $appId . ' app_plan_id=' . $appPlanId . ' price_id=' . $priceId);
                            } else {
                                log_message('warning', 'checkout.session.completed fallback: nenhum app_plans ativo encontrado para price_id=' . $priceId);
                            }
                        } catch (\Throwable $e) {
                            log_message('warning', 'Falha ao consultar app_plans por price_id: ' . $e->getMessage());
                        }
                    }

                    if ($tenantId <= 0 || $appId <= 0) {
                        log_message('warning', 'checkout.session.completed metadados insuficientes: tenant_id=' . $tenantId . ' app_id=' . $appId . ' (verificar metadata da sessão e da assinatura). Não será feito upsert.');
                        break;
                    }

                    $now = date('Y-m-d H:i:s');
                    $subsTable = $this->db->table('subscriptions');
                    $existing = $subsTable
                        ->where('tenant_id', $tenantId)
                        ->where('app_id', $appId)
                        ->orderBy('id', 'DESC')
                        ->get()
                        ->getRowArray();

                    $data = [
                        'tenant_id' => $tenantId,
                        'app_id' => $appId,
                        'app_plan_id' => $appPlanId ?: ($existing['app_plan_id'] ?? null),
                        'status' => $status,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'currency' => $currency,
                        'current_period_start' => $periodStart,
                        'current_period_end' => $periodEnd,
                        'stripe_subscription_id' => $stripeSubId ?: ($existing['stripe_subscription_id'] ?? null),
                        'stripe_price_id' => $priceId ?: ($existing['stripe_price_id'] ?? null),
                        'is_active' => in_array($status, ['active', 'trialing', 'past_due', 'incomplete'], true) ? 1 : 0,
                        'incomplete_expires_at' => ($status === 'incomplete' ? ($existing['incomplete_expires_at'] ?? null) : null),
                        'updated_at' => $now,
                    ];

                    if ($existing) {
                        $existingCanceled = (
                            (isset($existing['status']) && $existing['status'] === 'canceled')
                            && (isset($existing['is_active']) && (int) $existing['is_active'] === 0)
                        );
                        if ($existingCanceled) {
                            $data['created_at'] = $now;
                            $subsTable->insert($data);
                            $newId = (int) $this->db->insertID();
                            log_message('info', 'Assinatura criada (nova) pois anterior estava cancelada via checkout.session.completed. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub_id_local=' . $newId . ' stripe_sub=' . ($stripeSubId ?: ''));
                        } else {
                            $subsTable->where('id', (int) $existing['id'])->update($data);
                            log_message('info', 'Assinatura atualizada via webhook. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub_id_local=' . (int) $existing['id'] . ' stripe_sub=' . ($stripeSubId ?: ''));
                        }
                    } else {
                        $data['created_at'] = $now;
                        $subsTable->insert($data);
                        $newId = (int) $this->db->insertID();
                        log_message('info', 'Assinatura criada via webhook. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub_id_local=' . $newId . ' stripe_sub=' . ($stripeSubId ?: ''));
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao fazer upsert de assinatura no webhook: ' . $e->getMessage());
                }
                break;

            default:
                log_message('info', 'Unhandled Stripe event type: ' . $type . ' event=' . ($eventId ?? ''));
                break;
        }

        // 4) Responder 200 OK para o Stripe
        return $this->response->setStatusCode(200)->setJSON(['received' => true]);
    }
}
