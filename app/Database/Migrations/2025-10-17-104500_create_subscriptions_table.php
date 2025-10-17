<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptionsTable extends Migration
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
            'app_plan_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'incomplete',
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 1,
            ],
            'unit_price' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'BRL',
            ],
            'current_period_start' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'current_period_end' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'incomplete_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'trial_end_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'stripe_subscription_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'stripe_price_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'cancel_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'canceled_at' => [
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'app_id', 'app_plan_id']);
        $this->forge->addKey('stripe_subscription_id', false, true);
        $this->forge->addKey('stripe_price_id');
        $this->forge->addKey('status');
        $this->forge->addKey('is_active');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('app_id', 'apps', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('app_plan_id', 'app_plans', 'id', 'CASCADE', 'CASCADE');

        // IF NOT EXISTS
        $this->forge->createTable('subscriptions', true);
    }

    public function down()
    {
        // IF EXISTS
        $this->forge->dropTable('subscriptions', true);
    }
}