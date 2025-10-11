<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AppsSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $apps = [
            ['name' => 'CRM', 'slug' => 'crm', 'app_key' => 'crm', 'description' => 'Gestão de relacionamento com clientes'],
            ['name' => 'Billing', 'slug' => 'billing', 'app_key' => 'billing', 'description' => 'Faturamento e cobranças'],
            ['name' => 'Helpdesk', 'slug' => 'helpdesk', 'app_key' => 'helpdesk', 'description' => 'Atendimento e suporte'],
            ['name' => 'Analytics', 'slug' => 'analytics', 'app_key' => 'analytics', 'description' => 'Métricas e relatórios'],
            ['name' => 'Email Marketing', 'slug' => 'email-marketing', 'app_key' => 'email_marketing', 'description' => 'Campanhas de e-mail'],
            ['name' => 'Inventory', 'slug' => 'inventory', 'app_key' => 'inventory', 'description' => 'Gestão de estoque'],
            ['name' => 'Project Management', 'slug' => 'project-management', 'app_key' => 'project_management', 'description' => 'Gestão de projetos e tarefas'],
            ['name' => 'HR', 'slug' => 'hr', 'app_key' => 'hr', 'description' => 'Recursos humanos'],
            ['name' => 'Chat', 'slug' => 'chat', 'app_key' => 'chat', 'description' => 'Chat de suporte e interno'],
            ['name' => 'Forms', 'slug' => 'forms', 'app_key' => 'forms', 'description' => 'Formulários e pesquisas'],
        ];

        foreach ($apps as $app) {
            // Evitar duplicações se executado mais de uma vez
            $exists = $this->db->table('apps')
                ->where('app_key', $app['app_key'])
                ->get()
                ->getRow();

            if ($exists) {
                continue;
            }

            $data = [
                'name' => $app['name'],
                'slug' => $app['slug'],
                'app_key' => $app['app_key'],
                'description' => $app['description'],
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            $this->db->table('apps')->insert($data);
        }
    }
}