<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PaymentsSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Buscar assinaturas existentes
        $subs = $this->db->table('subscriptions')
            ->select('id, tenant_id, app_id, unit_price, currency, status, current_period_start, current_period_end')
            ->get()
            ->getResultArray();

        if (empty($subs)) {
            return; // nada a fazer sem assinaturas
        }

        $methods = ['card', 'boleto', 'pix'];
        $statuses = ['succeeded', 'failed', 'refunded', 'pending'];

        foreach ($subs as $subscription) {
            $subscriptionId = (int) $subscription['id'];

            // Idempotência: se já existem pagamentos para a assinatura, não gerar novamente
            $existing = $this->db->table('payments')
                ->select('id')
                ->where('subscription_id', $subscriptionId)
                ->get()
                ->getRow();

            if ($existing) {
                continue;
            }

            $count = mt_rand(1, 3); // 1 a 3 pagamentos por assinatura

            for ($i = 1; $i <= $count; $i++) {
                $status = $this->weightedStatus();
                $method = $methods[array_rand($methods)];

                // Gerar IDs determinísticos para idempotência
                $pi = sprintf('pi_sub_%d_%d_%s', $subscriptionId, $i, bin2hex(random_bytes(3)));
                $ch = sprintf('ch_sub_%d_%d_%s', $subscriptionId, $i, bin2hex(random_bytes(3)));
                $in = sprintf('in_sub_%d_%d_%s', $subscriptionId, $i, bin2hex(random_bytes(3)));

                // Datas
                $periodStartTs = strtotime($subscription['current_period_start'] ?? $now);
                $periodEndTs   = strtotime($subscription['current_period_end'] ?? $now) ?: (time() + 30 * 86400);
                $randTs        = mt_rand($periodStartTs, $periodEndTs);
                $paidAt        = in_array($status, ['succeeded', 'refunded'], true) ? date('Y-m-d H:i:s', $randTs) : null;
                $dueAt         = ($status === 'pending') ? date('Y-m-d H:i:s', $periodEndTs) : null;

                $data = [
                    'tenant_id' => (int) $subscription['tenant_id'],
                    'app_id' => (int) $subscription['app_id'],
                    'subscription_id' => $subscriptionId,
                    'amount' => (float) $subscription['unit_price'],
                    'currency' => $subscription['currency'] ?? 'BRL',
                    'status' => $status,
                    'payment_method' => $method,
                    'provider' => 'stripe',
                    'stripe_payment_intent_id' => $pi,
                    'stripe_charge_id' => $ch,
                    'stripe_invoice_id' => $in,
                    'receipt_url' => null,
                    'error_code' => ($status === 'failed') ? 'card_declined' : null,
                    'error_message' => ($status === 'failed') ? 'Pagamento não aprovado pelo emissor.' : null,
                    'paid_at' => $paidAt,
                    'due_at' => $dueAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];

                $this->db->table('payments')->insert($data);
            }
        }
    }

    private function weightedStatus(): string
    {
        // Ponderar para maioria de pagamentos com sucesso
        $pool = array_merge(
            array_fill(0, 6, 'succeeded'),
            array_fill(0, 1, 'failed'),
            array_fill(0, 1, 'refunded'),
            array_fill(0, 2, 'pending'),
        );

        return $pool[array_rand($pool)];
    }
}