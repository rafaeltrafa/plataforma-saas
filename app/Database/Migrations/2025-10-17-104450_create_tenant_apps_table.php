<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantAppsTable extends Migration
{
    protected $group = 'default';
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'app_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'is_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'installed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'credential_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'credential_updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'app_id'], false, true);
        $this->forge->addKey('is_enabled');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('app_id', 'apps', 'id', 'CASCADE', 'CASCADE');

        // IF NOT EXISTS
        $this->forge->createTable('tenant_apps', true);
    }

    public function down()
    {
        // IF EXISTS
        $this->forge->dropTable('tenant_apps', true);
    }
}