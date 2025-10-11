<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TenantAppsSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $tenantRows = $this->db->table('tenants')->select('id')->get()->getResultArray();
        $appRows = $this->db->table('apps')->select('id')->get()->getResultArray();

        if (empty($tenantRows) || empty($appRows)) {
            return; // nada a fazer sem tenants ou apps
        }

        $appIds = array_column($appRows, 'id');

        $batch = [];
        foreach ($tenantRows as $t) {
            $tenantId = (int) $t['id'];

            // Selecionar aleatoriamente 2 a 4 apps por tenant
            shuffle($appIds);
            $count = mt_rand(2, min(4, count($appIds)));
            $selected = array_slice($appIds, 0, $count);

            foreach ($selected as $appId) {
                // Evitar duplicidade se rodar novamente
                $exists = $this->db->table('tenant_apps')
                    ->where(['tenant_id' => $tenantId, 'app_id' => $appId])
                    ->get()
                    ->getRow();
                if ($exists) {
                    continue;
                }

                $installedAt = date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 60) . ' days'));

                $batch[] = [
                    'tenant_id' => $tenantId,
                    'app_id' => (int) $appId,
                    'installed_at' => $installedAt,
                    'is_enabled' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($batch)) {
            $this->db->table('tenant_apps')->insertBatch($batch);
        }
    }
}