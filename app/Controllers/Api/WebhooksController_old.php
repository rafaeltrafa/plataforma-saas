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
        // Entrada do webhook
        log_message('info', 'Stripe webhook called: ' . $this->request->getMethod() . ' ' . (string) $this->request->getUri());

        $payload = $this->request->getBody() ?? '';
        $sigHeader = $this->request->getHeaderLine('Stripe-Signature');
        $secret = env('STRIPE_WEBHOOK_SECRET');

        if (!$secret) {
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

        // Handle event types
        $eventId = $event->id ?? null;
        $type = $event->type ?? 'unknown';

        switch ($type) {
            case 'payment_intent.succeeded':
                $pi = $event->data->object;
                log_message('info', 'Stripe payment succeeded. event=' . $eventId . ' intent=' . ($pi->id ?? '') . ' amount=' . ($pi->amount ?? '') . ' currency=' . ($pi->currency ?? ''));
                break;

            case 'payment_intent.payment_failed':
                $pi = $event->data->object;
                $failure = $pi->last_payment_error ? $pi->last_payment_error->message : 'Unknown reason';
                log_message('warning', 'Stripe payment failed. event=' . $eventId . ' intent=' . ($pi->id ?? '') . ' reason=' . $failure);
                break;

            case 'checkout.session.completed':
                $cs = $event->data->object;
                log_message('info', 'Stripe checkout.session.completed event=' . $eventId
                    . ' session=' . ($cs->id ?? '')
                    . ' mode=' . ($cs->mode ?? '')
                    . ' customer=' . ($cs->customer ?? '')
                    . ' subscription=' . ($cs->subscription ?? '')
                    . ' client_reference_id=' . ($cs->client_reference_id ?? '')
                );

                // Vincular stripe_customer_id ao tenant (se ainda não estiver salvo)
                try {
                    $customerId = isset($cs->customer) ? (string) $cs->customer : '';
                    $clientRef = isset($cs->client_reference_id) ? (string) $cs->client_reference_id : '';
                    $meta = (array) ($cs->metadata ?? []);
                    $tenantId = is_numeric($clientRef) ? (int) $clientRef : (int) ($meta['tenant_id'] ?? 0);
                    $appId = (int) ($meta['app_id'] ?? 0);
                    $appPlanId = (int) ($meta['app_plan_id'] ?? 0);
                    $priceId = (string) ($meta['price_id'] ?? '');
                    $stripeSubId = isset($cs->subscription) ? (string) $cs->subscription : '';

                    if ($tenantId > 0 && $customerId !== '') {
                        $tenant = $this->db->table('tenants')->where('id', $tenantId)->get()->getRowArray();
                        if ($tenant && empty($tenant['stripe_customer_id'])) {
                            $this->db->table('tenants')->where('id', $tenantId)->update([
                                'stripe_customer_id' => $customerId,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                            log_message('info', 'Stripe mapped customer to tenant. tenant_id=' . $tenantId . ' customer=' . $customerId);
                        }
                    }

                    // Pré-criar/atualizar assinatura local como incompleta, se metadados suficientes
                    if ($tenantId > 0 && $appId > 0 && $appPlanId > 0 && $stripeSubId !== '') {
                        $now = date('Y-m-d H:i:s');
                        $existing = $this->db->table('subscriptions')->where('stripe_subscription_id', $stripeSubId)->get()->getRowArray();
                        if ($existing) {
                            // Garantir vínculo tenant/app/price
                            $this->db->table('subscriptions')->where('id', (int) $existing['id'])->update([
                                'tenant_id' => $tenantId,
                                'app_id' => $appId,
                                'app_plan_id' => $appPlanId,
                                'stripe_price_id' => $priceId ?: ($existing['stripe_price_id'] ?? null),
                                'status' => $existing['status'] ?? 'incomplete',
                                'is_active' => (int) ($existing['is_active'] ?? 0),
                                'updated_at' => $now,
                            ]);
                        } else {
                            $this->db->table('subscriptions')->insert([
                                'tenant_id' => $tenantId,
                                'app_id' => $appId,
                                'app_plan_id' => $appPlanId,
                                'status' => 'incomplete',
                                'quantity' => 1,
                                'unit_price' => null,
                                'currency' => null,
                                'current_period_start' => null,
                                'current_period_end' => null,
                                'trial_end_at' => null,
                                'cancel_at' => null,
                                'canceled_at' => null,
                                'stripe_subscription_id' => $stripeSubId,
                                'stripe_price_id' => $priceId ?: null,
                                'is_active' => 0,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                            log_message('info', 'Local subscription created (incomplete). tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub=' . $stripeSubId);
                        }
                    } else {
                        if ($stripeSubId !== '') {
                            log_message('warning', 'checkout.session.completed sem metadados suficientes para criar assinatura local (precisa de tenant_id, app_id, app_plan_id). sub=' . $stripeSubId);
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao processar checkout.session.completed: ' . $e->getMessage());
                }
                break;

            case 'invoice.payment_succeeded':
                $inv = $event->data->object;
                log_message('info', 'Stripe invoice.payment_succeeded event=' . $eventId
                    . ' invoice=' . ($inv->id ?? '')
                    . ' customer=' . ($inv->customer ?? '')
                    . ' subscription=' . ($inv->subscription ?? '')
                    . ' amount_paid=' . ($inv->amount_paid ?? '')
                    . ' currency=' . ($inv->currency ?? '')
                    . ' status=' . ($inv->status ?? '')
                );

                try {
                    $invoiceId = (string) ($inv->id ?? '');
                    $customerId = (string) ($inv->customer ?? '');
                    $stripeSubId = (string) ($inv->subscription ?? '');
                    $amountPaid = isset($inv->amount_paid) ? (int) $inv->amount_paid : 0; // em centavos
                    $currency = (string) ($inv->currency ?? '');
                    $paymentIntentId = (string) ($inv->payment_intent ?? '');
                    $hostedInvoiceUrl = (string) ($inv->hosted_invoice_url ?? '');

                    // Fallback: assinatura pode estar nas linhas da fatura (varrer todas)
                    if ($stripeSubId === '' && isset($inv->lines) && isset($inv->lines->data) && is_array($inv->lines->data)) {
                        foreach ($inv->lines->data as $ln) {
                            if (isset($ln->subscription) && $ln->subscription) {
                                $stripeSubId = (string) $ln->subscription;
                                break;
                            }
                        }
                    }

                    // Idempotência por invoice_id ou payment_intent_id
                    $existingPayment = null;
                    if ($invoiceId !== '') {
                        $existingPayment = $this->db->table('payments')->where('stripe_invoice_id', $invoiceId)->get()->getRowArray();
                    }
                    if (! $existingPayment && $paymentIntentId !== '') {
                        $existingPayment = $this->db->table('payments')->where('stripe_payment_intent_id', $paymentIntentId)->get()->getRowArray();
                    }

                    // Encontrar assinatura local
                    $subscription = null;
                    if ($stripeSubId !== '') {
                        $subscription = $this->db->table('subscriptions')->where('stripe_subscription_id', $stripeSubId)->get()->getRowArray();
                    }
                    // Fallback por tenant via stripe_customer_id, se necessário
                    $tenant = null;
                    if (! $subscription && $customerId !== '') {
                        $tenant = $this->db->table('tenants')->where('stripe_customer_id', $customerId)->get()->getRowArray();
                    }

                    // Extrair dados da primeira linha (já usado abaixo) e tentar mapear app/plan pelo price
                    // Identificar price percorrendo linhas
                    $line = null;
                    $priceId = null;
                    if (isset($inv->lines) && isset($inv->lines->data) && is_array($inv->lines->data)) {
                        foreach ($inv->lines->data as $ln) {
                            // Preferir a linha com subscription ou price definido
                            if ((isset($ln->subscription) && $ln->subscription) || isset($ln->price)) {
                                $line = $ln;
                                if (isset($ln->price)) {
                                    if (is_object($ln->price)) {
                                        $priceId = (string) ($ln->price->id ?? '');
                                    } else {
                                        $priceId = (string) $ln->price;
                                    }
                                }
                                break;
                            }
                        }
                    }

                    // Se ainda faltarem períodos/price/currency ou subscription, buscar a fatura no Stripe
                    $periodStart = null;
                    $periodEnd = null;
                    $unitAmount = null;
                    if ((!$line || !$priceId || $currency === '' || $stripeSubId === '') && $invoiceId !== '') {
                        $secret = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
                        if ($secret !== '') {
                            try {
                                $client = new StripeClient($secret);
                                $remoteInv = $client->invoices->retrieve($invoiceId, [ 'expand' => ['lines.data.price'] ]);
                                if ($remoteInv) {
                                    if ($stripeSubId === '' && isset($remoteInv->subscription) && $remoteInv->subscription) {
                                        $stripeSubId = (string) $remoteInv->subscription;
                                    }
                                    if ($currency === '' && isset($remoteInv->currency)) {
                                        $currency = (string) $remoteInv->currency;
                                    }
                                    if (isset($remoteInv->lines) && isset($remoteInv->lines->data) && is_array($remoteInv->lines->data)) {
                                        foreach ($remoteInv->lines->data as $rln) {
                                            if ((isset($rln->subscription) && $rln->subscription) || isset($rln->price)) {
                                                $line = $rln;
                                                if (isset($rln->period)) {
                                                    $periodStart = isset($rln->period->start) ? date('Y-m-d H:i:s', (int) $rln->period->start) : null;
                                                    $periodEnd = isset($rln->period->end) ? date('Y-m-d H:i:s', (int) $rln->period->end) : null;
                                                }
                                                if (isset($rln->price)) {
                                                    if (is_object($rln->price)) {
                                                        $priceId = $priceId ?: (string) ($rln->price->id ?? '');
                                                        if (isset($rln->price->unit_amount)) {
                                                            $unitAmount = (int) $rln->price->unit_amount;
                                                        }
                                                        if ($currency === '' && isset($rln->price->currency)) {
                                                            $currency = (string) $rln->price->currency;
                                                        }
                                                    } else {
                                                        $priceId = $priceId ?: (string) $rln->price;
                                                    }
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao recuperar invoice no Stripe: ' . $e->getMessage());
                            }
                        }
                    }

                    // Atualizar assinatura (período e status) se encontrada
                    if ($subscription) {
                        $subIdLocal = (int) $subscription['id'];
                        $now = date('Y-m-d H:i:s');

                        // Extrair dados da primeira linha da fatura
                        // (já carregada acima em $line)
                        // Se não preenchido via invoice remota, tentar extrair da linha atual
                        if ($line && ($periodStart === null || $periodEnd === null || $unitAmount === null)) {
                            if (isset($line->period)) {
                                $periodStart = $periodStart ?: (isset($line->period->start) ? date('Y-m-d H:i:s', (int) $line->period->start) : null);
                                $periodEnd = $periodEnd ?: (isset($line->period->end) ? date('Y-m-d H:i:s', (int) $line->period->end) : null);
                            }
                            if ($unitAmount === null && isset($line->price) && is_object($line->price) && isset($line->price->unit_amount)) {
                                $unitAmount = (int) $line->price->unit_amount; // centavos
                            }
                        }

                        // Fallback: consultar assinatura diretamente no Stripe para obter períodos/valores
                        if (($periodStart === null || $periodEnd === null || $unitAmount === null || !$priceId || !$currency) && $stripeSubId !== '') {
                            $secret = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
                            if ($secret !== '') {
                                try {
                                    $client = new StripeClient($secret);
                                    $remoteSub = $client->subscriptions->retrieve($stripeSubId, []);
                                    if ($remoteSub) {
                                        if ($periodStart === null && isset($remoteSub->current_period_start)) {
                                            $periodStart = date('Y-m-d H:i:s', (int) $remoteSub->current_period_start);
                                        }
                                        if ($periodEnd === null && isset($remoteSub->current_period_end)) {
                                            $periodEnd = date('Y-m-d H:i:s', (int) $remoteSub->current_period_end);
                                        }
                                        // Complementar price/currency/unit_amount a partir do item da assinatura
                                        if (isset($remoteSub->items) && isset($remoteSub->items->data) && is_array($remoteSub->items->data) && count($remoteSub->items->data) > 0) {
                                            $rItem = $remoteSub->items->data[0];
                                            if (isset($rItem->price)) {
                                                if (is_object($rItem->price)) {
                                                    if (!$priceId) { $priceId = (string) ($rItem->price->id ?? ''); }
                                                    if ($unitAmount === null && isset($rItem->price->unit_amount)) {
                                                        $unitAmount = (int) $rItem->price->unit_amount;
                                                    }
                                                    if (!$currency && isset($rItem->price->currency)) {
                                                        $currency = (string) $rItem->price->currency;
                                                    }
                                                } else {
                                                    if (!$priceId) { $priceId = (string) $rItem->price; }
                                                }
                                            }
                                        }
                                    }
                                } catch (\Throwable $e) {
                                    log_message('warning', 'Falha ao recuperar assinatura no Stripe para invoice.payment_succeeded: ' . $e->getMessage());
                                }
                            }
                        }

                        $updateData = [
                            'status' => 'active',
                            'is_active' => 1,
                            'updated_at' => $now,
                        ];
                        if ($periodStart) {
                            $updateData['current_period_start'] = $periodStart;
                        }
                        if ($periodEnd) {
                            $updateData['current_period_end'] = $periodEnd;
                        }
                        if ($unitAmount !== null) {
                            $updateData['unit_price'] = ((float) $unitAmount) / 100.0;
                        }
                        if ($currency !== '') {
                            $updateData['currency'] = $currency;
                        }
                        if ($priceId) {
                            $updateData['stripe_price_id'] = $priceId;
                        }
                        $this->db->table('subscriptions')->where('id', $subIdLocal)->update($updateData);
                    }

                    // Inserir pagamento se não houver (ou retornar replay idempotente)
                    if (! $existingPayment) {
                        // Determinar tenant/app/subscription
                        $tenantId = null;
                        $appId = null;
                        $subscriptionId = null;
                        if ($subscription) {
                            $tenantId = (int) $subscription['tenant_id'];
                            $appId = (int) $subscription['app_id'];
                            $subscriptionId = (int) $subscription['id'];
                        } elseif ($tenant) {
                            $tenantId = (int) $tenant['id'];
                            // Tentar mapear app pelo price
                            if ($priceId) {
                                $planRow = $this->db->table('app_plans')
                                    ->select('app_id')
                                    ->where('stripe_price_id', $priceId)
                                    ->get()
                                    ->getRowArray();
                                if ($planRow) {
                                    $appId = (int) $planRow['app_id'];
                                }
                            }
                            if (! $appId) {
                                log_message('warning', 'Sem assinatura local para invoice.payment_succeeded; pagamento não será persistido por falta de app_id. invoice=' . $invoiceId);
                                return $this->response->setStatusCode(200)->setJSON(['received' => true]);
                            }
                        } else {
                            log_message('warning', 'Não foi possível associar pagamento (tenant/subscription ausentes). invoice=' . $invoiceId);
                            return $this->response->setStatusCode(200)->setJSON(['received' => true]);
                        }

                        $amount = ((float) $amountPaid) / 100.0;
                        $now = date('Y-m-d H:i:s');
                        $paidAt = null;
                        if (isset($inv->status_transitions) && isset($inv->status_transitions->paid_at)) {
                            $paidAt = date('Y-m-d H:i:s', (int) $inv->status_transitions->paid_at);
                        } else {
                            $paidAt = $now;
                        }

                        $this->db->table('payments')->insert([
                            'tenant_id' => $tenantId,
                            'app_id' => $appId,
                            'subscription_id' => $subscriptionId,
                            'amount' => $amount,
                            'currency' => $currency ?: 'BRL',
                            'status' => 'succeeded',
                            'payment_method' => 'card',
                            'provider' => 'stripe',
                            'stripe_payment_intent_id' => $paymentIntentId ?: null,
                            'stripe_charge_id' => null,
                            'stripe_invoice_id' => $invoiceId ?: null,
                            'receipt_url' => $hostedInvoiceUrl ?: null,
                            'paid_at' => $paidAt,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                        log_message('info', 'Local payment created. invoice=' . $invoiceId . ' amount=' . $amount . ' tenant_id=' . $tenantId);
                    } else {
                        log_message('info', 'Pagamento já registrado (idempotência). invoice=' . $invoiceId);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao processar invoice.payment_succeeded: ' . $e->getMessage());
                }
                break;

            case 'invoice.payment_failed':
                $inv = $event->data->object;
                $attemptCount = $inv->attempt_count ?? null;
                $nextAttempt = $inv->next_payment_attempt ?? null;
                log_message('warning', 'Stripe invoice.payment_failed event=' . $eventId
                    . ' invoice=' . ($inv->id ?? '')
                    . ' customer=' . ($inv->customer ?? '')
                    . ' subscription=' . ($inv->subscription ?? '')
                    . ' amount_due=' . ($inv->amount_due ?? '')
                    . ' attempt_count=' . ($attemptCount ?? '')
                    . ' next_attempt=' . ($nextAttempt ?? '')
                );

                try {
                    $stripeSubId = (string) ($inv->subscription ?? '');
                    if ($stripeSubId !== '') {
                        $now = date('Y-m-d H:i:s');
                        $this->db->table('subscriptions')->where('stripe_subscription_id', $stripeSubId)->update([
                            'status' => 'past_due',
                            'is_active' => 0,
                            'updated_at' => $now,
                        ]);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao atualizar assinatura em payment_failed: ' . $e->getMessage());
                }
                break;

            case 'invoice.paid':
                $inv = $event->data->object;
                log_message('info', 'Stripe invoice.paid event=' . $eventId . ' invoice=' . ($inv->id ?? '') . ' customer=' . ($inv->customer ?? '') . ' subscription=' . ($inv->subscription ?? ''));
                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                $sub = $event->data->object;
                log_message('info', 'Stripe ' . $type . ' event=' . $eventId
                    . ' subscription=' . ($sub->id ?? '')
                    . ' customer=' . ($sub->customer ?? '')
                    . ' status=' . ($sub->status ?? '')
                    . ' current_period_end=' . (isset($sub->current_period_end) ? date('c', (int) $sub->current_period_end) : '')
                );

                try {
                    $stripeSubId = (string) ($sub->id ?? '');
                    if ($stripeSubId !== '') {
                        $now = date('Y-m-d H:i:s');
                        // Tentar obter detalhes completos da assinatura no Stripe para preencher períodos/valores
                        $remoteSub = null;
                        $secret = (string) (env('STRIPE_SECRET_KEY') ?? getenv('STRIPE_SECRET_KEY') ?? '');
                        if ($secret !== '') {
                            try {
                                $client = new StripeClient($secret);
                                $remoteSub = $client->subscriptions->retrieve($stripeSubId, []);
                            } catch (\Throwable $e) {
                                log_message('warning', 'Falha ao recuperar assinatura no Stripe: ' . $e->getMessage());
                            }
                        }
                        // Verificar se a assinatura já existe localmente
                        $existing = $this->db->table('subscriptions')
                            ->where('stripe_subscription_id', $stripeSubId)
                            ->get()
                            ->getRowArray();

                        if ($existing) {
                            // Atualizar assinatura existente
                            $data = [
                                'updated_at' => $now,
                            ];
                            if (isset($sub->status)) {
                                $data['status'] = (string) $sub->status;
                                $data['is_active'] = $sub->status === 'active' ? 1 : 0;
                            }
                            // Preencher períodos a partir do evento ou da consulta remota
                            if (isset($sub->current_period_start)) {
                                $data['current_period_start'] = date('Y-m-d H:i:s', (int) $sub->current_period_start);
                            } elseif ($remoteSub && isset($remoteSub->current_period_start)) {
                                $data['current_period_start'] = date('Y-m-d H:i:s', (int) $remoteSub->current_period_start);
                            }
                            if (isset($sub->current_period_end)) {
                                $data['current_period_end'] = date('Y-m-d H:i:s', (int) $sub->current_period_end);
                            } elseif ($remoteSub && isset($remoteSub->current_period_end)) {
                                $data['current_period_end'] = date('Y-m-d H:i:s', (int) $remoteSub->current_period_end);
                            }
                            if ($type === 'customer.subscription.deleted') {
                                $data['canceled_at'] = $now;
                                $data['is_active'] = 0;
                            }
                            $this->db->table('subscriptions')->where('id', (int) $existing['id'])->update($data);
                        } else {
                            // Criar assinatura local se não existir (upsert)
                            $meta = (array) ($sub->metadata ?? []);
                            $tenantId = isset($meta['tenant_id']) ? (int) $meta['tenant_id'] : 0;
                            $appId = isset($meta['app_id']) ? (int) $meta['app_id'] : 0;
                            $appPlanId = isset($meta['app_plan_id']) ? (int) $meta['app_plan_id'] : 0;
                            $priceIdMeta = isset($meta['price_id']) ? (string) $meta['price_id'] : '';

                            // Fallback: mapear tenant pelo stripe_customer_id
                            if ($tenantId <= 0 && isset($sub->customer) && $sub->customer) {
                                $tenantRow = $this->db->table('tenants')
                                    ->where('stripe_customer_id', (string) $sub->customer)
                                    ->get()
                                    ->getRowArray();
                                if ($tenantRow) {
                                    $tenantId = (int) $tenantRow['id'];
                                }
                            }

                            // Extrair item/price/quantidade da assinatura do Stripe
                            $priceId = $priceIdMeta;
                            $quantity = 1;
                            $unitAmount = null;
                            $currency = null;
                            if (isset($sub->items) && isset($sub->items->data) && is_array($sub->items->data) && count($sub->items->data) > 0) {
                                $item = $sub->items->data[0];
                                if (isset($item->quantity)) {
                                    $quantity = (int) $item->quantity;
                                }
                                if (isset($item->price)) {
                                    if (is_object($item->price)) {
                                        $priceId = $priceId ?: (string) ($item->price->id ?? '');
                                        if (isset($item->price->unit_amount)) {
                                            $unitAmount = (int) $item->price->unit_amount;
                                        }
                                        if (isset($item->price->currency)) {
                                            $currency = (string) $item->price->currency;
                                        }
                                    } else {
                                        $priceId = $priceId ?: (string) $item->price;
                                    }
                                }
                            }
                            // Complementar com dados remotos quando faltarem
                            if ($remoteSub && (!$priceId || $unitAmount === null || !$currency || !$quantity)) {
                                if (isset($remoteSub->items) && isset($remoteSub->items->data) && is_array($remoteSub->items->data) && count($remoteSub->items->data) > 0) {
                                    $rItem = $remoteSub->items->data[0];
                                    if (!$quantity && isset($rItem->quantity)) {
                                        $quantity = (int) $rItem->quantity;
                                    }
                                    if (isset($rItem->price)) {
                                        if (is_object($rItem->price)) {
                                            $priceId = $priceId ?: (string) ($rItem->price->id ?? '');
                                            if ($unitAmount === null && isset($rItem->price->unit_amount)) {
                                                $unitAmount = (int) $rItem->price->unit_amount;
                                            }
                                            if (!$currency && isset($rItem->price->currency)) {
                                                $currency = (string) $rItem->price->currency;
                                            }
                                        } else {
                                            $priceId = $priceId ?: (string) $rItem->price;
                                        }
                                    }
                                }
                            }

                            // Se app_id/app_plan_id não vierem nos metadados, tentar mapear pelo price
                            if (($appId <= 0 || $appPlanId <= 0) && $priceId !== '') {
                                $planRow = $this->db->table('app_plans')
                                    ->select('id, app_id')
                                    ->where('stripe_price_id', $priceId)
                                    ->get()
                                    ->getRowArray();
                                if ($planRow) {
                                    $appPlanId = (int) $planRow['id'];
                                    $appId = (int) $planRow['app_id'];
                                }
                            }

                            // Período atual
                            $periodStart = isset($sub->current_period_start) ? date('Y-m-d H:i:s', (int) $sub->current_period_start) : null;
                            $periodEnd = isset($sub->current_period_end) ? date('Y-m-d H:i:s', (int) $sub->current_period_end) : null;
                            if ((!$periodStart || !$periodEnd) && $remoteSub) {
                                if (!$periodStart && isset($remoteSub->current_period_start)) {
                                    $periodStart = date('Y-m-d H:i:s', (int) $remoteSub->current_period_start);
                                }
                                if (!$periodEnd && isset($remoteSub->current_period_end)) {
                                    $periodEnd = date('Y-m-d H:i:s', (int) $remoteSub->current_period_end);
                                }
                            }

                            if ($tenantId > 0 && $appId > 0 && $appPlanId > 0) {
                                $this->db->table('subscriptions')->insert([
                                    'tenant_id' => $tenantId,
                                    'app_id' => $appId,
                                    'app_plan_id' => $appPlanId,
                                    'status' => isset($sub->status) ? (string) $sub->status : 'incomplete',
                                    'quantity' => max(1, (int) $quantity),
                                    'unit_price' => $unitAmount !== null ? (((float) $unitAmount) / 100.0) : null,
                                    'currency' => $currency ?: null,
                                    'current_period_start' => $periodStart,
                                    'current_period_end' => $periodEnd,
                                    'trial_end_at' => null,
                                    'cancel_at' => null,
                                    'canceled_at' => null,
                                    'stripe_subscription_id' => $stripeSubId,
                                    'stripe_price_id' => $priceId ?: null,
                                    'is_active' => (isset($sub->status) && $sub->status === 'active') ? 1 : 0,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]);
                                log_message('info', 'Local subscription upserted from ' . $type . '. tenant_id=' . $tenantId . ' app_id=' . $appId . ' sub=' . $stripeSubId);
                            } else {
                                log_message('warning', $type . ' sem metadados suficientes para criar assinatura local (precisa de tenant_id, app_id, app_plan_id). sub=' . $stripeSubId);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Erro ao processar customer.subscription.*: ' . $e->getMessage());
                }
                break;

            default:
                log_message('info', 'Unhandled Stripe event type: ' . $type . ' event=' . $eventId);
        }

        return $this->response->setStatusCode(200)->setJSON(['received' => true]);
    }
}