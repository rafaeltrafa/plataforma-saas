<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTenantsAddPassword extends Migration
{
    public function up(): void
    {
        $fields = [
            'password_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Hash de senha para autenticação direta de tenants (opcional)'
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Último login do tenant (se usar login direto)'
            ],
        ];

        $this->forge->addColumn('tenants', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('tenants', 'password_hash');
        $this->forge->dropColumn('tenants', 'last_login_at');
    }
}