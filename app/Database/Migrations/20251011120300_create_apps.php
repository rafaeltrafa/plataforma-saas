<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApps extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => 'PK autoincrement da app',
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
                'comment' => 'Nome público da aplicação',
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Identificador legível único (URL-safe) da aplicação',
            ],
            'app_key' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
                'comment' => 'Chave técnica única para identificação interna (ex.: billing, crm)',
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Descrição breve da aplicação',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Status de ativação da aplicação (1=ativa,0=inativa)',
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Excluído em (soft delete)',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('is_active');
        $this->forge->addKey('slug', false, true);
        $this->forge->addKey('app_key', false, true);

        $this->forge->createTable('apps', false, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('apps', true);
    }
}