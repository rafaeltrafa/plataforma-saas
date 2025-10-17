<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\AppModel;

class AppPlansSeeder extends Seeder
{
    public function run()
    {
        $appModel = new AppModel();
        $app = $appModel->orderBy('id', 'ASC')->first();

        if (!$app) {
            echo "No app found; skipping AppPlansSeeder.\n";
            return;
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            [
                'app_id' => $app['id'],
                'name' => 'Plano Básico',
                'billing_period' => 'monthly',
                'price' => 19.90,
                'currency' => 'BRL',
                'stripe_price_id' => 'price_basic_example',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'app_id' => $app['id'],
                'name' => 'Plano Pro',
                'billing_period' => 'quarterly',
                'price' => 59.90,
                'currency' => 'BRL',
                'stripe_price_id' => 'price_pro_example',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'app_id' => $app['id'],
                'name' => 'Plano Lifetime',
                'billing_period' => 'lifetime',
                'price' => 499.00,
                'currency' => 'BRL',
                'stripe_price_id' => 'price_lifetime_example',
                'is_active' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $builder = $this->db->table('app_plans');
        // Insere apenas se a tabela estiver vazia
        $count = $builder->countAll();
        if ($count === 0) {
            $builder->insertBatch($data);
        } else {
            echo "app_plans already has data; skipping insert.\n";
        }
    }
}