<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantApps extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => 'PK autoincrement da associação tenant->app',
            ],
            'tenant_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'FK para tenants.id',
            ],
            'app_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'FK para apps.id',
            ],
            'installed_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data/hora de instalação/ativação da app no tenant',
            ],
            'is_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Se a app está habilitada para o tenant (1=sim,0=não)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Criado em',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Atualizado em',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('is_enabled');
        $this->forge->addKey(['tenant_id', 'app_id'], false, true); // UNIQUE(tenant_id, app_id)

        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('app_id', 'apps', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('tenant_apps', false, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('tenant_apps', true);
    }
}