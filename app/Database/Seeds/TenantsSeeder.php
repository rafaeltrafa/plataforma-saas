<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TenantsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');
        $now = date('Y-m-d H:i:s');

        $batch = [];
        for ($i = 1; $i <= 30; $i++) {
            $name = $faker->company();

            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
            $slug .= '-' . $i; // garantir unicidade do slug

            $subdomain = 'tenant' . $i;
            $domain = $subdomain . '.example.local';

            $trialEnd = (mt_rand(0, 1) === 1)
                ? date('Y-m-d H:i:s', strtotime('+14 days'))
                : null;

            $batch[] = [
                'name' => $name,
                'slug' => $slug,
                'contact_email' => strtolower($faker->unique()->companyEmail()),
                'contact_phone' => $faker->phoneNumber(),
                'document_number' => $faker->numerify('##################'),
                'subdomain' => $subdomain,
                'domain' => $domain,
                'locale' => 'pt-BR',
                'timezone' => 'America/Sao_Paulo',
                'stripe_customer_id' => null,
                'trial_end_at' => $trialEnd,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];
        }

        $this->db->table('tenants')->insertBatch($batch);
    }
}