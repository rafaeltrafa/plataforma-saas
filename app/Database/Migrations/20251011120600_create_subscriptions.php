<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => 'PK autoincrement da assinatura',
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
            'app_plan_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'FK para app_plans.id (plano escolhido)',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'active',
                'comment' => 'Status: active, trialing, past_due, canceled, incomplete',
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1,
                'comment' => 'Quantidade/unidades do plano (se aplicável)',
            ],
            'unit_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Preço unitário capturado no momento da assinatura',
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
                'comment' => 'Moeda da assinatura (se capturada)',
            ],
            'current_period_start' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Início do período atual',
            ],
            'current_period_end' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Fim do período atual (próxima renovação)',
            ],
            'trial_end_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Fim do período de teste, se houver',
            ],
            'cancel_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Assinatura marcada para cancelamento nesta data',
            ],
            'canceled_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Assinatura efetivamente cancelada nesta data',
            ],
            'stripe_subscription_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'ID da assinatura na Stripe (sub_...)',
            ],
            'stripe_price_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'ID do preço associado na Stripe (price_...)',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Ativa para cobrança/uso (1=ativa,0=inativa)',
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
        $this->forge->addKey(['tenant_id', 'app_id']);
        $this->forge->addKey('app_plan_id');
        $this->forge->addKey('status');

        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('app_id', 'apps', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('app_plan_id', 'app_plans', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('subscriptions', false, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('subscriptions', true);
    }
}