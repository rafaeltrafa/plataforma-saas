<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters: roles before users (FKs in future)
        $this->call(RoleLevelsSeeder::class);
        $this->call(UsersSeeder::class);
    }
}