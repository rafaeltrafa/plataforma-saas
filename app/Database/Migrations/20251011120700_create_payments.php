<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
                'comment' => 'PK autoincrement do pagamento',
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
            'subscription_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'FK para subscriptions.id (opcional para pagamento avulso)',
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'comment' => 'Valor pago',
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'BRL',
                'comment' => 'Moeda do pagamento',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'default' => 'succeeded',
                'comment' => 'Status: pending, succeeded, failed, refunded, partial_refund',
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Método: card, boleto, pix, etc.',
            ],
            'provider' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'stripe',
                'comment' => 'Provedor de pagamento (ex.: stripe)',
            ],
            'stripe_payment_intent_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'ID do Payment Intent na Stripe (pi_...)',
            ],
            'stripe_charge_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'ID do Charge na Stripe (ch_...)',
            ],
            'stripe_invoice_id' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'ID da Invoice na Stripe (in_...)',
            ],
            'receipt_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'URL do recibo/nota fiscal do pagamento',
            ],
            'error_code' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'comment' => 'Código de erro do provedor, se falha',
            ],
            'error_message' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Mensagem de erro do provedor, se falha',
            ],
            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data/hora da confirmação do pagamento',
            ],
            'due_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Data/hora de vencimento (boletos, faturas)',
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
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('app_id');
        $this->forge->addKey('subscription_id');
        $this->forge->addKey('status');
        $this->forge->addKey('paid_at');
        $this->forge->addKey('stripe_invoice_id');
        $this->forge->addKey('stripe_payment_intent_id', false, true); // UNIQUE
        $this->forge->addKey('stripe_charge_id', false, true); // UNIQUE

        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('app_id', 'apps', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('subscription_id', 'subscriptions', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('payments', false, [
            'ENGINE' => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('payments', true);
    }
}