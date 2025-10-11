<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ordem:
        // 1) Role levels (níveis de acesso)
        // 2) Apps (catálogo de aplicações)
        // 3) Tenants (organizações)
        // 4) AppPlans (planos por app)
        // 5) TenantApps (vínculos entre tenants e apps)
        // 6) Subscriptions (assinaturas por tenant/app/plano)
        // 7) Payments (histórico de pagamentos)
        // 8) Users e Super Admin
        $this->call(RoleLevelsSeeder::class);
        $this->call(AppsSeeder::class);
        $this->call(TenantsSeeder::class);
        $this->call(AppPlansSeeder::class);
        $this->call(TenantAppsSeeder::class);
        $this->call(SubscriptionsSeeder::class);
        $this->call(PaymentsSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(SuperAdminSeeder::class);
    }
}