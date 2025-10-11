<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $password = password_hash('admin123', PASSWORD_DEFAULT);

        $faker = \Faker\Factory::create('pt_BR');
        $faker->unique(true); // reset unique state

        $batch = [];
        for ($i = 0; $i < 3; $i++) {
            $name = $faker->name();
            $email = strtolower($faker->unique()->safeEmail());

            $batch[] = [
                'name' => $name,
                'email' => $email,
                'password_hash' => $password,
                'role_level' => 10, // admin
                'is_active' => 1,
                'last_login_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];
        }

        $this->db->table('users')->insertBatch($batch);
    }
}