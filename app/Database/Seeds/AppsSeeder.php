<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AppsSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'name' => 'Kids Stories',
                'slug' => 'kids-stories',
                'description' => 'App de histórias infantis com assinaturas.',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Task Manager',
                'slug' => 'task-manager',
                'description' => 'Gerenciador de tarefas para equipes.',
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Analytics Pro',
                'slug' => 'analytics-pro',
                'description' => 'Painel de analytics com relatórios avançados.',
                'is_active' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('apps')->insertBatch($data);
    }
}