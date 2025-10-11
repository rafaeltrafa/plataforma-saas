<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleLevelsSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            [
                'name' => 'viewer',
                'level' => 0,
                'description' => 'Read-only access',
                'is_default' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'admin',
                'level' => 10,
                'description' => 'Admin access for managing apps',
                'is_default' => 1,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'super_admin',
                'level' => 20,
                'description' => 'Full platform administration',
                'is_default' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('role_levels')->insertBatch($data);
    }
}