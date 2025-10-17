<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppPlansTable extends Migration
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
            'app_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'slug' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'plan_key' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'billing_interval' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'monthly',
            ],
            'price_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'default' => 0.00,
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'BRL',
            ],
            'stripe_price_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'active',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->addKey(['app_id', 'slug'], false, true);
        $this->forge->addKey('plan_key', false, true);
        $this->forge->addKey('stripe_price_id', false, true);
        $this->forge->addKey('is_active');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('app_id', 'apps', 'id', 'CASCADE', 'CASCADE');

        // IF NOT EXISTS
        $this->forge->createTable('app_plans', true);
    }

    public function down()
    {
        // IF EXISTS
        $this->forge->dropTable('app_plans', true);
    }
}