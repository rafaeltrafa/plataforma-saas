<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $email = 'superadmin@example.com';

        $exists = $this->db->table('users')
            ->where('email', $email)
            ->get()
            ->getRow();

        if ($exists) {
            return; // já existe, não insere duplicado
        }

        $password = password_hash('superadmin123', PASSWORD_DEFAULT);

        $data = [
            'name' => 'Super Admin',
            'email' => $email,
            'password_hash' => $password,
            'role_level' => 20, // super_admin
            'is_active' => 1,
            'last_login_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        $this->db->table('users')->insert($data);
    }
}