<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AppPlansSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Buscar apps existentes
        $apps = $this->db->table('apps')->select('id, slug, app_key')->get()->getResultArray();
        if (empty($apps)) {
            return; // nada a fazer sem apps
        }

        // Planos padrão por app
        $basePlans = [
            ['name' => 'Basic', 'slug' => 'basic', 'plan_key' => 'basic', 'billing_interval' => 'month', 'price_amount' => 49.90, 'currency' => 'BRL'],
            ['name' => 'Pro', 'slug' => 'pro', 'plan_key' => 'pro', 'billing_interval' => 'month', 'price_amount' => 149.90, 'currency' => 'BRL'],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'plan_key' => 'enterprise', 'billing_interval' => 'month', 'price_amount' => 499.90, 'currency' => 'BRL'],
        ];

        foreach ($apps as $app) {
            $appId = (int) $app['id'];

            foreach ($basePlans as $plan) {
                // Evitar duplicações por (app_id, plan_key)
                $exists = $this->db->table('app_plans')
                    ->where(['app_id' => $appId, 'plan_key' => $plan['plan_key']])
                    ->get()
                    ->getRow();

                if ($exists) {
                    continue;
                }

                $data = [
                    'app_id' => $appId,
                    'name' => $plan['name'],
                    'slug' => $plan['slug'],
                    'plan_key' => $plan['plan_key'],
                    'billing_interval' => $plan['billing_interval'],
                    'price_amount' => $plan['price_amount'],
                    'currency' => $plan['currency'],
                    'stripe_price_id' => null,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];

                $this->db->table('app_plans')->insert($data);
            }
        }
    }
}