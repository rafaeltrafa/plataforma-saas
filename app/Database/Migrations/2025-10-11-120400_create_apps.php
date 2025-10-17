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
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'comment' => 'Nome do App',
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'comment' => 'Slug único do App',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Descrição opcional do App',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Status do App (1=ativo,0=inativo)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('slug', false, true); // UNIQUE(slug)
        $this->forge->addKey('is_active');
        $this->forge->addKey('name');

        $attributes = [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ];

        $this->forge->createTable('apps', true, $attributes);
    }

    public function down(): void
    {
        $this->forge->dropTable('apps', true);
    }
}