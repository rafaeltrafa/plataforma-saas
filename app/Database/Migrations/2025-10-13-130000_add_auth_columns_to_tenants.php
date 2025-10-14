<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuthColumnsToTenants extends Migration
{
    public function up(): void
    {
        // Adiciona colunas para autenticação de tenants
        $fields = [
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'timezone',
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'password_hash',
            ],
        ];

        $this->forge->addColumn('tenants', $fields);
    }

    public function down(): void
    {
        // Remove colunas adicionadas
        $this->forge->dropColumn('tenants', 'password_hash');
        $this->forge->dropColumn('tenants', 'last_login_at');
    }
}