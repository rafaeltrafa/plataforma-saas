<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppPlans extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => 'PK autoincrement do plano',
            ],
            'app_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'FK para apps.id',
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => false,
                'comment' => 'Nome do plano visível ao usuário',
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Identificador legível único (URL-safe) do plano dentro do app',
            ],
            'plan_key' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
                'comment' => 'Chave técnica única do plano (ex.: basic, pro)',
            ],
            'billing_interval' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'month',
                'comment' => 'Intervalo de cobrança: day, week, month, year',
            ],
            'price_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
                'comment' => 'Preço por intervalo (valor unitário)',
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'BRL',
                'comment' => 'Moeda (ex.: BRL, USD, EUR)',
            ],
            'stripe_price_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'ID do preço na Stripe (price_...)',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Status de ativação do plano (1=ativo,0=inativo)',
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
        $this->forge->addKey('app_id');
        $this->forge->addKey('is_active');
        $this->forge->addKey(['app_id', 'slug'], false, true); // UNIQUE por app
        $this->forge->addKey(['app_id', 'plan_key'], false, true); // UNIQUE por app

        $this->forge->addForeignKey('app_id', 'apps', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('app_plans', false, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('app_plans', true);
    }
}