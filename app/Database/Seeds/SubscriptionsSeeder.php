<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SubscriptionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Tenants existentes
        $tenants = $this->db->table('tenants')->select('id')->get()->getResultArray();
        if (empty($tenants)) {
            return; // nada a fazer sem tenants
        }

        // Apps por tenant (tenant_apps)
        $tenantApps = $this->db->table('tenant_apps')->select('tenant_id, app_id')->get()->getResultArray();
        if (empty($tenantApps)) {
            return; // nada a fazer sem vínculos tenant->apps
        }

        // Agrupar app_ids por tenant_id
        $appsByTenant = [];
        foreach ($tenantApps as $row) {
            $tid = (int) $row['tenant_id'];
            $aid = (int) $row['app_id'];
            $appsByTenant[$tid] = $appsByTenant[$tid] ?? [];
            $appsByTenant[$tid][$aid] = true; // usar set para evitar repetição
        }

        // Obter todos os planos por app_id
        $planRows = $this->db->table('app_plans')->select('id, app_id, price_amount, currency')->get()->getResultArray();
        if (empty($planRows)) {
            return; // nada a fazer sem planos
        }

        $plansByApp = [];
        foreach ($planRows as $p) {
            $plansByApp[(int) $p['app_id']][] = $p;
        }

        $statuses = ['active', 'trialing', 'past_due'];

        $batch = [];
        foreach ($tenants as $t) {
            $tenantId = (int) $t['id'];
            $availableAppIds = isset($appsByTenant[$tenantId]) ? array_keys($appsByTenant[$tenantId]) : [];
            if (empty($availableAppIds)) {
                continue;
            }

            // Selecionar 1–2 apps para assinar
            shuffle($availableAppIds);
            $count = mt_rand(1, min(2, count($availableAppIds)));
            $selectedApps = array_slice($availableAppIds, 0, $count);

            foreach ($selectedApps as $appId) {
                // Evitar duplicações por (tenant_id, app_id)
                $exists = $this->db->table('subscriptions')
                    ->where(['tenant_id' => $tenantId, 'app_id' => (int) $appId])
                    ->get()
                    ->getRow();
                if ($exists) {
                    continue;
                }

                // Escolher um plano para o app
                $plans = $plansByApp[(int) $appId] ?? [];
                if (empty($plans)) {
                    continue;
                }
                $plan = $plans[array_rand($plans)];

                // Datas de período atual
                $startTs = time() - mt_rand(0, 29) * 86400;
                $endTs = $startTs + 30 * 86400;
                $periodStart = date('Y-m-d H:i:s', $startTs);
                $periodEnd = date('Y-m-d H:i:s', $endTs);

                // Status
                $status = $statuses[array_rand($statuses)];
                $trialEnd = ($status === 'trialing') ? date('Y-m-d H:i:s', strtotime('+14 days')) : null;

                $batch[] = [
                    'tenant_id' => $tenantId,
                    'app_id' => (int) $appId,
                    'app_plan_id' => (int) $plan['id'],
                    'status' => $status,
                    'quantity' => 1,
                    'unit_price' => $plan['price_amount'],
                    'currency' => $plan['currency'],
                    'current_period_start' => $periodStart,
                    'current_period_end' => $periodEnd,
                    'trial_end_at' => $trialEnd,
                    'cancel_at' => null,
                    'canceled_at' => null,
                    'stripe_subscription_id' => null,
                    'stripe_price_id' => null,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }
        }

        if (! empty($batch)) {
            $this->db->table('subscriptions')->insertBatch($batch);
        }
    }
}